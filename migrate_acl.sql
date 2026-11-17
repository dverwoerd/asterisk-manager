-- Bel-rechten (ACL): groepen van toestellen die alleen onderling mogen bellen
CREATE TABLE IF NOT EXISTS call_acl_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    allowed_extras VARCHAR(255) NOT NULL DEFAULT '',
    notes TEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS call_acl_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    extension VARCHAR(32) NOT NULL,
    FOREIGN KEY (group_id) REFERENCES call_acl_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB;
