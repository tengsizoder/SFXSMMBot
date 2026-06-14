<?php
/**
 * SFXSMM Bot — Yordamchi funksiyalar
 * 
 * Telegram API bilan ishlash va umumiy yordamchi funksiyalar.
 */

// ==========================================
// TELEGRAM API FUNKSIYALARI
// ==========================================

/**
 * Telegram Bot API ga so'rov yuborish
 */
function bot(string $method, array $params = []): ?object
{
    $url = BOT_API_URL . $method;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('Telegram API xato: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    curl_close($ch);
    return json_decode($response);
}

/**
 * Xabar yuborish (qisqa variant)
 */
function sms(string $chatId, string $text, $markup = null): ?object
{
    $params = [
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ];

    if ($markup !== null) {
        $params['reply_markup'] = is_string($markup) ? $markup : json_encode($markup);
    }

    return bot('sendMessage', $params);
}

/**
 * Xabarni tahrirlash
 */
function edit(string $chatId, int $messageId, string $text, $markup = null): ?object
{
    $params = [
        'chat_id'    => $chatId,
        'message_id' => $messageId,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ];

    if ($markup !== null) {
        $params['reply_markup'] = is_string($markup) ? $markup : json_encode($markup);
    }

    return bot('editMessageText', $params);
}

/**
 * Xabarni o'chirish
 */
function deleteMessage(string $chatId, int $messageId): ?object
{
    return bot('deleteMessage', [
        'chat_id'    => $chatId,
        'message_id' => $messageId,
    ]);
}

/**
 * Callback query ga javob berish
 */
function answerCallback(string $callbackId, string $text = '', bool $showAlert = false): ?object
{
    return bot('answerCallbackQuery', [
        'callback_query_id' => $callbackId,
        'text'              => $text,
        'show_alert'        => $showAlert,
    ]);
}

/**
 * Rasm bilan xabar yuborish
 */
function sendPhoto(string $chatId, string $photo, string $caption = '', $markup = null): ?object
{
    $params = [
        'chat_id'    => $chatId,
        'photo'      => $photo,
        'caption'    => $caption,
        'parse_mode' => 'HTML',
    ];

    if ($markup !== null) {
        $params['reply_markup'] = is_string($markup) ? $markup : json_encode($markup);
    }

    return bot('sendPhoto', $params);
}

// ==========================================
// KEYBOARD YARATISH FUNKSIYALARI
// ==========================================

/**
 * Inline keyboard yaratish
 */
function inlineKeyboard(array $buttons): string
{
    return json_encode(['inline_keyboard' => $buttons]);
}

/**
 * Reply keyboard yaratish
 */
function replyKeyboard(array $buttons, bool $resize = true): string
{
    return json_encode([
        'resize_keyboard' => $resize,
        'keyboard'        => $buttons,
    ]);
}

// ==========================================
// YORDAMCHI FUNKSIYALAR
// ==========================================

/**
 * Base64 encode/decode
 */
function enc(string $action, string $data): string
{
    return $action === 'encode' ? base64_encode($data) : base64_decode($data);
}

/**
 * Raqamni formatlash (bo'sh joy bilan)
 */
function formatNumber($number): string
{
    return number_format(floatval($number), 0, '', ' ');
}

/**
 * Tasodifiy kalit yaratish (referal kod, API kalit)
 */
function generateKey(int $length = 7): string
{
    $chars = 'ABCDEFGHIJKLMNOPRSTUVXYZ1234567890';
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $key;
}

/**
 * API kaliti yaratish (MD5 asosida)
 */
function generateApiKey(): string
{
    return md5(uniqid(random_int(0, 999999), true));
}

/**
 * Google Translate orqali tarjima qilish
 */
function translate(string $text, string $targetLang = 'uz'): string
{
    $url = "http://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl={$targetLang}&dt=t&q=" . urlencode($text);
    $response = @json_decode(@file_get_contents($url), true);
    return $response[0][0][0] ?? $text;
}

/**
 * Hozirgi sana va vaqtni olish
 */
function currentDate(): string
{
    return date('d/m/Y | H:i');
}

/**
 * Step (qadam) ni saqlash
 */
function setStep(string $userId, string $step): void
{
    file_put_contents(USER_DIR . "$userId.step", $step);
}

/**
 * Step ni o'qish
 */
function getStep(string $userId): string
{
    $file = USER_DIR . "$userId.step";
    return file_exists($file) ? file_get_contents($file) : '';
}

/**
 * Step ni o'chirish
 */
function clearStep(string $userId): void
{
    $files = [
        USER_DIR . "$userId.step",
        USER_DIR . "$userId.ur",
        USER_DIR . "$userId.params",
        USER_DIR . "$userId.qu",
        USER_DIR . "$userId.si",
    ];

    foreach ($files as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }
}

/**
 * Foydalanuvchi admin ekanligini tekshirish
 */
function isAdmin(string $userId): bool
{
    return $userId === ADMIN_ID;
}

/**
 * Papkani rekursiv o'chirish
 */
function removeDirectory(string $path): bool
{
    if (!is_dir($path)) {
        return is_file($path) ? unlink($path) : false;
    }

    $items = array_diff(scandir($path), ['.', '..']);
    foreach ($items as $item) {
        removeDirectory($path . '/' . $item);
    }

    return rmdir($path);
}
