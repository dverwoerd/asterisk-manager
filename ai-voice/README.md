# AI-spraakassistent (smartwatches)

Klant belt -> stelt een vraag -> assistent antwoordt aan de hand van `catalogue.csv`.
Weet de assistent het niet, of vraagt de beller een medewerker, dan wordt het gesprek doorverbonden.

Keten: Asterisk (AudioSocket) -> Whisper (STT, lokaal) -> Claude (antwoord) -> Piper (stem, lokaal).

## Installatie (eenmalig)
```bash
cd /var/www/asterisk-manager && git pull
sudo bash scripts/make_status_sounds.sh          # installeert o.a. /opt/piper en de stem Ronnie (als dat nog niet is gedaan)
sudo apt install -y sox
sudo /opt/piper/bin/pip install anthropic faster-whisper numpy
sudo mkdir -p /var/lib/ai-voice/hf && sudo chown asterisk:asterisk /var/lib/ai-voice/hf
# De dienst draait als gebruiker 'asterisk' en heeft geen toegang tot /var/www: kopieer naar /opt/ai-voice
sudo install -d -o asterisk -g asterisk /opt/ai-voice
sudo cp ai-voice/voice_agent.py ai-voice/catalogue.csv /opt/ai-voice/ && sudo chown -R asterisk:asterisk /opt/ai-voice
sudo cp ai-voice/ai-voice.env.example /etc/ai-voice.env && sudo chmod 600 /etc/ai-voice.env
sudo nano /etc/ai-voice.env                      # vul ANTHROPIC_API_KEY in
sudo cp ai-voice/ai-voice.service /etc/systemd/system/ && sudo systemctl daemon-reload
sudo systemctl enable --now ai-voice
journalctl -u ai-voice -f                        # eerste start downloadt het Whisper-model
```
De dienst luistert alleen op 127.0.0.1:9092 en draait als gebruiker `asterisk`.

## Koppelen aan de PBX
Zet in de database (tabel `settings`) de volgende instellingen en genereer de dialplan opnieuw (extensie opslaan):
```sql
INSERT INTO settings (setting_key, setting_value) VALUES
  ('ai_voice_extension','880'),   -- interne extensie van de assistent
  ('ai_voice_transfer','101')     -- waar naartoe doorverbonden wordt
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
```
Maak daarna een inkomende route met als bestemming **Extensie 880**, of bel 880 intern om te testen.

## Catalogus
`/opt/ai-voice/catalogue.csv` is een voorbeeld. Vervang door de echte producten (kolommen zijn vrij; elke kolom wordt aan de assistent meegegeven).
Na wijziging van de catalogus of van `voice_agent.py` (opnieuw kopiëren vanuit de repo): `sudo systemctl restart ai-voice`. Houd de catalogus onder ca. 200 producten; groter vraagt zoeken/RAG.

## Tests
`/opt/piper/bin/pip install pytest && cd ai-voice && /opt/piper/bin/python -m pytest -q`

## Beperkingen
- Reactietijd ca. 2-4 s per beurt (STT + LLM + stem).
- De assistent kan niets bestellen of wijzigen; alleen informeren en doorverbinden.
- Kosten: alleen de Claude API (een paar cent per gesprek). Gesprekstekst gaat daarvoor naar Anthropic.
- Maximaal 12 beurten of 5 minuten per gesprek, daarna doorverbinden.
