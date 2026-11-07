<?php

function cms_page_content_default_stylesheets()
{
    return [
        'stylesheets/screen.css',
        'css/tables.css',
        'css/font-awesome.min.css',
        'stylesheets/cms.css',
    ];
}

function cms_page_content_is_manageable($pageKey)
{
    $pageKey = trim((string) $pageKey);

    if ($pageKey === '' || strpos($pageKey, '_') === 0) {
        return false;
    }

    return !in_array($pageKey, ['contact', 'page', 'phpinfo'], true);
}

function cms_homepage_default_content_html()
{
    return <<<'HTML'
<section id="content">
    {{homepage-slider}}
    <div class="container">
        <div class="container mar_t_-56">
            <div class="row">
                <div class="grid_4">
                    <div class="banner_1">
                        <div class="pad_1">
                            <h2>О школи</h2>
                            <p>
                                <a href="o-skoli">Друга основна школа</a><br><br>
                                Наша школа основана је 11. септембра 1951. године као Друга основна школа у Ваљеву. Данас ради у Сувоборској улици и носи име Владике Николаја Велимировића.
                            </p>
                            <a href="o-skoli" class="btn-default btn_1">Прочитај више</a>
                        </div>
                    </div>
                </div>
                <div class="grid_4">
                    <div class="banner_2">
                        <div class="pad_1">
                            <h2>Биографија</h2>
                            <p>
                                <a href="biogragfija-vladike-nikolaja">Владика Николај</a><br><br>
                                Николај Велимировић рођен је 23. децембра 1880. године у Лелићу код Ваљева. Његов живот, дело и духовно наслеђе трајно су везани за идентитет школе.
                            </p>
                            <a href="biogragfija-vladike-nikolaja" class="btn-default btn_1">Прочитај више</a>
                        </div>
                    </div>
                </div>
                <div class="grid_4">
                    <div class="banner_3">
                        <div class="pad_1">
                            <h2>Сећања</h2>
                            <p>
                                <a href="secanja">Миодраг Лома</a><br><br>
                                „Моја прва, дакле моја основна школа, остала је за мене увек поуздан темељ знања о свету и о човеку, на коме се даље могло градити.”
                            </p>
                            <a href="secanja" class="btn-default btn_1">Прочитај више</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<div class="container">
    {{homepage-widgets}}
    <img src="images/upis_u_prvi_2025.jpg" alt="Упис у први разред" style="width: 100%">
</div>
HTML;
}

function cms_letopisi_default_content_html()
{
    return <<<'HTML'
<section class="pad_t_65 page4" id="content">
    <div class="container">
        <div class="row">
            <div class="grid_12 mar_t_3">
                <h3 class="s4">Летописи</h3>
                {{letopisi-filter-label}}
            </div>
        </div>
        <div class="row">
            <div class="grid_12">
                <div class="grid_9">
                    {{letopisi-timeline}}
                </div>
            </div>
        </div>
    </div>
</section>
HTML;
}

function cms_special_page_payload($pageKey)
{
    return null;
}

function cms_page_source_to_utf8($value)
{
    $value = (string) $value;

    for ($attempt = 0; $attempt < 2; $attempt++) {
        if (!preg_match('/Ð|Ñ|Ã|â€|Â|Å/u', $value)) {
            break;
        }

        $candidate = @iconv('UTF-8', 'Windows-1252//IGNORE', $value);
        if ($candidate === false || $candidate === '' || $candidate === $value) {
            break;
        }

        if (!preg_match('//u', $candidate)) {
            break;
        }

        $value = $candidate;
    }

    return $value;
}

function cms_read_source_file($filePath)
{
    $filePath = (string) $filePath;

    if ($filePath === '' || !is_file($filePath)) {
        return null;
    }

    $contents = file_get_contents($filePath);
    if ($contents === false) {
        return null;
    }

    return cms_page_source_to_utf8($contents);
}

function cms_expand_source_partials($source, $currentDirectory)
{
    return preg_replace_callback(
        '/<\?php\s+include(?:_once)?\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*;?\s*\?>/i',
        static function ($matches) use ($currentDirectory) {
            $relativePath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, (string) $matches[1]);
            $includePath = realpath($currentDirectory . DIRECTORY_SEPARATOR . $relativePath);

            if ($includePath === false || !is_file($includePath)) {
                return '';
            }

            $basename = basename($includePath);
            if ($basename === 'aside.php') {
                return '';
            }

            if (strpos($basename, '_') !== 0) {
                return '';
            }

            return cms_read_source_file($includePath) ?: '';
        },
        (string) $source
    );
}

function cms_expand_legacy_excel_calls($source)
{
    $source = (string) $source;
    if (stripos($source, 'call_excel') === false) {
        return $source;
    }

    $readerPath = site_root_path() . '/exsel/spreadsheet-reader-master/SpreadsheetReader.php';
    $legacyReaderPath = site_root_path() . '/exsel/spreadsheet-reader-master/php-excel-reader/excel_reader2.php';
    $functionPath = site_root_path() . '/exsel/function.php';

    if (!is_file($readerPath) || !is_file($legacyReaderPath) || !is_file($functionPath)) {
        return $source;
    }

    $previousErrorReporting = error_reporting();
    error_reporting($previousErrorReporting & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);

    try {
        require_once $legacyReaderPath;
        require_once $readerPath;
        require_once $functionPath;

        $expandedSource = preg_replace_callback(
            '/<\?php\s*call_excel\s*\(\s*[\'\"]([^\'\"]+)[\'\"]\s*\)\s*;?\s*\?>/i',
            static function ($matches) {
                $relativePath = str_replace('\\', '/', trim((string) ($matches[1] ?? '')));
                if ($relativePath === '' || strpos($relativePath, '..') !== false) {
                    return '';
                }

                $previousDirectory = getcwd();
                ob_start();

                try {
                    chdir(site_root_path());
                    call_excel($relativePath);
                    $output = (string) ob_get_clean();
                } catch (Throwable $exception) {
                    ob_end_clean();
                    $output = '';
                } finally {
                    if ($previousDirectory !== false) {
                        chdir($previousDirectory);
                    }
                }

                return cms_page_source_to_utf8($output);
            },
            $source
        );
    } finally {
        error_reporting($previousErrorReporting);
    }

    return (string) $expandedSource;
}

function cms_extract_extra_head_html_from_source($source)
{
    $beforeBody = (string) $source;
    if (preg_match('/^(.*?)(<body\b.*)$/is', (string) $source, $matches)) {
        $beforeBody = $matches[1];
    }

    $defaults = cms_page_content_default_stylesheets();
    $extras = [];

    if (preg_match_all('/<link\b[^>]*href=["\']([^"\']+)["\'][^>]*>/is', $beforeBody, $links, PREG_SET_ORDER)) {
        foreach ($links as $link) {
            $href = trim((string) $link[1]);

            if ($href === '' || in_array($href, $defaults, true) || strpos($href, 'fonts.googleapis.com') !== false) {
                continue;
            }

            $extras[] = trim((string) $link[0]);
        }
    }

    if (preg_match_all('/<style\b[^>]*>.*?<\/style>/is', $beforeBody, $styles)) {
        foreach ($styles[0] as $style) {
            $extras[] = trim((string) $style);
        }
    }

    if (preg_match_all('/<meta\b[^>]*http-equiv=["\']X-UA-Compatible["\'][^>]*>/is', $beforeBody, $metaTags)) {
        foreach ($metaTags[0] as $metaTag) {
            $extras[] = trim((string) $metaTag);
        }
    }

    $extras = array_values(array_unique(array_filter($extras)));

    return $extras ? implode("\n", $extras) : null;
}

function cms_extract_extra_footer_html_from_source($source)
{
    $extras = [];

    if (!preg_match_all('/<script\b[^>]*>.*?<\/script>/is', (string) $source, $scripts)) {
        return null;
    }

    foreach ($scripts[0] as $script) {
        $script = trim((string) $script);

        if ($script === '') {
            continue;
        }

        if (strpos($script, 'ajax.googleapis.com/ajax/libs/jquery') !== false) {
            continue;
        }

        if (strpos($script, 'js/script.js') !== false) {
            continue;
        }

        if (strpos($script, 'js/weather/jquery.simpleWeather.min.js') !== false) {
            continue;
        }

        if (strpos($script, 'simpleWeather(') !== false) {
            continue;
        }

        if (strpos($script, 'html5shiv.min.js') !== false || strpos($script, 'respond.min.js') !== false) {
            continue;
        }

        $extras[] = $script;
    }

    $extras = array_values(array_unique(array_filter($extras)));

    return $extras ? implode("\n", $extras) : null;
}

function cms_extract_body_wrapper_html_from_source($source)
{
    if (!preg_match('/<body\b[^>]*>(.*?)<a class="fa fa-arrow-circle-up"/is', (string) $source, $matches)) {
        return null;
    }

    $contentHtml = trim((string) $matches[1]);
    $contentHtml = preg_replace('/<\?php\s+include(?:_once)?\s*\(\s*[\'"]includes\/header\.php[\'"]\s*\)\s*;?\s*\?>/i', '', $contentHtml);
    $contentHtml = preg_replace('/<\?php\s+include(?:_once)?\s*\(\s*[\'"]includes\/aside\.php[\'"]\s*\)\s*;?\s*\?>/i', '', $contentHtml);

    if (preg_match('/<\?php/i', $contentHtml)) {
        return null;
    }

    $contentHtml = trim($contentHtml);

    return $contentHtml !== '' ? $contentHtml : null;
}

function cms_extract_schedule_page_payload_from_source($source, $browserTitle, $metaDescription, $extraHeadHtml, $extraFooterHtml)
{
    if (!preg_match_all('/\[\s*"([^"]+)"\s*,\s*"([^"]+)"\s*\]/u', (string) $source, $matches, PREG_SET_ORDER)) {
        return null;
    }

    $linksHtml = [];
    foreach ($matches as $match) {
        $label = trim((string) $match[1]);
        $fileName = trim((string) $match[2]);

        if ($label === '' || $fileName === '') {
            continue;
        }

        $url = 'files/ucenici/raspored-casova/2024/' . rawurlencode($fileName);
        $url = str_replace('%2F', '/', $url);
        $url = str_replace('%20', ' ', $url);
        $linksHtml[] = sprintf(
            "- [.fajl] - <a href=\"%s\" target=\"_blank\">%s</a><br/>",
            site_escape($url),
            site_escape($label)
        );
    }

    if (!$linksHtml) {
        return null;
    }

    $contentHtml = <<<HTML
<section class="pad_t_65 page4" id="content">
    <div class="container">
        <div class="row">
            <div class="grid_12 mar_t_3">
                <h3 class="no-pad">Raspored časova 2024</h3>
            </div>
        </div>
        <div class="row">
            <div class="grid_12">
                <div class="grid_8 nmr">
                    <div class="grid_8">
                        <p class="font-post pad_t_2">
                            %s
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
HTML;

    return [
        'browser_title' => $browserTitle,
        'meta_description' => $metaDescription !== '' ? $metaDescription : null,
        'content_html' => sprintf($contentHtml, implode("\n", $linksHtml)),
        'extra_head_html' => $extraHeadHtml,
        'extra_footer_html' => $extraFooterHtml,
        'source_hash' => sha1($browserTitle . "\n" . implode("\n", $linksHtml) . "\n" . (string) $extraHeadHtml . "\n" . (string) $extraFooterHtml),
    ];
}

function cms_extract_page_payload_from_source($sourceFilePath)
{
    $sourceFilePath = (string) $sourceFilePath;
    $source = cms_read_source_file($sourceFilePath);

    if ($source === null) {
        return null;
    }

    $pageKey = pathinfo($sourceFilePath, PATHINFO_FILENAME);
    $specialPayload = cms_special_page_payload($pageKey);
    if ($specialPayload !== null) {
        return $specialPayload;
    }

    $source = cms_expand_source_partials($source, dirname($sourceFilePath));
    $source = cms_expand_legacy_excel_calls($source);

    if (!preg_match('/<title>(.*?)<\/title>/is', $source, $titleMatch)) {
        return null;
    }

    $browserTitle = trim(strip_tags((string) $titleMatch[1]));
    if ($browserTitle === '') {
        return null;
    }

    $metaDescription = null;
    if (preg_match('/<meta\b[^>]*name=["\']description["\'][^>]*content=["\'](.*?)["\'][^>]*>/is', $source, $descriptionMatch)) {
        $metaDescription = trim((string) $descriptionMatch[1]);
    }

    $extraHeadHtml = cms_extract_extra_head_html_from_source($source);
    $extraFooterHtml = cms_extract_extra_footer_html_from_source($source);

    if ($pageKey === 'raspored-casova-2024') {
        return cms_extract_schedule_page_payload_from_source($source, $browserTitle, $metaDescription, $extraHeadHtml, $extraFooterHtml);
    }

    if (preg_match_all('/<section\b.*?<\/section>/is', $source, $sectionMatches) && $sectionMatches[0]) {
        $contentHtml = trim(implode("\n\n", $sectionMatches[0]));
        $contentHtml = preg_replace('/<\?php\s+include(?:_once)?\s*\(\s*[\'"]includes\/aside\.php[\'"]\s*\)\s*;?\s*\?>/i', '', $contentHtml);

        $contentHtml = preg_replace('/<\?php[\s\S]*?\?>/i', '', $contentHtml);

        $contentHtml = trim((string) $contentHtml);
    } else {
        $contentHtml = cms_extract_body_wrapper_html_from_source($source);
    }

    if ($contentHtml === null || $contentHtml === '') {
        return null;
    }

    return [
        'browser_title' => $browserTitle,
        'meta_description' => $metaDescription !== '' ? $metaDescription : null,
        'content_html' => $contentHtml,
        'extra_head_html' => $extraHeadHtml,
        'extra_footer_html' => $extraFooterHtml,
        'source_hash' => sha1($browserTitle . "\n" . $contentHtml . "\n" . (string) $extraHeadHtml . "\n" . (string) $extraFooterHtml),
    ];
}

function cms_get_page_content(PDO $pdo, $recordId)
{
    $statement = $pdo->prepare('
        SELECT cms_page_content.*, cms_pages.title AS page_title, cms_pages.page_key, cms_pages.route_path, cms_pages.file_path
        FROM cms_page_content
        INNER JOIN cms_pages ON cms_pages.id = cms_page_content.page_id
        WHERE cms_page_content.id = :id
        LIMIT 1
    ');
    $statement->execute(['id' => (int) $recordId]);
    $record = $statement->fetch();

    return $record ?: null;
}

function cms_get_page_content_by_page_id(PDO $pdo, $pageId)
{
    $statement = $pdo->prepare('
        SELECT cms_page_content.*, cms_pages.title AS page_title, cms_pages.page_key, cms_pages.route_path, cms_pages.file_path
        FROM cms_page_content
        INNER JOIN cms_pages ON cms_pages.id = cms_page_content.page_id
        WHERE cms_page_content.page_id = :page_id
        LIMIT 1
    ');
    $statement->execute(['page_id' => (int) $pageId]);
    $record = $statement->fetch();

    return $record ?: null;
}

function cms_get_page_content_by_key(PDO $pdo, $pageKey, $publishedOnly = false)
{
    $sql = '
        SELECT cms_page_content.*, cms_pages.title AS page_title, cms_pages.page_key, cms_pages.route_path, cms_pages.file_path
        FROM cms_page_content
        INNER JOIN cms_pages ON cms_pages.id = cms_page_content.page_id
        WHERE cms_pages.page_key = :page_key
    ';

    if ($publishedOnly) {
        $sql .= ' AND cms_page_content.is_published = 1';
    }

    $sql .= ' LIMIT 1';

    $statement = $pdo->prepare($sql);
    $statement->execute(['page_key' => trim((string) $pageKey)]);
    $record = $statement->fetch();

    return $record ?: null;
}

function cms_list_page_contents(PDO $pdo)
{
    $statement = $pdo->query('
        SELECT cms_pages.*, cms_page_content.id AS content_id, cms_page_content.browser_title, cms_page_content.is_published, cms_page_content.updated_at AS content_updated_at, cms_page_content.seeded_from_source_at
        FROM cms_pages
        LEFT JOIN cms_page_content ON cms_page_content.page_id = cms_pages.id
        ORDER BY cms_pages.title ASC
    ');

    return $statement->fetchAll();
}

function cms_save_page_content(PDO $pdo, array $data, $adminId, $recordId = null)
{
    $recordId = $recordId !== null ? (int) $recordId : null;
    $existing = $recordId ? cms_get_page_content($pdo, $recordId) : null;

    if ($recordId && !$existing) {
        throw new RuntimeException('Traženi sadržaj stranice nije pronađen.');
    }

    $pageId = (int) ($data['page_id'] ?? ($existing['page_id'] ?? 0));
    $browserTitle = trim((string) ($data['browser_title'] ?? ''));
    $metaDescription = trim((string) ($data['meta_description'] ?? ($existing['meta_description'] ?? '')));
    $contentHtml = trim((string) ($data['content_html'] ?? ''));
    $extraHeadHtml = trim((string) ($data['extra_head_html'] ?? ''));
    $extraFooterHtml = trim((string) ($data['extra_footer_html'] ?? ''));
    $isPublished = !empty($data['is_published']) ? 1 : 0;
    $sourceHash = trim((string) ($data['source_hash'] ?? ($existing['source_hash'] ?? '')));
    $seededFromSourceAt = $existing['seeded_from_source_at'] ?? null;

    if ($pageId <= 0) {
        throw new InvalidArgumentException('Izbor stranice je obavezan.');
    }

    if ($browserTitle === '') {
        throw new InvalidArgumentException('Naslov pregledača je obavezan.');
    }

    $page = cms_get_page_by_id($pdo, $pageId);
    $isDatabasePage = $page && ($page['file_path'] ?? '') === '[db-page]';

    if ($contentHtml === '' && !$isDatabasePage) {
        throw new InvalidArgumentException('HTML sadržaj stranice je obavezan.');
    }

    $payload = [
        'page_id' => $pageId,
        'browser_title' => $browserTitle,
        'meta_description' => $metaDescription !== '' ? $metaDescription : null,
        'content_html' => $contentHtml,
        'extra_head_html' => $extraHeadHtml !== '' ? $extraHeadHtml : null,
        'extra_footer_html' => $extraFooterHtml !== '' ? $extraFooterHtml : null,
        'source_hash' => $sourceHash !== '' ? $sourceHash : null,
        'seeded_from_source_at' => $seededFromSourceAt,
        'is_published' => $isPublished,
        'updated_by' => (int) $adminId,
    ];

    if ($existing) {
        $payload['id'] = $recordId;
        $statement = $pdo->prepare('
            UPDATE cms_page_content
            SET page_id = :page_id,
                browser_title = :browser_title,
                meta_description = :meta_description,
                content_html = :content_html,
                extra_head_html = :extra_head_html,
                extra_footer_html = :extra_footer_html,
                source_hash = :source_hash,
                seeded_from_source_at = :seeded_from_source_at,
                is_published = :is_published,
                updated_by = :updated_by,
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute($payload);

        return $recordId;
    }

    $payload['created_by'] = (int) $adminId;
    $statement = $pdo->prepare('
        INSERT INTO cms_page_content (
            page_id,
            browser_title,
            meta_description,
            content_html,
            extra_head_html,
            extra_footer_html,
            source_hash,
            seeded_from_source_at,
            is_published,
            created_by,
            updated_by
        ) VALUES (
            :page_id,
            :browser_title,
            :meta_description,
            :content_html,
            :extra_head_html,
            :extra_footer_html,
            :source_hash,
            :seeded_from_source_at,
            :is_published,
            :created_by,
            :updated_by
        )
    ');
    $statement->execute($payload);

    return (int) $pdo->lastInsertId();
}

function cms_seed_page_content_from_source(PDO $pdo, $pageKey, $sourceFilePath = null, $overwrite = false)
{
    $pageKey = trim((string) $pageKey);

    if (!cms_page_content_is_manageable($pageKey)) {
        return [
            'status' => 'skipped',
            'message' => 'Stranica se ne uvozi preko ovog CMS sloja.',
        ];
    }

    $pageStatement = $pdo->prepare('SELECT * FROM cms_pages WHERE page_key = :page_key LIMIT 1');
    $pageStatement->execute(['page_key' => $pageKey]);
    $page = $pageStatement->fetch();

    if (!$page) {
        return [
            'status' => 'skipped',
            'message' => 'Stranica nije pronađena u registru.',
        ];
    }

    $sourceFilePath = $sourceFilePath ?: (site_root_path() . DIRECTORY_SEPARATOR . $page['file_path']);
    $existing = cms_get_page_content_by_page_id($pdo, $page['id']);

    if ($existing && !$overwrite) {
        return [
            'status' => 'existing',
            'message' => 'Sadržaj stranice već postoji u bazi.',
        ];
    }

    $payload = cms_extract_page_payload_from_source($sourceFilePath);
    if ($payload === null) {
        return [
            'status' => 'skipped',
            'message' => 'Izvorni fajl nije pogodan za automatski uvoz.',
        ];
    }

    if ($existing) {
        $statement = $pdo->prepare('
            UPDATE cms_page_content
            SET browser_title = :browser_title,
                meta_description = :meta_description,
                content_html = :content_html,
                extra_head_html = :extra_head_html,
                extra_footer_html = :extra_footer_html,
                source_hash = :source_hash,
                seeded_from_source_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ');
        $statement->execute([
            'id' => (int) $existing['id'],
            'browser_title' => $payload['browser_title'],
            'meta_description' => $payload['meta_description'],
            'content_html' => $payload['content_html'],
            'extra_head_html' => $payload['extra_head_html'],
            'extra_footer_html' => $payload['extra_footer_html'],
            'source_hash' => $payload['source_hash'],
        ]);

        return [
            'status' => 'updated',
            'message' => 'Sadržaj stranice je osvežen iz izvornog fajla.',
            'id' => (int) $existing['id'],
        ];
    }

    $statement = $pdo->prepare('
        INSERT INTO cms_page_content (
            page_id,
            browser_title,
            meta_description,
            content_html,
            extra_head_html,
            extra_footer_html,
            source_hash,
            seeded_from_source_at,
            is_published
        ) VALUES (
            :page_id,
            :browser_title,
            :meta_description,
            :content_html,
            :extra_head_html,
            :extra_footer_html,
            :source_hash,
            NOW(),
            1
        )
    ');
    $statement->execute([
        'page_id' => (int) $page['id'],
        'browser_title' => $payload['browser_title'],
        'meta_description' => $payload['meta_description'],
        'content_html' => $payload['content_html'],
        'extra_head_html' => $payload['extra_head_html'],
        'extra_footer_html' => $payload['extra_footer_html'],
        'source_hash' => $payload['source_hash'],
    ]);

    return [
        'status' => 'inserted',
        'message' => 'Sadržaj stranice je uvezen iz izvornog fajla.',
        'id' => (int) $pdo->lastInsertId(),
    ];
}

function cms_sync_page_content_from_sources(PDO $pdo, $overwrite = false)
{
    $summary = [
        'inserted' => 0,
        'updated' => 0,
        'existing' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    cms_sync_pages($pdo);
    $pages = cms_list_pages($pdo);

    foreach ($pages as $page) {
        if (!cms_page_content_is_manageable($page['page_key'])) {
            continue;
        }

        try {
            $result = cms_seed_page_content_from_source($pdo, $page['page_key'], null, $overwrite);
            $status = $result['status'] ?? 'skipped';

            if (array_key_exists($status, $summary)) {
                $summary[$status]++;
            } else {
                $summary['skipped']++;
            }
        } catch (Throwable $exception) {
            $summary['failed']++;
        }
    }

    return $summary;
}

function cms_capture_repaired_include($relativePath)
{
    $absolutePath = site_root_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, trim((string) $relativePath, '/'));

    if (!is_file($absolutePath)) {
        return '';
    }

    ob_start();
    include $absolutePath;
    $output = ob_get_clean();

    return cms_page_source_to_utf8((string) $output);
}

function cms_render_letopisi_filter_label_html()
{
    $selectedYear = trim((string) ($_GET['godina'] ?? ''));
    $selectedMonth = trim((string) ($_GET['mesec'] ?? ''));

    if ($selectedYear === '' && $selectedMonth === '') {
        return '';
    }

    $label = trim($selectedYear . ' ' . $selectedMonth);

    return '<p>Prikazan filter: ' . site_escape($label) . '</p>';
}

function cms_render_letopisi_timeline_html()
{
    $selectedYear = trim((string) ($_GET['godina'] ?? ''));
    $selectedMonth = trim((string) ($_GET['mesec'] ?? ''));
    $entries = [];

    if (site_has_tables(['letopisi'])) {
        $pdo = site_db();
        if ($pdo instanceof PDO) {
            $entries = cms_list_letopisi_public(
                $pdo,
                $selectedYear !== '' ? $selectedYear : null,
                $selectedMonth !== '' ? $selectedMonth : null
            );
        }
    }

    ob_start();
    ?>
<section id="cd-timeline" class="cd-container">
    <?php if (!$entries): ?>
        <div class="cd-timeline-block">
            <div class="cd-timeline-img cd-picture">
                <img src="img/cd-icon-location.svg" alt="Lokacija">
            </div>
            <div class="cd-timeline-content">
                <p>Još nema objavljenih letopisa.</p>
                <span class="cd-date">Administracija</span>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($entries as $entry): ?>
            <div class="cd-timeline-block">
                <div class="cd-timeline-img cd-picture">
                    <img src="img/cd-icon-location.svg" alt="Lokacija">
                </div>
                <div class="cd-timeline-content">
                    <?php if (!empty($entry['slika'])): ?>
                        <p>
                            <img src="<?php echo site_escape((string) $entry['slika']); ?>" alt="Slika letopisa" style="max-width: 100%; height: auto; margin-bottom: 16px;">
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($entry['glavni_tekst'])): ?>
                        <?php echo $entry['glavni_tekst']; ?>
                    <?php endif; ?>

                    <?php if (!empty($entry['dokument'])): ?>
                        <p style="margin: 16px 0 0;">
                            <a class="btn-default btn_1" href="<?php echo site_escape((string) $entry['dokument']); ?>" target="_blank">
                                Otvori dokument
                            </a>
                        </p>
                    <?php endif; ?>

                    <span class="cd-date"><?php echo site_escape(cms_build_letopis_display_label($entry)); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php

    return trim((string) ob_get_clean());
}

function cms_render_special_placeholder($placeholderName, array $record = [])
{
    $placeholderName = strtolower(trim((string) $placeholderName));

    switch ($placeholderName) {
        case 'homepage-slider':
            return cms_capture_repaired_include('includes/main-slider.php');
        case 'homepage-widgets':
            return cms_capture_repaired_include('includes/homepage-widgets.php');
        case 'letopisi-filter-label':
            return cms_render_letopisi_filter_label_html();
        case 'letopisi-timeline':
            return cms_render_letopisi_timeline_html();
        default:
            return null;
    }
}

function cms_replace_special_placeholders($contentHtml, array $record = [])
{
    $contentHtml = (string) $contentHtml;
    if (strpos($contentHtml, '{{') === false) {
        return $contentHtml;
    }

    return preg_replace_callback(
        '/\{\{\s*([a-z0-9\-]+)\s*\}\}/i',
        static function ($matches) use ($record) {
            $replacement = cms_render_special_placeholder($matches[1] ?? '', $record);

            return $replacement !== null ? $replacement : $matches[0];
        },
        $contentHtml
    );
}

function cms_page_area_titles()
{
    return [
        'homepage-important' => 'Važna obaveštenja',
        'homepage-events' => 'Aktuelnosti',
        'page-notices' => 'Obaveštenja',
        'page-downloads' => 'Preuzimanja',
        'page-links' => 'Korisni linkovi',
        'page-gallery' => 'Galerija',
    ];
}

function cms_public_item_file_extension(array $item)
{
    $itemUrl = cms_resolve_content_item_url($item);
    if (!$itemUrl) {
        return '';
    }

    $path = (string) parse_url((string) $itemUrl, PHP_URL_PATH);
    if ($path === '') {
        $path = (string) $itemUrl;
    }

    $extension = strtoupper((string) pathinfo(rawurldecode($path), PATHINFO_EXTENSION));

    return $extension !== '' ? '.' . $extension : '';
}

function cms_public_item_file_kind(array $item)
{
    return ltrim(cms_public_item_file_extension($item), '.');
}

function cms_public_link_is_remote($url)
{
    return (bool) preg_match('#^(?:https?:)?//#i', trim((string) $url));
}

function cms_public_item_icon_class(array $item)
{
    $fileKind = strtoupper(cms_public_item_file_kind($item));

    switch ($fileKind) {
        case 'PDF':
            return 'fa-file-pdf-o';
        case 'DOC':
        case 'DOCX':
        case 'ODT':
        case 'RTF':
            return 'fa-file-word-o';
        case 'XLS':
        case 'XLSX':
        case 'CSV':
        case 'ODS':
            return 'fa-file-excel-o';
        case 'PPT':
        case 'PPTX':
        case 'ODP':
            return 'fa-file-powerpoint-o';
        case 'ZIP':
            return 'fa-file-archive-o';
        case 'TXT':
            return 'fa-file-text-o';
        case 'JPG':
        case 'JPEG':
        case 'PNG':
        case 'GIF':
        case 'WEBP':
            return 'fa-file-image-o';
        default:
            return 'fa-file-o';
    }
}

function cms_format_file_size($bytes)
{
    $bytes = (float) $bytes;

    if ($bytes <= 0) {
        return '';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;

    while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
        $bytes /= 1024;
        $unitIndex++;
    }

    $precision = $unitIndex === 0 ? 0 : ($bytes >= 10 ? 0 : 1);

    return number_format($bytes, $precision, '.', '') . ' ' . $units[$unitIndex];
}

function cms_public_item_file_size_label(array $item)
{
    $itemUrl = cms_resolve_content_item_url($item);
    if (!$itemUrl || cms_public_link_is_remote($itemUrl)) {
        return '';
    }

    $path = (string) parse_url((string) $itemUrl, PHP_URL_PATH);
    if ($path === '') {
        $path = (string) $itemUrl;
    }

    $path = rawurldecode($path);
    $basePath = site_base_path();

    if ($basePath !== '' && strpos($path, $basePath . '/') === 0) {
        $path = substr($path, strlen($basePath) + 1);
    }

    $path = ltrim((string) $path, '/');

    if ($path === '' || strpos($path, '..') !== false) {
        return '';
    }

    $absolutePath = site_root_path() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (!is_file($absolutePath)) {
        return '';
    }

    $fileSize = filesize($absolutePath);
    if ($fileSize === false) {
        return '';
    }

    return cms_format_file_size($fileSize);
}

function cms_download_count_label($count)
{
    $count = (int) $count;

    if ($count === 1) {
        return '1 dokument';
    }

    $mod10 = $count % 10;
    $mod100 = $count % 100;

    if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
        return $count . ' dokumenta';
    }

    return $count . ' dokumenata';
}

function cms_gallery_item_group(array $item)
{
    $title = trim((string) ($item['title'] ?? ''));
    $summary = trim((string) ($item['summary'] ?? ''));
    $candidate = preg_replace('/\s+\d+$/u', '', $title);
    $candidate = trim((string) $candidate);

    if ($candidate === '') {
        $candidate = $summary !== '' ? $summary : $title;
    }

    $candidate = trim((string) $candidate);
    $slug = function_exists('site_slugify') ? site_slugify($candidate) : '';

    return $slug !== '' ? $slug : 'cms-gallery';
}

function cms_gallery_item_album_label(array $item)
{
    $title = trim((string) ($item['title'] ?? ''));
    $candidate = trim((string) preg_replace('/\s+\d+$/u', '', $title));

    if ($candidate !== '') {
        return $candidate;
    }

    $summary = trim((string) ($item['summary'] ?? ''));

    return $summary !== '' ? $summary : 'Galerija';
}

function cms_gallery_item_caption(array $item)
{
    $summary = trim((string) ($item['summary'] ?? ''));
    if ($summary !== '') {
        return $summary;
    }

    return trim((string) ($item['title'] ?? ''));
}

function cms_render_public_area_html($areaKey, array $items, array $options = [])
{
    if (!$items) {
        return '';
    }

    $titles = cms_page_area_titles();
    $sectionTitle = $titles[$areaKey] ?? ucwords(str_replace(['-', '_'], ' ', (string) $areaKey));
    $isEmbedded = !empty($options['embedded']);
    ob_start();

    if ($areaKey === 'page-downloads') {
        $documentCountLabel = cms_download_count_label(count($items));
        ?>
<?php if ($isEmbedded): ?>
<div class="cms-managed-area cms-download-area cms-download-area-embedded" data-area-key="<?php echo site_escape($areaKey); ?>">
<?php else: ?>
<section class="pad_t_65 page4 cms-managed-area cms-download-area" data-area-key="<?php echo site_escape($areaKey); ?>">
    <div class="container">
        <div class="row">
            <div class="grid_12">
<?php endif; ?>
                <div class="cms-download-shell">
                    <div class="cms-download-toolbar">
                        <div class="cms-download-toolbar-copy">
                            <p class="cms-download-eyebrow">Dokumenta</p>
                            <h3 class="cms-download-heading"><?php echo site_escape($sectionTitle); ?></h3>
                            <p class="cms-download-subtitle">Pregled dostupnih fajlova za ovu stranicu.</p>
                        </div>
                        <div class="cms-download-count"><?php echo site_escape($documentCountLabel); ?></div>
                    </div>
                    <ol class="cms-download-list">
                        <?php foreach ($items as $index => $item): ?>
                            <?php
                            $itemUrl = cms_resolve_content_item_url($item);
                            $extensionLabel = cms_public_item_file_extension($item);
                            $fileKind = cms_public_item_file_kind($item);
                            $fileSizeLabel = cms_public_item_file_size_label($item);
                            $isRemote = $itemUrl ? cms_public_link_is_remote($itemUrl) : false;
                            $downloadAttribute = ($itemUrl && !$isRemote) ? ' download' : '';
                            $publishedLabel = !empty($item['published_at']) ? site_format_date($item['published_at'], 'd.m.Y.') : '';
                            $iconClass = cms_public_item_icon_class($item);
                            $openTarget = $isRemote ? (!empty($item['link_target']) ? $item['link_target'] : '_blank') : '_blank';
                            ?>
                            <li class="cms-download-item" data-file-kind="<?php echo site_escape($fileKind !== '' ? $fileKind : 'FILE'); ?>">
                                <div class="cms-download-leading">
                                    <span class="cms-download-order"><?php echo site_escape(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                                    <span class="cms-download-icon">
                                        <i class="fa <?php echo site_escape($iconClass); ?>" aria-hidden="true"></i>
                                    </span>
                                </div>
                                <div class="cms-download-main">
                                    <div class="cms-download-meta">
                                        <?php if ($extensionLabel !== ''): ?>
                                            <span class="cms-download-badge cms-download-badge-file"><?php echo site_escape($extensionLabel); ?></span>
                                        <?php endif; ?>
                                        <?php if ($fileSizeLabel !== ''): ?>
                                            <span class="cms-download-badge"><?php echo site_escape($fileSizeLabel); ?></span>
                                        <?php endif; ?>
                                        <?php if ($publishedLabel !== ''): ?>
                                            <span class="cms-download-badge"><?php echo site_escape($publishedLabel); ?></span>
                                        <?php endif; ?>
                                        <?php if ($isRemote): ?>
                                            <span class="cms-download-badge">LINK</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="cms-download-name">
                                            <?php if ($itemUrl): ?>
                                                <a href="<?php echo site_escape($itemUrl); ?>" target="<?php echo site_escape($openTarget); ?>">
                                                    <?php echo site_escape($item['title']); ?>
                                                </a>
                                            <?php else: ?>
                                                <strong><?php echo site_escape($item['title']); ?></strong>
                                            <?php endif; ?>
                                    </p>
                                    <?php if (!empty($item['summary'])): ?>
                                        <p class="cms-download-summary"><?php echo nl2br(site_escape((string) $item['summary'])); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($item['body_html'])): ?>
                                        <div class="cms-download-body"><?php echo $item['body_html']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($itemUrl): ?>
                                    <div class="cms-download-actions">
                                        <a class="cms-download-button cms-download-button-secondary" href="<?php echo site_escape($itemUrl); ?>" target="<?php echo site_escape($openTarget); ?>">
                                            Otvori
                                        </a>
                                        <?php if (!$isRemote): ?>
                                            <a class="cms-download-button" href="<?php echo site_escape($itemUrl); ?>" target="_blank"<?php echo $downloadAttribute; ?>>
                                                Preuzmi
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
<?php if ($isEmbedded): ?>
</div>
<?php else: ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<?php
        return trim((string) ob_get_clean());
    }

    ?>
<section class="pad_t_65 page4 cms-managed-area" data-area-key="<?php echo site_escape($areaKey); ?>">
    <div class="container">
        <div class="row">
            <div class="grid_12 mar_t_3">
                <h3 class="no-pad"><?php echo site_escape($sectionTitle); ?></h3>
            </div>
        </div>
        <div class="row">
            <?php foreach ($items as $item): ?>
                <?php
                $itemUrl = cms_resolve_content_item_url($item);
                $imagePath = trim((string) ($item['image_path'] ?? ''));
                $isGallery = $areaKey === 'page-gallery';
                $galleryUrl = $itemUrl ?: ($imagePath !== '' ? $imagePath : null);
                $showGalleryMeta = !empty($item['summary']) || !empty($item['body_html']) || !empty($item['title']);
                ?>
                <?php if ($isGallery): ?>
                    <?php
                    $galleryGroup = cms_gallery_item_group($item);
                    $galleryAlbum = cms_gallery_item_album_label($item);
                    $galleryCaption = cms_gallery_item_caption($item);
                    ?>
                    <div class="grid_3 cms-gallery-card-wrap" style="margin-bottom: 24px;">
                        <div class="extra-wrap bord_1 cms-gallery-card" style="padding: 0; overflow: hidden; background: #fff;">
                            <?php if ($galleryUrl): ?>
                                <a
                                    href="<?php echo site_escape($galleryUrl); ?>"
                                    rel="prettyPhoto[<?php echo site_escape($galleryGroup); ?>]"
                                    data-gallery-lightbox="prettyphoto"
                                    data-gallery-group="<?php echo site_escape($galleryGroup); ?>"
                                    title="<?php echo site_escape($galleryCaption); ?>"
                                    class="cms-gallery-link"
                                    style="display: block;"
                                >
                            <?php endif; ?>
                            <?php if ($imagePath !== ''): ?>
                                <div class="cms-gallery-image-wrap" style="position: relative; overflow: hidden;">
                                    <img src="<?php echo site_escape($imagePath); ?>" alt="<?php echo site_escape($item['title']); ?>" class="cms-gallery-image" style="display: block; width: 100%; height: 220px; object-fit: cover;">
                                    <span class="cms-gallery-badge" style="position: absolute; top: 12px; left: 12px; z-index: 1; display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: rgba(255, 255, 255, 0.88); color: #4b3c2d; font-size: 12px;">
                                        <?php echo site_escape($galleryAlbum); ?>
                                    </span>
                                    <span class="cms-gallery-overlay" style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(21, 17, 14, 0.04) 0%, rgba(21, 17, 14, 0.55) 100%); opacity: 0; transition: opacity 0.24s ease;"></span>
                                </div>
                            <?php else: ?>
                                <div style="height: 220px; display: flex; align-items: center; justify-content: center; background: #f2f2f2; color: #777;">Nema slike</div>
                            <?php endif; ?>
                            <?php if ($galleryUrl): ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($showGalleryMeta): ?>
                                <div class="cms-gallery-meta" style="padding: 12px 14px 14px;">
                                    <?php if (!empty($item['title'])): ?>
                                        <p style="margin: 0 0 8px;"><strong><?php echo site_escape($item['title']); ?></strong></p>
                                    <?php endif; ?>
                                    <?php if (!empty($item['summary'])): ?>
                                        <p style="margin: 0 0 8px;"><?php echo nl2br(site_escape((string) $item['summary'])); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($item['body_html'])): ?>
                                        <div><?php echo $item['body_html']; ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="grid_12" style="margin-bottom: 24px;">
                        <div class="extra-wrap bord_1" style="padding: 18px;">
                            <?php if ($imagePath !== ''): ?>
                                <p style="margin-bottom: 14px;">
                                    <img src="<?php echo site_escape($imagePath); ?>" alt="<?php echo site_escape($item['title']); ?>" style="max-width: 100%; height: auto;">
                                </p>
                            <?php endif; ?>
                            <?php if ($itemUrl): ?>
                                <p style="margin: 0 0 10px;">
                                    <a href="<?php echo site_escape($itemUrl); ?>" target="<?php echo site_escape(!empty($item['link_target']) ? $item['link_target'] : '_self'); ?>">
                                        <?php echo site_escape($item['title']); ?>
                                    </a>
                                </p>
                            <?php else: ?>
                                <p style="margin: 0 0 10px;"><strong><?php echo site_escape($item['title']); ?></strong></p>
                            <?php endif; ?>
                            <?php if (!empty($item['summary'])): ?>
                                <p><?php echo nl2br(site_escape((string) $item['summary'])); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['body_html'])): ?>
                                <div><?php echo $item['body_html']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
        return trim((string) ob_get_clean());
    }

function cms_homepage_month_label($publishedAt)
{
    $timestamp = strtotime((string) $publishedAt);
    if ($timestamp === false) {
        return '';
    }

    $months = [
        1 => 'јануар', 2 => 'фебруар', 3 => 'март', 4 => 'април',
        5 => 'мај', 6 => 'јун', 7 => 'јул', 8 => 'август',
        9 => 'септембар', 10 => 'октобар', 11 => 'новембар', 12 => 'децембар',
    ];

    return $months[(int) date('n', $timestamp)] ?? '';
}

function cms_render_homepage_feed_html(array $importantItems, array $eventItems)
{
    if (!$importantItems && !$eventItems) {
        return '';
    }

    ob_start();
    ?>
<section class="obavestenja cms-homepage-feed" data-cms-homepage-feed>
    <div class="wrap">
        <div class="left" data-area-key="homepage-important">
            <h1><span><a href="sva_obavestenja.php">Важна обавештења</a></span></h1>
            <?php foreach ($importantItems as $item): ?>
                <?php $itemUrl = cms_resolve_content_item_url($item); ?>
                <div class="block">
                    <div class="file-box">
                        <img src="images/document.png" alt="" width="50">
                    </div>
                    <div class="text-box">
                        <div class="button">
                            <?php if ($itemUrl): ?>
                                <a class="button" href="<?php echo site_escape($itemUrl); ?>" target="<?php echo site_escape(!empty($item['link_target']) ? $item['link_target'] : '_self'); ?>">
                                    <?php echo site_escape($item['title']); ?>
                                    <span class="buton"></span><span class="buton"></span><span class="buton"></span><span class="buton"></span>
                                </a>
                            <?php else: ?>
                                <strong><?php echo site_escape($item['title']); ?></strong>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($item['summary'])): ?>
                            <p><?php echo nl2br(site_escape((string) $item['summary'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($item['body_html'])): ?>
                            <div class="cms-homepage-item-body"><?php echo $item['body_html']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="right" data-area-key="homepage-events">
            <h1><span><a href="aktuelno.php">Актуелно</a></span></h1>
            <?php foreach ($eventItems as $item): ?>
                <?php
                $itemUrl = cms_resolve_content_item_url($item);
                $publishedTimestamp = strtotime((string) ($item['published_at'] ?? ''));
                $isMediaOnly = !$itemUrl && ($item['content_type'] ?? '') === 'html' && !empty($item['body_html']);
                ?>
                <div class="block_1">
                    <?php if ($isMediaOnly): ?>
                        <div class="cms-homepage-item-body"><?php echo $item['body_html']; ?></div>
                    <?php else: ?>
                    <?php if ($publishedTimestamp !== false): ?>
                        <time datetime="<?php echo site_escape(date('Y-m-d', $publishedTimestamp)); ?>">
                            <span><?php echo site_escape(cms_homepage_month_label($item['published_at'])); ?></span>
                            <em><?php echo site_escape(date('j', $publishedTimestamp)); ?></em>
                        </time>
                    <?php endif; ?>
                    <div class="extra-wrap">
                        <div class="button">
                            <?php if ($itemUrl): ?>
                                <a class="button" href="<?php echo site_escape($itemUrl); ?>" target="<?php echo site_escape(!empty($item['link_target']) ? $item['link_target'] : '_self'); ?>">
                                    <?php echo site_escape($item['title']); ?>
                                    <span class="buton"></span><span class="buton"></span><span class="buton"></span><span class="buton"></span>
                                </a>
                            <?php else: ?>
                                <strong><?php echo site_escape($item['title']); ?></strong>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($item['summary'])): ?>
                            <p><?php echo nl2br(site_escape((string) $item['summary'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($item['body_html'])): ?>
                            <div class="cms-homepage-item-body"><?php echo $item['body_html']; ?></div>
                        <?php endif; ?>
                        <div class="clearfix"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="clearfix"></div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="clearfix"></div>
</section>
<?php
    return trim((string) ob_get_clean());
}

function cms_render_homepage_archive_html($areaKey, array $items)
{
    $isImportant = $areaKey === 'homepage-important';
    $heading = $isImportant ? 'Сва обавештења' : 'Актуелне вести';
    ob_start();
    ?>
<section class="single-page cms-homepage-archive" data-area-key="<?php echo site_escape($areaKey); ?>">
    <div class="wrap">
        <h1><span><?php echo site_escape($heading); ?></span></h1>
        <div class="single-box">
            <?php if (!$items): ?>
                <p>Тренутно нема објављених ставки.</p>
            <?php endif; ?>

            <?php foreach ($items as $item): ?>
                <?php
                $itemUrl = cms_resolve_content_item_url($item);
                $publishedLabel = !empty($item['published_at']) ? site_format_date($item['published_at'], 'd.m.Y.') : '';
                ?>
                <?php if ($isImportant): ?>
                    <div class="block">
                        <div class="file-box"><img src="images/document.png" alt="" width="50"></div>
                        <div class="text-box" style="display: inline-block;">
                            <div class="button">
                                <?php if ($itemUrl): ?>
                                    <a class="button" href="<?php echo site_escape($itemUrl); ?>" target="<?php echo site_escape(!empty($item['link_target']) ? $item['link_target'] : '_self'); ?>">
                                        <?php echo site_escape($item['title']); ?>
                                        <span class="buton"></span><span class="buton"></span><span class="buton"></span><span class="buton"></span>
                                    </a>
                                <?php else: ?>
                                    <strong><?php echo site_escape($item['title']); ?></strong>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($item['summary'])): ?><p><?php echo nl2br(site_escape((string) $item['summary'])); ?></p><?php endif; ?>
                            <?php if (!empty($item['body_html'])): ?><div><?php echo $item['body_html']; ?></div><?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <article style="margin-bottom: 20px;">
                        <?php if ($itemUrl): ?><a href="<?php echo site_escape($itemUrl); ?>" class="aktuelno" target="<?php echo site_escape(!empty($item['link_target']) ? $item['link_target'] : '_self'); ?>"><?php endif; ?>
                            <h2><?php echo site_escape($item['title']); ?></h2>
                            <?php if ($publishedLabel !== ''): ?><span class="datum"><?php echo site_escape($publishedLabel); ?></span><?php endif; ?>
                            <?php if (!empty($item['summary'])): ?><p><?php echo nl2br(site_escape((string) $item['summary'])); ?></p><?php endif; ?>
                        <?php if ($itemUrl): ?></a><?php endif; ?>
                        <?php if (!empty($item['body_html'])): ?><div><?php echo $item['body_html']; ?></div><?php endif; ?>
                    </article>
                <?php endif; ?>
                <hr>
            <?php endforeach; ?>
        </div>
        <div class="clearfix"></div>
    </div>
</section>
<?php
    return trim((string) ob_get_clean());
}

function cms_replace_page_area_placeholders(PDO $pdo, array $record, $contentHtml, array $areaLimits = [])
{
    $contentHtml = (string) $contentHtml;
    $usedAreaKeys = [];

    $archivePlaceholders = [
        '{{homepage-important-archive}}' => 'homepage-important',
        '{{homepage-events-archive}}' => 'homepage-events',
    ];
    foreach ($archivePlaceholders as $archivePlaceholder => $archiveAreaKey) {
        if (strpos($contentHtml, $archivePlaceholder) === false) {
            continue;
        }

        $homePage = cms_get_page_by_key($pdo, 'index');
        $archiveItems = $homePage
            ? cms_list_public_content_items($pdo, $homePage['id'], $archiveAreaKey, null, true)
            : [];
        $contentHtml = str_replace(
            $archivePlaceholder,
            cms_render_homepage_archive_html($archiveAreaKey, $archiveItems),
            $contentHtml
        );
    }

    if (strpos($contentHtml, '{{homepage-feed}}') !== false) {
        $importantLimit = $areaLimits['homepage-important'] ?? null;
        $eventsLimit = $areaLimits['homepage-events'] ?? null;
        $importantItems = cms_list_public_content_items(
            $pdo,
            $record['page_id'],
            'homepage-important',
            $importantLimit,
            $importantLimit !== null
        );
        $eventItems = cms_list_public_content_items(
            $pdo,
            $record['page_id'],
            'homepage-events',
            $eventsLimit,
            $eventsLimit !== null
        );
        $contentHtml = str_replace(
            '{{homepage-feed}}',
            cms_render_homepage_feed_html($importantItems, $eventItems),
            $contentHtml
        );
        $usedAreaKeys[] = 'homepage-important';
        $usedAreaKeys[] = 'homepage-events';
    }

    foreach (array_keys(cms_page_area_titles()) as $areaKey) {
        $placeholder = '{{' . $areaKey . '}}';

        if (strpos($contentHtml, $placeholder) === false) {
            continue;
        }

        $usedAreaKeys[] = $areaKey;
        $limit = $areaLimits[$areaKey] ?? null;
        $items = cms_list_public_content_items($pdo, $record['page_id'], $areaKey, $limit, $limit !== null);
        $contentHtml = str_replace($placeholder, cms_render_public_area_html($areaKey, $items, ['embedded' => true]), $contentHtml);
    }

    return [
        'content_html' => $contentHtml,
        'used_area_keys' => array_values(array_unique($usedAreaKeys)),
    ];
}

function cms_render_unused_page_areas(PDO $pdo, array $record, array $usedAreaKeys = [], array $areaLimits = [])
{
    $html = [];

    foreach (array_keys(cms_page_area_titles()) as $areaKey) {
        if (in_array($areaKey, $usedAreaKeys, true)) {
            continue;
        }

        $limit = $areaLimits[$areaKey] ?? null;
        $items = cms_list_public_content_items($pdo, $record['page_id'], $areaKey, $limit, $limit !== null);
        if (!$items) {
            continue;
        }

        $html[] = cms_render_public_area_html($areaKey, $items);
    }

    return implode("\n", array_filter($html));
}

function cms_get_page_content_by_route(PDO $pdo, $routePath, $publishedOnly = false)
{
    $routePath = trim((string) $routePath, '/');
    if ($routePath === '') {
        $routePath = 'index';
    }

    $sql = '
        SELECT cms_page_content.*, cms_pages.title AS page_title, cms_pages.page_key, cms_pages.route_path, cms_pages.file_path
        FROM cms_page_content
        INNER JOIN cms_pages ON cms_pages.id = cms_page_content.page_id
        WHERE cms_pages.route_path = :route_path
    ';

    if ($publishedOnly) {
        $sql .= ' AND cms_page_content.is_published = 1';
    }

    $sql .= ' LIMIT 1';

    $statement = $pdo->prepare($sql);
    $statement->execute(['route_path' => $routePath]);
    $record = $statement->fetch();

    return $record ?: null;
}

function cms_default_inner_page_banner_html()
{
    return sprintf(
        '<section class="cover-istorijat cms-default-page-banner" aria-label="Школска зграда">'
        . '<div class="wrap"><img src="%s" alt="Зид школске зграде са муралом" width="1270" height="300"></div>'
        . '</section>',
        site_escape(site_url('images/istorijat.jpg'))
    );
}

function cms_is_database_created_page(array $record)
{
    return ($record['file_path'] ?? '') === '[db-page]';
}

function cms_strip_generated_db_page_placeholder($contentHtml, $pageTitle = '')
{
    $contentHtml = (string) $contentHtml;
    $placeholderPattern = '~<p\b[^>]*>\s*Unesite\s+[^<\s]+\s+ove\s+stranice[.]?\s*</p>~iu';

    if (preg_match($placeholderPattern, $contentHtml) !== 1) {
        return $contentHtml;
    }

    $contentHtml = preg_replace($placeholderPattern, '', $contentHtml, 1);
    $pageTitle = trim((string) $pageTitle);

    if ($pageTitle !== '') {
        $headingPattern = '~<h[1-6]\b[^>]*>\s*' . preg_quote($pageTitle, '~') . '\s*</h[1-6]>~iu';
        $contentHtml = preg_replace($headingPattern, '', $contentHtml, 1);
    }

    if (trim(strip_tags((string) $contentHtml)) === '') {
        return '';
    }

    return trim((string) $contentHtml);
}

function cms_page_content_is_effectively_empty($contentHtml, $pageTitle = '')
{
    $contentHtml = cms_strip_generated_db_page_placeholder($contentHtml, $pageTitle);
    $text = html_entity_decode(strip_tags((string) $contentHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = trim((string) preg_replace('/\s+/u', ' ', $text));

    if ($text === '') {
        return true;
    }

    $pageTitle = trim((string) $pageTitle);

    return $pageTitle !== '' && strcasecmp($text, $pageTitle) === 0;
}

function cms_render_database_page_main_html($heading, $bodyHtml)
{
    $heading = trim((string) $heading);
    $bodyHtml = trim((string) $bodyHtml);
    ob_start();
    ?>
<section class="single-page cms-database-page" id="content">
    <div class="wrap">
        <h1><span><?php echo site_escape($heading); ?></span></h1>
        <?php if ($bodyHtml !== ''): ?>
            <div class="single-box cms-database-page-body"><?php echo $bodyHtml; ?></div>
        <?php endif; ?>
        <div class="clearfix"></div>
    </div>
</section>
<?php
    return trim((string) ob_get_clean());
}

function cms_page_content_needs_default_banner(array $record, $contentHtml)
{
    if (!cms_is_database_created_page($record)) {
        return false;
    }

    $contentHtml = (string) $contentHtml;

    return stripos($contentHtml, 'cover-istorijat') === false
        && stripos($contentHtml, 'images/istorijat.jpg') === false;
}

function cms_render_page_content_record(array $record)
{
    $title = trim((string) ($record['browser_title'] ?? $record['page_title'] ?? 'OŠ Zdravko Jovanović'));
    $metaDescription = trim((string) ($record['meta_description'] ?? ''));
    $contentHtml = (string) ($record['content_html'] ?? '');
    $extraHeadHtml = trim((string) ($record['extra_head_html'] ?? ''));
    $extraFooterHtml = trim((string) ($record['extra_footer_html'] ?? ''));
    $trailingAreaHtml = '';
    $isDatabasePage = cms_is_database_created_page($record);

    if ($isDatabasePage) {
        $contentHtml = cms_strip_generated_db_page_placeholder($contentHtml, $record['page_title'] ?? $title);
    }

    $pageBannerHtml = cms_page_content_needs_default_banner($record, $contentHtml)
        ? cms_default_inner_page_banner_html()
        : '';
    $pdo = site_db();

    if ($pdo instanceof PDO && !empty($record['page_id']) && site_table_exists($pdo, 'cms_content_items')) {
        $areaLimits = [];
        if (($record['page_key'] ?? '') === 'index') {
            $areaLimits = cms_get_homepage_display_limits($pdo);
        }

        $replacement = cms_replace_page_area_placeholders($pdo, $record, $contentHtml, $areaLimits);
        $contentHtml = $replacement['content_html'];
        $trailingAreaHtml = cms_render_unused_page_areas($pdo, $record, $replacement['used_area_keys'], $areaLimits);
    }

    $contentHtml = cms_replace_special_placeholders($contentHtml, $record);

    if ($isDatabasePage) {
        $contentHtml = cms_render_database_page_main_html($title, $contentHtml);
    }

    $hasDownloadArea = strpos($contentHtml . "\n" . $trailingAreaHtml, 'data-area-key="page-downloads"') !== false;
    $hasGalleryArea = strpos($contentHtml . "\n" . $trailingAreaHtml, 'data-area-key="page-gallery"') !== false;
    ?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo site_escape($metaDescription); ?>">
    <meta name="author" content="OŠ Zdravko Jovanović">
    <title><?php echo site_escape($title); ?></title>
    <link rel="shortcut icon" href="">
    <?php foreach (cms_page_content_default_stylesheets() as $stylesheet): ?>
        <link rel="stylesheet" href="<?php echo site_escape($stylesheet); ?>">
    <?php endforeach; ?>
    <?php if ($hasDownloadArea): ?>
        <style>
            .cms-download-area {
                margin: 10px 0 0;
            }

            .cms-download-area-embedded {
                margin-top: 18px;
            }

            .cms-download-shell {
                background: #ffffff;
                border: 1px solid #e9e1d5;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 8px 20px rgba(49, 37, 25, 0.05);
            }

            .cms-download-toolbar {
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                gap: 14px;
                align-items: flex-start;
                padding: 18px 20px;
                background: #fcfaf6;
                border-bottom: 1px solid #eee6da;
            }

            .cms-download-toolbar-copy {
                max-width: 700px;
            }

            .cms-download-eyebrow {
                margin: 0 0 6px;
                font-size: 0.95em;
                letter-spacing: 0.04em;
                text-transform: none;
                color: #8a8177;
                font-weight: 600;
            }

            .cms-download-heading {
                display: block;
                margin: 0;
                padding: 0;
                font-size: 1.85em;
                line-height: 1.2;
                color: #2f3133;
            }

            .cms-download-subtitle {
                margin: 6px 0 0;
                font-size: 1.15em;
                line-height: 1.55;
                color: #777068;
            }

            .cms-download-count {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 34px;
                padding: 7px 12px;
                border-radius: 999px;
                background: #f7f3ec;
                border: 1px solid #e7decf;
                color: #756a5d;
                font-size: 1.05em;
                font-weight: 600;
            }

            .cms-download-list {
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .cms-download-item {
                display: flex;
                gap: 18px;
                align-items: flex-start;
                padding: 16px 20px;
                border-bottom: 1px solid #f1ebe1;
                transition: background 0.2s ease;
            }

            .cms-download-item:last-child {
                border-bottom: 0;
            }

            .cms-download-item:hover {
                background: #fcfaf6;
            }

            .cms-download-leading {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 7px;
                min-width: 58px;
            }

            .cms-download-order {
                font-size: 0.95em;
                font-weight: 700;
                letter-spacing: 0.08em;
                color: #a4947a;
            }

            .cms-download-icon {
                width: 42px;
                height: 42px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 12px;
                background: #f8f5ef;
                color: #766a5d;
                font-size: 1.6em;
                box-shadow: inset 0 0 0 1px rgba(146, 116, 72, 0.04);
            }

            .cms-download-item[data-file-kind="PDF"] .cms-download-icon {
                background: #f8f2ef;
                color: #9b6a61;
            }

            .cms-download-item[data-file-kind="DOC"] .cms-download-icon,
            .cms-download-item[data-file-kind="DOCX"] .cms-download-icon,
            .cms-download-item[data-file-kind="ODT"] .cms-download-icon,
            .cms-download-item[data-file-kind="RTF"] .cms-download-icon {
                background: #f1f4f8;
                color: #617a9e;
            }

            .cms-download-item[data-file-kind="XLS"] .cms-download-icon,
            .cms-download-item[data-file-kind="XLSX"] .cms-download-icon,
            .cms-download-item[data-file-kind="CSV"] .cms-download-icon,
            .cms-download-item[data-file-kind="ODS"] .cms-download-icon {
                background: #f0f5ee;
                color: #5f855e;
            }

            .cms-download-item[data-file-kind="ZIP"] .cms-download-icon {
                background: #f3f1f8;
                color: #776b9f;
            }

            .cms-download-main {
                flex: 1 1 auto;
                min-width: 0;
            }

            .cms-download-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                margin-bottom: 7px;
            }

            .cms-download-badge {
                display: inline-flex;
                align-items: center;
                padding: 3px 8px;
                border-radius: 999px;
                background: #f7f3ec;
                color: #7a7064;
                font-size: 0.92em;
                font-weight: 600;
                letter-spacing: 0.02em;
                text-transform: uppercase;
            }

            .cms-download-badge-file {
                background: #efe6d4;
                color: #6b5b42;
            }

            .cms-download-name {
                margin: 0;
                font-size: 1.45em;
                line-height: 1.4;
                font-weight: 600;
            }

            .cms-download-name a {
                color: #2f3133;
                text-decoration: none;
            }

            .cms-download-name a:hover {
                color: #6f5a3c;
            }

            .cms-download-summary {
                margin: 8px 0 0;
                font-size: 1.15em;
                line-height: 1.6;
                color: #66615c;
            }

            .cms-download-body {
                margin-top: 10px;
                font-size: 1.12em;
                line-height: 1.7;
                color: #4c4f52;
            }

            .cms-download-body > :last-child {
                margin-bottom: 0;
            }

            .cms-download-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                justify-content: flex-end;
                min-width: 144px;
            }

            .cms-download-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 98px;
                padding: 10px 14px;
                border-radius: 999px;
                font-size: 1em;
                font-weight: 600;
                letter-spacing: 0.02em;
                text-transform: none;
                text-decoration: none;
                color: #2f3133;
                background: #f3ead0;
                border: 1px solid #e4d8b7;
                box-shadow: none;
            }

            .cms-download-button:hover {
                background: #ede1c0;
                color: #2f3133;
            }

            .cms-download-button-secondary {
                background: #fffdfa;
                border: 1px solid #e8dfd2;
                box-shadow: none;
            }

            .cms-download-button-secondary:hover {
                background: #f8f4ed;
            }

            @media (max-width: 767px) {
                .cms-download-toolbar {
                    padding: 16px;
                }

                .cms-download-heading {
                    font-size: 1.7em;
                }

                .cms-download-item {
                    flex-direction: column;
                    padding: 16px;
                }

                .cms-download-leading {
                    flex-direction: row;
                    min-width: 0;
                }

                .cms-download-actions {
                    width: 100%;
                    justify-content: flex-start;
                    min-width: 0;
                }
            }
        </style>
    <?php endif; ?>
    <?php if ($hasGalleryArea): ?>
        <link rel="stylesheet" href="css/prettyPhoto.css">
        <style>
            .cms-gallery-card {
                transition: transform 0.24s ease, box-shadow 0.24s ease;
            }

            .cms-gallery-link:hover .cms-gallery-overlay,
            .cms-gallery-link:focus .cms-gallery-overlay {
                opacity: 1;
            }

            .cms-gallery-link:hover .cms-gallery-image,
            .cms-gallery-link:focus .cms-gallery-image {
                transform: scale(1.04);
            }

            .cms-gallery-image {
                transition: transform 0.28s ease;
            }

            .cms-gallery-meta p:last-child {
                margin-bottom: 0;
            }

            .cms-gallery-card-wrap:hover .cms-gallery-card {
                transform: translateY(-4px);
                box-shadow: 0 16px 32px rgba(38, 28, 21, 0.14);
            }
        </style>
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600&subset=latin,cyrillic" rel="stylesheet" type="text/css">
    <!--[if IE]>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <?php if ($extraHeadHtml !== ''): ?>
        <?php echo $extraHeadHtml; ?>
    <?php endif; ?>
</head>
<body>
<?php echo cms_capture_repaired_include('includes/header.php'); ?>
<?php echo $pageBannerHtml; ?>
<?php echo $contentHtml; ?>
<?php echo $trailingAreaHtml; ?>
<a class="fa fa-arrow-circle-up" id="toTop" href="#" style="display: inline;"></a>
<?php echo cms_capture_repaired_include('includes/footer.php'); ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="js/script.js"></script>
<?php if ($hasGalleryArea): ?>
<script src="js/photo-plugin/jquery.prettyPhoto.js"></script>
<script>
    jQuery(function ($) {
        var $galleryLinks = $('a[data-gallery-lightbox="prettyphoto"]');
        if ($galleryLinks.length && $.isFunction($.fn.prettyPhoto)) {
            $galleryLinks.prettyPhoto({
                theme: 'light_square',
                social_tools: false,
                deeplinking: false,
                overlay_gallery: true,
                show_title: true,
                slideshow: false
            });
        }
    });
</script>
<?php endif; ?>
<?php if ($extraFooterHtml !== ''): ?>
    <?php echo $extraFooterHtml; ?>
<?php endif; ?>
</body>
</html>
<?php
}

function cms_maybe_render_managed_page($pageKey, array $options = [])
{
    $pageKey = trim((string) $pageKey);

    if ($pageKey === '' || !cms_page_content_is_manageable($pageKey)) {
        return false;
    }

    $pdo = site_db();
    if (!$pdo instanceof PDO || !site_has_tables(['cms_pages', 'cms_page_content'])) {
        return false;
    }

    cms_sync_pages($pdo);

    $record = cms_get_page_content_by_key($pdo, $pageKey, true);

    if (!$record) {
        $sourceFile = $options['source_file'] ?? (site_root_path() . DIRECTORY_SEPARATOR . $pageKey . '.php');
        cms_seed_page_content_from_source($pdo, $pageKey, $sourceFile, false);
        $record = cms_get_page_content_by_key($pdo, $pageKey, true);
    }

    if (!$record) {
        return false;
    }

    cms_render_page_content_record($record);
    exit;
}

function cms_render_managed_page_by_route($routePath)
{
    $pdo = site_db();
    if (!$pdo instanceof PDO || !site_has_tables(['cms_pages', 'cms_page_content'])) {
        return false;
    }

    cms_sync_pages($pdo);
    $record = cms_get_page_content_by_route($pdo, $routePath, true);

    if (!$record) {
        return false;
    }

    cms_render_page_content_record($record);
    exit;
}
