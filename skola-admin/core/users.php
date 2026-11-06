<?php

require_once __DIR__ . '/admin.php';
admin_bootstrap();
admin_require_login();

$pdo = site_db_or_fail();
$currentUser = admin_current_user();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_verify_csrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            cms_create_admin($pdo, $_POST);
            admin_flash('success', 'Novi administrator je kreiran.');
        }

        if ($action === 'toggle') {
            $targetId = (int) ($_POST['id'] ?? 0);
            $newState = !empty($_POST['is_active']);
            if ((int) $currentUser['id'] === $targetId && !$newState && admin_active_count($pdo) <= 1) {
                throw new RuntimeException('Ne možete deaktivirati poslednji aktivni administratorski nalog.');
            }
            cms_update_admin_status($pdo, $targetId, $newState);
            admin_flash('success', 'Status administratora je ažuriran.');
        }

        if ($action === 'password') {
            cms_update_admin_password($pdo, (int) ($_POST['id'] ?? 0), (string) ($_POST['password'] ?? ''));
            admin_flash('success', 'Lozinka administratora je ažurirana.');
        }

        admin_redirect('users.php');
    }
} catch (Throwable $exception) {
    admin_flash('error', $exception->getMessage());
}

$admins = cms_list_admins($pdo);
$pageTitle = 'Admin korisnici';
$activeNav = 'users';
require __DIR__ . '/layout-start.php';
?>
<section class="form-card">
    <h2>Dodaj novog administratora</h2>
    <form method="post" class="form-grid three">
        <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
        <input type="hidden" name="action" value="create">
        <label>
            Korisničko ime
            <input type="text" name="username" required>
        </label>
        <label>
            Ime i prezime
            <input type="text" name="full_name" required>
        </label>
        <label>
            Lozinka
            <input type="password" name="password" required>
        </label>
        <div class="actions">
            <button type="submit">Kreiraj administratora</button>
        </div>
    </form>
</section>

<section class="table-card">
    <h2>Postojeći administratori</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Korisnik</th>
                    <th>Status</th>
                    <th>Poslednja prijava</th>
                    <th>Aktivacija</th>
                    <th>Reset lozinke</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td>
                            <strong><?php echo site_escape($admin['full_name']); ?></strong>
                            <div class="muted">@<?php echo site_escape($admin['username']); ?></div>
                        </td>
                        <td><?php echo !empty($admin['is_active']) ? 'Aktivan' : 'Neaktivan'; ?></td>
                        <td><?php echo !empty($admin['last_login_at']) ? site_escape(site_format_date($admin['last_login_at'], 'd.m.Y. H:i')) : 'Nikada'; ?></td>
                        <td>
                            <form method="post" class="actions">
                                <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo (int) $admin['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo empty($admin['is_active']) ? '1' : '0'; ?>">
                                <button class="button-secondary" type="submit"><?php echo empty($admin['is_active']) ? 'Aktiviraj' : 'Deaktiviraj'; ?></button>
                            </form>
                        </td>
                        <td>
                            <form method="post" class="inline-form" style="grid-template-columns: 1fr auto; align-items: end;">
                                <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
                                <input type="hidden" name="action" value="password">
                                <input type="hidden" name="id" value="<?php echo (int) $admin['id']; ?>">
                                <label>
                                    Nova lozinka
                                    <input type="password" name="password" required>
                                </label>
                                <button type="submit">Sačuvaj</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/layout-end.php'; ?>