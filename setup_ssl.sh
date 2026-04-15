#!/bin/bash
# ============================================================
# SSL Setup met Certbot voor Asterisk Manager
# Uitvoeren als root: sudo bash setup_ssl.sh
# ============================================================

set -e
GREEN='\033[0;32m'; CYAN='\033[0;36m'; RED='\033[0;31m'; NC='\033[0m'

info()    { echo -e "${CYAN}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
error()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# ---- Vraag domein naam ----
if [ -z "$1" ]; then
    read -p "Voer de domeinnaam in (bijv. pbx.clearvoip.nl): " DOMAIN
else
    DOMAIN="$1"
fi

[ -z "$DOMAIN" ] && error "Geen domeinnaam opgegeven"

read -p "Voer uw e-mailadres in (voor Certbot): " EMAIL
[ -z "$EMAIL" ] && error "Geen e-mailadres opgegeven"

info "Domein: $DOMAIN"
info "Email:  $EMAIL"

# ---- Installeer Certbot ----
info "Installeer Certbot..."
apt-get update -qq
apt-get install -y certbot python3-certbot-apache 2>/dev/null
success "Certbot geïnstalleerd"

# ---- Update Apache VHost voor HTTP ----
info "Update Apache VHost..."
cat > /etc/apache2/sites-available/asterisk-manager.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    DocumentRoot /var/www/asterisk-manager

    <Directory /var/www/asterisk-manager>
        Options +FollowSymLinks -Indexes
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch "\.(sql|log|bak\..*)$">
        Require all denied
    </FilesMatch>

    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"

    ErrorLog \${APACHE_LOG_DIR}/asterisk-manager-error.log
    CustomLog \${APACHE_LOG_DIR}/asterisk-manager-access.log combined
</VirtualHost>
EOF

a2ensite asterisk-manager 2>/dev/null || true
a2enmod rewrite headers ssl 2>/dev/null || true
apache2ctl configtest && systemctl reload apache2
success "Apache geconfigureerd voor $DOMAIN"

# ---- Vraag SSL certificaat aan ----
info "Certbot SSL certificaat aanvragen voor $DOMAIN..."
certbot --apache \
    --non-interactive \
    --agree-tos \
    --email "$EMAIL" \
    --domains "$DOMAIN" \
    --redirect

success "SSL certificaat aangevraagd"

# ---- Update APP_URL in config.php ----
info "Update APP_URL naar HTTPS..."
CONFIG="/var/www/asterisk-manager/config.php"
if grep -q "APP_URL" "$CONFIG"; then
    sed -i "s|define('APP_URL'.*|define('APP_URL', 'https://$DOMAIN');|" "$CONFIG"
    success "APP_URL bijgewerkt naar https://$DOMAIN"
fi

# ---- Automatische verlenging testen ----
info "Test automatische verlenging..."
certbot renew --dry-run
success "Automatische verlenging werkt"

# ---- Controleer certificaat ----
info "Certificaat informatie:"
certbot certificates | grep -A 5 "$DOMAIN" || true

echo ""
echo "============================================"
echo -e "  ${GREEN}SSL Setup Compleet!${NC}"
echo "============================================"
echo ""
echo "  URL: https://$DOMAIN"
echo ""
echo "  Het certificaat wordt automatisch verlengd"
echo "  via de cron job: /etc/cron.d/certbot"
echo ""
echo "  Controleer: certbot certificates"
echo "============================================"
