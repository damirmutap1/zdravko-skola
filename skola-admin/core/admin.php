<?php

require_once dirname(dirname(__DIR__)) . '/includes/app.php';

function admin_bootstrap()
{
    static $booted = false;

    if ($booted) {
        return;
    }

    $booted = true;

    if (session_status() !== PHP_SESSION_ACTIVE) {
        $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_name('skola_admin');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function admin_redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function admin_is_logged_in()
{
    return !empty($_SESSION['admin_user']['id']);
}

function admin_current_user()
{
    return $_SESSION['admin_user'] ?? null;
}

function admin_flash($type, $message)
{
    if (!isset($_SESSION['admin_flashes']) || !is_array($_SESSION['admin_flashes'])) {
        $_SESSION['admin_flashes'] = [];
    }

    $_SESSION['admin_flashes'][] = [
        'type' => (string) $type,
        'message' => (string) $message,
    ];
}

function admin_consume_flashes()
{
    $flashes = $_SESSION['admin_flashes'] ?? [];
    unset($_SESSION['admin_flashes']);

    return is_array($flashes) ? $flashes : [];
}

function admin_csrf_token()
{
    if (empty($_SESSION['admin_csrf_token'])) {
        try {
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        } catch (Throwable $exception) {
            $_SESSION['admin_csrf_token'] = sha1(uniqid((string) mt_rand(), true));
        }
    }

    return $_SESSION['admin_csrf_token'];
}

function admin_verify_csrf()
{
    $token = $_POST['csrf_token'] ?? '';

    if ($token === '' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $postLimit = site_parse_ini_bytes(ini_get('post_max_size'));

        if ($postLimit > 0 && $contentLength > $postLimit) {
            throw new RuntimeException(
                'Ukupna veličina izabranih fajlova prelazi ograničenje servera od '
                . site_format_bytes($postLimit)
                . '. Smanjite fajlove i pokušajte ponovo.'
            );
        }
    }

    if (!hash_equals(admin_csrf_token(), (string) $token)) {
        throw new RuntimeException('Sesija formulara je istekla. Pokušajte ponovo.');
    }
}

function admin_require_login()
{
    if (!admin_is_logged_in()) {
        admin_redirect('../index.php');
    }
}

function admin_require_guest()
{
    if (admin_is_logged_in()) {
        admin_redirect('core/dashboard.php');
    }
}

function admin_login($username, $password)
{
    $pdo = site_db_or_fail();

    if (!site_table_exists($pdo, 'admins')) {
        throw new RuntimeException('Tabela admins ne postoji. Prvo uvezite migraciju.');
    }

    $admin = cms_find_admin_by_username($pdo, $username);

    if (!$admin || empty($admin['is_active']) || !password_verify((string) $password, (string) $admin['password_hash'])) {
        throw new RuntimeException('Pogrešno korisničko ime ili lozinka.');
    }

    $statement = $pdo->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id');
    $statement->execute(['id' => (int) $admin['id']]);

    session_regenerate_id(true);
    $_SESSION['admin_user'] = [
        'id' => (int) $admin['id'],
        'username' => $admin['username'],
        'full_name' => $admin['full_name'],
    ];
}

function admin_logout()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function admin_active_count(PDO $pdo)
{
    $statement = $pdo->query('SELECT COUNT(*) FROM admins WHERE is_active = 1');

    return (int) $statement->fetchColumn();
}
