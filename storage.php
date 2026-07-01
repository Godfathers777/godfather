<?php
require_once __DIR__ . '/config.php';

function ensure_session_dir() {
    if (!is_dir(SESSION_DIR)) {
        mkdir(SESSION_DIR, 0755, true);
    }
}

function valid_session_key($key) {
    return is_string($key) && preg_match('/^ob_[A-Za-z0-9_-]{24,80}$/', $key);
}

function session_path($key) {
    if (!valid_session_key($key)) {
        return null;
    }
    return SESSION_DIR . '/' . $key . '.json';
}

function new_session_key() {
    return 'ob_' . bin2hex(random_bytes(18));
}

function read_session($key) {
    $path = session_path($key);
    if (!$path || !is_file($path)) {
        return null;
    }
    $raw = file_get_contents($path);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function write_session($key, array $data) {
    ensure_session_dir();
    $path = session_path($key);
    if (!$path) {
        return false;
    }
    $data['updatedAt'] = gmdate('c');
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents($path, $json, LOCK_EX) !== false;
}

function public_session(array $data) {
    return [
        'status' => $data['status'] ?? 'pending',
        'message' => $data['message'] ?? 'Waiting for admin review.',
        'updatedAt' => $data['updatedAt'] ?? null,
    ];
}

function clean_text($value, $max = 160) {
    $value = trim((string)$value);
    $value = preg_replace('/\s+/', ' ', $value);
    return mb_substr($value, 0, $max);
}

function json_response(array $payload, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}
