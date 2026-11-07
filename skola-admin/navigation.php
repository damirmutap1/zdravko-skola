<?php

$queryString = trim((string) ($_SERVER['QUERY_STRING'] ?? ''));
$location = 'core/navigation.php' . ($queryString !== '' ? '?' . $queryString : '');

header('Location: ' . $location, true, 302);
exit;
