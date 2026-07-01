<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/' || $path === '') {
    require __DIR__ . '/index.html';
    exit;
}

$file = __DIR__ . $path;
if (is_file($file)) {
    return false;
}

require __DIR__ . '/webhook.php';
