# OŠ Zdravko Jovanović website

PHP website with a database-backed CMS and administration panel.

## Installation

See [`database/README.md`](database/README.md) for the database import and first-login instructions.

Production credentials are intentionally excluded from Git. Create these files from their examples when deploying:

- `config/database.php` from `config/database.example.php`
- `config/contact.php` from `config/contact.example.php`

The administration panel is available at `/skola-admin/` after the CMS migration is imported.
