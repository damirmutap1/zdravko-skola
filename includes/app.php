<?php

if (!defined('SITE_ROOT_PATH')) {
    define('SITE_ROOT_PATH', dirname(__DIR__));
}

function site_root_path()
{
    return SITE_ROOT_PATH;
}

function site_config()
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $config = [];
    $configFile = site_root_path() . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

    if (is_file($configFile)) {
        $loaded = require $configFile;
        if (is_array($loaded)) {
            $config = $loaded;
        }
    }

    $defaults = [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'zdravkoj_website',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
    ];

    $config = array_merge($defaults, $config);

    $environmentMap = [
        'host' => 'ZDRAVKO_DB_HOST',
        'port' => 'ZDRAVKO_DB_PORT',
        'database' => 'ZDRAVKO_DB_NAME',
        'username' => 'ZDRAVKO_DB_USER',
        'password' => 'ZDRAVKO_DB_PASSWORD',
    ];

    foreach ($environmentMap as $configKey => $environmentKey) {
        $environmentValue = getenv($environmentKey);
        if ($environmentValue !== false) {
            $config[$configKey] = $environmentValue;
        }
    }

    return $config;
}

function site_db()
{
    static $pdo = null;
    static $attempted = false;

    if ($attempted) {
        return $pdo;
    }

    $attempted = true;
    $config = site_config();

    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    } catch (Throwable $exception) {
        $pdo = null;
    }

    return $pdo;
}

function site_db_or_fail()
{
    $pdo = site_db();

    if (!$pdo instanceof PDO) {
        throw new RuntimeException('Povezivanje sa bazom nije dostupno. Ažurirajte config/database.php i prvo uvezite migraciju.');
    }

    return $pdo;
}

function site_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function site_slugify($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($transliterated !== false) {
        $value = $transliterated;
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = trim((string) $value, '-');

    return $value;
}

function site_humanize_slug($value)
{
    $value = trim((string) $value);

    if ($value === 'index') {
        return 'Početna';
    }

    $value = str_replace(['-', '_'], ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);

    return ucwords(trim((string) $value));
}

function site_normalize_datetime_input($value)
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    $timestamp = strtotime(str_replace('T', ' ', $value));
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

function site_format_date($value, $format = 'd.m.Y.')
{
    if (!$value) {
        return '';
    }

    $timestamp = strtotime((string) $value);
    if ($timestamp === false) {
        return '';
    }

    return date($format, $timestamp);
}

function site_excerpt($value, $limit = 180)
{
    $value = trim(strip_tags((string) $value));
    $value = preg_replace('/\s+/', ' ', $value);

    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit - 1)) . '...';
    }

    if (strlen($value) <= $limit) {
        return $value;
    }

    return rtrim(substr($value, 0, $limit - 1)) . '...';
}

function site_request_path()
{
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $path = (string) parse_url($requestUri, PHP_URL_PATH);

    if ($path === '') {
        return 'index';
    }

    $scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    if ($scriptDirectory !== '' && $scriptDirectory !== '/' && strpos($path, $scriptDirectory) === 0) {
        $path = substr($path, strlen($scriptDirectory));
    }

    $path = trim((string) $path, '/');
    if ($path === '') {
        return 'index';
    }

    return preg_replace('/\.php$/i', '', $path);
}

function site_base_path()
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
    $siteRoot = realpath(site_root_path());

    if ($documentRoot !== false && $siteRoot !== false) {
        $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
        $normalizedSiteRoot = rtrim(str_replace('\\', '/', $siteRoot), '/');

        if ($normalizedSiteRoot === $normalizedDocumentRoot) {
            $basePath = '';

            return $basePath;
        }

        $prefix = strtolower($normalizedDocumentRoot) . '/';
        if (strpos(strtolower($normalizedSiteRoot), $prefix) === 0) {
            $basePath = substr($normalizedSiteRoot, strlen($normalizedDocumentRoot));

            return $basePath;
        }
    }

    $basePath = '';

    return $basePath;
}

function site_url($path = '')
{
    $basePath = site_base_path();
    $path = ltrim((string) $path, '/');

    if ($path === '') {
        return $basePath === '' ? '/' : rtrim($basePath, '/') . '/';
    }

    if ($basePath === '') {
        return '/' . $path;
    }

    return rtrim($basePath, '/') . '/' . $path;
}

function site_route_to_url($routePath)
{
    $routePath = trim((string) $routePath, '/');

    return $routePath === '' || $routePath === 'index' ? 'index' : $routePath;
}

function site_make_directory($absolutePath)
{
    if (is_dir($absolutePath)) {
        return true;
    }

    return mkdir($absolutePath, 0775, true);
}

function site_is_valid_upload(array $file)
{
    return isset($file['error'], $file['tmp_name']) && (int) $file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name']);
}

function site_upload_was_attempted(array $file)
{
    if (!isset($file['error'])) {
        return false;
    }

    return (int) $file['error'] !== UPLOAD_ERR_NO_FILE;
}

function site_format_bytes($bytes)
{
    $bytes = max(0, (int) $bytes);
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    $value = (float) $bytes;

    while ($value >= 1024 && $unitIndex < count($units) - 1) {
        $value /= 1024;
        $unitIndex++;
    }

    $precision = $unitIndex === 0 || $value >= 10 ? 0 : 1;

    return number_format($value, $precision, ',', '.') . ' ' . $units[$unitIndex];
}

function site_parse_ini_bytes($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return 0;
    }

    $unit = strtolower(substr($value, -1));
    $bytes = (float) $value;

    if ($unit === 'g') {
        $bytes *= 1024;
        $unit = 'm';
    }
    if ($unit === 'm') {
        $bytes *= 1024;
        $unit = 'k';
    }
    if ($unit === 'k') {
        $bytes *= 1024;
    }

    return max(0, (int) $bytes);
}

function site_effective_upload_limit($applicationLimit)
{
    $limits = [(int) $applicationLimit];

    foreach (['upload_max_filesize', 'post_max_size'] as $setting) {
        $configuredLimit = site_parse_ini_bytes(ini_get($setting));
        if ($configuredLimit > 0) {
            $limits[] = $configuredLimit;
        }
    }

    return min($limits);
}

function site_assert_upload_ready(array $file, $maxBytes)
{
    $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_NO_FILE) {
        return false;
    }

    if ($error !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Fajl je veći od ograničenja servera od ' . site_format_bytes($maxBytes) . '. Smanjite fajl i pokušajte ponovo.',
            UPLOAD_ERR_FORM_SIZE => 'Fajl je veći od dozvoljenih ' . site_format_bytes($maxBytes) . '.',
            UPLOAD_ERR_PARTIAL => 'Otpremanje je prekinuto pre završetka. Proverite internet vezu i pokušajte ponovo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server nema privremeni folder za otpremanje. Obratite se administratoru hostinga.',
            UPLOAD_ERR_CANT_WRITE => 'Server ne može da sačuva fajl. Proverite dozvole foldera uploads.',
            UPLOAD_ERR_EXTENSION => 'Server je zaustavio otpremanje fajla. Pokušajte sa drugim fajlom ili se obratite administratoru.',
        ];

        throw new RuntimeException($messages[$error] ?? 'Otpremanje nije uspelo. Izaberite fajl ponovo i pokušajte još jednom.');
    }

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Otpremanje nije završeno ispravno. Izaberite fajl ponovo i pokušajte još jednom.');
    }

    if ((int) ($file['size'] ?? 0) > (int) $maxBytes) {
        throw new InvalidArgumentException('Fajl je prevelik. Najveća dozvoljena veličina je ' . site_format_bytes($maxBytes) . '.');
    }

    return true;
}

function site_store_uploaded_file(array $file, $relativeBaseDirectory, array $allowedExtensions, $maxBytes = 10485760, $mustBeImage = false)
{
    if (!site_assert_upload_ready($file, $maxBytes)) {
        return null;
    }

    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

    if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
        throw new InvalidArgumentException('Format fajla nije podržan. Dozvoljeno: ' . strtoupper(implode(', ', $allowedExtensions)) . '.');
    }

    if ($mustBeImage) {
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new InvalidArgumentException('Izabrani fajl nije ispravna slika. Koristite JPG, PNG, GIF ili WEBP.');
        }

        $allowedImageMimeTypes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
        ];
        $detectedMimeType = strtolower((string) ($imageInfo['mime'] ?? ''));

        if (!isset($allowedImageMimeTypes[$extension]) || !in_array($detectedMimeType, $allowedImageMimeTypes[$extension], true)) {
            throw new InvalidArgumentException('Ekstenzija slike ne odgovara njenom formatu. Sačuvajte sliku ponovo kao JPG, PNG, GIF ili WEBP.');
        }
    }

    $safeName = site_slugify(pathinfo((string) $file['name'], PATHINFO_FILENAME));
    if ($safeName === '') {
        $safeName = 'file';
    }

    $relativeBaseDirectory = trim(str_replace('\\', '/', $relativeBaseDirectory), '/');
    $relativeDirectory = $relativeBaseDirectory . '/' . date('Y') . '/' . date('m');
    $absoluteDirectory = site_root_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

    if (!site_make_directory($absoluteDirectory) || !is_dir($absoluteDirectory)) {
        throw new RuntimeException('Folder za otpremanje nije mogao da bude napravljen. Proverite dozvole foldera uploads.');
    }

    if (!is_writable($absoluteDirectory)) {
        throw new RuntimeException('Folder za otpremanje nije upisiv. Proverite dozvole foldera uploads.');
    }

    try {
        $randomChunk = bin2hex(random_bytes(5));
    } catch (Throwable $exception) {
        $randomChunk = (string) mt_rand(10000, 99999);
    }

    $finalName = sprintf('%s-%s.%s', date('YmdHis'), $randomChunk . '-' . $safeName, $extension);
    $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $finalName;

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Fajl je primljen, ali nije mogao da bude sačuvan. Proverite slobodan prostor i dozvole foldera uploads.');
    }

    return $relativeDirectory . '/' . $finalName;
}

function site_delete_managed_file($relativePath)
{
    $relativePath = trim(str_replace('\\', '/', (string) $relativePath), '/');

    if ($relativePath === '' || strpos($relativePath, 'uploads/cms/') !== 0) {
        return;
    }

    $absolutePath = site_root_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function site_table_exists(PDO $pdo, $tableName)
{
    static $cache = [];

    $tableName = (string) $tableName;

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name');
    $statement->execute(['table_name' => $tableName]);

    $cache[$tableName] = (int) $statement->fetchColumn() > 0;

    return $cache[$tableName];
}

function site_has_tables(array $tableNames)
{
    $pdo = site_db();

    if (!$pdo instanceof PDO) {
        return false;
    }

    foreach ($tableNames as $tableName) {
        if (!site_table_exists($pdo, $tableName)) {
            return false;
        }
    }

    return true;
}

function cms_area_options()
{
    return [
        'homepage-important' => 'Početna – važna obaveštenja',
        'homepage-events' => 'Početna – aktuelnosti',
        'page-notices' => 'Obaveštenja na stranici',
        'page-downloads' => 'Dokumenti za preuzimanje',
        'page-links' => 'Korisni linkovi',
        'page-gallery' => 'Fotografije i galerija',
    ];
}

function cms_content_type_options()
{
    return [
        'link' => 'Link',
        'file' => 'Dokument ili fajl',
        'image' => 'Fotografija',
        'html' => 'Tekst ili objava',
    ];
}

function cms_content_image_type_is_ready(PDO $pdo)
{
    if (!site_table_exists($pdo, 'cms_content_items')) {
        return false;
    }

    $statement = $pdo->query("SHOW COLUMNS FROM cms_content_items LIKE 'content_type'");
    $column = $statement ? $statement->fetch() : null;
    $columnType = strtolower((string) ($column['Type'] ?? ''));

    if (strpos($columnType, 'enum(') === 0) {
        return strpos($columnType, "'image'") !== false;
    }

    return $columnType !== '';
}

function cms_month_options()
{
    return [
        'Januar',
        'Februar',
        'Mart',
        'April',
        'Maj',
        'Jun',
        'Jul',
        'Avgust',
        'Septembar',
        'Oktobar',
        'Novembar',
        'Decembar',
    ];
}

function cms_download_file_extensions()
{
    return [
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'ppt',
        'pptx',
        'csv',
        'txt',
        'zip',
        'rtf',
        'odt',
        'ods',
        'odp',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
    ];
}

function cms_is_download_href($href)
{
    $href = trim(html_entity_decode((string) $href, ENT_QUOTES, 'UTF-8'));

    if ($href === '' || preg_match('~^(?:#|javascript:|mailto:|tel:)~i', $href)) {
        return false;
    }

    $path = (string) parse_url($href, PHP_URL_PATH);
    if ($path === '') {
        $path = $href;
    }

    $extension = strtolower(pathinfo(rawurldecode($path), PATHINFO_EXTENSION));

    return $extension !== '' && in_array($extension, cms_download_file_extensions(), true);
}

function cms_download_item_title_from_href($href)
{
    $path = (string) parse_url((string) $href, PHP_URL_PATH);
    if ($path === '') {
        $path = (string) $href;
    }

    $baseName = rawurldecode(basename($path));
    $title = trim((string) preg_replace('/\.[^.]+$/', '', $baseName));
    $title = str_replace(['_', '-'], ' ', $title);
    $title = preg_replace('/\s+/', ' ', $title);

    return $title !== '' ? $title : 'Dokument';
}

function cms_extract_download_items_from_html($html)
{
    $html = (string) $html;
    if ($html === '') {
        return [];
    }

    $items = [];
    if (!preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
        return [];
    }

    foreach ($matches as $match) {
        $href = trim(html_entity_decode((string) ($match[1] ?? ''), ENT_QUOTES, 'UTF-8'));
        if (!cms_is_download_href($href)) {
            continue;
        }

        $title = trim(html_entity_decode(strip_tags((string) ($match[2] ?? '')), ENT_QUOTES, 'UTF-8'));
        if ($title === '') {
            $title = cms_download_item_title_from_href($href);
        }

        $items[] = [
            'title' => $title,
            'href' => $href,
        ];
    }

    return $items;
}

function cms_build_download_library_content_html($pageTitle, $intro = null)
{
    $pageTitle = trim((string) $pageTitle);
    $intro = trim((string) $intro);

    if ($pageTitle === '') {
        $pageTitle = 'Dokumenti';
    }

    $introHtml = '';
    if ($intro !== '') {
        $introHtml = '
                        <p class="font-post pad_t_2">' . site_escape($intro) . '</p>';
    }

    return sprintf(
        '<section class="pad_t_65 page4" id="content">
    <div class="container">
        <div class="row">
            <div class="grid_12 mar_t_3">
                <h3 class="s4 no-pad">%s</h3>
            </div>
        </div>
        <div class="row">
            <div class="grid_12">
                <div class="grid_8 nmr">
                    <div class="grid_8">
%s
                        {{page-downloads}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>',
        site_escape($pageTitle),
        $introHtml
    );
}

function cms_discover_public_pages()
{
    $files = glob(site_root_path() . DIRECTORY_SEPARATOR . '*.php');
    $pages = [];

    foreach ($files as $filePath) {
        $fileName = basename($filePath);
        $pageKey = pathinfo($fileName, PATHINFO_FILENAME);

        if (
            $pageKey === ''
            || strpos($pageKey, '_') === 0
            || in_array($pageKey, ['contact', 'page', 'phpinfo'], true)
        ) {
            continue;
        }

        $pages[] = [
            'page_key' => $pageKey,
            'title' => site_humanize_slug($pageKey),
            'file_path' => $fileName,
            'route_path' => $pageKey === 'index' ? 'index' : $pageKey,
        ];
    }

    usort(
        $pages,
        static function ($left, $right) {
            return strcmp($left['title'], $right['title']);
        }
    );

    return $pages;
}

function cms_sync_pages(PDO $pdo)
{
    if (!site_table_exists($pdo, 'cms_pages')) {
        throw new RuntimeException('Tabela cms_pages ne postoji. Prvo uvezite migraciju.');
    }

    $pages = cms_discover_public_pages();
    $inserted = 0;
    $updated = 0;

    $lookupStatement = $pdo->prepare('SELECT id FROM cms_pages WHERE page_key = :page_key LIMIT 1');
    $insertStatement = $pdo->prepare('
        INSERT INTO cms_pages (page_key, title, file_path, route_path, is_visible, is_system)
        VALUES (:page_key, :title, :file_path, :route_path, 1, 1)
    ');
    $updateStatement = $pdo->prepare('
        UPDATE cms_pages
        SET title = :title, file_path = :file_path, route_path = :route_path, is_system = 1, updated_at = NOW()
        WHERE page_key = :page_key
    ');

    foreach ($pages as $page) {
        $lookupStatement->execute(['page_key' => $page['page_key']]);
        $existingId = $lookupStatement->fetchColumn();

        if ($existingId) {
            $updateStatement->execute($page);
            $updated++;
        } else {
            $insertStatement->execute($page);
            $inserted++;
        }
    }

    return [
        'inserted' => $inserted,
        'updated' => $updated,
        'total' => count($pages),
    ];
}

function cms_list_pages(PDO $pdo, array $filters = [])
{
    $conditions = [];
    $parameters = [];
    $query = trim((string) ($filters['q'] ?? ''));

    if ($query !== '') {
        $conditions[] = '(title LIKE :query OR page_key LIKE :query OR file_path LIKE :query OR route_path LIKE :query)';
        $parameters['query'] = '%' . $query . '%';
    }

    $sql = 'SELECT * FROM cms_pages';

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY title ASC';

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}

function cms_get_content_item(PDO $pdo, $itemId)
{
    $statement = $pdo->prepare('
        SELECT cms_content_items.*, cms_pages.title AS page_title, cms_pages.page_key
        FROM cms_content_items
        INNER JOIN cms_pages ON cms_pages.id = cms_content_items.page_id
        WHERE cms_content_items.id = :id
        LIMIT 1
    ');
    $statement->execute(['id' => (int) $itemId]);
    $item = $statement->fetch();

    return $item ?: null;
}

function cms_list_content_items(PDO $pdo, array $filters = [])
{
    $conditions = [];
    $parameters = [];

    if (!empty($filters['page_id'])) {
        $conditions[] = 'cms_content_items.page_id = :page_id';
        $parameters['page_id'] = (int) $filters['page_id'];
    }

    if (!empty($filters['area_key'])) {
        $conditions[] = 'cms_content_items.area_key = :area_key';
        $parameters['area_key'] = trim((string) $filters['area_key']);
    }

    if (isset($filters['is_published']) && $filters['is_published'] !== '') {
        $conditions[] = 'cms_content_items.is_published = :is_published';
        $parameters['is_published'] = (int) $filters['is_published'];
    }

    if (!empty($filters['q'])) {
        $query = '%' . trim((string) $filters['q']) . '%';
        $conditions[] = '(
            cms_content_items.title LIKE :query_title
            OR cms_content_items.summary LIKE :query_summary
            OR cms_pages.title LIKE :query_page_title
            OR cms_pages.page_key LIKE :query_page_key
            OR cms_pages.route_path LIKE :query_route
        )';
        $parameters['query_title'] = $query;
        $parameters['query_summary'] = $query;
        $parameters['query_page_title'] = $query;
        $parameters['query_page_key'] = $query;
        $parameters['query_route'] = $query;
    }

    $sql = '
        SELECT cms_content_items.*, cms_pages.title AS page_title, cms_pages.page_key
        FROM cms_content_items
        INNER JOIN cms_pages ON cms_pages.id = cms_content_items.page_id
    ';

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY cms_content_items.sort_order ASC, cms_content_items.published_at DESC, cms_content_items.id DESC';

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}

function cms_list_recent_content_items(PDO $pdo, $limit = 6, $publishedOnly = false)
{
    $limit = max(1, min(20, (int) $limit));
    $conditions = [];

    if ($publishedOnly) {
        $conditions[] = 'cms_content_items.is_published = 1';
        $conditions[] = 'cms_content_items.published_at <= NOW()';
        $conditions[] = 'cms_pages.is_visible = 1';
        if (site_table_exists($pdo, 'cms_page_content')) {
            $conditions[] = 'EXISTS (
                SELECT 1
                FROM cms_page_content public_page_content
                WHERE public_page_content.page_id = cms_pages.id
                  AND public_page_content.is_published = 1
            )';
        }
    }

    $sql = '
        SELECT cms_content_items.*, cms_pages.title AS page_title, cms_pages.page_key, cms_pages.route_path
        FROM cms_content_items
        INNER JOIN cms_pages ON cms_pages.id = cms_content_items.page_id
    ';

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY cms_content_items.updated_at DESC, cms_content_items.id DESC LIMIT ' . $limit;
    $statement = $pdo->query($sql);

    return $statement->fetchAll();
}

function cms_get_recent_public_updates($limit = 6)
{
    $pdo = site_db();
    if (!$pdo instanceof PDO || !site_has_tables(['cms_pages', 'cms_content_items'])) {
        return [];
    }

    return cms_list_recent_content_items($pdo, $limit, true);
}

function cms_save_content_item(PDO $pdo, array $data, array $files, $adminId, $itemId = null)
{
    $itemId = $itemId !== null ? (int) $itemId : null;
    $existingItem = $itemId ? cms_get_content_item($pdo, $itemId) : null;

    if ($itemId && !$existingItem) {
        throw new RuntimeException('Izabrana stavka sadržaja nije pronađena.');
    }

    $pageId = (int) ($data['page_id'] ?? 0);
    $areaKey = trim((string) ($data['area_key'] ?? ''));
    $contentType = trim((string) ($data['content_type'] ?? 'link'));
    $title = trim((string) ($data['title'] ?? ''));
    $summary = trim((string) ($data['summary'] ?? ''));
    $bodyHtml = trim((string) ($data['body_html'] ?? ''));
    $linkUrl = trim((string) ($data['link_url'] ?? ''));
    $linkTarget = ($data['link_target'] ?? '_blank') === '_self' ? '_self' : '_blank';
    $publishedAt = site_normalize_datetime_input($data['published_at'] ?? '') ?: date('Y-m-d H:i:s');
    $sortOrder = (int) ($data['sort_order'] ?? 0);
    $isPublished = !empty($data['is_published']) ? 1 : 0;
    $originalFilePath = $existingItem['file_path'] ?? null;
    $originalImagePath = $existingItem['image_path'] ?? null;
    $filePath = $originalFilePath;
    $imagePath = $originalImagePath;

    if ($pageId <= 0) {
        throw new InvalidArgumentException('Izbor stranice je obavezan.');
    }

    if ($areaKey === '') {
        throw new InvalidArgumentException('Ključ sekcije je obavezan.');
    }

    if ($title === '') {
        throw new InvalidArgumentException('Naslov je obavezan.');
    }

    $allowedTypes = array_keys(cms_content_type_options());
    if (!in_array($contentType, $allowedTypes, true)) {
        $contentType = 'link';
    }

    if (($contentType === 'image' || $areaKey === 'page-gallery') && !cms_content_image_type_is_ready($pdo)) {
        throw new RuntimeException('Baza još ne podržava fotografije. Uvezite migraciju database/migrations/20261106_full_cms.sql i pokušajte ponovo.');
    }

    if (!empty($data['remove_file'])) {
        $filePath = null;
    }

    if (!empty($data['remove_image'])) {
        $imagePath = null;

        if ($linkUrl !== '' && $linkUrl === (string) $originalImagePath) {
            $linkUrl = '';
        }
    }

    if ($areaKey === 'page-gallery') {
        $filePath = null;
    }

    if ($areaKey === 'page-downloads') {
        $imagePath = null;
    }

    $newManagedFiles = [];

    try {
        if ($areaKey !== 'page-gallery' && isset($files['attachment']) && site_upload_was_attempted($files['attachment'])) {
            $filePath = site_store_uploaded_file(
                $files['attachment'],
                'uploads/cms/files',
                cms_download_file_extensions(),
                site_effective_upload_limit(15728640),
                false
            );
            $newManagedFiles[] = $filePath;
        }

        if ($areaKey !== 'page-downloads' && isset($files['image']) && site_upload_was_attempted($files['image'])) {
            $imagePath = site_store_uploaded_file(
                $files['image'],
                'uploads/cms/images',
                ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                site_effective_upload_limit(10485760),
                true
            );
            $newManagedFiles[] = $imagePath;
        }

        if (($contentType === 'image' || $areaKey === 'page-gallery') && $imagePath && !$filePath) {
            $contentType = 'image';
            if ($areaKey === 'page-gallery' || $linkUrl === '' || $linkUrl === (string) $originalImagePath) {
                $linkUrl = $imagePath;
            }
            $linkTarget = '_blank';
        }

        if (($contentType === 'file' || $areaKey === 'page-downloads') && $filePath) {
            $contentType = 'file';
            $linkTarget = '_blank';
        }

        if ($contentType === 'file' && !$filePath && $linkUrl === '') {
            throw new InvalidArgumentException('Dodajte dokument ili unesite link ka postojećem dokumentu.');
        }

        if ($contentType === 'link' && $linkUrl === '' && !$filePath) {
            throw new InvalidArgumentException('Unesite adresu linka ili dodajte fajl.');
        }

        if ($contentType === 'image' && !$imagePath) {
            throw new InvalidArgumentException('Izaberite fotografiju za otpremanje. Podržani formati su JPG, PNG, GIF i WEBP.');
        }

        if ($contentType === 'html' && $summary === '' && $bodyHtml === '') {
            throw new InvalidArgumentException('Unesite kratak opis ili glavni tekst.');
        }

        $payload = [
            'page_id' => $pageId,
            'area_key' => $areaKey,
            'content_type' => $contentType,
            'title' => $title,
            'summary' => $summary !== '' ? $summary : null,
            'body_html' => $bodyHtml !== '' ? $bodyHtml : null,
            'link_url' => $linkUrl !== '' ? $linkUrl : null,
            'link_target' => $linkTarget,
            'file_path' => $filePath,
            'image_path' => $imagePath,
            'published_at' => $publishedAt,
            'sort_order' => $sortOrder,
            'is_published' => $isPublished,
            'created_by' => (int) $adminId,
        ];

        if ($itemId) {
            $payload['id'] = $itemId;
            unset($payload['created_by']);
            $statement = $pdo->prepare('
                UPDATE cms_content_items
                SET page_id = :page_id,
                    area_key = :area_key,
                    content_type = :content_type,
                    title = :title,
                    summary = :summary,
                    body_html = :body_html,
                    link_url = :link_url,
                    link_target = :link_target,
                    file_path = :file_path,
                    image_path = :image_path,
                    published_at = :published_at,
                    sort_order = :sort_order,
                    is_published = :is_published,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $statement->execute($payload);
            $savedId = $itemId;
        } else {
            $statement = $pdo->prepare('
                INSERT INTO cms_content_items (
                    page_id,
                    area_key,
                    content_type,
                    title,
                    summary,
                    body_html,
                    link_url,
                    link_target,
                    file_path,
                    image_path,
                    published_at,
                    sort_order,
                    is_published,
                    created_by
                ) VALUES (
                    :page_id,
                    :area_key,
                    :content_type,
                    :title,
                    :summary,
                    :body_html,
                    :link_url,
                    :link_target,
                    :file_path,
                    :image_path,
                    :published_at,
                    :sort_order,
                    :is_published,
                    :created_by
                )
            ');
            $statement->execute($payload);
            $savedId = (int) $pdo->lastInsertId();
        }
    } catch (Throwable $exception) {
        foreach ($newManagedFiles as $newManagedFile) {
            site_delete_managed_file($newManagedFile);
        }

        throw $exception;
    }

    if ($originalFilePath && $originalFilePath !== $filePath) {
        site_delete_managed_file($originalFilePath);
    }

    if ($originalImagePath && $originalImagePath !== $imagePath) {
        site_delete_managed_file($originalImagePath);
    }

    return $savedId;
}

function cms_delete_content_item(PDO $pdo, $itemId)
{
    $item = cms_get_content_item($pdo, $itemId);

    if (!$item) {
        return;
    }

    if (!empty($item['file_path'])) {
        site_delete_managed_file($item['file_path']);
    }

    if (!empty($item['image_path'])) {
        site_delete_managed_file($item['image_path']);
    }

    $statement = $pdo->prepare('DELETE FROM cms_content_items WHERE id = :id');
    $statement->execute(['id' => (int) $itemId]);
}

function cms_resolve_content_item_url(array $item)
{
    if (!empty($item['file_path'])) {
        return str_replace('\\', '/', $item['file_path']);
    }

    if (!empty($item['image_path']) && (($item['content_type'] ?? '') === 'image' || ($item['area_key'] ?? '') === 'page-gallery')) {
        return str_replace('\\', '/', $item['image_path']);
    }

    if (!empty($item['link_url'])) {
        return $item['link_url'];
    }

    return null;
}

function cms_get_homepage_items($areaKey, $limit = 6)
{
    $pdo = site_db();

    if (!$pdo instanceof PDO || !site_has_tables(['cms_pages', 'cms_content_items'])) {
        return [];
    }

    $statement = $pdo->prepare('
        SELECT cms_content_items.*, cms_pages.page_key
        FROM cms_content_items
        INNER JOIN cms_pages ON cms_pages.id = cms_content_items.page_id
        WHERE cms_pages.page_key = :page_key
          AND cms_content_items.area_key = :area_key
          AND cms_content_items.is_published = 1
          AND cms_content_items.published_at <= NOW()
        ORDER BY cms_content_items.sort_order ASC, cms_content_items.published_at DESC, cms_content_items.id DESC
        LIMIT ' . (int) $limit
    );
    $statement->execute([
        'page_key' => 'index',
        'area_key' => $areaKey,
    ]);

    return $statement->fetchAll();
}

function cms_list_admins(PDO $pdo)
{
    $statement = $pdo->query('SELECT * FROM admins ORDER BY username ASC');

    return $statement->fetchAll();
}

function cms_find_admin_by_username(PDO $pdo, $username)
{
    $statement = $pdo->prepare('SELECT * FROM admins WHERE username = :username LIMIT 1');
    $statement->execute(['username' => trim((string) $username)]);
    $admin = $statement->fetch();

    return $admin ?: null;
}

function cms_create_admin(PDO $pdo, array $data)
{
    $username = strtolower(trim((string) ($data['username'] ?? '')));
    $fullName = trim((string) ($data['full_name'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($username === '' || !preg_match('/^[a-z0-9._-]{3,50}$/', $username)) {
        throw new InvalidArgumentException('Korisničko ime mora imati 3-50 znakova i može sadržati slova, brojeve, tačke, crtice i donje crte.');
    }

    if ($fullName === '') {
        throw new InvalidArgumentException('Ime i prezime su obavezni.');
    }

    if (strlen($password) < 8) {
        throw new InvalidArgumentException('Lozinka mora imati najmanje 8 karaktera.');
    }

    if (cms_find_admin_by_username($pdo, $username)) {
        throw new InvalidArgumentException('To korisničko ime već postoji.');
    }

    $statement = $pdo->prepare('
        INSERT INTO admins (username, full_name, password_hash, is_active)
        VALUES (:username, :full_name, :password_hash, 1)
    ');
    $statement->execute([
        'username' => $username,
        'full_name' => $fullName,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return (int) $pdo->lastInsertId();
}

function cms_update_admin_status(PDO $pdo, $adminId, $isActive)
{
    $statement = $pdo->prepare('UPDATE admins SET is_active = :is_active, updated_at = NOW() WHERE id = :id');
    $statement->execute([
        'id' => (int) $adminId,
        'is_active' => $isActive ? 1 : 0,
    ]);
}

function cms_update_admin_password(PDO $pdo, $adminId, $password)
{
    $password = (string) $password;

    if (strlen($password) < 8) {
        throw new InvalidArgumentException('Lozinka mora imati najmanje 8 karaktera.');
    }

    $statement = $pdo->prepare('UPDATE admins SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
    $statement->execute([
        'id' => (int) $adminId,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
}

function cms_get_letopis(PDO $pdo, $itemId)
{
    $statement = $pdo->prepare('SELECT * FROM letopisi WHERE id = :id LIMIT 1');
    $statement->execute(['id' => (int) $itemId]);
    $item = $statement->fetch();

    return $item ?: null;
}

function cms_list_letopisi(PDO $pdo)
{
    $statement = $pdo->query('SELECT * FROM letopisi ORDER BY CAST(godina AS UNSIGNED) DESC, id DESC');

    return $statement->fetchAll();
}

function cms_list_letopisi_public(PDO $pdo, $year = null, $month = null)
{
    $conditions = [];
    $parameters = [];

    if ($year !== null && $year !== '') {
        $conditions[] = 'godina = :godina';
        $parameters['godina'] = (string) $year;
    }

    if ($month !== null && $month !== '') {
        $conditions[] = 'mesec = :mesec';
        $parameters['mesec'] = (string) $month;
    }

    $sql = 'SELECT * FROM letopisi';

    if ($conditions) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY id DESC';

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}

function cms_save_letopis(PDO $pdo, array $data, array $files, $itemId = null)
{
    $itemId = $itemId !== null ? (int) $itemId : null;
    $existingItem = $itemId ? cms_get_letopis($pdo, $itemId) : null;

    if ($itemId && !$existingItem) {
        throw new RuntimeException('Izabrani letopis nije pronađen.');
    }

    $dan = trim((string) ($data['dan'] ?? ''));
    $mesec = trim((string) ($data['mesec'] ?? ''));
    $godina = trim((string) ($data['godina'] ?? ''));
    $glavniTekst = trim((string) ($data['glavni_tekst'] ?? ''));
    $originalSlika = $existingItem['slika'] ?? '';
    $originalDokument = $existingItem['dokument'] ?? '';
    $slika = $originalSlika;
    $dokument = $originalDokument;

    if ($godina === '') {
        throw new InvalidArgumentException('Godina je obavezna.');
    }

    if (!empty($data['remove_image'])) {
        $slika = '';
    }

    if (!empty($data['remove_document'])) {
        $dokument = '';
    }

    $newManagedFiles = [];

    try {
        if (isset($files['image']) && site_upload_was_attempted($files['image'])) {
            $slika = site_store_uploaded_file(
                $files['image'],
                'uploads/cms/letopisi',
                ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                site_effective_upload_limit(10485760),
                true
            );
            $newManagedFiles[] = $slika;
        }

        if (isset($files['document']) && site_upload_was_attempted($files['document'])) {
            $dokument = site_store_uploaded_file(
                $files['document'],
                'uploads/cms/letopisi',
                ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'txt'],
                site_effective_upload_limit(20971520),
                false
            );
            $newManagedFiles[] = $dokument;
        }

        if ($glavniTekst === '' && $dokument === '') {
            throw new InvalidArgumentException('Unesite tekst letopisa ili dodajte dokument.');
        }

        $payload = [
            'dan' => $dan,
            'mesec' => $mesec,
            'godina' => $godina,
            'slika' => $slika,
            'dokument' => $dokument,
            'glavni_tekst' => $glavniTekst,
        ];

        if ($itemId) {
            $payload['id'] = $itemId;
            $statement = $pdo->prepare('
                UPDATE letopisi
                SET dan = :dan,
                    mesec = :mesec,
                    godina = :godina,
                    slika = :slika,
                    dokument = :dokument,
                    glavni_tekst = :glavni_tekst,
                    updated_at = NOW()
                WHERE id = :id
            ');
            $statement->execute($payload);
            $savedId = $itemId;
        } else {
            $statement = $pdo->prepare('
                INSERT INTO letopisi (dan, mesec, godina, slika, dokument, glavni_tekst)
                VALUES (:dan, :mesec, :godina, :slika, :dokument, :glavni_tekst)
            ');
            $statement->execute($payload);
            $savedId = (int) $pdo->lastInsertId();
        }

    } catch (Throwable $exception) {
        foreach ($newManagedFiles as $newManagedFile) {
            site_delete_managed_file($newManagedFile);
        }

        throw $exception;
    }

    if ($originalSlika && $originalSlika !== $slika) {
        site_delete_managed_file($originalSlika);
    }

    if ($originalDokument && $originalDokument !== $dokument) {
        site_delete_managed_file($originalDokument);
    }

    cms_sync_letopisi_navigation($pdo);

    return $savedId;
}

function cms_delete_letopis(PDO $pdo, $itemId)
{
    $item = cms_get_letopis($pdo, $itemId);

    if (!$item) {
        return;
    }

    if (!empty($item['slika'])) {
        site_delete_managed_file($item['slika']);
    }

    $documentPath = (string) ($item['dokument'] ?? '');

    if ($documentPath !== '') {
        site_delete_managed_file($documentPath);
    }

    $statement = $pdo->prepare('DELETE FROM letopisi WHERE id = :id');
    $statement->execute(['id' => (int) $itemId]);

    cms_sync_letopisi_navigation($pdo);
}

function cms_get_page_by_id(PDO $pdo, $pageId)
{
    $statement = $pdo->prepare('SELECT * FROM cms_pages WHERE id = :id LIMIT 1');
    $statement->execute(['id' => (int) $pageId]);
    $page = $statement->fetch();

    return $page ?: null;
}

function cms_get_page_by_key(PDO $pdo, $pageKey)
{
    $statement = $pdo->prepare('SELECT * FROM cms_pages WHERE page_key = :page_key LIMIT 1');
    $statement->execute(['page_key' => trim((string) $pageKey)]);
    $page = $statement->fetch();

    return $page ?: null;
}

function cms_get_page_by_route(PDO $pdo, $routePath)
{
    $routePath = trim((string) $routePath, '/');
    if ($routePath === '') {
        $routePath = 'index';
    }

    $statement = $pdo->prepare('SELECT * FROM cms_pages WHERE route_path = :route_path LIMIT 1');
    $statement->execute(['route_path' => $routePath]);
    $page = $statement->fetch();

    return $page ?: null;
}

function cms_save_page(PDO $pdo, array $data, $pageId = null)
{
    $pageId = $pageId !== null ? (int) $pageId : null;
    $existing = $pageId ? cms_get_page_by_id($pdo, $pageId) : null;

    if ($pageId && !$existing) {
        throw new RuntimeException('Page record was not found.');
    }

    if ($existing && !empty($existing['is_system'])) {
        throw new RuntimeException('System pages cannot be rewritten from this screen.');
    }

    $title = trim((string) ($data['title'] ?? ''));
    $pageKey = site_slugify($data['page_key'] ?? '');
    $routePath = trim((string) ($data['route_path'] ?? ''));
    $routePath = str_replace('\\', '/', $routePath);
    $routePath = trim($routePath, '/');

    if ($routePath === '') {
        $routePath = site_slugify($title);
    } else {
        $segments = array_filter(array_map('trim', explode('/', $routePath)), 'strlen');
        $segments = array_map('site_slugify', $segments);
        $routePath = implode('/', array_filter($segments, 'strlen'));
    }

    if ($pageKey === '') {
        $pageKey = str_replace('/', '-', $routePath);
    }

    if ($title === '') {
        throw new InvalidArgumentException('Page title is required.');
    }

    if ($routePath === '') {
        throw new InvalidArgumentException('Route path is required.');
    }

    if ($pageKey === '') {
        throw new InvalidArgumentException('Page key is required.');
    }

    $firstSegment = explode('/', $routePath)[0] ?? $routePath;
    if ($firstSegment === 'skola-admin' || $routePath === 'page') {
        throw new InvalidArgumentException('This route is reserved by the application.');
    }

    $matchingPhysicalFile = site_root_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $routePath . '.php');
    $matchingDirectory = site_root_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $routePath);
    if (is_file($matchingPhysicalFile) || is_dir($matchingDirectory)) {
        throw new InvalidArgumentException('This route is already used by a file or directory.');
    }

    $currentId = $pageId ?: 0;

    $lookupKey = $pdo->prepare('SELECT id FROM cms_pages WHERE page_key = :page_key AND (:current_id_zero = 0 OR id <> :current_id_match) LIMIT 1');
    $lookupKey->execute([
        'page_key' => $pageKey,
        'current_id_zero' => $currentId,
        'current_id_match' => $currentId,
    ]);
    if ($lookupKey->fetchColumn()) {
        throw new InvalidArgumentException('Another page already uses this key.');
    }

    $lookupRoute = $pdo->prepare('SELECT id FROM cms_pages WHERE route_path = :route_path AND (:current_id_zero = 0 OR id <> :current_id_match) LIMIT 1');
    $lookupRoute->execute([
        'route_path' => $routePath,
        'current_id_zero' => $currentId,
        'current_id_match' => $currentId,
    ]);
    if ($lookupRoute->fetchColumn()) {
        throw new InvalidArgumentException('Another page already uses this route.');
    }

    $payload = [
        'title' => $title,
        'page_key' => $pageKey,
        'route_path' => $routePath,
        'file_path' => '[db-page]',
        'is_visible' => !empty($data['is_visible']) ? 1 : 0,
        'is_system' => 0,
    ];

    if ($existing) {
        $payload['id'] = $pageId;
        $statement = $pdo->prepare('
            UPDATE cms_pages
            SET title = :title,
                page_key = :page_key,
                route_path = :route_path,
                file_path = :file_path,
                is_visible = :is_visible,
                is_system = :is_system,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute($payload);

        return $pageId;
    }

    $statement = $pdo->prepare('
        INSERT INTO cms_pages (page_key, title, file_path, route_path, is_visible, is_system)
        VALUES (:page_key, :title, :file_path, :route_path, :is_visible, :is_system)
    ');
    $statement->execute($payload);

    return (int) $pdo->lastInsertId();
}

function cms_delete_page(PDO $pdo, $pageId)
{
    $pageId = (int) $pageId;
    $page = cms_get_page_by_id($pdo, $pageId);

    if (!$page) {
        throw new RuntimeException('Stranica nije pronađena.');
    }

    if (!empty($page['is_system']) || (string) ($page['file_path'] ?? '') !== '[db-page]') {
        throw new RuntimeException('Sistemske PHP stranice ne mogu se obrisati iz administracije.');
    }

    $managedFiles = [];
    if (site_table_exists($pdo, 'cms_content_items')) {
        $fileStatement = $pdo->prepare('SELECT file_path, image_path FROM cms_content_items WHERE page_id = :page_id');
        $fileStatement->execute(['page_id' => $pageId]);

        foreach ($fileStatement->fetchAll() as $contentItem) {
            foreach (['file_path', 'image_path'] as $pathColumn) {
                $relativePath = trim((string) ($contentItem[$pathColumn] ?? ''));
                $normalizedPath = trim(str_replace('\\', '/', $relativePath), '/');
                if ($normalizedPath !== '' && strpos($normalizedPath, 'uploads/cms/') === 0) {
                    $managedFiles[$normalizedPath] = true;
                }
            }
        }
    }

    $removedMenuItems = 0;
    $preservedMenuGroups = 0;

    $pdo->beginTransaction();
    try {
        if (site_table_exists($pdo, 'cms_navigation_items')) {
            $navigationStatement = $pdo->prepare('
                SELECT nav.id, COUNT(child.id) AS child_count
                FROM cms_navigation_items nav
                LEFT JOIN cms_navigation_items child ON child.parent_id = nav.id
                WHERE nav.page_id = :page_id
                GROUP BY nav.id
            ');
            $navigationStatement->execute(['page_id' => $pageId]);

            $deleteNavigation = $pdo->prepare('DELETE FROM cms_navigation_items WHERE id = :id');
            $preserveNavigation = $pdo->prepare('UPDATE cms_navigation_items SET page_id = NULL, updated_at = NOW() WHERE id = :id');

            foreach ($navigationStatement->fetchAll() as $navigationItem) {
                if ((int) $navigationItem['child_count'] > 0) {
                    $preserveNavigation->execute(['id' => (int) $navigationItem['id']]);
                    $preservedMenuGroups++;
                } else {
                    $deleteNavigation->execute(['id' => (int) $navigationItem['id']]);
                    $removedMenuItems++;
                }
            }
        }

        if (site_table_exists($pdo, 'cms_content_items')) {
            $deleteContentItems = $pdo->prepare('DELETE FROM cms_content_items WHERE page_id = :page_id');
            $deleteContentItems->execute(['page_id' => $pageId]);
        }

        if (site_table_exists($pdo, 'cms_page_content')) {
            $deletePageContent = $pdo->prepare('DELETE FROM cms_page_content WHERE page_id = :page_id');
            $deletePageContent->execute(['page_id' => $pageId]);
        }

        $deletePage = $pdo->prepare('DELETE FROM cms_pages WHERE id = :id');
        $deletePage->execute(['id' => $pageId]);

        if ($deletePage->rowCount() !== 1) {
            throw new RuntimeException('Stranica nije obrisana. Pokušajte ponovo.');
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    foreach (array_keys($managedFiles) as $relativePath) {
        site_delete_managed_file($relativePath);
    }

    return [
        'title' => (string) $page['title'],
        'removed_menu_items' => $removedMenuItems,
        'preserved_menu_groups' => $preservedMenuGroups,
        'removed_files' => count($managedFiles),
    ];
}

function cms_list_public_content_items(PDO $pdo, $pageId, $areaKey = null)
{
    $sql = '
        SELECT cms_content_items.*, cms_pages.page_key, cms_pages.route_path
        FROM cms_content_items
        INNER JOIN cms_pages ON cms_pages.id = cms_content_items.page_id
        WHERE cms_content_items.page_id = :page_id
          AND cms_content_items.is_published = 1
          AND cms_content_items.published_at <= NOW()
    ';
    $parameters = ['page_id' => (int) $pageId];

    if ($areaKey !== null && $areaKey !== '') {
        $sql .= ' AND cms_content_items.area_key = :area_key';
        $parameters['area_key'] = trim((string) $areaKey);
    }

    $sql .= ' ORDER BY cms_content_items.sort_order ASC, cms_content_items.published_at DESC, cms_content_items.id DESC';
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    return $statement->fetchAll();
}

function cms_import_page_downloads(PDO $pdo, $pageId, $adminId, $replacePageContent = true)
{
    $page = cms_get_page_by_id($pdo, $pageId);
    if (!$page) {
        throw new RuntimeException('Izabrana stranica nije pronadjena.');
    }

    $record = cms_get_page_content_by_page_id($pdo, $pageId);
    if (!$record) {
        throw new RuntimeException('DB sadrzaj stranice jos nije dostupan za uvoz fajlova.');
    }

    $downloads = cms_extract_download_items_from_html($record['content_html'] ?? '');
    if (!$downloads) {
        return [
            'imported' => 0,
            'skipped' => 0,
            'message' => 'Na ovoj stranici nisu pronadjeni linkovi ka dokumentima za uvoz.',
        ];
    }

    $existingStatement = $pdo->prepare('
        SELECT title, COALESCE(file_path, link_url, "") AS source_path
        FROM cms_content_items
        WHERE page_id = :page_id
          AND area_key = :area_key
    ');
    $existingStatement->execute([
        'page_id' => (int) $pageId,
        'area_key' => 'page-downloads',
    ]);

    $existingMap = [];
    $normalizeSignaturePart = static function ($value) {
        $value = trim((string) $value);

        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    };
    foreach ($existingStatement->fetchAll() as $existing) {
        $existingMap[sha1($normalizeSignaturePart($existing['title']) . '|' . strtolower(trim((string) $existing['source_path'])))] = true;
    }

    $orderStatement = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM cms_content_items WHERE page_id = :page_id AND area_key = :area_key');
    $orderStatement->execute([
        'page_id' => (int) $pageId,
        'area_key' => 'page-downloads',
    ]);
    $sortOrder = (int) $orderStatement->fetchColumn();

    $imported = 0;
    $skipped = 0;

    foreach ($downloads as $download) {
        $signature = sha1($normalizeSignaturePart($download['title']) . '|' . strtolower(trim((string) $download['href'])));
        if (isset($existingMap[$signature])) {
            $skipped++;
            continue;
        }

        $sortOrder += 10;
        cms_save_content_item(
            $pdo,
            [
                'page_id' => (int) $pageId,
                'area_key' => 'page-downloads',
                'content_type' => 'file',
                'title' => $download['title'],
                'summary' => '',
                'body_html' => '',
                'link_url' => $download['href'],
                'link_target' => '_blank',
                'published_at' => date('Y-m-d H:i:s'),
                'sort_order' => $sortOrder,
                'is_published' => 1,
            ],
            [],
            $adminId
        );

        $existingMap[$signature] = true;
        $imported++;
    }

    if ($replacePageContent) {
        cms_save_page_content(
            $pdo,
            [
                'page_id' => (int) $pageId,
                'browser_title' => (string) ($record['browser_title'] ?? $page['title']),
                'meta_description' => (string) ($record['meta_description'] ?? ''),
                'content_html' => cms_build_download_library_content_html((string) $page['title']),
                'extra_head_html' => (string) ($record['extra_head_html'] ?? ''),
                'extra_footer_html' => (string) ($record['extra_footer_html'] ?? ''),
                'is_published' => !empty($record['is_published']) ? 1 : 0,
                'source_hash' => (string) ($record['source_hash'] ?? ''),
            ],
            $adminId,
            (int) $record['id']
        );
    }

    return [
        'imported' => $imported,
        'skipped' => $skipped,
        'message' => sprintf('Uvezeno: %d, preskoceno: %d.', $imported, $skipped),
    ];
}

function cms_navigation_seed_blueprint()
{
    return [
        ['label' => 'Početna', 'page_key' => 'index'],
        ['label' => 'O nama', 'page_key' => 'istorijat'],
        ['label' => 'Raspored časova', 'page_key' => 'raspored_casova'],
        [
            'label' => 'Škola',
            'children' => [
                [
                    'label' => 'Organi škole',
                    'children' => [
                        ['label' => 'Direktor škole', 'page_key' => 'direktor-skole'],
                        ['label' => 'Školski odbor', 'page_key' => 'skolski-odbor'],
                        ['label' => 'Savet roditelja', 'page_key' => 'saveti-roditelja'],
                        ['label' => 'Stručni organi i timovi', 'page_key' => 'strucni-timovi'],
                    ],
                ],
                [
                    'label' => 'Zaposleni',
                    'children' => [
                        ['label' => 'Stručna služba', 'page_key' => 'strucna-sluzba'],
                        ['label' => 'Nastavno osoblje', 'page_key' => 'nastavno-osoblje'],
                        ['label' => 'Nenastavno osoblje', 'page_key' => 'nenastavno-osoblje'],
                    ],
                ],
                ['label' => 'Aktuelno', 'page_key' => 'aktuelno'],
            ],
        ],
        [
            'label' => 'Učenici',
            'children' => [
                [
                    'label' => 'Raspored pismenih provera',
                    'children' => [
                        ['label' => '2023-2024', 'page_key' => 'raspored-pismenih-provera-2023-2024'],
                        ['label' => '2022-2023', 'page_key' => 'raspored-pismenih-provera-2022-2023'],
                        ['label' => '2022', 'page_key' => 'raspored-pismenih-provera-2022'],
                        ['label' => '2021-2022', 'page_key' => 'raspored-pismenih-provera-2021-2022'],
                        ['label' => '2021', 'page_key' => 'raspored-pismenih-provera-2021'],
                    ],
                ],
                ['label' => 'Prijem roditelja', 'page_key' => 'prijem-roditelja'],
                ['label' => 'Za osmake', 'page_key' => 'za-osmake'],
                [
                    'label' => 'Za prvake',
                    'children' => [
                        ['label' => 'Priprema za školu', 'page_key' => 'priprema-za-skolu'],
                        ['label' => 'Upoznavanje deteta sa školom', 'page_key' => 'upoznavanje-deteta-sa-skolom'],
                    ],
                ],
                ['label' => 'Takmičenja', 'page_key' => 'takmicenja'],
            ],
        ],
        [
            'label' => 'Dokumenta',
            'children' => [
                ['label' => 'Javne nabavke', 'page_key' => 'javne-nabavke'],
                ['label' => 'Finansijski izveštaj', 'page_key' => 'finansijski-izvestaj'],
                ['label' => 'Statut', 'page_key' => 'statut'],
                ['label' => 'Pravilnik o radu škole', 'page_key' => 'pravilnik-o-radu'],
                ['label' => 'Dokumenta škole', 'page_key' => 'godisnji-plan-rada'],
            ],
        ],
        ['label' => 'Galerija', 'page_key' => 'galerija'],
        [
            'label' => 'Važni linkovi',
            'children' => [
                ['label' => 'Ministarstvo prosvete', 'custom_url' => 'https://prosveta.gov.rs/', 'open_in_new_tab' => 1],
                ['label' => 'Zavod za vrednovanje kvaliteta obrazovanja', 'custom_url' => 'https://ceo.edu.rs/', 'open_in_new_tab' => 1],
                ['label' => 'Zavod za unapređivanje obrazovanja', 'custom_url' => 'https://zuov.gov.rs/', 'open_in_new_tab' => 1],
                ['label' => 'Portal eUprava', 'custom_url' => 'https://euprava.gov.rs/', 'open_in_new_tab' => 1],
                ['label' => 'Grad Valjevo', 'custom_url' => 'https://www.valjevo.rs/', 'open_in_new_tab' => 1],
            ],
        ],
        ['label' => 'Kontakt', 'page_key' => 'kontakt'],
    ];
}

function cms_seed_navigation_from_blueprint(PDO $pdo, array $blueprint = null, $parentId = null)
{
    $blueprint = $blueprint ?: cms_navigation_seed_blueprint();

    foreach ($blueprint as $index => $item) {
        $pageId = null;
        if (!empty($item['page_key'])) {
            $page = cms_get_page_by_key($pdo, $item['page_key']);
            $pageId = $page['id'] ?? null;
        }

        $statement = $pdo->prepare('
            INSERT INTO cms_navigation_items (
                parent_id,
                page_id,
                label,
                custom_url,
                open_in_new_tab,
                sort_order,
                is_published
            ) VALUES (
                :parent_id,
                :page_id,
                :label,
                :custom_url,
                :open_in_new_tab,
                :sort_order,
                1
            )
        ');
        $statement->execute([
            'parent_id' => $parentId,
            'page_id' => $pageId,
            'label' => trim((string) ($item['label'] ?? '')),
            'custom_url' => !empty($item['custom_url']) ? trim((string) $item['custom_url']) : null,
            'open_in_new_tab' => !empty($item['open_in_new_tab']) ? 1 : 0,
            'sort_order' => (($index + 1) * 10),
        ]);

        $navigationId = (int) $pdo->lastInsertId();
        if (!empty($item['children']) && is_array($item['children'])) {
            cms_seed_navigation_from_blueprint($pdo, $item['children'], $navigationId);
        }
    }
}

function cms_navigation_bootstrap(PDO $pdo)
{
    if (!site_table_exists($pdo, 'cms_navigation_items')) {
        return false;
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM cms_navigation_items')->fetchColumn();
    if ($count === 0) {
        cms_sync_pages($pdo);
        cms_seed_navigation_from_blueprint($pdo);
    }

    return true;
}

function cms_list_navigation_items(PDO $pdo)
{
    cms_navigation_bootstrap($pdo);

    $statement = $pdo->query('
        SELECT nav.*, parent.label AS parent_label, cms_pages.title AS page_title, cms_pages.route_path, cms_pages.is_system AS page_is_system
        FROM cms_navigation_items nav
        LEFT JOIN cms_navigation_items parent ON parent.id = nav.parent_id
        LEFT JOIN cms_pages ON cms_pages.id = nav.page_id
        ORDER BY COALESCE(nav.parent_id, 0) ASC, nav.sort_order ASC, nav.id ASC
    ');

    return $statement->fetchAll();
}

function cms_get_navigation_item(PDO $pdo, $itemId)
{
    $statement = $pdo->prepare('
        SELECT nav.*, cms_pages.route_path
        FROM cms_navigation_items nav
        LEFT JOIN cms_pages ON cms_pages.id = nav.page_id
        WHERE nav.id = :id
        LIMIT 1
    ');
    $statement->execute(['id' => (int) $itemId]);
    $item = $statement->fetch();

    return $item ?: null;
}

function cms_save_navigation_item(PDO $pdo, array $data, $itemId = null)
{
    $itemId = $itemId !== null ? (int) $itemId : null;
    $existing = $itemId ? cms_get_navigation_item($pdo, $itemId) : null;

    if ($itemId && !$existing) {
        throw new RuntimeException('Navigation item was not found.');
    }

    $label = trim((string) ($data['label'] ?? ''));
    $pageId = (int) ($data['page_id'] ?? 0);
    $parentId = (int) ($data['parent_id'] ?? 0);
    $customUrl = trim((string) ($data['custom_url'] ?? ''));
    $sortOrder = (int) ($data['sort_order'] ?? 0);
    $isPublished = !empty($data['is_published']) ? 1 : 0;
    $openInNewTab = !empty($data['open_in_new_tab']) ? 1 : 0;

    if ($label === '') {
        throw new InvalidArgumentException('Menu label is required.');
    }

    if ($parentId > 0 && $itemId !== null && $parentId === $itemId) {
        throw new InvalidArgumentException('An item cannot be its own parent.');
    }

    if ($pageId <= 0) {
        $pageId = null;
    }

    if ($parentId <= 0) {
        $parentId = null;
    }

    if ($customUrl === '') {
        $customUrl = null;
    }

    $payload = [
        'parent_id' => $parentId,
        'page_id' => $pageId,
        'label' => $label,
        'custom_url' => $customUrl,
        'open_in_new_tab' => $openInNewTab,
        'sort_order' => $sortOrder,
        'is_published' => $isPublished,
    ];

    if ($existing) {
        $payload['id'] = $itemId;
        $statement = $pdo->prepare('
            UPDATE cms_navigation_items
            SET parent_id = :parent_id,
                page_id = :page_id,
                label = :label,
                custom_url = :custom_url,
                open_in_new_tab = :open_in_new_tab,
                sort_order = :sort_order,
                is_published = :is_published,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute($payload);

        return $itemId;
    }

    $statement = $pdo->prepare('
        INSERT INTO cms_navigation_items (
            parent_id,
            page_id,
            label,
            custom_url,
            open_in_new_tab,
            sort_order,
            is_published
        ) VALUES (
            :parent_id,
            :page_id,
            :label,
            :custom_url,
            :open_in_new_tab,
            :sort_order,
            :is_published
        )
    ');
    $statement->execute($payload);

    return (int) $pdo->lastInsertId();
}

function cms_delete_navigation_item(PDO $pdo, $itemId)
{
    $statement = $pdo->prepare('DELETE FROM cms_navigation_items WHERE id = :id');
    $statement->execute(['id' => (int) $itemId]);
}

function cms_navigation_tree(PDO $pdo, $publishedOnly = true)
{
    if (!cms_navigation_bootstrap($pdo)) {
        return [];
    }

    $sql = '
        SELECT nav.*, cms_pages.route_path
        FROM cms_navigation_items nav
        LEFT JOIN cms_pages ON cms_pages.id = nav.page_id
    ';
    if ($publishedOnly) {
        $sql .= ' WHERE nav.is_published = 1';
    }
    $sql .= ' ORDER BY COALESCE(nav.parent_id, 0) ASC, nav.sort_order ASC, nav.id ASC';

    $statement = $pdo->query($sql);
    $items = $statement->fetchAll();

    $byParent = [];
    foreach ($items as $item) {
        $parentKey = $item['parent_id'] ? (int) $item['parent_id'] : 0;
        if (!isset($byParent[$parentKey])) {
            $byParent[$parentKey] = [];
        }
        $byParent[$parentKey][] = $item;
    }

    $build = static function ($parentId) use (&$build, $byParent) {
        $branch = [];
        foreach ($byParent[(int) $parentId] ?? [] as $item) {
            $item['children'] = $build((int) $item['id']);
            $branch[] = $item;
        }

        return $branch;
    };

    return $build(0);
}

function cms_navigation_item_url(array $item)
{
    if (!empty($item['route_path'])) {
        return site_route_to_url($item['route_path']);
    }

    if (!empty($item['custom_url'])) {
        return $item['custom_url'];
    }

    return '#';
}

function cms_navigation_item_is_active(array $item, $currentRoute)
{
    $currentRoute = trim((string) $currentRoute, '/');
    if ($currentRoute === '') {
        $currentRoute = 'index';
    }

    $itemRoute = trim((string) ($item['route_path'] ?? ''), '/');
    if ($itemRoute === '') {
        return false;
    }

    return $itemRoute === $currentRoute;
}

function cms_build_letopis_display_label(array $item)
{
    $parts = [];
    $dan = trim((string) ($item['dan'] ?? ''));
    $mesec = trim((string) ($item['mesec'] ?? ''));
    $godina = trim((string) ($item['godina'] ?? ''));

    if ($mesec === '' && $dan === '' && $godina !== '') {
        return $godina;
    }

    if ($dan !== '') {
        $parts[] = $dan;
    }

    if ($mesec !== '') {
        $parts[] = $mesec;
    }

    if ($godina !== '') {
        $parts[] = $godina;
    }

    return trim(implode(' ', $parts));
}

function cms_find_letopisi_navigation_parent(PDO $pdo)
{
    if (!site_table_exists($pdo, 'cms_navigation_items')) {
        return null;
    }

    cms_navigation_bootstrap($pdo);

    $page = cms_get_page_by_key($pdo, 'letopisi');
    if ($page) {
        $statement = $pdo->prepare('SELECT * FROM cms_navigation_items WHERE parent_id IS NULL AND page_id = :page_id LIMIT 1');
        $statement->execute(['page_id' => (int) $page['id']]);
        $item = $statement->fetch();

        if ($item) {
            return $item;
        }
    }

    $statement = $pdo->prepare('SELECT * FROM cms_navigation_items WHERE parent_id IS NULL AND label = :label LIMIT 1');
    $statement->execute(['label' => 'Letopis']);
    $item = $statement->fetch();

    return $item ?: null;
}

function cms_sync_letopisi_navigation(PDO $pdo)
{
    if (!site_table_exists($pdo, 'cms_navigation_items') || !site_table_exists($pdo, 'letopisi')) {
        return ['updated' => 0, 'inserted' => 0];
    }

    $parent = cms_find_letopisi_navigation_parent($pdo);
    if (!$parent) {
        return ['updated' => 0, 'inserted' => 0];
    }

    $entries = cms_list_letopisi($pdo);
    $usedUrls = [];
    $updated = 0;
    $inserted = 0;
    $sortOrder = 10;

    $findChild = $pdo->prepare('SELECT * FROM cms_navigation_items WHERE parent_id = :parent_id AND custom_url = :custom_url LIMIT 1');
    $updateChild = $pdo->prepare('
        UPDATE cms_navigation_items
        SET label = :label,
            open_in_new_tab = 1,
            sort_order = :sort_order,
            is_published = 1,
            updated_at = NOW()
        WHERE id = :id
    ');
    $insertChild = $pdo->prepare('
        INSERT INTO cms_navigation_items (
            parent_id,
            page_id,
            label,
            custom_url,
            open_in_new_tab,
            sort_order,
            is_published
        ) VALUES (
            :parent_id,
            NULL,
            :label,
            :custom_url,
            1,
            :sort_order,
            1
        )
    ');

    foreach ($entries as $entry) {
        $documentPath = trim((string) ($entry['dokument'] ?? ''));
        if ($documentPath === '') {
            continue;
        }

        $usedUrls[] = $documentPath;
        $label = trim((string) ($entry['godina'] ?? ''));
        if ($label === '') {
            $label = cms_build_letopis_display_label($entry);
        }
        if ($label === '') {
            $label = basename($documentPath);
        }

        $findChild->execute([
            'parent_id' => (int) $parent['id'],
            'custom_url' => $documentPath,
        ]);
        $existingChild = $findChild->fetch();

        if ($existingChild) {
            $updateChild->execute([
                'id' => (int) $existingChild['id'],
                'label' => $label,
                'sort_order' => $sortOrder,
            ]);
            $updated++;
        } else {
            $insertChild->execute([
                'parent_id' => (int) $parent['id'],
                'label' => $label,
                'custom_url' => $documentPath,
                'sort_order' => $sortOrder,
            ]);
            $inserted++;
        }

        $sortOrder += 10;
    }

    if ($usedUrls) {
        $children = $pdo->prepare('SELECT id, custom_url FROM cms_navigation_items WHERE parent_id = :parent_id AND page_id IS NULL');
        $children->execute(['parent_id' => (int) $parent['id']]);
        foreach ($children->fetchAll() as $child) {
            $customUrl = trim((string) ($child['custom_url'] ?? ''));
            if ($customUrl === '') {
                continue;
            }

            if (!in_array($customUrl, $usedUrls, true) && strpos($customUrl, 'files/letopisi/') !== false) {
                $pdo->prepare('DELETE FROM cms_navigation_items WHERE id = :id')->execute(['id' => (int) $child['id']]);
            }
        }
    }

    return ['updated' => $updated, 'inserted' => $inserted];
}

function cms_import_letopisi_from_navigation(PDO $pdo)
{
    if (!site_table_exists($pdo, 'cms_navigation_items') || !site_table_exists($pdo, 'letopisi')) {
        return ['imported' => 0, 'existing' => 0];
    }

    $parent = cms_find_letopisi_navigation_parent($pdo);
    if (!$parent) {
        return ['imported' => 0, 'existing' => 0];
    }

    $statement = $pdo->prepare('
        SELECT *
        FROM cms_navigation_items
        WHERE parent_id = :parent_id
          AND custom_url IS NOT NULL
          AND custom_url <> ""
        ORDER BY sort_order ASC, id ASC
    ');
    $statement->execute(['parent_id' => (int) $parent['id']]);
    $items = $statement->fetchAll();

    $lookup = $pdo->prepare('SELECT id FROM letopisi WHERE dokument = :dokument LIMIT 1');
    $insert = $pdo->prepare('
        INSERT INTO letopisi (dan, mesec, godina, slika, dokument, glavni_tekst)
        VALUES (:dan, :mesec, :godina, :slika, :dokument, :glavni_tekst)
    ');

    $imported = 0;
    $existing = 0;

    foreach ($items as $item) {
        $documentPath = trim((string) ($item['custom_url'] ?? ''));
        if ($documentPath === '') {
            continue;
        }

        $lookup->execute(['dokument' => $documentPath]);
        if ($lookup->fetchColumn()) {
            $existing++;
            continue;
        }

        $label = trim((string) ($item['label'] ?? ''));
        $insert->execute([
            'dan' => '',
            'mesec' => '',
            'godina' => $label !== '' ? $label : basename($documentPath),
            'slika' => '',
            'dokument' => $documentPath,
            'glavni_tekst' => '',
        ]);
        $imported++;
    }

    cms_sync_letopisi_navigation($pdo);

    return ['imported' => $imported, 'existing' => $existing];
}

function cms_navigation_blueprint_tree(PDO $pdo = null, array $blueprint = null)
{
    $blueprint = $blueprint === null ? cms_navigation_seed_blueprint() : $blueprint;
    $tree = [];

    foreach ($blueprint as $item) {
        $page = null;
        if ($pdo instanceof PDO && !empty($item['page_key'])) {
            $page = cms_get_page_by_key($pdo, $item['page_key']);
        }

        $tree[] = [
            'id' => null,
            'parent_id' => null,
            'page_id' => $page['id'] ?? null,
            'label' => trim((string) ($item['label'] ?? '')),
            'custom_url' => !empty($item['custom_url']) ? trim((string) $item['custom_url']) : null,
            'route_path' => $page['route_path'] ?? (!empty($item['page_key']) ? $item['page_key'] : null),
            'open_in_new_tab' => !empty($item['open_in_new_tab']) ? 1 : 0,
            'is_published' => 1,
            'children' => cms_navigation_blueprint_tree($pdo, $item['children'] ?? []),
        ];
    }

    return $tree;
}

$pageCmsBootstrapFile = __DIR__ . '/page-cms.php';

if (is_file($pageCmsBootstrapFile)) {
    require_once $pageCmsBootstrapFile;
} else {
    // Preserve the original website during an incomplete file deployment.
    function cms_maybe_render_managed_page($pageKey, array $options = [])
    {
        return false;
    }

    function cms_render_managed_page_by_route($routePath)
    {
        return false;
    }
}
