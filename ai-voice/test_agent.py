import asyncio, struct, numpy as np, voice_agent as va

def tone(n_frames, amp):
    t = np.arange(160 * n_frames)
    return (amp * np.sin(t * 0.3)).astype(np.int16).tobytes()

def frames(pcm):
    return [pcm[i:i+320] for i in range(0, len(pcm), 320)]

def test_endpointer_detects_utterance():
    ep = va.Endpointer()
    out = []
    for f in frames(tone(30, 50) + tone(40, 5000) + tone(60, 50)):   # ruis, spraak 0.8s, stilte 1.2s
        u = ep.feed(f)
        if u: out.append(u)
    assert len(out) == 1 and len(out[0]) >= 320 * 40

def test_endpointer_ignores_click():
    ep = va.Endpointer()
    assert not any(ep.feed(f) for f in frames(tone(5, 5000) + tone(80, 50)))

def test_frame_roundtrip():
    async def go():
        r = asyncio.StreamReader(); r.feed_data(va.pack_frame(va.T_AUDIO, b"\x01\x02" * 160))
        return await va.read_frame(r)
    k, p = asyncio.run(go())
    assert k == va.T_AUDIO and len(p) == 320

def test_parse_reply():
    assert va.parse_reply('{"zeg": "Hallo", "actie": "geen"}') == ("Hallo", "geen")
    assert va.parse_reply('Tekst {"zeg": "Dag", "actie": "ophangen"} einde') == ("Dag", "ophangen")
    assert va.parse_reply('{"zeg": "x", "actie": "raar"}')[1] == "geen"
    assert va.parse_reply("gewoon tekst") == ("gewoon tekst", "geen")

def test_catalogue():
    c = va.load_catalogue(va.CATALOGUE)
    assert "Voorbeeld Pro 3" in c and "prijs_eur: 299" in c
