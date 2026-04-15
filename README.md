# Asterisk Manager

A complete web-based management system for **Asterisk 22** on Debian with Apache2.

## Features

| Module | Description |
|---|---|
| **Extensions** | PJSIP endpoint management, voicemail, codecs, recording |
| **Trunks** | SIP/PJSIP trunk configuration with registration |
| **Queues** | Call queues with member management and strategies |
| **Ring Groups** | Ring groups with multiple strategies (ringall, hunt, etc.) |
| **Inbound Routes** | DID-based inbound call routing |
| **Outbound Routes** | Dial pattern matching, trunk failover |
| **Dialplan** | Custom context/extension editor |
| **CDR** | Call Detail Records with sync, filtering, CSV export |
| **Rate Plans** | Tariff plans with per-prefix rates, CSV import |
| **Customers** | Customer management with extension assignment |
| **Invoices** | Auto-generate invoices from CDR data with print/PDF |
| **Settings** | AMI connection, company info, users, i18n |
| **Multi-language** | English + Dutch included; easily extendable |

---

## Requirements

- Debian 11 or 12 (Bookworm recommended)
- Asterisk 22 (PJSIP-based)
- Apache2 with mod_rewrite, mod_headers
- PHP 8.1+ with extensions: pdo, pdo_mysql, mbstring, xml, zip
- MariaDB 10.6+ or MySQL 8+

---

## Quick Install

```bash
# Clone or extract the project
sudo mkdir -p /var/www/asterisk-manager
sudo cp -r asterisk-manager/. /var/www/asterisk-manager/

# Run the installer (as root)
cd /var/www/asterisk-manager
sudo bash install.sh
```

The installer will:
- Install PHP, Apache2, MariaDB (if not present)
- Create the database and user
- Generate `config.php` with random secrets
- Configure the Apache virtual host
- Set correct file permissions
- Install the CDR sync cron job
- Output login credentials

---

## Manual Install

### 1. Database

```bash
mysql -u root -p
```
```sql
CREATE DATABASE asterisk_manager CHARACTER SET utf8mb4;
CREATE USER 'asterisk_mgr'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL ON asterisk_manager.* TO 'asterisk_mgr'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
```bash
mysql -u root asterisk_manager < install.sql
```

Set admin password:
```bash
php -r "echo password_hash('YOUR_ADMIN_PASS', PASSWORD_BCRYPT);"
# Copy the hash and:
mysql -u root asterisk_manager -e "UPDATE users SET password_hash='HASH_HERE' WHERE username='admin';"
```

### 2. Configuration

Edit `config.php`:
```php
define('DB_USER', 'asterisk_mgr');
define('DB_PASS', 'STRONG_PASSWORD');
define('SECRET_KEY', 'RANDOM_32_CHAR_STRING');
```

### 3. Apache

```bash
sudo cp asterisk-manager.conf /etc/apache2/sites-available/
sudo a2ensite asterisk-manager
sudo a2enmod rewrite headers expires
sudo systemctl reload apache2
```

### 4. File Permissions

```bash
sudo chown -R www-data:www-data /var/www/asterisk-manager
sudo chmod -R 750 /var/www/asterisk-manager
sudo chmod -R 770 /var/www/asterisk-manager/logs /var/www/asterisk-manager/uploads

# Allow web server to write Asterisk configs
sudo chown -R root:www-data /etc/asterisk
sudo chmod -R 664 /etc/asterisk/*.conf
sudo chmod 770 /etc/asterisk
```

---

## Asterisk Configuration

### manager.conf (AMI)

Add to `/etc/asterisk/manager.conf`:

```ini
[general]
enabled = yes
port = 5038
bindaddr = 127.0.0.1

[asterisk_mgr]
secret = YOUR_AMI_SECRET
read = all
write = all
deny = 0.0.0.0/0.0.0.0
permit = 127.0.0.1/255.255.255.0
```

### cdr_mysql.conf (CDR to MySQL)

To sync CDR automatically, configure Asterisk to write CDR to MySQL.
Install the module:
```bash
apt install asterisk-mysql   # or build from source
```

Add `/etc/asterisk/cdr_mysql.conf`:
```ini
[global]
hostname=localhost
dbname=asteriskcdrdb
table=cdr
user=asterisk_mgr
password=YOUR_DB_PASS
charset=utf8mb4
```

Load the module:
```bash
asterisk -rx "module load cdr_mysql.so"
```

Then use the **CDR → Sync** button or the cron job to import records.

---

## Adding a New Language

1. Copy `lang/en.php` to `lang/XX.php` (e.g., `lang/de.php`)
2. Set `_language_name` to the display name
3. Translate all values
4. The language appears automatically in Settings and the sidebar switcher

---

## Directory Structure

```
asterisk-manager/
├── index.php              # Front controller / router
├── config.php             # Database, paths, secrets
├── install.sql            # Full database schema + seed data
├── install.sh             # Automated installer
├── .htaccess              # Apache URL routing + security
├── asterisk-manager.conf  # Apache VHost config
├── includes/
│   ├── Database.php       # PDO singleton
│   ├── AsteriskAMI.php    # AMI client (TCP socket)
│   ├── AsteriskConfig.php # Config file generator (PJSIP, dialplan, queues)
│   ├── i18n.php           # Translation system
│   └── helpers.php        # Global utility functions
├── controllers/           # One controller per page/module
├── views/                 # PHP HTML templates
│   ├── layout.php         # Main HTML wrapper with sidebar
│   ├── auth/login.php
│   ├── dashboard/
│   ├── extensions/
│   ├── queues/
│   ├── ring_groups/
│   ├── routes/
│   ├── trunks/
│   ├── dialplan/
│   ├── cdr/
│   ├── invoices/
│   ├── rate_plans/
│   ├── customers/
│   └── settings/
├── lang/
│   ├── en.php             # English
│   └── nl.php             # Dutch (Nederlands)
├── assets/
│   ├── css/style.css      # Full stylesheet
│   └── js/app.js          # Frontend JavaScript
├── cron/
│   └── cdr_sync.php       # Auto-CDR cost calculation
└── logs/                  # Application logs (writable)
```

---

## How Config Generation Works

When you save extensions, queues, ring groups, or routes, the system:

1. Reads all data from the database
2. **Generates** the appropriate Asterisk config file:
   - `pjsip.conf` — for extensions and trunks
   - `queues.conf` — for queues
   - `extensions.conf` — complete dialplan (from-internal, from-trunk, outbound routes)
3. **Backs up** the existing `.conf` file as `.conf.bak.TIMESTAMP`
4. **Writes** the new config
5. **Reloads** the relevant Asterisk module via AMI

---

## Invoice Workflow

```
CDR Sync → Apply Rate Plan → Generate Invoice → Print/Send → Mark Paid
```

1. **Sync CDR** — imports call records from `asteriskcdrdb.cdr`
2. **Calculate Costs** — matches each call's destination to the best rate plan prefix
3. **Create Customer** — assign extensions and a rate plan
4. **Generate Invoice** — select customer + period → system groups calls by destination, calculates totals, creates line items
5. **Print** — browser print dialog opens with a clean A4 invoice layout

---

## Security Notes

- Change the default admin password immediately after install
- Use HTTPS in production (see commented VHost config)
- The `SECRET_KEY` in `config.php` protects CSRF tokens — keep it secret
- The `logs/` and `config.php` are protected from web access via `.htaccess`
- AMI is bound to `127.0.0.1` only (no external access)
- www-data only has write access to `/etc/asterisk` — not execute

---

## Roles

| Role | Permissions |
|---|---|
| **admin** | Full access, user management, system settings |
| **operator** | Can add/edit/delete telephony config, generate invoices |
| **viewer** | Read-only access to all pages |

---

## Troubleshooting

**Cannot connect to AMI:**  
Check `manager.conf` has the user block and `enabled=yes`. Verify port 5038 is open: `ss -tlnp | grep 5038`.

**Config files not written:**  
Verify `www-data` has write access to `/etc/asterisk`: `ls -la /etc/asterisk`.

**CDR not importing:**  
Check `asteriskcdrdb.cdr` table exists and `cdr_mysql.so` is loaded: `asterisk -rx "module show like cdr_mysql"`.

**PHP errors:**  
Check `/var/www/asterisk-manager/logs/php_errors.log`.
