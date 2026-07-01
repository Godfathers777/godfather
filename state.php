<?php
require_once __DIR__ . '/storage.php';

$key = $_GET['session'] ?? '';
$session = read_session($key);
if (!$session) {
    json_response(['ok' => false, 'error' => 'Session not found'], 404);
}

json_response(['ok' => true, 'session' => public_session($session)]);
