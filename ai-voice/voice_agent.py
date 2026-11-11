#!/usr/bin/env python3
"""
AI-spraakassistent voor Asterisk (AudioSocket).

Gesprek: beller praat -> Whisper (STT) -> Claude (antwoord op basis van catalogus)
         -> Piper (TTS) -> terug naar de beller. Bij twijfel/verzoek: doorverbinden.

Start:  /opt/piper/bin/python voice_agent.py   (zie README.md en ai-voice.service)
"""
import asyncio, csv, json, logging, os, re, struct, subprocess, time
from pathlib import Path
import numpy as np

log = logging.getLogger("ai-voice")

# ---------------- Configuratie (omgevingsvariabelen) ----------------
HOST         = os.getenv("AI_HOST", "127.0.0.1")
PORT         = int(os.getenv("AI_PORT", "9092"))
CATALOGUE    = os.getenv("AI_CATALOGUE", str(Path(__file__).with_name("catalogue.csv")))
COMPANY      = os.getenv("AI_COMPANY", "Pantronic")
MODEL        = os.getenv("AI_MODEL", "claude-haiku-5-5")
WHISPER      = os.getenv("AI_WHISPER_MODEL", "small")
VOICE_PATH   = os.getenv("AI_VOICE", "/opt/piper/voices/nl_NL-ronnie-medium.onnx")
LENGTH_SCALE = float(os.getenv("AI_SPEED", "1.05"))
MAX_TURNS    = int(os.getenv("AI_MAX_TURNS", "12"))
MAX_SECONDS  = int(os.getenv("AI_MAX_SECONDS", "300"))

# ---------------- AudioSocket protocol ----------------
T_HANGUP, T_UUID, T_DTMF, T_AUDIO, T_ERROR = 0x00, 0x01, 0x03, 0x10, 0xFF
RATE, FRAME_BYTES = 8000, 320            # 20 ms slin 8 kHz 16 bit mono


def pack_frame(kind: int, payload: bytes = b"") -> bytes:
    return struct.pack(">BH", kind, len(payload)) + payload


async def read_frame(reader):
    head = await reader.readexactly(3)
    kind, length = struct.unpack(">BH", head)
    return kind, (await reader.readexactly(length) if length else b"")


# ---------------- Spraakdetectie (energie, zelf-kalibrerend) ----------------
class Endpointer:
    """Zet een stroom 20ms-frames om in uitingen (begin/einde van spraak)."""
    def __init__(self, silence_s=0.9, min_speech_s=0.35, max_s=15.0):
        self.silence_frames = int(silence_s / 0.02)
        self.min_frames = int(min_speech_s / 0.02)
        self.max_frames = int(max_s / 0.02)
        self.noise = 150.0                      # lopende ruisvloer (RMS)
        self.reset()

    def reset(self):
        self.buf, self.speech, self.quiet, self.active = [], 0, 0, False

    @staticmethod
    def rms(frame: bytes) -> float:
        a = np.frombuffer(frame, dtype=np.int16).astype(np.float32)
        return float(np.sqrt(np.mean(a * a))) if a.size else 0.0

    def is_speech(self, frame: bytes) -> bool:
        level = self.rms(frame)
        loud = level > max(self.noise * 3.0, 400.0)
        if not loud:                            # ruisvloer langzaam bijwerken
            self.noise = 0.98 * self.noise + 0.02 * level
        return loud

    def feed(self, frame: bytes):
        """Geeft bytes van een complete uiting terug, anders None."""
        speech = self.is_speech(frame)
        if not self.active:
            if speech:
                self.active, self.buf, self.speech, self.quiet = True, [frame], 1, 0
            return None
        self.buf.append(frame)
        if speech:
            self.speech += 1; self.quiet = 0
        else:
            self.quiet += 1
        if self.quiet >= self.silence_frames or len(self.buf) >= self.max_frames:
            ok = self.speech >= self.min_frames
            data = b"".join(self.buf)
            self.reset()
            return data if ok else None
        return None


# ---------------- Catalogus ----------------
def load_catalogue(path: str) -> str:
    rows = list(csv.DictReader(open(path, encoding="utf-8")))
    lines = []
    for r in rows:
        lines.append("; ".join(f"{k}: {v}" for k, v in r.items() if v and v.strip()))
    return "\n".join(lines)


def build_system_prompt(catalogue: str) -> str:
    return f"""Je bent de telefonische assistent van {COMPANY}. Je spreekt Nederlands met klanten die bellen over smartwatches.
Regels:
- Antwoord KORT (maximaal twee zinnen), gesproken taal, geen opsommingstekens, geen markdown.
- Gebruik alleen informatie uit de catalogus hieronder. Verzin nooit prijzen, voorraad, specificaties of levertijden.
- Weet je het niet, of vraagt de beller om een medewerker, klacht, retour, bestelling of iets buiten de catalogus: verbind door.
- Noem prijzen als '299 euro'. Spel modelnamen uit zoals ze in de catalogus staan.
- Stel hoogstens één wedervraag tegelijk om te kunnen adviseren.
Antwoord ALTIJD als JSON: {{"zeg": "<wat je zegt>", "actie": "geen" | "doorverbinden" | "ophangen"}}
Gebruik "ophangen" alleen na een afscheid van de beller.

CATALOGUS:
{catalogue}"""


def parse_reply(text: str):
    m = re.search(r"\{.*\}", text, re.S)
    if m:
        try:
            d = json.loads(m.group(0))
            act = d.get("actie", "geen")
            return str(d.get("zeg", "")).strip(), act if act in ("geen", "doorverbinden", "ophangen") else "geen"
        except json.JSONDecodeError:
            pass
    return text.strip(), "geen"


# ---------------- Engines (STT / LLM / TTS) ----------------
class Engines:
    def __init__(self):
        from faster_whisper import WhisperModel
        from piper import PiperVoice, SynthesisConfig
        import anthropic
        log.info("Modellen laden...")
        self.stt = WhisperModel(WHISPER, device="cpu", compute_type="int8")
        self.voice = PiperVoice.load(VOICE_PATH)
        self.syn_cfg = SynthesisConfig(length_scale=LENGTH_SCALE)
        self.llm = anthropic.Anthropic()          # ANTHROPIC_API_KEY uit omgeving
        self.system = build_system_prompt(load_catalogue(CATALOGUE))
        log.info("Klaar.")

    def transcribe(self, pcm8k: bytes) -> str:
        a = np.frombuffer(pcm8k, dtype=np.int16).astype(np.float32) / 32768.0
        a16 = np.interp(np.arange(0, len(a), 0.5), np.arange(len(a)), a).astype(np.float32)  # 8k -> 16k
        segs, _ = self.stt.transcribe(a16, language="nl", beam_size=1, vad_filter=False,
                                      initial_prompt="Smartwatch, prijs, batterij, hartslag, GPS, waterdicht.")
        return " ".join(s.text.strip() for s in segs).strip()

    def think(self, history: list) -> tuple:
        r = self.llm.messages.create(model=MODEL, max_tokens=300, system=self.system, messages=history)
        return parse_reply(r.content[0].text)

    def speak(self, text: str) -> bytes:
        """Tekst -> slin 8 kHz mono (bytes)."""
        pcm, rate = b"", 22050
        for ch in self.voice.synthesize(text, self.syn_cfg):
            pcm += ch.audio_int16_bytes; rate = ch.sample_rate
        out = subprocess.run(
            ["sox", "-t", "raw", "-r", str(rate), "-e", "signed", "-b", "16", "-c", "1", "-",
             "-t", "raw", "-r", str(RATE), "-e", "signed", "-b", "16", "-c", "1", "-"],
            input=pcm, capture_output=True, check=True).stdout
        return out


# ---------------- Gesprek ----------------
class Call:
    def __init__(self, engines, reader, writer):
        self.e, self.r, self.w = engines, reader, writer
        self.uuid, self.history, self.turns = "", [], 0
        self.ep = Endpointer()
        self.playing: asyncio.Task | None = None
        self.busy = False
        self.transfer = False
        self.end = False
        self.started = time.monotonic()

    async def send_audio(self, pcm: bytes):
        for i in range(0, len(pcm), FRAME_BYTES):
            chunk = pcm[i:i + FRAME_BYTES].ljust(FRAME_BYTES, b"\0")
            self.w.write(pack_frame(T_AUDIO, chunk))
            await self.w.drain()
            await asyncio.sleep(0.019)

    async def say(self, text: str):
        pcm = await asyncio.to_thread(self.e.speak, text)
        self.playing = asyncio.create_task(self.send_audio(pcm))
        try:
            await self.playing
        except asyncio.CancelledError:
            if not self.playing.cancelled():   # buitenste taak geannuleerd, niet alleen het afspelen
                raise
        finally:
            self.playing = None

    async def handle_utterance(self, pcm: bytes):
        self.busy = True
        try:
            text = await asyncio.to_thread(self.e.transcribe, pcm)
            log.info("[%s] beller: %s", self.uuid[:8], text)
            if len(text) < 2:
                return
            self.turns += 1
            self.history.append({"role": "user", "content": text})
            say, action = await asyncio.to_thread(self.e.think, self.history[-20:])
            self.history.append({"role": "assistant", "content": json.dumps({"zeg": say, "actie": action}, ensure_ascii=False)})
            log.info("[%s] AI (%s): %s", self.uuid[:8], action, say)
            if self.turns >= MAX_TURNS or time.monotonic() - self.started > MAX_SECONDS:
                say, action = "Ik verbind u door met een medewerker.", "doorverbinden"
            if say:
                self.busy = False
                await self.say(say)
            if action == "doorverbinden":
                self.transfer = True
            if action in ("doorverbinden", "ophangen"):
                self.end = True
        except Exception:
            log.exception("Fout in gesprek, doorverbinden")
            self.transfer = self.end = True
        finally:
            self.busy = False

    def mark_transfer(self):
        subprocess.run(["asterisk", "-rx", f"database put aivoice {self.uuid} transfer"],
                       capture_output=True, timeout=5)

    async def run(self):
        task = None
        try:
            while not self.end:
                kind, payload = await read_frame(self.r)
                if kind == T_UUID:
                    self.uuid = payload.hex()
                    self.uuid = f"{self.uuid[:8]}-{self.uuid[8:12]}-{self.uuid[12:16]}-{self.uuid[16:20]}-{self.uuid[20:]}"
                    log.info("Gesprek gestart %s", self.uuid)
                    task = asyncio.create_task(self.say(f"Goedendag, u spreekt met de assistent van {COMPANY}. Waarmee kan ik u helpen?"))
                elif kind == T_HANGUP:
                    break
                elif kind == T_AUDIO:
                    speaking = self.playing is not None
                    if self.busy:
                        continue
                    utt = self.ep.feed(payload)
                    # barge-in: beller praat door de AI heen -> stop het afspelen
                    if speaking and self.ep.active and self.ep.speech >= 8:
                        self.playing.cancel()
                    if utt:
                        task = asyncio.create_task(self.handle_utterance(utt))
        except (asyncio.IncompleteReadError, ConnectionResetError):
            pass
        finally:
            if task and not task.done():
                try: await asyncio.wait_for(task, timeout=30)
                except Exception: pass
            if self.transfer and self.uuid:
                self.mark_transfer()
            try: self.w.write(pack_frame(T_HANGUP)); await self.w.drain()
            except Exception: pass
            self.w.close()
            log.info("Gesprek geeindigd %s (doorverbinden=%s)", self.uuid, self.transfer)


async def main():
    logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
    engines = Engines()

    async def client(r, w):
        await Call(engines, r, w).run()

    server = await asyncio.start_server(client, HOST, PORT)
    log.info("AudioSocket luistert op %s:%s", HOST, PORT)
    async with server:
        await server.serve_forever()


if __name__ == "__main__":
    asyncio.run(main())
