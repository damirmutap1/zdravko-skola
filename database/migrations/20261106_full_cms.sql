SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admins_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_pages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    page_key VARCHAR(120) NOT NULL,
    title VARCHAR(180) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    route_path VARCHAR(255) NOT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    is_system TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cms_pages_page_key (page_key),
    UNIQUE KEY uq_cms_pages_route_path (route_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_page_content (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    browser_title VARCHAR(255) NOT NULL,
    meta_description TEXT NULL,
    content_html MEDIUMTEXT NOT NULL,
    extra_head_html MEDIUMTEXT NULL,
    extra_footer_html MEDIUMTEXT NULL,
    source_hash CHAR(40) NULL,
    seeded_from_source_at DATETIME NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cms_page_content_page_id (page_id),
    KEY idx_cms_page_content_published (is_published),
    CONSTRAINT fk_cms_page_content_page FOREIGN KEY (page_id) REFERENCES cms_pages (id) ON DELETE CASCADE,
    CONSTRAINT fk_cms_page_content_created_by FOREIGN KEY (created_by) REFERENCES admins (id) ON DELETE SET NULL,
    CONSTRAINT fk_cms_page_content_updated_by FOREIGN KEY (updated_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_content_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    page_id INT UNSIGNED NOT NULL,
    area_key VARCHAR(120) NOT NULL,
    content_type ENUM('link', 'file', 'image', 'html') NOT NULL DEFAULT 'link',
    title VARCHAR(255) NOT NULL,
    summary TEXT NULL,
    body_html MEDIUMTEXT NULL,
    link_url VARCHAR(500) NULL,
    link_target ENUM('_self', '_blank') NOT NULL DEFAULT '_blank',
    file_path VARCHAR(500) NULL,
    image_path VARCHAR(500) NULL,
    published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_cms_content_page_area (page_id, area_key, is_published),
    KEY idx_cms_content_publish (published_at),
    CONSTRAINT fk_cms_content_page FOREIGN KEY (page_id) REFERENCES cms_pages (id) ON DELETE CASCADE,
    CONSTRAINT fk_cms_content_admin FOREIGN KEY (created_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_settings (
    setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_cms_settings_updated_by (updated_by),
    CONSTRAINT fk_cms_settings_admin FOREIGN KEY (updated_by) REFERENCES admins (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_navigation_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NULL,
    page_id INT UNSIGNED NULL,
    label VARCHAR(190) NOT NULL,
    custom_url VARCHAR(500) NULL,
    open_in_new_tab TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_cms_navigation_parent (parent_id, sort_order),
    KEY idx_cms_navigation_page (page_id),
    CONSTRAINT fk_cms_navigation_parent FOREIGN KEY (parent_id) REFERENCES cms_navigation_items (id) ON DELETE CASCADE,
    CONSTRAINT fk_cms_navigation_page FOREIGN KEY (page_id) REFERENCES cms_pages (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admins (username, full_name, password_hash, is_active)
VALUES ('damirmutap', 'Damir Mutap', '$2y$10$32.6oFkyikj/UqnY60mMDOM5lQ1OQTJ1aDVQKaOxJGN8NcucJF/82', 1)
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    password_hash = VALUES(password_hash),
    is_active = VALUES(is_active);

INSERT INTO cms_settings (setting_key, setting_value)
VALUES
    ('homepage_important_limit', '6'),
    ('homepage_events_limit', '6')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
