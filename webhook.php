<?php
/**
 * Telegram webhook for safe onboarding status updates.
 *
 * Callback format:
 *   A:status:sessionKey
 */
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/telegram.php';

$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    http_response_code(200);
    exit('OK');
}

if (!isset($update['callback_query'])) {
    http_response_code(200);
    exit('OK');
}

$cb = $update['callback_query'];
$cbId = $cb['id'] ?? '';
$data = $cb['data'] ?? '';
$parts = explode(':', $data, 3);

if (($parts[0] ?? '') !== 'A') {
    tg_answer($cbId, 'Unknown callback');
    http_response_code(200);
    exit('OK');
}

$status = $parts[1] ?? '';
$key = $parts[2] ?? '';
$session = read_session($key);

if (!$session) {
    tg_answer($cbId, 'Session not found', true);
    http_response_code(200);
    exit('OK');
}

$messages = [
    'review' => 'Your request is under review by the operations team.',
    'approved' => 'Your request has been approved. Account setup is in progress.',
    'complete' => 'Your onboarding is complete. The team will share the next steps.',
    'blocked' => 'This request could not be approved. Please contact your manager.',
];

if (!array_key_exists($status, $messages)) {
    tg_answer($cbId, 'Unknown action', true);
    http_response_code(200);
    exit('OK');
}

$session['status'] = $status;
$session['message'] = $messages[$status];
write_session($key, $session);

$profile = $session['profile'] ?? [];
$name = $profile['fullName'] ?? 'request';
tg_answer($cbId, ucfirst($status) . ' saved');
tg_send(
    "*Onboarding Status Updated*\n"
    . "Name: `" . md_escape($name) . "`\n"
    . "Status: `" . md_escape($status) . "`\n"
    . "Session: `" . md_escape($key) . "`"
);

http_response_code(200);
echo 'OK';
