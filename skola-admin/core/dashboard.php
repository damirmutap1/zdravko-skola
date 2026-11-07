<?php

require_once __DIR__ . '/admin.php';
admin_bootstrap();
admin_require_login();

$pdo = site_db_or_fail();
$syncSummary = null;

if (site_table_exists($pdo, 'cms_pages')) {
    $syncSummary = cms_sync_pages($pdo);
}

if (site_table_exists($pdo, 'cms_page_content')) {
    cms_sync_page_content_from_sources($pdo, false);
}

if (site_table_exists($pdo, 'cms_navigation_items')) {
    cms_navigation_bootstrap($pdo);
}

$pageTitle = 'Pregled';
$pageDescription = 'Izaberite šta želite da dodate ili promenite.';
$activeNav = 'dashboard';
$stats = [
    'pages' => site_table_exists($pdo, 'cms_pages') ? (int) $pdo->query('SELECT COUNT(*) FROM cms_pages')->fetchColumn() : 0,
    'content' => site_table_exists($pdo, 'cms_content_items') ? (int) $pdo->query('SELECT COUNT(*) FROM cms_content_items')->fetchColumn() : 0,
    'admins' => site_table_exists($pdo, 'admins') ? (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() : 0,
];
$recentItems = site_table_exists($pdo, 'cms_content_items') ? cms_list_recent_content_items($pdo, 5) : [];
$homePage = site_table_exists($pdo, 'cms_pages') ? cms_get_page_by_key($pdo, 'index') : null;
$areaOptions = cms_area_options();

require __DIR__ . '/layout-start.php';
?>
<section aria-labelledby="quick-actions-title">
    <div style="margin-bottom: 16px;">
        <h2 id="quick-actions-title" style="margin-bottom: 4px;">Šta želite da uradite?</h2>
        <p class="muted" style="margin: 0;">Najčešći poslovi su dostupni jednim klikom.</p>
    </div>
    <div class="action-grid">
        <?php if ($homePage): ?>
            <a class="action-card" href="content-edit.php?page_id=<?php echo (int) $homePage['id']; ?>&area_key=homepage-important&content_type=html">
                <span class="badge">Početna strana</span>
                <h2>Dodaj obaveštenje</h2>
                <p>Objavite važnu vest, link ili dokument.</p>
                <span class="action-card-arrow">Započni →</span>
            </a>
            <a class="action-card" href="content-edit.php?page_id=<?php echo (int) $homePage['id']; ?>&area_key=homepage-events&content_type=html">
                <span class="badge">Početna strana</span>
                <h2>Dodaj događaj</h2>
                <p>Unesite novost, datum i kratak opis.</p>
                <span class="action-card-arrow">Započni →</span>
            </a>
            <a class="action-card" href="homepage-settings.php">
                <span class="badge">Početna strana</span>
                <h2>Podesi broj objava</h2>
                <p>Odredite koliko poslednjih obaveštenja i aktuelnosti se prikazuje.</p>
                <span class="action-card-arrow">Otvori podešavanja →</span>
            </a>
        <?php endif; ?>
        <a class="action-card" href="content-edit.php?area_key=page-downloads&content_type=file">
            <span class="badge">Dokumenti</span>
            <h2>Dodaj fajl</h2>
            <p>Izaberite stranicu i otpremite PDF ili Office dokument.</p>
            <span class="action-card-arrow">Započni →</span>
        </a>
        <a class="action-card" href="content-edit.php?area_key=page-gallery&content_type=image">
            <span class="badge">Fotografije</span>
            <h2>Dodaj fotografiju</h2>
            <p>Otpremite sliku uz pregled pre čuvanja.</p>
            <span class="action-card-arrow">Započni →</span>
        </a>
        <a class="action-card" href="pages.php">
            <span class="badge">Stranice</span>
            <h2>Izmeni tekst stranice</h2>
            <p>Pronađite stranicu i uredite njen glavni sadržaj.</p>
            <span class="action-card-arrow">Otvori stranice →</span>
        </a>
        <a class="action-card" href="pages.php?status=legacy">
            <span class="badge">Postojeći sajt</span>
            <h2>Uredi staru PHP stranicu</h2>
            <p>Menjajte tekst starih stranica kroz CMS, bez ručnog uređivanja fajlova.</p>
            <span class="action-card-arrow">Prikaži stare stranice →</span>
        </a>
    </div>
</section>

<section class="grid-cards" aria-label="Kratak pregled">
    <article class="card">
        <span class="muted">Stranice</span>
        <div class="metric"><?php echo site_escape((string) $stats['pages']); ?></div>
    </article>
    <article class="card">
        <span class="muted">Objave i fajlovi</span>
        <div class="metric"><?php echo site_escape((string) $stats['content']); ?></div>
    </article>
</section>

<section class="table-card">
    <div class="actions" style="justify-content: space-between; margin-bottom: 14px;">
        <div>
            <h2 style="margin-bottom: 4px;">Poslednje izmene</h2>
            <p class="muted" style="margin: 0;">Brzo otvorite sadržaj na kome ste skoro radili.</p>
        </div>
        <div class="actions">
            <a class="button button-secondary" href="content.php">Prikaži sve</a>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Naslov</th>
                    <th>Stranica</th>
                    <th>Vrsta</th>
                    <th>Status</th>
                    <th>Izmenjeno</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$recentItems): ?>
                    <tr>
                        <td colspan="5">Još nema CMS sadržaja. Napravite prvo važno obaveštenje ili događaj.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentItems as $item): ?>
                        <tr>
                            <td><a href="content-edit.php?id=<?php echo (int) $item['id']; ?>"><?php echo site_escape($item['title']); ?></a></td>
                            <td><?php echo site_escape($item['page_title']); ?></td>
                            <td><?php echo site_escape($areaOptions[$item['area_key']] ?? 'Ostali sadržaj'); ?></td>
                            <td><span class="status-pill <?php echo !empty($item['is_published']) ? 'is-live' : ''; ?>"><?php echo !empty($item['is_published']) ? 'Objavljeno' : 'Skica'; ?></span></td>
                            <td><?php echo site_escape(site_format_date($item['updated_at'], 'd.m.Y. H:i')); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/layout-end.php'; ?>
