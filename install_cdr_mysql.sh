#!/bin/bash
# ============================================================
# Installeer cdr_mysql.so voor Asterisk 22
# ============================================================

set -e
RED='\033[0;31m'; GREEN='\033[0;32m'; CYAN='\033[0;36m'; NC='\033[0m'

info()    { echo -e "${CYAN}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC} $1"; }
error()   { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# Haal Asterisk versie op
ASTERISK_VER=$(asterisk -rx "core show version" 2>/dev/null | grep -oP '\d+\.\d+\.\d+' | head -1)
[[ -z "$ASTERISK_VER" ]] && error "Asterisk niet gevonden of niet actief"
info "Asterisk versie: $ASTERISK_VER"

# Haal DB wachtwoord op
DB_PASS=$(grep "DB_PASS" /var/www/asterisk-manager/config.php | grep -oP "(?<=')[^']+(?=')" | tail -1)
DB_USER=$(grep "DB_USER" /var/www/asterisk-manager/config.php | grep -oP "(?<=')[^']+(?=')" | head -1)
[[ -z "$DB_PASS" ]] && error "Kan DB_PASS niet ophalen uit config.php"

info "Installeer build dependencies..."
apt-get install -y -qq \
    build-essential \
    asterisk-dev \
    libmysqlclient-dev \
    libmariadb-dev \
    wget \
    2>/dev/null || true

# Download Asterisk broncode
WORK_DIR="/tmp/asterisk-src"
mkdir -p "$WORK_DIR"
cd "$WORK_DIR"

info "Download Asterisk $ASTERISK_VER broncode..."
wget -q "https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-${ASTERISK_VER}.tar.gz" -O asterisk.tar.gz \
    || wget -q "https://downloads.asterisk.org/pub/telephony/asterisk/old-releases/asterisk-${ASTERISK_VER}.tar.gz" -O asterisk.tar.gz

tar -xzf asterisk.tar.gz
cd asterisk-${ASTERISK_VER}

info "Configureer en compileer cdr_mysql.so..."
./configure --with-mysql 2>/dev/null | tail -3

# Compileer alleen de cdr_mysql module
make menuselect.makeopts 2>/dev/null
menuselect/menuselect --enable cdr_mysql menuselect.makeopts 2>/dev/null || true
make -C cdr cdr_mysql.so 2>/dev/null || make cdr_mysql.so

# Kopieer de module
MODULE_PATH=$(asterisk -rx "module show like cdr_csv" 2>/dev/null | grep "cdr_csv" | awk '{print $1}' | head -1)
MODULE_DIR=$(dirname $(find /usr/lib /usr/lib64 -name "cdr_csv.so" 2>/dev/null | head -1))
[[ -z "$MODULE_DIR" ]] && MODULE_DIR="/usr/lib/asterisk/modules"

info "Kopieer cdr_mysql.so naar $MODULE_DIR..."
cp cdr/cdr_mysql.so "$MODULE_DIR/"
chmod 755 "$MODULE_DIR/cdr_mysql.so"

# Configureer cdr_mysql.conf
info "Configureer /etc/asterisk/cdr_mysql.conf..."
cat > /etc/asterisk/cdr_mysql.conf << EOF
[global]
hostname=localhost
dbname=asteriskcdrdb
table=cdr
user=$DB_USER
password=$DB_PASS
port=3306
charset=utf8mb4
EOF

# Maak de database aan
info "Maak asteriskcdrdb database aan..."
mysql -u root << SQL
CREATE DATABASE IF NOT EXISTS asteriskcdrdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE asteriskcdrdb;
CREATE TABLE IF NOT EXISTS cdr (
    calldate    datetime     NOT NULL DEFAULT '1900-01-01 00:00:00',
    clid        varchar(80)  NOT NULL DEFAULT '',
    src         varchar(80)  NOT NULL DEFAULT '',
    dst         varchar(80)  NOT NULL DEFAULT '',
    dcontext    varchar(80)  NOT NULL DEFAULT '',
    channel     varchar(80)  NOT NULL DEFAULT '',
    dstchannel  varchar(80)  NOT NULL DEFAULT '',
    lastapp     varchar(80)  NOT NULL DEFAULT '',
    lastdata    varchar(80)  NOT NULL DEFAULT '',
    duration    int(11)      NOT NULL DEFAULT 0,
    billsec     int(11)      NOT NULL DEFAULT 0,
    disposition varchar(45)  NOT NULL DEFAULT '',
    amaflags    int(11)      NOT NULL DEFAULT 0,
    accountcode varchar(20)  NOT NULL DEFAULT '',
    uniqueid    varchar(32)  NOT NULL DEFAULT '',
    userfield   varchar(255) NOT NULL DEFAULT '',
    INDEX calldate (calldate)
);
GRANT ALL ON asteriskcdrdb.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

# Laad de module
info "Laad cdr_mysql.so in Asterisk..."
asterisk -rx "module load cdr_mysql.so"
sleep 1
asterisk -rx "cdr show status"

success "cdr_mysql installatie voltooid!"
success "CDR records worden nu automatisch opgeslagen in asteriskcdrdb.cdr"
echo ""
echo "Gebruik de Sync CDR knop in de webinterface om records te importeren."
