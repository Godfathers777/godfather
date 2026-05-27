<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve index.html for root path
if ($path === '/' || $path === '') {
    readfile(__DIR__ . '/index.html');
    exit;
}

$file = __DIR__ . $path;

// Serve existing static files directly
if (is_file($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $mime = [
        'html' => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
    ];
    if (isset($mime[$ext])) {
        header('Content-Type: ' . $mime[$ext]);
    }
    readfile($file);
    exit;
}

// Fall through to webhook handler
require __DIR__ . '/webhook.php';
