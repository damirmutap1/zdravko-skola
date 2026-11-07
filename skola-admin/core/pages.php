<?php

require_once __DIR__ . '/admin.php';
admin_bootstrap();
admin_require_login();

$pdo = site_db_or_fail();
$hasPageContentTable = site_table_exists($pdo, 'cms_page_content');
$pageQuery = trim((string) ($_GET['q'] ?? ''));
$pageStatusFilter = trim((string) ($_GET['status'] ?? 'all'));
$allowedPageStatusFilters = ['all', 'published', 'attention', 'legacy', 'custom'];
if (!in_array($pageStatusFilter, $allowedPageStatusFilters, true)) {
    $pageStatusFilter = 'all';
}
$pageNumber = max(1, (int) ($_GET['page'] ?? 1));
$editPageId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$editingPage = $editPageId > 0 ? cms_get_page_by_id($pdo, $editPageId) : null;

if ($editPageId > 0 && !$editingPage) {
    admin_flash('error', 'Izabrana stranica nije pronađena.');
    admin_redirect('pages.php');
}

if ($editingPage && !empty($editingPage['is_system'])) {
    admin_flash('error', 'URL sistemske stranice određuje njen PHP fajl i ne može se menjati ovde.');
    admin_redirect('pages.php');
}

$pageForm = $editingPage ?: [
    'title' => '',
    'page_key' => '',
    'route_path' => '',
    'is_visible' => 1,
];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_verify_csrf();
        $action = (string) ($_POST['action'] ?? 'sync');

        if ($action === 'delete_db_page') {
            $summary = cms_delete_page($pdo, (int) ($_POST['page_id'] ?? 0));
            $message = 'Stranica „' . $summary['title'] . '“ je trajno obrisana';
            if ($summary['removed_files'] > 0) {
                $message .= ', zajedno sa ' . $summary['removed_files'] . ' povezanih fajlova';
            }
            if ($summary['preserved_menu_groups'] > 0) {
                $message .= '. Roditeljska stavka menija je sačuvana kao grupa jer ima podstavke';
            }
            admin_flash('success', $message . '.');
            admin_redirect('pages.php');
        }

        if ($action === 'create_db_page') {
            $pageId = cms_save_page($pdo, $_POST);
            admin_flash('success', 'Nova stranica je kreirana. Sada unesite njen glavni tekst.');
            admin_redirect('page-content-edit.php?page_id=' . $pageId);
        }

        if ($action === 'update_db_page') {
            $pageId = (int) ($_POST['page_id'] ?? 0);
            cms_save_page($pdo, $_POST, $pageId);
            admin_flash('success', 'Naziv i URL stranice su ažurirani. Sve povezane stavke menija sada koriste novi URL.');
            admin_redirect('pages.php?edit_id=' . $pageId);
        }

        $summary = cms_sync_pages($pdo);
        admin_flash('success', 'Registar stranica je sinhronizovan. Dodato: ' . $summary['inserted'] . ', ažurirano: ' . $summary['updated'] . '.');
        admin_redirect('pages.php');
    }

    cms_sync_pages($pdo);
    if ($hasPageContentTable) {
        cms_sync_page_content_from_sources($pdo, false);
    }
} catch (Throwable $exception) {
    $pageForm = array_merge($pageForm, $_POST);
    admin_flash('error', $exception->getMessage());
}

if ($hasPageContentTable) {
    $sql = '
        SELECT cms_pages.*, COUNT(cms_content_items.id) AS content_count, cms_page_content.id AS page_content_id, cms_page_content.is_published AS page_content_published
        FROM cms_pages
        LEFT JOIN cms_content_items ON cms_content_items.page_id = cms_pages.id
        LEFT JOIN cms_page_content ON cms_page_content.page_id = cms_pages.id
    ';

    if ($pageQuery !== '') {
        $sql .= ' WHERE (cms_pages.title LIKE :query_title OR cms_pages.page_key LIKE :query_key OR cms_pages.file_path LIKE :query_file OR cms_pages.route_path LIKE :query_route)';
    }

    $sql .= '
        GROUP BY cms_pages.id, cms_page_content.id, cms_page_content.is_published
        ORDER BY cms_pages.title ASC
    ';
    $statement = $pdo->prepare($sql);
    $statement->execute($pageQuery !== '' ? [
        'query_title' => '%' . $pageQuery . '%',
        'query_key' => '%' . $pageQuery . '%',
        'query_file' => '%' . $pageQuery . '%',
        'query_route' => '%' . $pageQuery . '%',
    ] : []);
} else {
    $sql = '
        SELECT cms_pages.*, COUNT(cms_content_items.id) AS content_count, NULL AS page_content_id, NULL AS page_content_published
        FROM cms_pages
        LEFT JOIN cms_content_items ON cms_content_items.page_id = cms_pages.id
    ';

    if ($pageQuery !== '') {
        $sql .= ' WHERE (cms_pages.title LIKE :query_title OR cms_pages.page_key LIKE :query_key OR cms_pages.file_path LIKE :query_file OR cms_pages.route_path LIKE :query_route)';
    }

    $sql .= '
        GROUP BY cms_pages.id
        ORDER BY cms_pages.title ASC
    ';
    $statement = $pdo->prepare($sql);
    $statement->execute($pageQuery !== '' ? [
        'query_title' => '%' . $pageQuery . '%',
        'query_key' => '%' . $pageQuery . '%',
        'query_file' => '%' . $pageQuery . '%',
        'query_route' => '%' . $pageQuery . '%',
    ] : []);
}
$pages = $statement->fetchAll();

if ($pageStatusFilter !== 'all') {
    $pages = array_values(array_filter($pages, static function ($page) use ($pageStatusFilter) {
        $isSystemPage = !empty($page['is_system']);
        $hasPageText = !empty($page['page_content_id']);
        $isPublished = !empty($page['is_visible']) && ($isSystemPage || ($hasPageText && !empty($page['page_content_published'])));

        if ($pageStatusFilter === 'published') {
            return $isPublished;
        }

        if ($pageStatusFilter === 'attention') {
            return !$isSystemPage && !$isPublished;
        }

        if ($pageStatusFilter === 'legacy') {
            return $isSystemPage;
        }

        return !$isSystemPage;
    }));
}

$pageSize = 9;
$totalPageResults = count($pages);
$totalPageSets = max(1, (int) ceil($totalPageResults / $pageSize));
$pageNumber = min($pageNumber, $totalPageSets);
$pageResultStart = $totalPageResults > 0 ? (($pageNumber - 1) * $pageSize) + 1 : 0;
$pageResultEnd = min($pageNumber * $pageSize, $totalPageResults);
$pages = array_slice($pages, ($pageNumber - 1) * $pageSize, $pageSize);
$paginationQuery = array_filter([
    'q' => $pageQuery,
    'status' => $pageStatusFilter !== 'all' ? $pageStatusFilter : '',
], static function ($value) {
    return $value !== '';
});

$pageTitle = 'Stranice';
$pageDescription = 'Na jednom mestu uredite tekst, dodajte fotografije ili fajlove i otvorite javnu stranicu.';
$activeNav = 'pages';
require __DIR__ . '/layout-start.php';
?>
<section class="table-card">
    <div class="page-manager-toolbar">
        <div>
            <h2>Izaberite stranicu</h2>
            <p class="muted">Najčešće radnje su odmah ispod svake stranice.</p>
        </div>
        <div class="actions">
            <a class="button button-secondary" href="pages.php?status=legacy">Stare PHP stranice</a>
            <a class="button" href="#page-form">Dodaj novu stranicu</a>
        </div>
    </div>

    <form method="get" class="page-search-form" role="search">
        <div class="page-search-fields">
            <label for="page-search">
                Pronađite stranicu po nazivu ili URL adresi
                <input id="page-search" type="search" name="q" value="<?php echo site_escape($pageQuery); ?>" placeholder="Na primer: Raspored časova">
            </label>
            <label for="page-status-filter">
                Prikaži
                <select id="page-status-filter" name="status">
                    <option value="all" <?php echo $pageStatusFilter === 'all' ? 'selected' : ''; ?>>Sve stranice</option>
                    <option value="published" <?php echo $pageStatusFilter === 'published' ? 'selected' : ''; ?>>Objavljene</option>
                    <option value="attention" <?php echo $pageStatusFilter === 'attention' ? 'selected' : ''; ?>>Za dovršavanje</option>
                    <option value="legacy" <?php echo $pageStatusFilter === 'legacy' ? 'selected' : ''; ?>>Stare PHP stranice</option>
                    <option value="custom" <?php echo $pageStatusFilter === 'custom' ? 'selected' : ''; ?>>Napravljene u administraciji</option>
                </select>
            </label>
            <div class="actions page-search-actions">
                <button type="submit">Prikaži</button>
                <?php if ($pageQuery !== '' || $pageStatusFilter !== 'all'): ?>
                    <a class="button button-secondary" href="pages.php">Poništi</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="page-results-heading">
        <strong>Prikaz <?php echo $pageResultStart; ?>–<?php echo $pageResultEnd; ?> od <?php echo $totalPageResults; ?> stranica</strong>
        <?php if ($pageQuery !== ''): ?>
            <span class="muted">Rezultati za „<?php echo site_escape($pageQuery); ?>“</span>
        <?php endif; ?>
    </div>

    <?php if (!$hasPageContentTable): ?>
        <div class="flash flash-warning" role="status">
            <strong>Potrebno je ažuriranje baze.</strong>
            Za uređivanje glavnog teksta stranice uvezite <code>database/migrations/20261106_full_cms.sql</code>.
        </div>
    <?php endif; ?>

    <?php if (!$pages): ?>
        <div class="empty-state">
            <h3>Nema pronađenih stranica</h3>
            <p>Promenite pojam za pretragu ili prikažite sve stranice.</p>
            <a class="button button-secondary" href="pages.php">Prikaži sve stranice</a>
        </div>
    <?php else: ?>
        <div class="page-card-grid">
            <?php foreach ($pages as $page): ?>
                <?php
                $isSystemPage = !empty($page['is_system']);
                $hasPageText = !empty($page['page_content_id']);
                $isPublished = $hasPageText && !empty($page['page_content_published']);
                if (empty($page['is_visible'])) {
                    $statusLabel = 'Sakrivena';
                } elseif ($isPublished) {
                    $statusLabel = 'Objavljeno';
                } elseif ($hasPageText) {
                    $statusLabel = 'Skica';
                } elseif ($isSystemPage) {
                    $statusLabel = 'Sistemska stranica';
                } else {
                    $statusLabel = 'Potrebno je uneti tekst';
                }
                $statusClass = !empty($page['is_visible']) && ($isPublished || $isSystemPage) ? ' is-live' : '';
                $publicUrl = '../../' . site_route_to_url($page['route_path']);
                ?>
                <article class="page-manage-card">
                    <div class="page-card-header">
                        <div>
                            <h3><?php echo site_escape($page['title']); ?></h3>
                            <span class="page-card-route">/<?php echo site_escape(site_route_to_url($page['route_path'])); ?></span>
                        </div>
                        <span class="status-pill<?php echo $statusClass; ?>"><?php echo site_escape($statusLabel); ?></span>
                    </div>

                    <?php if ($hasPageContentTable): ?>
                        <div class="page-card-primary-actions">
                            <a class="button" href="page-content-edit.php?page_id=<?php echo (int) $page['id']; ?>"><?php echo $isSystemPage ? 'Uredi staru stranicu' : 'Uredi tekst'; ?></a>
                            <a class="button button-secondary" href="content.php?page_id=<?php echo (int) $page['id']; ?>">Uredi slike i fajlove</a>
                            <a class="button button-secondary" href="content-edit.php?page_id=<?php echo (int) $page['id']; ?>&area_key=page-gallery&content_type=image">Dodaj fotografiju</a>
                            <a class="button button-secondary" href="content-edit.php?page_id=<?php echo (int) $page['id']; ?>&area_key=page-downloads&content_type=file">Dodaj fajl</a>
                            <a class="button button-ghost" href="<?php echo site_escape($publicUrl); ?>" target="_blank" rel="noopener">Otvori stranicu</a>
                        </div>
                    <?php else: ?>
                        <p class="muted">Uređivanje će biti dostupno nakon ažuriranja baze.</p>
                    <?php endif; ?>

                    <details class="page-card-more">
                        <summary>Više opcija</summary>
                        <div class="page-card-more-content">
                            <dl class="page-card-metadata">
                                <div><dt>Dodatni sadržaj</dt><dd><?php echo (int) $page['content_count']; ?> stavki</dd></div>
                                <div><dt>Vrsta stranice</dt><dd><?php echo $isSystemPage ? 'Stara PHP stranica – sadržaj se uređuje kroz CMS' : 'Stranica napravljena u administraciji'; ?></dd></div>
                                <div><dt>Ključ</dt><dd><code><?php echo site_escape($page['page_key']); ?></code></dd></div>
                                <?php if ($isSystemPage): ?>
                                    <div><dt>Fajl</dt><dd><code><?php echo site_escape($page['file_path']); ?></code></dd></div>
                                <?php endif; ?>
                            </dl>
                            <div class="actions">
                                <a class="button button-secondary" href="navigation.php">Podesi meni sajta</a>
                                <?php if (!$isSystemPage): ?>
                                    <a class="button button-ghost" href="pages.php?edit_id=<?php echo (int) $page['id']; ?>#page-form">Promeni naziv i URL</a>
                                <?php endif; ?>
                            </div>
                            <?php if (!$isSystemPage): ?>
                                <div class="page-card-danger">
                                    <strong>Trajno brisanje</strong>
                                    <p>Biće obrisani stranica, njen tekst, fotografije i fajlovi.</p>
                                    <form method="post" onsubmit="return confirm('Trajno obrisati ovu stranicu, njen sadržaj, objave, fotografije i fajlove? Ova radnja se ne može poništiti.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
                                        <input type="hidden" name="action" value="delete_db_page">
                                        <input type="hidden" name="page_id" value="<?php echo (int) $page['id']; ?>">
                                        <button class="button-danger" type="submit">Trajno obriši stranicu</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($totalPageSets > 1): ?>
        <nav class="pagination" aria-label="Stranice rezultata">
            <?php if ($pageNumber > 1): ?>
                <a class="button button-secondary" href="pages.php?<?php echo site_escape(http_build_query(array_merge($paginationQuery, ['page' => $pageNumber - 1]))); ?>">Prethodne</a>
            <?php endif; ?>
            <div class="pagination-pages">
                <?php for ($pageIndex = 1; $pageIndex <= $totalPageSets; $pageIndex++): ?>
                    <a
                        class="button <?php echo $pageIndex === $pageNumber ? '' : 'button-secondary'; ?>"
                        href="pages.php?<?php echo site_escape(http_build_query(array_merge($paginationQuery, ['page' => $pageIndex]))); ?>"
                        <?php echo $pageIndex === $pageNumber ? 'aria-current="page"' : ''; ?>
                    ><?php echo $pageIndex; ?></a>
                <?php endfor; ?>
            </div>
            <?php if ($pageNumber < $totalPageSets): ?>
                <a class="button button-secondary" href="pages.php?<?php echo site_escape(http_build_query(array_merge($paginationQuery, ['page' => $pageNumber + 1]))); ?>">Sledeće</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <details class="advanced-panel page-sync-panel">
        <summary>Napredni alat: ponovo pronađi sistemske stranice</summary>
        <div class="advanced-panel-content">
            <p class="muted">Koristite samo ako je dodat novi PHP fajl stranice, a on se ne pojavljuje na ovoj listi.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
                <input type="hidden" name="action" value="sync">
                <button class="button button-secondary" type="submit">Ponovo pronađi stranice</button>
            </form>
        </div>
    </details>
</section>

<section class="form-card" id="page-form">
    <h2><?php echo $editingPage ? 'Promeni naziv i URL stranice' : 'Dodaj novu stranicu'; ?></h2>
    <p class="muted">
        <?php if ($editingPage): ?>
            Meni ostaje povezan sa ovom stranicom i automatski će koristiti novu URL adresu.
        <?php else: ?>
            Unesite naziv. URL možete ostaviti prazan — napraviće se automatski. Nova stranica automatski dobija široki baner škole; zatim unesite tekst i po želji je povežite u „Meniju sajta“.
        <?php endif; ?>
    </p>
    <form method="post" class="form-grid two" data-guard-unsaved>
        <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
        <input type="hidden" name="action" value="<?php echo $editingPage ? 'update_db_page' : 'create_db_page'; ?>">
        <?php if ($editingPage): ?>
            <input type="hidden" name="page_id" value="<?php echo (int) $editingPage['id']; ?>">
        <?php endif; ?>
        <label>
            Naslov stranice
            <input type="text" name="title" value="<?php echo site_escape((string) ($pageForm['title'] ?? '')); ?>" autocomplete="off" required>
        </label>
        <label>
            URL adresa
            <input type="text" name="route_path" value="<?php echo site_escape((string) ($pageForm['route_path'] ?? '')); ?>" placeholder="nova-stranica…" autocomplete="off" spellcheck="false">
            <span class="field-help">Ostavite prazno da se napravi iz naziva stranice.</span>
        </label>
        <label class="checkbox-row" style="align-self: end; margin-top: 24px;">
            <input type="checkbox" name="is_visible" value="1" <?php echo !empty($pageForm['is_visible']) ? 'checked' : ''; ?>>
            Prikaži kao javnu stranicu
        </label>
        <details class="advanced-panel form-grid-full">
            <summary>Napredna podešavanja</summary>
            <div class="advanced-panel-content">
                <label>
                    Interni ključ stranice
                    <input type="text" name="page_key" value="<?php echo site_escape((string) ($pageForm['page_key'] ?? '')); ?>" placeholder="nova-stranica…" autocomplete="off" spellcheck="false">
                    <span class="field-help">Obično nije potrebno menjati. Ako ostane prazno, napraviće se automatski.</span>
                </label>
            </div>
        </details>
        <div class="actions form-grid-full">
            <button type="submit"><?php echo $editingPage ? 'Sačuvaj naziv i URL' : 'Kreiraj stranicu'; ?></button>
            <?php if ($editingPage): ?>
                <a class="button button-secondary" href="pages.php">Otkaži</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<?php require __DIR__ . '/layout-end.php'; ?>
