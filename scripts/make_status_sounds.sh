#!/bin/bash
# Genereert gesproken Nederlandse statusmeldingen met Piper (neurale stem, natuurlijk).
# Gebruik: sudo bash scripts/make_status_sounds.sh
#   VOICE=nl_NL-ronnie-medium (man, standaard) | nl_NL-pim-medium (man) | nl_BE-nathalie-medium (vrouw, Vlaams)
#   SPEED=1.15   (length_scale: hoger = langzamer)
#   ENGINE=espeak  forceert de oude, robotachtige stem
set -euo pipefail
VOICE="${VOICE:-nl_NL-ronnie-medium}"
SPEED="${SPEED:-1.1}"
ENGINE="${ENGINE:-piper}"
OUT=/var/lib/asterisk/sounds/custom
PIPER_DIR=/opt/piper
mkdir -p "$OUT"

declare -A MSG=(
  [status-aanwezig]="Je status is nu aanwezig."
  [status-afwezig]="Je status is nu afwezig. Oproepen worden doorgeschakeld."
  [status-buiten-kantoor]="Je status is nu buiten kantoor. Oproepen worden doorgeschakeld."
)

if [ "$ENGINE" = "piper" ] && [ ! -x "$PIPER_DIR/bin/python" ]; then
  echo ">> Piper installeren in $PIPER_DIR"
  apt-get install -y python3-venv sox >/dev/null
  python3 -m venv "$PIPER_DIR"
  "$PIPER_DIR/bin/pip" install -q piper-tts
fi
if [ "$ENGINE" = "piper" ] && [ ! -f "$PIPER_DIR/voices/$VOICE.onnx" ]; then
  echo ">> Stem $VOICE downloaden"
  mkdir -p "$PIPER_DIR/voices"
  "$PIPER_DIR/bin/python" -m piper.download_voices --download-dir "$PIPER_DIR/voices" "$VOICE"
fi

TMP=$(mktemp -d); trap 'rm -rf "$TMP"' EXIT
for name in "${!MSG[@]}"; do
  if [ "$ENGINE" = "piper" ]; then
    "$PIPER_DIR/bin/python" -m piper -m "$PIPER_DIR/voices/$VOICE.onnx" \
        --length-scale "$SPEED" -f "$TMP/$name.wav" -- "${MSG[$name]}" >/dev/null
  else
    espeak-ng -v mb-nl3 -s 130 -w "$TMP/$name.wav" "${MSG[$name]}"
  fi
  # Asterisk: 8 kHz, mono, 16 bit
  if command -v sox >/dev/null; then
    sox "$TMP/$name.wav" -r 8000 -c 1 -b 16 "$OUT/$name.wav"
  else
    ffmpeg -loglevel error -y -i "$TMP/$name.wav" -ar 8000 -ac 1 -sample_fmt s16 "$OUT/$name.wav"
  fi
  echo "OK  $OUT/$name.wav"
done
chown -R asterisk:asterisk "$OUT"
