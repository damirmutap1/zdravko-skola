<?php

require_once __DIR__ . '/admin.php';
admin_bootstrap();
admin_require_login();

$pdo = site_db_or_fail();
$migrationMissing = !site_table_exists($pdo, 'cms_navigation_items');

if (!$migrationMissing) {
    cms_sync_pages($pdo);
    cms_navigation_bootstrap($pdo);
}

$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editingItem = (!$migrationMissing && $itemId > 0) ? cms_get_navigation_item($pdo, $itemId) : null;

if ($itemId > 0 && !$migrationMissing && !$editingItem) {
    admin_flash('error', 'Izabrana navigaciona stavka nije pronađena.');
    admin_redirect('navigation.php');
}

$form = $editingItem ?: [
    'label' => '',
    'page_id' => '',
    'parent_id' => '',
    'custom_url' => '',
    'destination_type' => 'new_page',
    'new_page_title' => '',
    'new_page_slug' => '',
    'open_in_new_tab' => 0,
    'sort_order' => 10,
    'is_published' => 1,
];

if (!isset($form['destination_type'])) {
    if (!empty($form['page_id'])) {
        $form['destination_type'] = 'existing_page';
    } elseif (!empty($form['custom_url'])) {
        $form['destination_type'] = 'custom_url';
    } else {
        $form['destination_type'] = 'group';
    }
}

try {
    if (!$migrationMissing && $_SERVER['REQUEST_METHOD'] === 'POST') {
        admin_verify_csrf();
        $action = (string) ($_POST['action'] ?? 'save');

        if ($action === 'delete') {
            cms_delete_navigation_item($pdo, (int) ($_POST['id'] ?? 0));
            admin_flash('success', 'Navigaciona stavka je obrisana.');
            admin_redirect('navigation.php');
        }

        $destinationType = trim((string) ($_POST['destination_type'] ?? 'existing_page'));
        if (!in_array($destinationType, ['new_page', 'existing_page', 'custom_url', 'group'], true)) {
            throw new InvalidArgumentException('Izaberite ispravnu vrstu odredišta.');
        }

        $navigationData = $_POST;
        $newPageId = null;
        $newPageRoute = '';

        if ($destinationType === 'new_page') {
            if (!site_table_exists($pdo, 'cms_page_content')) {
                throw new RuntimeException('Za novu stranicu prvo uvezite migraciju database/migrations/20261106_full_cms.sql.');
            }

            $newPageTitle = trim((string) ($_POST['new_page_title'] ?? ''));
            if ($newPageTitle === '') {
                $newPageTitle = trim((string) ($_POST['label'] ?? ''));
            }

            $newPageSlug = trim((string) ($_POST['new_page_slug'] ?? ''));
            if ($newPageSlug === '') {
                $newPageSlug = site_slugify($newPageTitle);
            }

            $pdo->beginTransaction();
            try {
                $newPageId = cms_save_page($pdo, [
                    'title' => $newPageTitle,
                    'page_key' => $newPageSlug,
                    'route_path' => $newPageSlug,
                    'is_visible' => 1,
                ]);

                $newPage = cms_get_page_by_id($pdo, $newPageId);
                $newPageRoute = (string) ($newPage['route_path'] ?? $newPageSlug);
                cms_save_page_content($pdo, [
                    'page_id' => $newPageId,
                    'browser_title' => $newPageTitle,
                    'meta_description' => '',
                    'content_html' => '',
                    'extra_head_html' => '',
                    'extra_footer_html' => '',
                    'is_published' => 1,
                ], (int) admin_current_user()['id']);

                $navigationData['page_id'] = $newPageId;
                $navigationData['custom_url'] = '';
                $navigationData['open_in_new_tab'] = 0;
                $savedId = cms_save_navigation_item($pdo, $navigationData, $itemId ?: null);
                $pdo->commit();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }

            admin_flash('success', 'Stranica i stavka menija su kreirane. URL: ' . site_route_to_url($newPageRoute));
            admin_redirect('page-content-edit.php?page_id=' . $newPageId);
        }

        if ($destinationType === 'existing_page') {
            $selectedPageId = (int) ($navigationData['page_id'] ?? 0);
            if ($selectedPageId <= 0 || !cms_get_page_by_id($pdo, $selectedPageId)) {
                throw new InvalidArgumentException('Izaberite postojeću stranicu koju stavka menija treba da otvara.');
            }
            $navigationData['custom_url'] = '';
            $navigationData['open_in_new_tab'] = 0;
        } elseif ($destinationType === 'custom_url') {
            if (trim((string) ($navigationData['custom_url'] ?? '')) === '') {
                throw new InvalidArgumentException('Unesite adresu ili putanju do dokumenta.');
            }
            $navigationData['page_id'] = '';
        } else {
            $navigationData['page_id'] = '';
            $navigationData['custom_url'] = '';
            $navigationData['open_in_new_tab'] = 0;
        }

        $savedId = cms_save_navigation_item($pdo, $navigationData, $itemId ?: null);
        admin_flash('success', $itemId ? 'Navigaciona stavka je ažurirana.' : 'Navigaciona stavka je kreirana.');
        admin_redirect('navigation.php?id=' . $savedId);
    }
} catch (Throwable $exception) {
    $form = array_merge($form, $_POST);
    admin_flash('error', $exception->getMessage());
}

$pages = !$migrationMissing ? cms_list_pages($pdo) : [];
$navigationItems = !$migrationMissing ? cms_list_navigation_items($pdo) : [];
$pageTitle = 'Meni sajta';
$pageDescription = 'Odredite koje stranice posetioci vide u glavnom meniju.';
$activeNav = 'navigation';
require __DIR__ . '/layout-start.php';
?>
<section class="form-card">
    <div class="actions" style="justify-content: space-between; margin-bottom: 16px;">
        <div>
            <h2 style="margin-bottom: 4px;"><?php echo $editingItem ? 'Izmenite stavku menija' : 'Dodajte stavku menija'; ?></h2>
            <p class="muted" style="margin: 0;">Napravite novu stranicu sa URL adresom ili povežite stavku sa postojećim sadržajem.</p>
        </div>
    </div>

    <?php if ($migrationMissing): ?>
        <p class="muted">Prvo uvezite <code>database/migrations/20261106_full_cms.sql</code>.</p>
    <?php else: ?>
        <form method="post" data-guard-unsaved data-navigation-form>
            <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
            <section class="form-section">
                <div class="form-section-heading">
                    <span class="step-number" aria-hidden="true">1</span>
                    <div>
                        <h3>Osnovni podaci</h3>
                        <p>Unesite naziv, položaj i redosled stavke u meniju.</p>
                    </div>
                </div>
                <div class="form-grid three">
                    <label>
                        Naziv u meniju
                        <input type="text" name="label" value="<?php echo site_escape((string) ($form['label'] ?? '')); ?>" autocomplete="off" required data-menu-label>
                    </label>
                    <label>
                        Prikaži ispod stavke
                        <select name="parent_id">
                            <option value="">Prikaži u glavnom meniju</option>
                            <?php foreach ($navigationItems as $item): ?>
                                <?php if ($editingItem && (int) $editingItem['id'] === (int) $item['id']) { continue; } ?>
                                <option value="<?php echo (int) $item['id']; ?>" <?php echo (string) ($form['parent_id'] ?? '') === (string) $item['id'] ? 'selected' : ''; ?>>
                                    <?php echo site_escape($item['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-help">Ovde povezujete stavku kao podmeni („tab“) izabrane grupe.</span>
                    </label>
                    <label>
                        Redosled (manji broj ide prvi)
                        <input type="number" name="sort_order" value="<?php echo site_escape((string) ($form['sort_order'] ?? 10)); ?>">
                    </label>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-heading">
                    <span class="step-number" aria-hidden="true">2</span>
                    <div>
                        <h3>Odredište stavke</h3>
                        <p>Izaberite šta se otvara klikom. Ovo ne menja grupu izabranu u koraku 1.</p>
                    </div>
                </div>
                <label>
                    Šta želite da napravite?
                    <select name="destination_type" data-destination-type>
                        <option value="new_page" <?php echo ($form['destination_type'] ?? '') === 'new_page' ? 'selected' : ''; ?>>Novu stranicu sa URL adresom</option>
                        <option value="existing_page" <?php echo ($form['destination_type'] ?? '') === 'existing_page' ? 'selected' : ''; ?>>Link ka postojećoj stranici</option>
                        <option value="custom_url" <?php echo ($form['destination_type'] ?? '') === 'custom_url' ? 'selected' : ''; ?>>Drugu adresu ili dokument</option>
                        <option value="group" <?php echo ($form['destination_type'] ?? '') === 'group' ? 'selected' : ''; ?>>Samo naslov grupe — bez stranice i linka</option>
                    </select>
                    <span class="field-help">Za novu stavku sa sopstvenim sadržajem izaberite „Novu stranicu sa URL adresom“.</span>
                </label>

                <div class="form-grid two" style="margin-top: 14px;" data-destination-panel="new_page">
                    <label>
                        Naslov nove stranice
                        <input type="text" name="new_page_title" value="<?php echo site_escape((string) ($form['new_page_title'] ?? '')); ?>" placeholder="Ako ostavite prazno, koristi se naziv iz menija…" autocomplete="off" data-new-page-title>
                    </label>
                    <label>
                        URL adresa nove stranice
                        <input type="text" name="new_page_slug" value="<?php echo site_escape((string) ($form['new_page_slug'] ?? '')); ?>" placeholder="raspored-casova-2026-27…" autocomplete="off" spellcheck="false" data-new-page-slug>
                        <span class="field-help">Pregled: <code data-slug-preview aria-live="polite">/nova-stranica</code>. Ako ostavite prazno, URL se pravi automatski.</span>
                    </label>
                </div>

                <div style="margin-top: 14px;" data-destination-panel="existing_page" hidden>
                    <label>
                        Otvori postojeću stranicu
                        <select name="page_id" data-existing-page>
                            <option value="">Izaberite stranicu…</option>
                            <?php foreach ($pages as $page): ?>
                                <option value="<?php echo (int) $page['id']; ?>" <?php echo (string) ($form['page_id'] ?? '') === (string) $page['id'] ? 'selected' : ''; ?>>
                                    <?php echo site_escape($page['title'] . ' (' . site_route_to_url($page['route_path']) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div class="form-grid two" style="margin-top: 14px;" data-destination-panel="custom_url" hidden>
                    <label>
                        Druga adresa ili putanja do dokumenta
                        <input type="text" name="custom_url" value="<?php echo site_escape((string) ($form['custom_url'] ?? '')); ?>" placeholder="https://primer.rs ili files/dokument.pdf…" autocomplete="off" data-custom-url>
                    </label>
                    <label class="checkbox-row" style="align-self: end; margin-bottom: 12px;">
                        <input type="checkbox" name="open_in_new_tab" value="1" <?php echo !empty($form['open_in_new_tab']) ? 'checked' : ''; ?>>
                        Otvori drugu adresu u novom tabu
                    </label>
                </div>

                <p class="muted" style="margin: 14px 0 0;" data-destination-panel="group" hidden>
                    Ova stavka neće imati svoju URL adresu. Koristi se samo kao naslov iznad podstavki.
                </p>
            </section>
            <label class="checkbox-row" style="margin-top: 6px;">
                <input type="checkbox" name="is_published" value="1" <?php echo !empty($form['is_published']) ? 'checked' : ''; ?>>
                Prikaži ovu stavku u meniju
            </label>
            <div class="actions" style="margin-top: 20px;">
                <button type="submit"><?php echo $editingItem ? 'Sačuvaj izmene' : 'Dodaj stavku'; ?></button>
                <?php if ($editingItem): ?>
                    <a class="button button-secondary" href="navigation.php">Nova stavka</a>
                <?php endif; ?>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php if (!$migrationMissing): ?>
    <section class="table-card">
        <h2>Trenutni meni</h2>
        <p class="section-note">Podmeni je označen nazivom glavne stavke ispod koje se prikazuje.</p>
        <?php if (!$navigationItems): ?>
            <div class="empty-state">
                <h3>Meni još nema stavke</h3>
                <p>Dodajte prvu stavku pomoću formulara iznad.</p>
            </div>
        <?php else: ?>
            <div class="navigation-card-list">
                <?php foreach ($navigationItems as $item): ?>
                    <?php
                    $hasChildItems = false;
                    foreach ($navigationItems as $possibleChild) {
                        if ((int) ($possibleChild['parent_id'] ?? 0) === (int) $item['id']) {
                            $hasChildItems = true;
                            break;
                        }
                    }
                    ?>
                    <article class="navigation-manage-card <?php echo !empty($item['parent_id']) ? 'is-child' : ''; ?>">
                        <div class="navigation-card-main">
                            <div class="navigation-card-heading">
                                <div>
                                    <h3><?php echo site_escape($item['label']); ?></h3>
                                    <?php if (!empty($item['parent_label'])): ?>
                                        <span class="badge">Podmeni: <?php echo site_escape($item['parent_label']); ?></span>
                                    <?php else: ?>
                                        <span class="badge">Glavni meni</span>
                                    <?php endif; ?>
                                </div>
                                <span class="status-pill <?php echo !empty($item['is_published']) ? 'is-live' : ''; ?>">
                                    <?php echo !empty($item['is_published']) ? 'Prikazano' : 'Skriveno'; ?>
                                </span>
                            </div>
                            <p class="navigation-card-destination">
                                <?php if (!empty($item['route_path'])): ?>
                                    Otvara stranicu <code>/<?php echo site_escape(site_route_to_url($item['route_path'])); ?></code>
                                <?php elseif (!empty($item['custom_url'])): ?>
                                    Otvara adresu <code><?php echo site_escape($item['custom_url']); ?></code>
                                <?php else: ?>
                                    Naslov grupe bez linka
                                <?php endif; ?>
                                <span> · Redosled: <?php echo (int) $item['sort_order']; ?></span>
                            </p>
                        </div>
                        <div class="navigation-card-actions">
                            <a class="button" href="navigation.php?id=<?php echo (int) $item['id']; ?>">Izmeni stavku</a>
                            <?php if (!empty($item['page_id'])): ?>
                                <a class="button button-secondary" href="page-content-edit.php?page_id=<?php echo (int) $item['page_id']; ?>">Uredi stranicu</a>
                                <?php if (empty($item['page_is_system'])): ?>
                                    <a class="button button-ghost" href="pages.php?edit_id=<?php echo (int) $item['page_id']; ?>#page-form">Promeni URL</a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <form method="post" onsubmit="return confirm('<?php echo $hasChildItems ? 'Ukloniti ovu grupu i sve njene podstavke iz menija? Stranice i njihov sadržaj neće biti obrisani.' : 'Ukloniti ovu stavku iz menija? Stranica i njen sadržaj neće biti obrisani.'; ?>');">
                                <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                                <button class="button-danger" type="submit">Ukloni iz menija</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
<script>
    (function () {
        var form = document.querySelector('[data-navigation-form]');
        if (!form) {
            return;
        }

        var destination = form.querySelector('[data-destination-type]');
        var panels = form.querySelectorAll('[data-destination-panel]');
        var existingPage = form.querySelector('[data-existing-page]');
        var customUrl = form.querySelector('[data-custom-url]');
        var menuLabel = form.querySelector('[data-menu-label]');
        var pageTitle = form.querySelector('[data-new-page-title]');
        var pageSlug = form.querySelector('[data-new-page-slug]');
        var slugPreview = form.querySelector('[data-slug-preview]');

        function slugify(value) {
            return String(value || '')
                .replace(/[Đđ]/g, function (character) { return character === 'Đ' ? 'D' : 'd'; })
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function updateSlugPreview() {
            if (!slugPreview) {
                return;
            }

            var source = pageSlug.value || pageTitle.value || menuLabel.value;
            slugPreview.textContent = '/' + (slugify(source) || 'nova-stranica');
        }

        function updateDestination() {
            var selectedType = destination.value;

            panels.forEach(function (panel) {
                var isActive = panel.getAttribute('data-destination-panel') === selectedType;
                panel.hidden = !isActive;
                panel.style.display = isActive ? '' : 'none';
            });

            if (existingPage) {
                existingPage.required = selectedType === 'existing_page';
            }
            if (customUrl) {
                customUrl.required = selectedType === 'custom_url';
            }

            updateSlugPreview();
        }

        destination.addEventListener('change', updateDestination);
        [menuLabel, pageTitle, pageSlug].forEach(function (input) {
            if (input) {
                input.addEventListener('input', updateSlugPreview);
            }
        });

        updateDestination();
    }());
</script>
<?php require __DIR__ . '/layout-end.php'; ?>
