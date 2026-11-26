-- Eenmalige koppelcodes voor de Pantronic softphone (provisioning zonder wachtwoord in de link)
CREATE TABLE IF NOT EXISTS softphone_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    extension_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    used_ip VARCHAR(45) NULL,
    created_by VARCHAR(64) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ext (extension_id),
    INDEX idx_exp (expires_at),
    FOREIGN KEY (extension_id) REFERENCES extensions(id) ON DELETE CASCADE
) ENGINE=InnoDB;
