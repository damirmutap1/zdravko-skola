<?php

require_once __DIR__ . '/admin.php';
admin_bootstrap();
admin_require_login();

$pdo = site_db_or_fail();

if (!site_table_exists($pdo, 'cms_page_content')) {
    admin_flash('error', 'Tabela cms_page_content ne postoji. Prvo uvezite migraciju za sadržaj stranica.');
    admin_redirect('page-content.php');
}

cms_sync_pages($pdo);

$pageId = isset($_GET['page_id']) ? (int) $_GET['page_id'] : 0;
$page = cms_get_page_by_id($pdo, $pageId);

if (!$page) {
    admin_flash('error', 'Izabrana stranica nije pronađena.');
    admin_redirect('page-content.php');
}

$record = cms_get_page_content_by_page_id($pdo, $pageId);
$canImportSource = !empty($page['is_system']) && !empty($page['file_path']) && $page['file_path'] !== '[db-page]';
$sourceLoadError = '';

if ($canImportSource && $_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'import_source') {
    try {
        admin_verify_csrf();
        $result = cms_seed_page_content_from_source($pdo, $page['page_key'], null, true);
        admin_flash('success', $result['message'] ?? 'Sadržaj je osvežen iz izvornog fajla.');
        admin_redirect('page-content-edit.php?page_id=' . $pageId);
    } catch (Throwable $exception) {
        admin_flash('error', $exception->getMessage());
    }
}

if (
    $canImportSource
    && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && (!$record || cms_page_content_is_effectively_empty($record['content_html'] ?? '', $page['title'] ?? ''))
) {
    try {
        $result = cms_seed_page_content_from_source($pdo, $page['page_key'], null, (bool) $record);
        $record = cms_get_page_content_by_page_id($pdo, $pageId);

        if (!$record || cms_page_content_is_effectively_empty($record['content_html'] ?? '', $page['title'] ?? '')) {
            $sourceLoadError = $result['message'] ?? 'Tekst izvornog PHP fajla nije mogao da se učita.';
        }
    } catch (Throwable $exception) {
        $sourceLoadError = $exception->getMessage();
    }
}

if ($record && ($page['file_path'] ?? '') === '[db-page]') {
    $record['content_html'] = cms_strip_generated_db_page_placeholder(
        $record['content_html'] ?? '',
        $page['title'] ?? ''
    );
}

if ($record && $_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'import_downloads') {
    try {
        admin_verify_csrf();
        $summary = cms_import_page_downloads($pdo, $pageId, admin_current_user()['id'], true);
        admin_flash('success', $summary['message'] ?? 'Fajlovi su uvezeni u CMS.');
        admin_redirect('page-content-edit.php?page_id=' . $pageId);
    } catch (Throwable $exception) {
        admin_flash('error', $exception->getMessage());
    }
}

$form = $record ?: [
    'page_id' => $pageId,
    'browser_title' => $page['title'],
    'content_html' => '',
    'extra_head_html' => '',
    'extra_footer_html' => '',
    'is_published' => 1,
    'source_hash' => '',
];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && !in_array((string) ($_POST['action'] ?? ''), ['import_source', 'import_downloads'], true)
) {
    try {
        admin_verify_csrf();
        $savedId = cms_save_page_content($pdo, $_POST + ['page_id' => $pageId], admin_current_user()['id'], $record['id'] ?? null);
        admin_flash('success', 'Sadržaj stranice je sačuvan.');
        admin_redirect('page-content-edit.php?page_id=' . $pageId . '&saved=' . $savedId);
    } catch (Throwable $exception) {
        $form = array_merge($form, $_POST);
        admin_flash('error', $exception->getMessage());
    }
}

$pageTitle = 'Uredi stranicu';
$pageDescription = 'Promenite naslov i glavni tekst, zatim sačuvajte stranicu.';
$activeNav = 'pages';
$previewUrl = '../../' . site_route_to_url($page['route_path']);
require __DIR__ . '/layout-start.php';
?>
<section class="form-card page-editor-card">
    <div class="page-editor-header">
        <div>
            <span class="badge"><?php echo $canImportSource ? 'Stara PHP stranica · uređivanje kroz CMS' : 'Uređujete stranicu'; ?></span>
            <h2><?php echo site_escape($page['title']); ?></h2>
            <p class="muted"><?php echo $canImportSource ? 'Promene se čuvaju u bazi i prikazuju na postojećoj URL adresi; originalni PHP fajl ostaje rezervna kopija.' : 'Promene se prikazuju posetiocima tek nakon čuvanja.'; ?></p>
        </div>
        <div class="page-editor-header-actions">
            <a class="button button-secondary" href="content.php?page_id=<?php echo (int) $pageId; ?>">Slike i fajlovi</a>
            <a class="button button-ghost" href="<?php echo site_escape($previewUrl); ?>" target="_blank" rel="noopener">Otvori stranicu</a>
            <a class="button button-secondary" href="pages.php">Nazad</a>
        </div>
    </div>

    <form method="post" data-guard-unsaved>
        <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
        <input type="hidden" name="page_id" value="<?php echo (int) $pageId; ?>">
        <input type="hidden" name="source_hash" value="<?php echo site_escape((string) ($form['source_hash'] ?? '')); ?>">

        <section class="form-section page-editor-section" aria-labelledby="page-text-heading">
            <?php if ($sourceLoadError !== ''): ?>
                <div class="flash flash-error" role="alert">
                    Tekst stare PHP stranice nije učitan: <?php echo site_escape($sourceLoadError); ?>
                </div>
            <?php endif; ?>
            <div class="form-section-heading">
                <span class="step-number">1</span>
                <div>
                    <h2 id="page-text-heading">Naslov i glavni tekst</h2>
                    <p>Ovo je sadržaj koji posetioci vide na stranici.</p>
                </div>
            </div>
            <label>
                Naslov stranice
                <input type="text" name="browser_title" value="<?php echo site_escape((string) ($form['browser_title'] ?? '')); ?>" required>
                <span class="field-help"><?php echo ($page['file_path'] ?? '') === '[db-page]' ? 'Prikazuje se kao glavni naslov stranice i kao naziv u tabu pregledača.' : 'Koristi se kao naziv stranice u tabu pregledača.'; ?></span>
            </label>
            <label class="page-editor-content-field">
                Glavni tekst stranice
                <textarea id="page-content-html" name="content_html" data-editor="rich"><?php echo site_escape((string) ($form['content_html'] ?? '')); ?></textarea>
            </label>
            <p class="field-help">Široki baner škole dodaje se automatski iznad ovog teksta. Za druge slike unutar teksta koristite dugme fotografije u editoru.</p>
            <div class="actions page-editor-additions">
                <a class="button button-secondary" href="content-edit.php?page_id=<?php echo (int) $pageId; ?>&area_key=page-gallery&content_type=image">Dodaj fotografiju</a>
                <a class="button button-secondary" href="content-edit.php?page_id=<?php echo (int) $pageId; ?>&area_key=page-downloads&content_type=file">Dodaj fajl</a>
            </div>
        </section>

        <section class="form-section page-editor-section" aria-labelledby="page-publish-heading">
            <div class="form-section-heading">
                <span class="step-number">2</span>
                <div>
                    <h2 id="page-publish-heading">Objavljivanje</h2>
                    <p>Odredite da li posetioci mogu da vide ovu stranicu.</p>
                </div>
            </div>
            <label class="publish-control">
                <input type="checkbox" name="is_published" value="1" <?php echo !empty($form['is_published']) ? 'checked' : ''; ?>>
                <span>
                    <strong>Stranica je objavljena</strong>
                    <small>Isključite ovu opciju da sačuvate stranicu kao skicu.</small>
                </span>
            </label>
        </section>

        <details class="advanced-panel">
            <summary>Napredna podešavanja za programera</summary>
            <div class="advanced-panel-content">
                <p class="field-help">Ova polja ostavite nepromenjena osim ako programer ne zatraži drugačije.</p>
                <div class="form-grid two">
                    <label>
                        Dodatni HTML za <code>&lt;head&gt;</code>
                        <textarea name="extra_head_html"><?php echo site_escape((string) ($form['extra_head_html'] ?? '')); ?></textarea>
                    </label>
                    <label>
                        Dodatni HTML pre zatvaranja <code>&lt;/body&gt;</code>
                        <textarea name="extra_footer_html"><?php echo site_escape((string) ($form['extra_footer_html'] ?? '')); ?></textarea>
                    </label>
                </div>
            </div>
        </details>

        <div class="page-editor-savebar">
            <span class="muted">Kada završite, sačuvajte promene.</span>
            <div class="actions">
                <a class="button button-secondary" href="pages.php">Otkaži</a>
                <a class="button button-ghost" href="<?php echo site_escape($previewUrl); ?>" target="_blank" rel="noopener">Otvori trenutnu stranicu</a>
                <button type="submit">Sačuvaj izmene</button>
            </div>
        </div>
    </form>

    <?php if ($canImportSource || $record): ?>
        <details class="advanced-panel">
            <summary>Alati za uvoz postojećeg sadržaja</summary>
            <div class="advanced-panel-content actions">
                <?php if ($canImportSource): ?>
                    <form method="post" onsubmit="return confirm('Ponovni uvoz iz PHP fajla zameniće izmene koje ste napravili kroz CMS. Nastaviti?');">
                        <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
                        <input type="hidden" name="action" value="import_source">
                        <button type="submit" class="button button-secondary">Ponovo uvezi iz PHP fajla</button>
                    </form>
                <?php endif; ?>
                <?php if ($record): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
                        <input type="hidden" name="action" value="import_downloads">
                        <button type="submit" class="button button-secondary">Uvezi postojeći spisak fajlova</button>
                    </form>
                <?php endif; ?>
            </div>
        </details>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/layout-end.php'; ?>
