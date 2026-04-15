#!/bin/bash
# ============================================================
# Setup CDR naar database via CSV auto-import
# Uitvoeren als root: sudo bash setup_cdr.sh
# ============================================================

set -e
GREEN='\033[0;32m'; CYAN='\033[0;36m'; YELLOW='\033[1;33m'; NC='\033[0m'

info()    { echo -e "${CYAN}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
warn()    { echo -e "${YELLOW}[WARN]${NC} $1"; }

APP_DIR="/var/www/asterisk-manager"

# ---- Stap 1: Configureer cdr.conf ----
info "Configureer /etc/asterisk/cdr.conf..."
cat > /etc/asterisk/cdr.conf << 'EOF'
[general]
enable=yes
unanswered=yes
congestion=no

[csv]
usegmtime=no
loguniqueid=yes
loguserfield=yes
accountlogs=no
EOF
success "cdr.conf geconfigureerd"

# ---- Stap 2: Laad CDR CSV module ----
info "Laad cdr_csv module..."
asterisk -rx "module load cdr_csv.so" 2>/dev/null || true
sleep 1

# Test of het werkt
CDR_STATUS=$(asterisk -rx "cdr show status" 2>/dev/null)
echo "$CDR_STATUS"

if echo "$CDR_STATUS" | grep -q "CSV"; then
    success "CDR CSV module actief"
else
    warn "CDR CSV module niet gevonden, probeer herstart..."
    systemctl restart asterisk
    sleep 3
fi

# ---- Stap 3: Maak log directory aan ----
info "Controleer CDR log directory..."
mkdir -p /var/log/asterisk/cdr-csv
chown asterisk:asterisk /var/log/asterisk/cdr-csv 2>/dev/null || true
chmod 755 /var/log/asterisk/cdr-csv

# Maak leeg Master.csv als het niet bestaat
if [ ! -f /var/log/asterisk/cdr-csv/Master.csv ]; then
    touch /var/log/asterisk/cdr-csv/Master.csv
    chown asterisk:asterisk /var/log/asterisk/cdr-csv/Master.csv
fi

# Geef www-data leesrechten
chmod 644 /var/log/asterisk/cdr-csv/Master.csv
success "CDR CSV directory gereed: /var/log/asterisk/cdr-csv/Master.csv"

# ---- Stap 4: Rechten voor www-data ----
info "Rechten instellen voor www-data..."
# Voeg www-data toe aan asterisk groep voor leesrechten
usermod -aG asterisk www-data 2>/dev/null || true
chmod g+r /var/log/asterisk/cdr-csv/Master.csv 2>/dev/null || true

# ---- Stap 5: Installeer cron job ----
info "Installeer cron job (elke minuut)..."
cat > /etc/cron.d/asterisk-cdr-import << 'CRONEOF'
# Asterisk CDR auto-import - elke minuut
* * * * * www-data /usr/bin/php /var/www/asterisk-manager/cron/cdr_auto_import.php
CRONEOF
chmod 644 /etc/cron.d/asterisk-cdr-import
success "Cron job geïnstalleerd"

# ---- Stap 6: Rechten voor import script ----
chmod 755 "$APP_DIR/cron/cdr_auto_import.php"
chown www-data:www-data "$APP_DIR/cron/cdr_auto_import.php"
mkdir -p "$APP_DIR/logs"
chown www-data:www-data "$APP_DIR/logs"

# ---- Stap 7: Test de import ----
info "Test de CDR import..."
sudo -u www-data php "$APP_DIR/cron/cdr_auto_import.php"
echo ""

# ---- Stap 8: Herlaad Asterisk CDR ----
info "Herlaad Asterisk CDR..."
asterisk -rx "cdr reload" 2>/dev/null || true

echo ""
echo "============================================"
echo -e "  ${GREEN}CDR Setup Compleet!${NC}"
echo "============================================"
echo ""
echo "  CDR records worden nu elke minuut"
echo "  automatisch geïmporteerd in de database."
echo ""
echo "  CSV bestand: /var/log/asterisk/cdr-csv/Master.csv"
echo "  Import log:  $APP_DIR/logs/cdr_import.log"
echo ""
echo "  Maak een testgesprek en wacht 1 minuut."
echo "  Controleer dan: Call Records in de webinterface"
echo ""
echo "  Of importeer direct: php $APP_DIR/cron/cdr_auto_import.php"
echo "============================================"
