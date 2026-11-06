<?php

require_once __DIR__ . '/admin.php';
admin_bootstrap();
admin_require_login();

$pdo = site_db_or_fail();
cms_sync_pages($pdo);

$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = $itemId > 0 ? cms_get_content_item($pdo, $itemId) : null;

if ($itemId > 0 && !$item) {
    admin_flash('error', 'Izabrana stavka sadržaja nije pronađena.');
    admin_redirect('content.php');
}

$pages = cms_list_pages($pdo);
$areaOptions = cms_area_options();
$contentTypeOptions = cms_content_type_options();

$requestedPageId = isset($_GET['page_id']) ? (int) $_GET['page_id'] : 0;
$requestedPageKey = trim((string) ($_GET['page_key'] ?? ''));
$requestedAreaKey = trim((string) ($_GET['area_key'] ?? ''));
$requestedContentType = trim((string) ($_GET['content_type'] ?? ''));

if ($requestedPageId <= 0 && $requestedPageKey !== '') {
    $requestedPage = cms_get_page_by_key($pdo, $requestedPageKey);
    if ($requestedPage) {
        $requestedPageId = (int) $requestedPage['id'];
    }
}

if ($requestedPageId > 0 && !cms_get_page_by_id($pdo, $requestedPageId)) {
    $requestedPageId = 0;
}

$defaultAreaKey = isset($areaOptions[$requestedAreaKey]) ? $requestedAreaKey : 'page-notices';
$defaultContentType = isset($contentTypeOptions[$requestedContentType]) ? $requestedContentType : 'link';

if ($defaultAreaKey === 'page-downloads') {
    $defaultContentType = 'file';
}

if ($defaultAreaKey === 'page-gallery') {
    $defaultContentType = 'image';
}

$form = $item ?: [
    'page_id' => $requestedPageId,
    'area_key' => $defaultAreaKey,
    'content_type' => $defaultContentType,
    'title' => '',
    'summary' => '',
    'body_html' => '',
    'link_url' => '',
    'link_target' => '_blank',
    'file_path' => '',
    'image_path' => '',
    'published_at' => date('Y-m-d H:i:s'),
    'sort_order' => 0,
    'is_published' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        admin_verify_csrf();
        $savedId = cms_save_content_item($pdo, $_POST, $_FILES, admin_current_user()['id'], $itemId ?: null);
        $savedItem = cms_get_content_item($pdo, $savedId);
        admin_flash('success', 'Stavka sadržaja je sačuvana.');

        $redirectQuery = [];
        if (!empty($savedItem['page_id'])) {
            $redirectQuery['page_id'] = (int) $savedItem['page_id'];
        }
        if (!empty($savedItem['area_key'])) {
            $redirectQuery['area_key'] = (string) $savedItem['area_key'];
        }

        $redirectPath = 'content.php';
        if ($redirectQuery) {
            $redirectPath .= '?' . http_build_query($redirectQuery);
        }

        admin_redirect($redirectPath);
    } catch (Throwable $exception) {
        $form = array_merge($form, $_POST);
        admin_flash('error', $exception->getMessage());
    }
}

$selectedPage = !empty($form['page_id']) ? cms_get_page_by_id($pdo, (int) $form['page_id']) : null;
$currentAreaKey = trim((string) ($form['area_key'] ?? ''));
$currentContentType = trim((string) ($form['content_type'] ?? 'link'));

if ($currentAreaKey === 'page-downloads') {
    $currentContentType = 'file';
    $form['content_type'] = 'file';
}

if ($currentAreaKey === 'page-gallery') {
    $currentContentType = 'image';
    $form['content_type'] = 'image';
}

$isDownloadArea = $currentAreaKey === 'page-downloads';
$isGalleryArea = $currentAreaKey === 'page-gallery';
$isSimpleUploadMode = $isDownloadArea || $isGalleryArea;
$isLockedArea = !$itemId && $requestedAreaKey !== '' && isset($areaOptions[$requestedAreaKey]);
$publishedAtValue = '';
$publishedTimestamp = strtotime((string) ($form['published_at'] ?? ''));
if ($publishedTimestamp !== false) {
    $publishedAtValue = date('Y-m-d\TH:i', $publishedTimestamp);
}

$assetUrlResolver = static function ($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (preg_match('~^(?:https?:)?//~i', $value) || preg_match('~^(?:mailto|tel):~i', $value)) {
        return $value;
    }

    return site_url(ltrim($value, '/'));
};

$filePath = trim((string) ($form['file_path'] ?? ''));
$imagePath = trim((string) ($form['image_path'] ?? ''));
$linkUrl = trim((string) ($form['link_url'] ?? ''));
$existingFileUrl = $filePath !== '' ? $assetUrlResolver($filePath) : ($isDownloadArea && $linkUrl !== '' ? $assetUrlResolver($linkUrl) : '');
$existingImageUrl = $imagePath !== '' ? $assetUrlResolver($imagePath) : ($isGalleryArea && $linkUrl !== '' ? $assetUrlResolver($linkUrl) : '');
$attachmentUploadLimit = site_effective_upload_limit(15728640);
$imageUploadLimit = site_effective_upload_limit(10485760);

$pageTitle = $itemId
    ? ($isGalleryArea ? 'Izmeni fotografiju' : ($isDownloadArea ? 'Izmeni dokument' : 'Izmeni sadržaj'))
    : ($isGalleryArea ? 'Dodaj fotografiju' : ($isDownloadArea ? 'Dodaj dokument' : 'Dodaj sadržaj'));
$pageDescription = $isSimpleUploadMode
    ? 'Izaberite stranicu i fajl, unesite naziv i sačuvajte.'
    : ($itemId ? 'Promenite podatke i sačuvajte kada završite.' : 'Popunite kratke korake ispod; prikazana su samo potrebna polja.');
$activeNav = 'content';

require __DIR__ . '/layout-start.php';
?>
<section class="form-card">
    <div class="actions" style="justify-content: space-between; margin-bottom: 18px;">
        <div>
            <h2 style="margin-bottom: 4px;"><?php echo site_escape($pageTitle); ?></h2>
            <p class="muted" style="margin: 0;"><?php echo $isSimpleUploadMode ? 'Tri kratka koraka bez tehničkih podešavanja.' : 'Izaberite gde se prikazuje, unesite naslov i dodajte potreban fajl ili link.'; ?></p>
        </div>
        <div class="actions">
            <a class="button button-secondary" href="content.php<?php echo $selectedPage ? '?page_id=' . (int) $selectedPage['id'] : ''; ?>">Nazad na sadržaj</a>
            <a
                class="button button-secondary"
                href="<?php echo $selectedPage ? 'page-content-edit.php?page_id=' . (int) $selectedPage['id'] : '#'; ?>"
                data-edit-selected-page
                style="<?php echo $selectedPage ? '' : 'display: none;'; ?>"
            >Uredi glavni tekst</a>
        </div>
    </div>

    <div class="card" style="margin-bottom: 20px;">
        <div class="helper-list" style="margin-bottom: 10px;">
            <?php if ($selectedPage): ?>
                <span class="helper-chip" data-selected-page-chip>Stranica: <?php echo site_escape($selectedPage['title']); ?></span>
            <?php else: ?>
                <span class="helper-chip" data-selected-page-chip>Prvo izaberite stranicu</span>
            <?php endif; ?>
            <?php if ($currentAreaKey !== '' && isset($areaOptions[$currentAreaKey])): ?>
                <span class="helper-chip">Prikazuje se u: <?php echo site_escape($areaOptions[$currentAreaKey]); ?></span>
            <?php endif; ?>
            <span class="helper-chip content-mode-chip">
                <?php
                if ($isDownloadArea) {
                    echo 'Dodajete dokument';
                } elseif ($isGalleryArea) {
                    echo 'Dodajete fotografiju';
                } else {
                    echo 'Dodajete objavu ili link';
                }
                ?>
            </span>
        </div>
        <p class="muted content-mode-help" style="margin: 0;">
            <?php
            if ($isDownloadArea) {
                echo 'Izaberite stranicu i dokument. Naziv će se automatski predložiti iz imena fajla.';
            } elseif ($isGalleryArea) {
                echo 'Izaberite stranicu i fotografiju. Naziv će se automatski predložiti iz imena slike.';
            } else {
                echo 'Za opšte kartice i linkove možete dodati naslov, opis, opcionalnu sliku i po potrebi vezu ili fajl.';
            }
            ?>
        </p>
    </div>

    <form method="post" enctype="multipart/form-data" class="<?php echo $isSimpleUploadMode ? 'simple-upload-form' : ''; ?>" data-guard-unsaved>
        <input type="hidden" name="csrf_token" value="<?php echo site_escape(admin_csrf_token()); ?>">
        <div class="form-section">
            <div class="form-section-heading">
                <span class="step-number">1</span>
                <div>
                    <h2><?php echo $isSimpleUploadMode ? 'Izaberite stranicu' : 'Osnovni podaci'; ?></h2>
                    <p><?php echo $isSimpleUploadMode ? 'Odredite na kojoj stranici će se prikazati ovaj sadržaj.' : 'Odredite mesto prikaza i naziv koji će posetioci videti.'; ?></p>
                </div>
            </div>
        <div class="form-grid <?php echo $isSimpleUploadMode ? '' : 'two'; ?>" style="margin-bottom: 14px;">
            <div class="stacked-field">
                <label for="content-page-search">Pronađite stranicu</label>
                <input id="content-page-search" type="search" data-page-search-target="content-page-id" data-ignore-unsaved placeholder="Kucajte naziv stranice…" autocomplete="off">
                <label for="content-page-id">Izabrana stranica</label>
                <select id="content-page-id" name="page_id" required>
                    <option value="">Izaberite stranicu…</option>
                    <?php foreach ($pages as $page): ?>
                        <option value="<?php echo (int) $page['id']; ?>" <?php echo (string) ($form['page_id'] ?? '') === (string) $page['id'] ? 'selected' : ''; ?>>
                            <?php echo site_escape($page['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="field-help"><?php echo $isGalleryArea ? 'Fotografija će se prikazati u galeriji izabrane stranice.' : ($isDownloadArea ? 'Dokument će se prikazati među fajlovima izabrane stranice.' : 'Možete izabrati bilo koju postojeću stranicu.'); ?></span>
            </div>

            <?php if ($isSimpleUploadMode): ?>
                <input type="hidden" id="content-area-key" name="area_key" value="<?php echo site_escape($currentAreaKey); ?>">
                <input type="hidden" id="content-type" name="content_type" value="<?php echo site_escape($currentContentType); ?>">
            <?php else: ?>
                <label>
                    Mesto na stranici
                    <?php if ($isLockedArea && isset($areaOptions[$requestedAreaKey])): ?>
                        <input type="hidden" id="content-area-key" name="area_key" value="<?php echo site_escape($requestedAreaKey); ?>">
                        <input type="text" value="<?php echo site_escape($areaOptions[$requestedAreaKey]); ?>" readonly>
                    <?php else: ?>
                        <select id="content-area-key" name="area_key" required>
                            <?php foreach ($areaOptions as $key => $label): ?>
                                <option value="<?php echo site_escape($key); ?>" <?php echo (string) ($form['area_key'] ?? '') === (string) $key ? 'selected' : ''; ?>>
                                    <?php echo site_escape($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
        </div>

        <?php if (!$isSimpleUploadMode): ?>
        <div class="form-grid two" style="margin-bottom: 14px;">
            <label data-field-group="content-type">
                Šta dodajete?
                <select id="content-type" name="content_type">
                    <?php foreach ($contentTypeOptions as $typeKey => $typeLabel): ?>
                        <option value="<?php echo site_escape($typeKey); ?>" <?php echo $currentContentType === $typeKey ? 'selected' : ''; ?>>
                            <?php echo site_escape($typeLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Naziv za prikaz
                <input
                    id="content-title"
                    type="text"
                    name="title"
                    value="<?php echo site_escape((string) ($form['title'] ?? '')); ?>"
                    required
                    placeholder="<?php echo $isDownloadArea ? 'Na primer: Izveštaj 1. polugodište' : ($isGalleryArea ? 'Na primer: Dan škole 2026' : 'Naslov stavke'); ?>"
                >
            </label>
        </div>

        <label style="margin-bottom: 14px;">
            Kratak opis ili sažetak
            <textarea name="summary" rows="4" placeholder="<?php echo $isGalleryArea ? 'Kratak opis fotografije ili albuma…' : 'Opciono: kratak opis koji će se prikazati uz stavku…'; ?>"><?php echo site_escape((string) ($form['summary'] ?? '')); ?></textarea>
        </label>
        <?php endif; ?>
        </div>

        <div class="form-section">
            <div class="form-section-heading">
                <span class="step-number">2</span>
                <div>
                    <h2><?php echo $isGalleryArea ? 'Izaberite fotografiju' : ($isDownloadArea ? 'Izaberite dokument' : 'Fajl, fotografija ili link'); ?></h2>
                    <p><?php echo $isSimpleUploadMode ? 'Kliknite na polje ispod i izaberite fajl sa računara.' : 'Izaberite ono što odgovara ovoj stavci. Postojeći fajl ostaje sačuvan ako nova otprema ne uspe.'; ?></p>
                </div>
            </div>

        <?php if (!$isSimpleUploadMode): ?>
        <label data-field-group="body-html" style="margin-bottom: 14px;">
            Dodatni HTML sadržaj
            <textarea name="body_html" data-editor="rich"><?php echo site_escape((string) ($form['body_html'] ?? '')); ?></textarea>
        </label>
        <?php endif; ?>

        <div class="form-grid <?php echo $isSimpleUploadMode ? '' : 'two'; ?>" style="margin-bottom: 14px;">
            <?php if (!$isGalleryArea): ?>
            <div class="upload-field" data-field-group="attachment" data-upload-control data-max-bytes="<?php echo (int) $attachmentUploadLimit; ?>">
                <label class="upload-zone">
                    <strong>Kliknite ovde da izaberete dokument</strong>
                    <span class="upload-help">PDF, Word, Excel, PowerPoint, tekst ili ZIP · najviše <?php echo site_escape(site_format_bytes($attachmentUploadLimit)); ?></span>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.zip,.rtf,.odt,.ods,.odp">
                </label>
                <div class="upload-feedback" data-upload-feedback aria-live="polite">Nijedan novi fajl nije izabran.</div>
            </div>
            <?php endif; ?>

            <?php if (!$isDownloadArea): ?>
            <div class="upload-field" data-field-group="image" data-upload-control data-max-bytes="<?php echo (int) $imageUploadLimit; ?>">
                <label class="upload-zone">
                    <strong>Kliknite ovde da izaberete fotografiju</strong>
                    <span class="upload-help">JPG, PNG, GIF ili WEBP · najviše <?php echo site_escape(site_format_bytes($imageUploadLimit)); ?></span>
                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
                </label>
                <div class="upload-feedback" data-upload-feedback aria-live="polite">Nijedna nova fotografija nije izabrana.</div>
                <img class="upload-preview" data-upload-preview alt="Pregled izabrane fotografije" width="320" height="200">
            </div>
            <?php endif; ?>
        </div>

        <?php if (!$isSimpleUploadMode): ?>
        <div class="form-grid two" style="margin-bottom: 14px;">
            <label data-field-group="link-url">
                Link ili postojeća putanja
                <input
                    type="text"
                    name="link_url"
                    value="<?php echo site_escape($linkUrl); ?>"
                    placeholder="https://primer.rs ili files/dokument.pdf…"
                >
                <span class="muted">Ovo koristite samo kada stavka vodi na drugu stranicu ili na postojeći dokument koji nije otpremljen kroz CMS.</span>
            </label>

            <label data-field-group="link-target">
                Otvaranje linka
                <select name="link_target">
                    <option value="_blank" <?php echo (string) ($form['link_target'] ?? '_blank') === '_blank' ? 'selected' : ''; ?>>Novi tab</option>
                    <option value="_self" <?php echo (string) ($form['link_target'] ?? '_blank') === '_self' ? 'selected' : ''; ?>>Isti tab</option>
                </select>
            </label>
        </div>
        <?php endif; ?>

        <?php if ($existingFileUrl !== ''): ?>
            <div class="current-asset" data-field-group="existing-file" style="margin-bottom: 14px;">
                <div class="actions" style="justify-content: space-between; align-items: flex-start;">
                    <div>
                        <strong>Trenutno vezan dokument</strong>
                        <div class="muted" style="margin-top: 6px;">
                            <?php echo site_escape($filePath !== '' ? $filePath : $linkUrl); ?>
                        </div>
                    </div>
                    <a class="button button-secondary" href="<?php echo site_escape($existingFileUrl); ?>" target="_blank" rel="noopener">Otvori fajl</a>
                </div>
                <?php if ($filePath !== ''): ?>
                    <label class="checkbox-row" style="margin-top: 12px;">
                        <input type="checkbox" name="remove_file" value="1">
                        Ukloni trenutni dokument
                    </label>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($existingImageUrl !== ''): ?>
            <div class="current-asset" data-field-group="existing-image" style="margin-bottom: 14px;">
                <div>
                    <div>
                        <strong>Trenutna slika</strong>
                        <div class="muted" style="margin-top: 6px;">
                            <?php echo site_escape($imagePath !== '' ? $imagePath : $linkUrl); ?>
                        </div>
                    </div>
                    <a class="button button-secondary" href="<?php echo site_escape($existingImageUrl); ?>" target="_blank" rel="noopener" style="margin-top: 12px;">Otvori sliku</a>
                <?php if ($imagePath !== ''): ?>
                    <label class="checkbox-row" style="margin-top: 12px;">
                        <input type="checkbox" name="remove_image" value="1">
                        Ukloni trenutnu fotografiju
                    </label>
                <?php endif; ?>
                </div>
                <img src="<?php echo site_escape($existingImageUrl); ?>" alt="Trenutna slika za ovu stavku" width="320" height="200">
            </div>
        <?php endif; ?>

        </div>

        <?php if ($isSimpleUploadMode): ?>
            <section class="form-section">
                <div class="form-section-heading">
                    <span class="step-number">3</span>
                    <div>
                        <h2>Naziv i čuvanje</h2>
                        <p>Proverite predloženi naziv i po želji dodajte kratak opis.</p>
                    </div>
                </div>
                <label>
                    <?php echo $isGalleryArea ? 'Naziv fotografije ili albuma' : 'Naziv dokumenta'; ?>
                    <input
                        id="content-title"
                        type="text"
                        name="title"
                        value="<?php echo site_escape((string) ($form['title'] ?? '')); ?>"
                        required
                        placeholder="<?php echo $isGalleryArea ? 'Na primer: Dan škole 2026' : 'Na primer: Izveštaj za 1. polugodište'; ?>"
                    >
                    <span class="field-help">Naziv se može automatski popuniti nakon izbora fajla.</span>
                </label>
                <label style="margin-top: 14px;">
                    Kratak opis <span class="field-help">(nije obavezno)</span>
                    <textarea name="summary" rows="3" placeholder="<?php echo $isGalleryArea ? 'Kratak opis fotografije ili albuma…' : 'Kratko objašnjenje dokumenta…'; ?>"><?php echo site_escape((string) ($form['summary'] ?? '')); ?></textarea>
                </label>
                <label class="publish-control" style="margin-top: 14px;">
                    <input type="checkbox" name="is_published" value="1" <?php echo !empty($form['is_published']) ? 'checked' : ''; ?>>
                    <span>
                        <strong>Odmah prikaži posetiocima</strong>
                        <small>Isključite ako želite da sadržaj ostane sačuvan kao skica.</small>
                    </span>
                </label>
            </section>
        <?php endif; ?>

        <details class="advanced-panel">
            <summary>Dodatna podešavanja</summary>
            <div class="advanced-panel-content">
        <div class="form-grid <?php echo $isSimpleUploadMode ? 'two' : 'three'; ?>">
            <label>
                Redosled prikaza (manji broj ide prvi)
                <input type="number" name="sort_order" value="<?php echo site_escape((string) ($form['sort_order'] ?? '0')); ?>">
            </label>
            <label>
                Datum objave
                <input type="datetime-local" name="published_at" value="<?php echo site_escape($publishedAtValue); ?>">
            </label>
            <?php if (!$isSimpleUploadMode): ?>
                <label class="checkbox-row" style="align-self: end; margin-top: 24px;">
                    <input type="checkbox" name="is_published" value="1" <?php echo !empty($form['is_published']) ? 'checked' : ''; ?>>
                    Objavljeno
                </label>
            <?php endif; ?>
        </div>
            </div>
        </details>

        <div class="page-editor-savebar content-upload-savebar">
            <span class="muted"><?php echo $isSimpleUploadMode ? 'Proverite stranicu, fajl i naziv pre čuvanja.' : 'Kada završite, sačuvajte sadržaj.'; ?></span>
            <div class="actions">
                <a class="button button-secondary" href="content.php<?php echo $selectedPage ? '?page_id=' . (int) $selectedPage['id'] : ''; ?>">Otkaži</a>
                <button type="submit">
                    <?php
                    if ($itemId) {
                        echo 'Sačuvaj izmene';
                    } elseif ($isGalleryArea) {
                        echo 'Sačuvaj fotografiju';
                    } elseif ($isDownloadArea) {
                        echo 'Sačuvaj dokument';
                    } else {
                        echo 'Objavi sadržaj';
                    }
                    ?>
                </button>
            </div>
        </div>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var areaSelect = document.getElementById('content-area-key');
    var pageSelect = document.getElementById('content-page-id');
    var typeSelect = document.getElementById('content-type');
    var pageChip = document.querySelector('[data-selected-page-chip]');
    var editPageLink = document.querySelector('[data-edit-selected-page]');
    var modeChip = document.querySelector('.content-mode-chip');
    var modeHelp = document.querySelector('.content-mode-help');
    var titleInput = document.getElementById('content-title');
    var imageInput = document.querySelector('input[name="image"]');
    var attachmentInput = document.querySelector('input[name="attachment"]');
    var hasExistingImage = <?php echo $imagePath !== '' ? 'true' : 'false'; ?>;
    var hasExistingFile = <?php echo $filePath !== '' ? 'true' : 'false'; ?>;

    if (!areaSelect || !typeSelect) {
        return;
    }

    var groups = {
        contentType: document.querySelector('[data-field-group="content-type"]'),
        bodyHtml: document.querySelector('[data-field-group="body-html"]'),
        attachment: document.querySelector('[data-field-group="attachment"]'),
        image: document.querySelector('[data-field-group="image"]'),
        linkUrl: document.querySelector('[data-field-group="link-url"]'),
        linkTarget: document.querySelector('[data-field-group="link-target"]'),
        existingFile: document.querySelector('[data-field-group="existing-file"]'),
        existingImage: document.querySelector('[data-field-group="existing-image"]')
    };

    function setVisible(element, shouldShow) {
        if (!element) {
            return;
        }

        element.style.display = shouldShow ? '' : 'none';
        element.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
    }

    function syncSelectedPage() {
        if (!pageSelect) {
            return;
        }

        var selectedOption = pageSelect.options[pageSelect.selectedIndex];
        var pageId = pageSelect.value;
        var pageTitle = pageId && selectedOption ? selectedOption.textContent.trim() : '';

        if (pageChip) {
            pageChip.textContent = pageTitle ? 'Stranica: ' + pageTitle : 'Prvo izaberite stranicu';
        }

        if (editPageLink) {
            editPageLink.href = pageId ? 'page-content-edit.php?page_id=' + encodeURIComponent(pageId) : '#';
            editPageLink.style.display = pageId ? '' : 'none';
        }
    }

    function syncFormMode() {
        var area = areaSelect.value || '';
        var type = typeSelect.value || 'link';
        var isDownloadArea = area === 'page-downloads';
        var isGalleryArea = area === 'page-gallery';
        var isHtmlType = type === 'html';

        if (isDownloadArea) {
            typeSelect.value = 'file';
            type = 'file';
        }

        if (isGalleryArea) {
            typeSelect.value = 'image';
            type = 'image';
        }

        setVisible(groups.contentType, !isDownloadArea && !isGalleryArea);
        setVisible(groups.bodyHtml, !isDownloadArea && !isGalleryArea && isHtmlType);
        setVisible(groups.attachment, !isGalleryArea);
        setVisible(groups.image, !isDownloadArea);
        setVisible(groups.linkUrl, !isDownloadArea && !isGalleryArea);
        setVisible(groups.linkTarget, !isDownloadArea && !isGalleryArea);
        setVisible(groups.existingFile, !!groups.existingFile && !isGalleryArea);
        setVisible(groups.existingImage, !!groups.existingImage && !isDownloadArea);

        if (imageInput) {
            imageInput.required = isGalleryArea && !hasExistingImage;
        }

        if (attachmentInput) {
            attachmentInput.required = isDownloadArea && !hasExistingFile;
        }

        if (modeChip) {
            if (isDownloadArea) {
                modeChip.textContent = 'Dodajete dokument';
            } else if (isGalleryArea) {
                modeChip.textContent = 'Dodajete fotografiju';
            } else {
                modeChip.textContent = 'Dodajete objavu ili link';
            }
        }

        if (modeHelp) {
            if (isDownloadArea) {
                modeHelp.textContent = 'Unesite naziv koji korisnik vidi, zatim izaberite dokument. Redosled možete promeniti u dodatnim podešavanjima.';
            } else if (isGalleryArea) {
                modeHelp.textContent = 'Izaberite fotografiju, dodajte jasan naziv i po želji kratak opis.';
            } else if (type === 'html') {
                modeHelp.textContent = 'Za običnu objavu dovoljan je naslov i kratak opis. Po želji možete dodati fotografiju ili duži tekst.';
            } else {
                modeHelp.textContent = 'Dodajte naslov, kratak opis i adresu linka. Fotografija i fajl su opcionalni.';
            }
        }

        if (titleInput) {
            if (isDownloadArea) {
                titleInput.placeholder = 'Na primer: Izveštaj za 1. polugodište…';
            } else if (isGalleryArea) {
                titleInput.placeholder = 'Na primer: Dan škole 2026…';
            } else {
                titleInput.placeholder = 'Unesite naslov…';
            }
        }
    }

    function suggestTitleFromFile(input) {
        if (!input || !titleInput) {
            return;
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file || titleInput.value.trim() !== '') {
                return;
            }

            var suggestedTitle = file.name
                .replace(/\.[^.]+$/, '')
                .replace(/[_-]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            if (suggestedTitle !== '') {
                titleInput.value = suggestedTitle.charAt(0).toLocaleUpperCase('sr') + suggestedTitle.slice(1);
            }
        });
    }

    areaSelect.addEventListener('change', syncFormMode);
    typeSelect.addEventListener('change', syncFormMode);
    suggestTitleFromFile(imageInput);
    suggestTitleFromFile(attachmentInput);
    if (pageSelect) {
        pageSelect.addEventListener('change', syncSelectedPage);
    }
    syncSelectedPage();
    syncFormMode();
});
</script>
<?php require __DIR__ . '/layout-end.php'; ?>
