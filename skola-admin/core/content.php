<?php

require_once __DIR__ . '/admin.php';
admin_bootstrap();
admin_require_login();

$pdo = site_db_or_fail();
$filters = [
    'page_id' => $_GET['page_id'] ?? '',
    'area_key' => $_GET['area_key'] ?? '',
    'is_published' => $_GET['is_published'] ?? '',
    'q' => $_GET['q'] ?? '',
];

try {
    cms_sync_pages($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
        admin_verify_csrf();
        cms_delete_content_item($pdo, (int) ($_POST['id'] ?? 0));
        admin_flash('success', 'Stavka je obrisana.');
        $returnFilters = array_filter([
            'page_id' => (int) ($_POST['return_page_id'] ?? 0),
            'area_key' => trim((string) ($_POST['return_area_key'] ?? '')),
            'is_published' => isset($_POST['return_is_published']) ? (string) $_POST['return_is_published'] : '',
            'q' => trim((string) ($_POST['return_q'] ?? '')),
        ], static function ($value) {
            return $value !== '' && $value !== 0;
        });
        admin_redirect('content.php' . ($returnFilters ? '?' . http_build_query($returnFilters) : ''));
    }
} catch (Throwable $exception) {
    admin_flash('error', $exception->getMessage());
}

$pages = cms_list_pages($pdo);
$homePage = cms_get_page_by_key($pdo, 'index');
$selectedPage = !empty($filters['page_id']) ? cms_get_page_by_id($pdo, (int) $filters['page_id']) : null;
$items = cms_list_content_items($pdo, $filters);
$areaOptions = cms_area_options();
$contentTypeOptions = cms_content_type_options();
$documentUploadLimit = site_effective_upload_limit(15728640);
$imageUploadLimit = site_effective_upload_limit(10485760);
$pageTitle = 'Objave, slike i fajlovi';
$pageDescription = 'Pronađite i izmenite sav dodatni sadržaj povezan sa stranicama.';
$activeNav = 'content';

require __DIR__ . '/layout-start.php';
?>
<section aria-labelledby="content-actions-title">
    <div class="actions" style="justify-content: space-between; margin-bottom: 14px;">
        <div>
            <h2 id="content-actions-title" style="margin: 0 0 4px;">Dodajte novi sadržaj</h2>
            <p class="muted" style="margin: 0;">Izaberite vrstu, a formular će prikazati samo potrebna polja.</p>
        </div>
        <a class="button button-secondary" href="content-edit.php">Druga vrsta sadržaja</a>
    </div>

    <div class="action-grid">
        <?php if ($homePage): ?>
            <a class="action-card" href="content-edit.php?page_id=<?php echo (int) $homePage['id']; ?>&area_key=homepage-important&content_type=html">
                <span class="badge">Početna strana</span>
                <h2>Obaveštenje</h2>
                <p>Naslov, kratak opis i po želji link, fajl ili slika.</p>
                <span class="action-card-arrow">Dodaj obaveštenje →</span>
            </a>
        <?php endif; ?>

        <a class="action-card" href="content-edit.php?<?php echo $selectedPage ? 'page_id=' . (int) $selectedPage['id'] . '&' : ''; ?>area_key=page-downloads&content_type=file">
            <span class="badge"><?php echo $selectedPage ? site_escape($selectedPage['title']) : 'Izaberite stranicu'; ?></span>
            <h2>Dokument ili fajl</h2>
            <p>PDF, Word, Excel, prezentacija ili ZIP do <?php echo site_escape(site_format_bytes($documentUploadLimit)); ?>.</p>
            <span class="action-card-arrow">Dodaj fajl →</span>
        </a>

        <a class="action-card" href="content-edit.php?<?php echo $selectedPage ? 'page_id=' . (int) $selectedPage['id'] . '&' : ''; ?>area_key=page-gallery&content_type=image">
            <span class="badge"><?php echo $selectedPage ? site_escape($selectedPage['title']) : 'Izaberite stranicu'; ?></span>
            <h2>Fotografija</h2>
            <p>JPG, PNG, GIF ili WEBP slika do <?php echo site_escape(site_format_bytes($imageUploadLimit)); ?>.</p>
            <span class="action-card-arrow">Dodaj fotografiju →</span>
        </a>
    </div>
</section>

<?php if ($selectedPage): ?>
    <section class="card" style="margin-bottom: 20px;">
        <div class="actions" style="justify-content: space-between;">
            <div>
                <span class="badge">Trenutno gledate</span>
                <h2 style="margin: 10px 0 4px;"><?php echo site_escape($selectedPage['title']); ?></h2>
                <p class="muted" style="margin: 0;">Lista ispod je filtrirana za ovu stranicu.</p>
            </div>
            <div class="actions">
                <a class="button button-secondary" href="page-content-edit.php?page_id=<?php echo (int) $selectedPage['id']; ?>">Uredi glavni tekst</a>
                <a class="button button-ghost" href="content.php">Prikaži sav sadržaj</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="table-card" aria-labelledby="library-title">
    <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
        <div>
            <h2 id="library-title" style="margin-bottom: 4px;">Postojeći sadržaj</h2>
            <p class="muted" style="margin: 0;">Pronađite stavku po naslovu, stranici ili statusu.</p>
        </div>
        <span class="status-pill"><?php echo count($items); ?> rezultata</span>
    </div>

    <form class="filters" method="get" style="margin-bottom: 18px;">
        <label>
            Brzo pronađite stranicu
            <input type="search" data-page-search-target="content-filter-page" placeholder="Počnite da kucate naziv…" autocomplete="off">
            <span class="field-help">Ovo polje sužava spisak stranica ispod.</span>
        </label>
        <label>
            Stranica
            <select id="content-filter-page" name="page_id">
                <option value="">Sve stranice</option>
                <?php foreach ($pages as $page): ?>
                    <option value="<?php echo (int) $page['id']; ?>" <?php echo (string) $filters['page_id'] === (string) $page['id'] ? 'selected' : ''; ?>>
                        <?php echo site_escape($page['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Vrsta
            <select name="area_key">
                <option value="">Sve vrste</option>
                <?php foreach ($areaOptions as $key => $label): ?>
                    <option value="<?php echo site_escape($key); ?>" <?php echo (string) $filters['area_key'] === (string) $key ? 'selected' : ''; ?>>
                        <?php echo site_escape($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Naslov, opis ili stranica
            <input type="search" name="q" value="<?php echo site_escape((string) $filters['q']); ?>" placeholder="Šta tražite?" autocomplete="off">
        </label>
        <label>
            Status
            <select name="is_published">
                <option value="">Svi statusi</option>
                <option value="1" <?php echo (string) $filters['is_published'] === '1' ? 'selected' : ''; ?>>Objavljeno</option>
                <option value="0" <?php echo (string) $filters['is_published'] === '0' ? 'selected' : ''; ?>>Skica</option>
            </select>
        </label>
        <div class="actions">
            <button type="submit">Prikaži rezultate</button>
            <a class="button button-secondary" href="content.php">Poništi filtere</a>
        </div>
    </form>

    <?php if (!$items): ?>
        <div class="empty-state">
            <h3>Nema pronađenog sadržaja</h3>
            <p>Promenite pretragu ili filtere, ili dodajte novu fotografiju, fajl ili objavu.</p>
            <a class="button button-secondary" href="content.php">Poništi filtere</a>
        </div>
    <?php else: ?>
        <div class="content-card-list">
            <?php foreach ($items as $item): ?>
                <?php $itemTypeLabel = $areaOptions[$item['area_key']] ?? ($contentTypeOptions[$item['content_type']] ?? 'Sadržaj'); ?>
                <article class="content-manage-card">
                    <?php if (!empty($item['image_path'])): ?>
                        <img class="content-card-image" src="<?php echo site_escape(site_url($item['image_path'])); ?>" alt="" width="120" height="90" loading="lazy">
                    <?php else: ?>
                        <div class="content-card-placeholder" aria-hidden="true"><?php echo $item['content_type'] === 'file' ? 'FAJL' : 'OBJAVA'; ?></div>
                    <?php endif; ?>
                    <div class="content-card-main">
                        <div class="content-card-heading">
                            <div>
                                <h3><?php echo site_escape($item['title']); ?></h3>
                                <p>
                                    <a href="content.php?page_id=<?php echo (int) $item['page_id']; ?>"><?php echo site_escape($item['page_title']); ?></a>
                                    <span aria-hidden="true"> · </span><?php echo site_escape($itemTypeLabel); ?>
                                </p>
                            </div>
                            <span class="status-pill <?php echo !empty($item['is_published']) ? 'is-live' : ''; ?>"><?php echo !empty($item['is_published']) ? 'Objavljeno' : 'Skica'; ?></span>
                        </div>
                        <?php if (!empty($item['summary'])): ?>
                            <p class="content-card-summary"><?php echo site_escape(site_excerpt($item['summary'], 150)); ?></p>
                        <?php endif; ?>
                        <div class="content-card-footer">
                            <span class="muted">Datum: <?php echo site_escape(site_format_date($item['published_at'], 'd.m.Y.')); ?></span>
                            <div class="actions">
                                <a class="button" href="content-edit.php?id=<?php echo (int) $item['id']; ?>">Izmeni</a>
                                <form method="post" aria-label="Obriši <?php echo site_escape($item['title']); ?>" onsubmit="return confirm('Da li želite trajno da obrišete ovu stavku?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="return_page_id" value="<?php echo site_escape((string) $filters['page_id']); ?>">
                                    <input type="hidden" name="return_area_key" value="<?php echo site_escape((string) $filters['area_key']); ?>">
                                    <input type="hidden" name="return_is_published" value="<?php echo site_escape((string) $filters['is_published']); ?>">
                                    <input type="hidden" name="return_q" value="<?php echo site_escape((string) $filters['q']); ?>">
                                    <button class="button-danger" type="submit">Obriši</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/layout-end.php'; ?>
