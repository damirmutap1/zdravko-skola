SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS cms_settings (
    setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_cms_settings_updated_by (updated_by),
    CONSTRAINT fk_cms_settings_admin FOREIGN KEY (updated_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO cms_settings (setting_key, setting_value)
VALUES
    ('homepage_important_limit', '6'),
    ('homepage_events_limit', '6')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
