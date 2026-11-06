# OŠ Zdravko Jovanović website

PHP website with a database-backed CMS and administration panel.

## Runtime requirements

- PHP 8.1 or newer; production is verified on PHP 8.5
- MySQL or MariaDB with `utf8mb4` support
- PHP extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `zip`, and `curl`
- Apache `mod_rewrite` for extensionless database-managed routes
- Write access for `uploads/cms/`

## Installation

See [`database/README.md`](database/README.md) for the database import and first-login instructions.

Production credentials are intentionally excluded from Git. Create these files from their examples when deploying:

- `config/database.php` from `config/database.example.php`
- `config/contact.php` from `config/contact.example.php`

The administration panel is available at `/skola-admin/` after the CMS migration is imported.
