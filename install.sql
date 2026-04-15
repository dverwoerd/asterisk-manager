-- ============================================================
-- Asterisk Manager - Database Schema
-- Compatible with: MySQL 8+ / MariaDB 10.6+
-- ============================================================

CREATE DATABASE IF NOT EXISTS asterisk_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE asterisk_manager;

-- ============================================================
-- USERS & AUTH
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(128),
    email VARCHAR(128),
    role ENUM('admin','operator','viewer') DEFAULT 'operator',
    language VARCHAR(8) DEFAULT 'en',
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- Default admin user (password: admin123 - CHANGE AFTER INSTALL)
INSERT INTO users (username, password_hash, full_name, role)
VALUES ('admin', '$2y$12$placeholder_change_me', 'Administrator', 'admin');

-- ============================================================
-- TRUNKS (SIP/PJSIP)
-- ============================================================
CREATE TABLE trunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    type ENUM('pjsip','sip','dahdi') DEFAULT 'pjsip',
    host VARCHAR(128) NOT NULL,
    port INT DEFAULT 5060,
    username VARCHAR(64),
    password VARCHAR(128),
    auth_type ENUM('userpass','md5','none') DEFAULT 'userpass',
    context VARCHAR(64) DEFAULT 'from-trunk',
    codecs VARCHAR(255) DEFAULT 'ulaw,alaw,g722',
    max_channels INT DEFAULT 30,
    outbound_cid VARCHAR(64),
    notes TEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- EXTENSIONS (PJSIP endpoints)
-- ============================================================
CREATE TABLE extensions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    extension VARCHAR(32) NOT NULL UNIQUE,
    full_name VARCHAR(128) NOT NULL,
    email VARCHAR(128),
    secret VARCHAR(128) NOT NULL,
    context VARCHAR(64) DEFAULT 'from-internal',
    mailbox VARCHAR(64),
    voicemail_enabled TINYINT(1) DEFAULT 0,
    voicemail_pin VARCHAR(16),
    callerid_name VARCHAR(64),
    callerid_number VARCHAR(32),
    max_contacts INT DEFAULT 1,
    transport VARCHAR(32) DEFAULT 'transport-udp',
    codecs VARCHAR(255) DEFAULT 'ulaw,alaw,g722',
    dtmf_mode ENUM('rfc4733','inband','info','auto') DEFAULT 'rfc4733',
    call_waiting TINYINT(1) DEFAULT 1,
    call_recording ENUM('never','always','on_demand') DEFAULT 'never',
    notes TEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- RING GROUPS
-- ============================================================
CREATE TABLE ring_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number VARCHAR(32) NOT NULL UNIQUE,
    name VARCHAR(128) NOT NULL,
    strategy ENUM('ringall','hunt','memoryhunt','firstnotonphone','random') DEFAULT 'ringall',
    ring_time INT DEFAULT 20,
    destination_type ENUM('extension','queue','voicemail','external','hangup') DEFAULT 'extension',
    destination VARCHAR(64),
    announcement VARCHAR(255),
    caller_id_prefix VARCHAR(32),
    notes TEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE ring_group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ring_group_id INT NOT NULL,
    extension VARCHAR(32) NOT NULL,
    ring_time INT DEFAULT 20,
    order_num INT DEFAULT 0,
    FOREIGN KEY (ring_group_id) REFERENCES ring_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- QUEUES
-- ============================================================
CREATE TABLE queues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    number VARCHAR(32) NOT NULL UNIQUE,
    strategy ENUM('ringall','leastrecent','fewestcalls','random','rrmemory','linear','wrandom','rrordered') DEFAULT 'ringall',
    timeout INT DEFAULT 15,
    wrapup_time INT DEFAULT 5,
    max_callers INT DEFAULT 0,
    announce_hold_time ENUM('yes','no','once') DEFAULT 'yes',
    announce_position ENUM('yes','no','limit','more','verbose') DEFAULT 'yes',
    announce_frequency INT DEFAULT 30,
    music_on_hold VARCHAR(64) DEFAULT 'default',
    join_announcement VARCHAR(255),
    caller_id_prefix VARCHAR(32),
    timeout_destination_type ENUM('extension','queue','voicemail','external','hangup') DEFAULT 'hangup',
    timeout_destination VARCHAR(64),
    notes TEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE queue_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id INT NOT NULL,
    extension VARCHAR(32) NOT NULL,
    penalty INT DEFAULT 0,
    paused TINYINT(1) DEFAULT 0,
    FOREIGN KEY (queue_id) REFERENCES queues(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- INBOUND ROUTES (DIDs)
-- ============================================================
CREATE TABLE inbound_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    did VARCHAR(32) NOT NULL,
    cid_number VARCHAR(32),
    description VARCHAR(128),
    destination_type ENUM('extension','queue','ring_group','voicemail','ivr','external','hangup','announcement') DEFAULT 'extension',
    destination VARCHAR(64) NOT NULL,
    time_group_id INT DEFAULT NULL,
    priority INT DEFAULT 0,
    notes TEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- OUTBOUND ROUTES
-- ============================================================
CREATE TABLE outbound_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    priority INT DEFAULT 0,
    emergency TINYINT(1) DEFAULT 0,
    notes TEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE outbound_route_dial_patterns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    match_pattern VARCHAR(64) NOT NULL,
    prepend VARCHAR(32) DEFAULT '',
    prefix VARCHAR(32) DEFAULT '',
    FOREIGN KEY (route_id) REFERENCES outbound_routes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE outbound_route_trunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    trunk_id INT NOT NULL,
    order_num INT DEFAULT 0,
    FOREIGN KEY (route_id) REFERENCES outbound_routes(id) ON DELETE CASCADE,
    FOREIGN KEY (trunk_id) REFERENCES trunks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DIALPLAN CONTEXTS
-- ============================================================
CREATE TABLE dialplan_contexts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE dialplan_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    context_id INT NOT NULL,
    extension VARCHAR(64) NOT NULL,
    priority INT DEFAULT 1,
    application VARCHAR(64) NOT NULL,
    app_data VARCHAR(512),
    notes VARCHAR(255),
    order_num INT DEFAULT 0,
    FOREIGN KEY (context_id) REFERENCES dialplan_contexts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- RATE PLANS & RATES
-- ============================================================
CREATE TABLE rate_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    description TEXT,
    currency VARCHAR(8) DEFAULT 'EUR',
    billing_increment INT DEFAULT 60,
    minimum_duration INT DEFAULT 0,
    connection_fee DECIMAL(10,4) DEFAULT 0.0000,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rate_plan_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    destination_name VARCHAR(128) NOT NULL,
    prefix VARCHAR(32) NOT NULL,
    rate_per_minute DECIMAL(10,4) NOT NULL,
    connection_fee DECIMAL(10,4) DEFAULT 0.0000,
    billing_increment INT DEFAULT 60,
    time_start TIME DEFAULT '00:00:00',
    time_end TIME DEFAULT '23:59:59',
    days_of_week VARCHAR(32) DEFAULT '1234567',
    notes VARCHAR(255),
    FOREIGN KEY (plan_id) REFERENCES rate_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Default rate plan
INSERT INTO rate_plans (name, description, currency) VALUES
('Standard', 'Default rate plan', 'EUR');

INSERT INTO rate_plan_rates (plan_id, destination_name, prefix, rate_per_minute) VALUES
(1, 'Netherlands - National', '31', 0.0200),
(1, 'Netherlands - Mobile', '316', 0.1500),
(1, 'Europe - Fixed', '3', 0.0300),
(1, 'USA & Canada', '1', 0.0150),
(1, 'International', '', 0.2500);

-- ============================================================
-- CUSTOMERS (for invoicing)
-- ============================================================
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(128),
    contact_name VARCHAR(128) NOT NULL,
    email VARCHAR(128),
    phone VARCHAR(32),
    address TEXT,
    city VARCHAR(64),
    postal_code VARCHAR(16),
    country VARCHAR(64) DEFAULT 'Netherlands',
    vat_number VARCHAR(32),
    rate_plan_id INT DEFAULT 1,
    extensions_csv VARCHAR(512) COMMENT 'Comma-separated list of assigned extensions',
    notes TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rate_plan_id) REFERENCES rate_plans(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- INVOICES
-- ============================================================
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(32) NOT NULL UNIQUE,
    customer_id INT,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    issue_date DATE DEFAULT (CURRENT_DATE),
    due_date DATE,
    subtotal DECIMAL(12,2) DEFAULT 0.00,
    tax_rate DECIMAL(5,2) DEFAULT 21.00,
    tax_amount DECIMAL(12,2) DEFAULT 0.00,
    total DECIMAL(12,2) DEFAULT 0.00,
    currency VARCHAR(8) DEFAULT 'EUR',
    status ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1.00,
    unit VARCHAR(32) DEFAULT 'minutes',
    unit_price DECIMAL(10,4) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    cdr_ids TEXT COMMENT 'JSON array of CDR IDs included',
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- CDR MIRROR (synced from Asterisk cdr MySQL table)
-- ============================================================
CREATE TABLE cdr_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calldate DATETIME,
    clid VARCHAR(80),
    src VARCHAR(80),
    dst VARCHAR(80),
    dcontext VARCHAR(80),
    channel VARCHAR(80),
    dstchannel VARCHAR(80),
    lastapp VARCHAR(80),
    lastdata VARCHAR(255),
    duration INT DEFAULT 0,
    billsec INT DEFAULT 0,
    disposition ENUM('NO ANSWER','FAILED','BUSY','ANSWERED','CONGESTION') DEFAULT 'ANSWERED',
    amaflags INT DEFAULT 0,
    accountcode VARCHAR(20),
    uniqueid VARCHAR(32),
    userfield VARCHAR(255),
    rate_plan_id INT DEFAULT NULL,
    rate_applied DECIMAL(10,4) DEFAULT NULL,
    cost DECIMAL(10,4) DEFAULT NULL,
    destination_name VARCHAR(128),
    invoiced TINYINT(1) DEFAULT 0,
    invoice_id INT DEFAULT NULL,
    INDEX idx_calldate (calldate),
    INDEX idx_src (src),
    INDEX idx_dst (dst),
    INDEX idx_invoiced (invoiced),
    UNIQUE KEY unique_call (uniqueid)
) ENGINE=InnoDB;

-- ============================================================
-- SYSTEM SETTINGS
-- ============================================================
CREATE TABLE settings (
    setting_key VARCHAR(64) PRIMARY KEY,
    setting_value TEXT,
    setting_type ENUM('string','integer','boolean','json') DEFAULT 'string',
    description VARCHAR(255),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('company_name', 'My Company', 'string', 'Company name for invoices'),
('company_address', '', 'string', 'Company address'),
('company_vat', '', 'string', 'Company VAT number'),
('company_email', '', 'string', 'Company email'),
('company_phone', '', 'string', 'Company phone'),
('invoice_prefix', 'INV-', 'string', 'Invoice number prefix'),
('invoice_next_number', '1001', 'integer', 'Next invoice number'),
('default_tax_rate', '21', 'integer', 'Default VAT/tax rate percentage'),
('default_currency', 'EUR', 'string', 'Default currency code'),
('asterisk_host', '127.0.0.1', 'string', 'Asterisk server host'),
('asterisk_ami_port', '5038', 'integer', 'Asterisk AMI port'),
('asterisk_ami_user', 'manager', 'string', 'Asterisk AMI username'),
('asterisk_ami_secret', '', 'string', 'Asterisk AMI secret'),
('asterisk_config_path', '/etc/asterisk', 'string', 'Path to Asterisk config files'),
('cdr_sync_enabled', '1', 'boolean', 'Enable CDR synchronization'),
('cdr_sync_interval', '300', 'integer', 'CDR sync interval in seconds'),
('default_language', 'en', 'string', 'Default interface language'),
('timezone', 'Europe/Amsterdam', 'string', 'System timezone'),
('asterisk_external_host', '', 'string', 'External hostname or IP for NAT traversal'),
('asterisk_local_nets', '192.168.0.0/16,172.16.0.0/12,10.0.0.0/8', 'string', 'Local networks for NAT detection (comma-separated)'),
('date_format', 'd-m-Y', 'string', 'Date format'),
('logo_path', '', 'string', 'Path to company logo for invoices');

-- ============================================================
-- YEALINK AUTOPROVISIONERING
-- ============================================================

CREATE TABLE IF NOT EXISTS provision_phones (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    extension_id    INT NOT NULL,
    mac_address     VARCHAR(17) NOT NULL UNIQUE COMMENT 'Format: AA:BB:CC:DD:EE:FF',
    model           VARCHAR(64) DEFAULT 'T46U' COMMENT 'Yealink model bijv T46U, T42U, T58W',
    admin_password  VARCHAR(64) DEFAULT 'admin',
    display_name    VARCHAR(128),
    timezone        VARCHAR(64) DEFAULT 'Europe/Amsterdam',
    ntp_server      VARCHAR(128) DEFAULT 'pool.ntp.org',
    language        VARCHAR(16) DEFAULT 'Dutch',
    notes           TEXT,
    last_provision  DATETIME DEFAULT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (extension_id) REFERENCES extensions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS provision_blf_keys (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    phone_id        INT NOT NULL,
    key_number      INT NOT NULL COMMENT 'DSS key nummer (1-40)',
    key_type        ENUM('blf','speed_dial','line','dtmf','prefix','local_group','xml_group','xml_browser','ldap','conference','forward','transfer','hold','dnd','recall','reboot','hot_desking','acd','zero_touch','url','multicast','group_listening','paging_list') DEFAULT 'blf',
    label           VARCHAR(64),
    value           VARCHAR(128) COMMENT 'Extensie nummer of URI',
    extension_id    INT DEFAULT NULL COMMENT 'Gekoppelde extensie voor BLF',
    pickup_code     VARCHAR(16) DEFAULT '*8' COMMENT 'Code voor call pickup via BLF',
    FOREIGN KEY (phone_id) REFERENCES provision_phones(id) ON DELETE CASCADE,
    FOREIGN KEY (extension_id) REFERENCES extensions(id) ON DELETE SET NULL,
    UNIQUE KEY unique_phone_key (phone_id, key_number)
) ENGINE=InnoDB;

-- Provisioning settings
INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES
('provision_base_url', '', 'string', 'Base URL voor provisioning bijv http://pbx.clearvoip.nl/provision'),
('provision_admin_pass', 'admin', 'string', 'Standaard admin wachtwoord voor Yealink toestellen'),
('provision_ntp_server', 'pool.ntp.org', 'string', 'NTP tijdserver'),
('provision_timezone', 'Europe/Amsterdam', 'string', 'Standaard tijdzone voor toestellen'),
('provision_tftp_path', '/var/www/asterisk-manager/provision', 'string', 'Pad naar provisioning bestanden');

-- ============================================================
-- COMPANY ADRESBOEK
-- ============================================================
CREATE TABLE IF NOT EXISTS phonebook_groups (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(128) NOT NULL,
    description TEXT,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS phonebook_contacts (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    group_id        INT NOT NULL,
    first_name      VARCHAR(64) DEFAULT '',
    last_name       VARCHAR(64) DEFAULT '',
    company         VARCHAR(128) DEFAULT '',
    phone_mobile    VARCHAR(32) DEFAULT '',
    phone_work      VARCHAR(32) DEFAULT '',
    phone_home      VARCHAR(32) DEFAULT '',
    email           VARCHAR(128) DEFAULT '',
    notes           TEXT,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES phonebook_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Koppel adresboek aan toestel
ALTER TABLE provision_phones 
    ADD COLUMN IF NOT EXISTS phonebook_group_id INT DEFAULT NULL;

-- ============================================================
-- LOGIN SECURITY
-- ============================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username   VARCHAR(64) DEFAULT '',
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ip_blacklist (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ip_address  VARCHAR(45) NOT NULL UNIQUE,
    reason      VARCHAR(255) DEFAULT '',
    blocked_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at  DATETIME DEFAULT NULL,
    whitelisted TINYINT DEFAULT 0,
    INDEX idx_ip (ip_address)
) ENGINE=InnoDB;
