<?php
require_once __DIR__ . '/config.php';

function md_escape($text) {
    return str_replace(
        ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'],
        ['\_', '\*', '\[', '\]', '\(', '\)', '\~', '\`', '\>', '\#', '\+', '\-', '\=', '\|', '\{', '\}', '\.', '\!'],
        (string)$text
    );
}

function tg_request($method, array $data) {
    if (defined('TELEGRAM_ENABLED') && !TELEGRAM_ENABLED) {
        return ['ok' => true, 'description' => 'Telegram disabled'];
    }
    if (!defined('BOT_TOKEN') || BOT_TOKEN === '') {
        return ['ok' => false, 'description' => 'BOT_TOKEN is missing'];
    }
    $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $result = curl_exec($ch);
    if ($result === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['ok' => false, 'description' => $error];
    }
    curl_close($ch);
    $decoded = json_decode($result, true);
    return is_array($decoded) ? $decoded : ['ok' => false, 'description' => 'Invalid Telegram response'];
}

function tg_send($text, $reply_markup = null) {
    $payload = [
        'chat_id' => CHAT_ID,
        'text' => $text,
        'parse_mode' => 'MarkdownV2',
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
        'text' => $text,
        'show_alert' => $show_alert,
    ]);
}
