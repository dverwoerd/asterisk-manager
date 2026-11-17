-- Gespreksspecificaties: klanten (koppeling met toestellen)
-- Uitvoeren:  mysql asterisk_manager < migrate_gespecificeerd.sql
--             mysql asterisk_manager < migrate_bel_tarieven.sql   (tarieventabel + basistarieven)
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(128),
    contact_name VARCHAR(128) NOT NULL,
    email VARCHAR(128),
    phone VARCHAR(32),
    address TEXT,
    city VARCHAR(64),
    postal_code VARCHAR(16),
    country VARCHAR(64) DEFAULT 'Nederland',
    vat_number VARCHAR(32),
    extensions_csv VARCHAR(512) COMMENT 'Komma-gescheiden toestelnummers van deze klant',
    notes TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bestond customers al uit de oude billing? Haal de koppeling met rate_plans weg.
ALTER TABLE customers DROP FOREIGN KEY IF EXISTS customers_ibfk_1;
ALTER TABLE customers DROP COLUMN IF EXISTS rate_plan_id;
