<?php

require_once __DIR__ . '/core/admin.php';
admin_bootstrap();
admin_require_guest();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_verify_csrf();
        admin_login($_POST['username'] ?? '', $_POST['password'] ?? '');
        admin_flash('success', 'Uspešno ste prijavljeni.');
        admin_redirect('core/dashboard.php');
    } catch (Throwable $exception) {
        $errorMessage = $exception->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OŠ Zdravko Jovanović administracija</title>
    <link rel="stylesheet" href="css/admin-panel.css">
</head>
<body class="login-body">
    <section class="login-card">
        <span class="badge">Zdravko CMS</span>
        <h1>Prijava administratora</h1>
        <p>Na jednom mestu upravljajte stranicama, vestima, dokumentima, fotografijama i menijem školskog sajta.</p>
        <?php if ($errorMessage !== ''): ?>
            <div class="flash flash-error" role="alert" style="margin-bottom: 14px;">
                <?php echo site_escape($errorMessage); ?>
            </div>
        <?php endif; ?>
        <form method="post" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
            <label>
                Korisničko ime
                <input type="text" name="username" autocomplete="username" spellcheck="false" required>
            </label>
            <label>
                Lozinka
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <div class="actions">
                <button type="submit">Prijava</button>
            </div>
        </form>
    </section>
</body>
</html>
