<?php

admin_bootstrap();
$adminUser = admin_current_user();
$flashes = admin_consume_flashes();
$pageTitle = $pageTitle ?? 'Administracija';
$pageDescription = $pageDescription ?? 'Upravljajte sadržajem školskog sajta.';
$activeNav = $activeNav ?? 'dashboard';
$adminStylesheetPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'admin-panel.css';
$adminStylesheetVersion = is_file($adminStylesheetPath) ? (string) filemtime($adminStylesheetPath) : '1';
$navItems = [
    'dashboard' => ['label' => 'Početna', 'href' => 'dashboard.php', 'group' => 'Sadržaj'],
    'pages' => ['label' => 'Stranice', 'href' => 'pages.php', 'group' => 'Sadržaj'],
    'content' => ['label' => 'Objave, slike i fajlovi', 'href' => 'content.php', 'group' => 'Sadržaj'],
    'navigation' => ['label' => 'Meni sajta', 'href' => 'navigation.php', 'group' => 'Podešavanja'],
    'users' => ['label' => 'Admin korisnici', 'href' => 'users.php', 'group' => 'Podešavanja'],
];
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo site_escape($pageTitle); ?></title>
    <link rel="stylesheet" href="../css/admin-panel.css?v=<?php echo site_escape($adminStylesheetVersion); ?>">
</head>
<body class="admin-body">
    <a class="skip-link" href="#main-content">Pređi na glavni sadržaj</a>
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="admin-brand" href="dashboard.php">
                <span class="admin-brand-mark">OS</span>
                <span>
                    <strong>Zdravko CMS</strong>
                    <small>Administracija</small>
                </span>
            </a>
            <nav class="admin-nav" aria-label="Glavna administracija">
                <?php $currentGroup = null; ?>
                <?php foreach ($navItems as $key => $item): ?>
                    <?php if ($currentGroup !== $item['group']): ?>
                        <?php $currentGroup = $item['group']; ?>
                        <span class="admin-nav-label"><?php echo site_escape($currentGroup); ?></span>
                    <?php endif; ?>
                    <a
                        class="admin-nav-link <?php echo $activeNav === $key ? 'is-active' : ''; ?>"
                        href="<?php echo site_escape($item['href']); ?>"
                        <?php echo $activeNav === $key ? 'aria-current="page"' : ''; ?>
                    >
                        <?php echo site_escape($item['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <main class="admin-main" id="main-content" tabindex="-1">
            <header class="admin-topbar">
                <div>
                    <h1><?php echo site_escape($pageTitle); ?></h1>
                    <p><?php echo site_escape($pageDescription); ?></p>
                </div>
                <div class="admin-userbox">
                    <div>
                        <strong><?php echo site_escape($adminUser['full_name'] ?? 'Admin'); ?></strong>
                        <small>@<?php echo site_escape($adminUser['username'] ?? 'admin'); ?></small>
                    </div>
                    <a class="button button-ghost" href="../../" target="_blank" rel="noopener">Otvori sajt</a>
                    <a class="button button-secondary" href="logout.php">Odjava</a>
                </div>
            </header>
            <?php if ($flashes): ?>
                <section class="flash-stack" aria-live="polite" aria-atomic="true">
                    <?php foreach ($flashes as $flash): ?>
                        <div
                            class="flash flash-<?php echo site_escape($flash['type']); ?>"
                            role="<?php echo ($flash['type'] ?? '') === 'error' ? 'alert' : 'status'; ?>"
                            <?php echo ($flash['type'] ?? '') === 'error' ? 'tabindex="-1"' : ''; ?>
                        >
                            <strong><?php echo ($flash['type'] ?? '') === 'success' ? 'Završeno' : (($flash['type'] ?? '') === 'warning' ? 'Napomena' : 'Nije sačuvano'); ?></strong>
                            <?php echo site_escape($flash['message']); ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
