#!/bin/bash
# Genereert gesproken Nederlandse statusmeldingen (vrouwenstem, langzaam).
# Gebruik: sudo bash scripts/make_status_sounds.sh   (optioneel: VOICE=mb-nl2 SPEED=140)
set -euo pipefail
VOICE="${VOICE:-mb-nl3}"       # mb-nl3 = vrouw, mb-nl2 = man
SPEED="${SPEED:-130}"          # woorden per minuut (lager = langzamer)
OUT=/var/lib/asterisk/sounds/custom
mkdir -p "$OUT"

declare -A MSG=(
  [status-aanwezig]="Je status is nu aanwezig."
  [status-afwezig]="Je status is nu afwezig. Oproepen worden doorgeschakeld."
  [status-buiten-kantoor]="Je status is nu buiten kantoor. Oproepen worden doorgeschakeld."
)

TMP=$(mktemp -d); trap 'rm -rf "$TMP"' EXIT
for name in "${!MSG[@]}"; do
  espeak-ng -v "$VOICE" -s "$SPEED" -w "$TMP/$name.wav" "${MSG[$name]}"
  # Asterisk: 8 kHz, mono, 16 bit
  if command -v sox >/dev/null; then
    sox "$TMP/$name.wav" -r 8000 -c 1 -b 16 "$OUT/$name.wav"
  else
    ffmpeg -loglevel error -y -i "$TMP/$name.wav" -ar 8000 -ac 1 -sample_fmt s16 "$OUT/$name.wav"
  fi
  echo "OK  $OUT/$name.wav"
done
chown -R asterisk:asterisk "$OUT"
