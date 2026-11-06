# CMS installation

1. Upload the project files, including `.htaccess`, `skola-admin/`, `includes/`, `stylesheets/cms.css`, and the writable `uploads/cms/` directory.
2. Create `config/database.php` from `config/database.example.php` and add the production database connection.
3. Create `config/contact.php` from `config/contact.example.php` and add the contact recipient and reCAPTCHA secret.
4. In phpMyAdmin, select the site database and import `database/migrations/20261106_full_cms.sql` once.
5. Open `/skola-admin/` and sign in. The first dashboard visit automatically registers and imports all existing public PHP pages into `cms_page_content` and creates the editable navigation tree.
6. Change the initial password from **Admin korisnici** after the first login.

The public site keeps rendering the original PHP files when the database is unavailable. Once a page has been imported, its published database version is used at the same URL.
