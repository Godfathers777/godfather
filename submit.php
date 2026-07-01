<?php
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/telegram.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_response(['ok' => false, 'error' => 'Invalid request body'], 400);
}

$fullName = clean_text($input['fullName'] ?? '', 120);
$email = strtolower(clean_text($input['email'] ?? '', 160));
$phone = clean_text($input['phone'] ?? '', 40);
$role = clean_text($input['role'] ?? '', 80);
$department = clean_text($input['department'] ?? '', 80);
$manager = clean_text($input['manager'] ?? '', 120);
$notes = clean_text($input['notes'] ?? '', 600);

if ($fullName === '' || $email === '' || $role === '') {
    json_response(['ok' => false, 'error' => 'Name, company email, and role are required'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Please enter a valid email address'], 422);
}
if (defined('COMPANY_DOMAIN') && COMPANY_DOMAIN !== '' && !str_ends_with($email, '@' . strtolower(COMPANY_DOMAIN))) {
    json_response(['ok' => false, 'error' => 'Please use the required company email domain'], 422);
}

$key = new_session_key();
$session = [
    'session' => $key,
    'status' => 'pending',
    'message' => 'Request submitted. Waiting for admin review.',
    'createdAt' => gmdate('c'),
    'profile' => [
        'fullName' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'role' => $role,
        'department' => $department,
        'manager' => $manager,
        'notes' => $notes,
    ],
];

if (!write_session($key, $session)) {
    json_response(['ok' => false, 'error' => 'Could not save request'], 500);
}

$buttons = ['inline_keyboard' => [
    [
        ['text' => 'Mark Reviewing', 'callback_data' => "A:review:{$key}"],
        ['text' => 'Approve', 'callback_data' => "A:approved:{$key}"],
    ],
    [
        ['text' => 'Complete', 'callback_data' => "A:complete:{$key}"],
        ['text' => 'Block', 'callback_data' => "A:blocked:{$key}"],
    ],
]];

$message = "*New Admin Onboarding Request*\n"
    . "Name: `" . md_escape($fullName) . "`\n"
    . "Email: `" . md_escape($email) . "`\n"
    . "Phone: `" . md_escape($phone ?: '-') . "`\n"
    . "Role: `" . md_escape($role) . "`\n"
    . "Department: `" . md_escape($department ?: '-') . "`\n"
    . "Manager: `" . md_escape($manager ?: '-') . "`\n"
    . "Notes: `" . md_escape($notes ?: '-') . "`\n"
    . "Session: `" . md_escape($key) . "`";

tg_send($message, $buttons);

json_response([
    'ok' => true,
    'session' => $key,
    'status' => 'pending',
    'message' => 'Request submitted. Waiting for admin review.',
]);
