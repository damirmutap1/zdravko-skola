<?php

require_once __DIR__ . '/includes/app.php';

$slug = trim((string) ($_GET['slug'] ?? ''), '/');

if ($slug === '' || !cms_render_managed_page_by_route($slug)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="sr"><head><meta charset="utf-8"><title>Stranica nije pronađena</title></head><body><h1>Stranica nije pronađena</h1><p><a href="index.php">Povratak na početnu</a></p></body></html>';
}
