<?php

$BOT_TOKEN = "8449094358:AAGO5B_LAw1huy3hF1mxDI96bgFeO7CJGsw";
$CHANNEL_ID = -1003182883148
$ADMIN_ID = 620451383;

function tg($method, $data = []) {
    global $BOT_TOKEN;
    $url = "https://api.telegram.org/bot{$BOT_TOKEN}/{$method}";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

$update = json_decode(file_get_contents("php://input"), true);
if (!$update) exit;

// /start — просто проверка
if (isset($update['message'])) {
    $chat_id = $update['message']['chat']['id'];
    $text = trim($update['message']['text'] ?? '');

    if ($text === "/start") {
        tg("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "✅ Бот работает. Жду заявок в канал."
        ]);
        exit;
    }
}

// ЗАЯВКА В КАНАЛ
if (isset($update['chat_join_request'])) {

    $chat_id = $update['chat_join_request']['chat']['id'];
    $user_id = $update['chat_join_request']['from']['id'];

    if ($chat_id != $CHANNEL_ID) exit;

    // создаём ссылку
    $res = tg("createChatInviteLink", [
        "chat_id" => $CHANNEL_ID,
        "member_limit" => 1,
        "expire_date" => time() + 600
    ]);

    if (!isset($res['result']['invite_link'])) exit;

    $link = $res['result']['invite_link'];

    // отправляем ссылку
    tg("sendMessage", [
        "chat_id" => $user_id,
        "text" => "🔗 Твоя персональная ссылка:\n\n$link"
    ]);

    // отклоняем заявку
    tg("declineChatJoinRequest", [
        "chat_id" => $CHANNEL_ID,
        "user_id" => $user_id
    ]);

    // лог админу
    tg("sendMessage", [
        "chat_id" => $ADMIN_ID,
        "text" => "✅ Заявка → ссылка\n👤 $user_id\n$link"
    ]);

    exit;
}
