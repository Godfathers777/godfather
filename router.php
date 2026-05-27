<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false;
}
$phpFile = __DIR__ . $path;
if (is_file($phpFile)) {
    require $phpFile;
    exit;
}
require __DIR__ . '/index.html';
