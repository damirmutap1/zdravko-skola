<?php

require_once __DIR__ . '/admin.php';
admin_bootstrap();
admin_require_login();

$pdo = site_db_or_fail();
$migrationMissing = !site_table_exists($pdo, 'cms_page_content');
$pageQuery = trim((string) ($_GET['q'] ?? ''));

try {
    cms_sync_pages($pdo);

    if (!$migrationMissing && $_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_verify_csrf();
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'import_missing') {
            $summary = cms_sync_page_content_from_sources($pdo, false);
            admin_flash(
                'success',
                sprintf(
                    'Uvoz završen. Novo: %d, osveženo: %d, postojeće: %d, preskočeno: %d, greške: %d.',
                    $summary['inserted'],
                    $summary['updated'],
                    $summary['existing'],
                    $summary['skipped'],
                    $summary['failed']
                )
            );
            admin_redirect('page-content.php');
        }

        if ($action === 'import_page') {
            $pageKey = (string) ($_POST['page_key'] ?? '');
            $overwrite = !empty($_POST['overwrite']);
            $result = cms_seed_page_content_from_source($pdo, $pageKey, null, $overwrite);
            admin_flash('success', $result['message'] ?? 'Uvoz je završen.');
            admin_redirect('page-content.php');
        }
    }
} catch (Throwable $exception) {
    admin_flash('error', $exception->getMessage());
}

$pages = $migrationMissing ? [] : cms_list_page_contents($pdo);
if (!$migrationMissing && $pageQuery !== '') {
    $pages = array_values(array_filter(
        $pages,
        static function ($page) use ($pageQuery) {
            foreach (['title', 'page_key', 'route_path', 'file_path'] as $field) {
                if (stripos((string) ($page[$field] ?? ''), $pageQuery) !== false) {
                    return true;
                }
            }

            return false;
        }
    ));
}
$pageTitle = 'Uredi Stranice';
$pageDescription = 'Pronađite stranicu, promenite tekst ili dodajte dokumente i fotografije.';
$activeNav = 'pages';
require __DIR__ . '/layout-start.php';
?>
<section class="table-card">
    <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
        <div>
            <h2 style="margin-bottom: 4px;">Stranice sajta</h2>
            <p class="muted" style="margin: 0;">Kliknite „Uredi tekst“ za sadržaj stranice ili „Objave i fajlovi“ za njene dodatke.</p>
        </div>
        <div class="actions">
            <?php if (!$migrationMissing): ?>
                <form method="get" class="inline-form">
                    <label>
                        Pretraga stranice
                        <input type="search" name="q" value="<?php echo site_escape($pageQuery); ?>" placeholder="Unesite naziv stranice…" autocomplete="off">
                    </label>
                    <div class="actions">
                        <button type="submit">Pronađi</button>
                        <a class="button button-secondary" href="page-content.php">Resetuj</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($migrationMissing): ?>
        <p class="muted">Uvezite objedinjenu migraciju <code>database/migrations/20261106_full_cms.sql</code>, pa se vratite na ovaj ekran.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Stranica</th>
                        <th>Status</th>
                        <th>Ažurirano</th>
                        <th>Akcije</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                        <?php
                        $hasContent = !empty($page['content_id']);
                        $previewUrl = '../../' . ($page['route_path'] === 'index' ? 'index' : $page['route_path']);
                        $canImportSource = !empty($page['is_system']) && !empty($page['file_path']) && $page['file_path'] !== '[db-page]';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo site_escape($page['title']); ?></strong>
                            </td>
                            <td>
                                <?php if (!$hasContent): ?>
                                    Nije uvezeno
                                <?php else: ?>
                                    <?php echo !empty($page['is_published']) ? 'Objavljeno' : 'Skica'; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($page['content_updated_at'])): ?>
                                    <?php echo site_escape(site_format_date($page['content_updated_at'], 'd.m.Y. H:i')); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <a class="button" href="page-content-edit.php?page_id=<?php echo (int) $page['id']; ?>">Uredi tekst</a>
                                    <a class="button button-secondary" href="content.php?page_id=<?php echo (int) $page['id']; ?>">Objave i fajlovi</a>
                                    <a class="button button-ghost" href="<?php echo site_escape($previewUrl); ?>" target="_blank" rel="noopener">Pregled</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/layout-end.php'; ?>
