# Nederlandse taalbestanden voor Yealink toestellen

Deze map bevat Nederlandse UI-taalbestanden voor Yealink IP-telefoons.
Yealink levert zelf geen Nederlands taalpakket mee in de internationale
firmware (bevestigd door Yealink support - hiervoor is speciale "Dutch
Firmware" via distributeur Lydis nodig). Deze community-vertaalde bestanden
zijn een werkend alternatief.

Bron: https://github.com/XXLNet/Yealink-Dutch-Language-File

## Installatie

Download de benodigde bestanden naar `/var/www/asterisk-manager/provision/lang/`:

```bash
sudo mkdir -p /var/www/asterisk-manager/provision/lang
cd /var/www/asterisk-manager/provision/lang

for f in SIP-T19_lang-Dutch.txt SIP-T21_lang-Dutch.txt SIP-T23_lang-Dutch.txt \
         SIP-T40_lang-Dutch.txt SIP-T41_lang-Dutch.txt SIP-T42_lang-Dutch.txt \
         SIP-T46_lang-Dutch.txt; do
    sudo wget -q "https://raw.githubusercontent.com/XXLNet/Yealink-Dutch-Language-File/master/$f"
done

sudo chown -R www-data:www-data /var/www/asterisk-manager/provision/lang
```

Toestellen halen deze bestanden automatisch op via `gui_lang.url` in hun
provisioning-config wanneer de taal op "Dutch"/"Nederlands" staat.
