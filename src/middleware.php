<?php
/**
 * SFXSMM Bot — Middleware (O'rta qatlam)
 * 
 * Har bir so'rov uchun:
 * - Bot holati tekshirish
 * - Foydalanuvchini ro'yxatdan o'tkazish
 * - Ban tekshirish
 * - Kanal obuna tekshirish
 */

// ==========================================
// BOT HOLATI TEKSHIRISH
// ==========================================

/**
 * Bot yoqilganmi tekshirish
 * Agar o'chirilgan bo'lsa — faqat admin ishlata oladi
 */
function checkBotStatus(string $userId): bool
{
    if (isAdmin($userId)) {
        return true;
    }

    $settings = Database::fetchOne("SELECT bot_status FROM settings WHERE id = 1");

    if ($settings && $settings['bot_status'] === 'deactive') {
        sms($userId, "⚠️ <b>Texnik ishlar olib borilmoqda</b>

Hurmatli foydalanuvchi, hozirda botda texnik ishlar olib borilmoqda. Tez orada xizmat qayta tiklanadi.

⏳ Iltimos, biroz kutib qayta urinib ko'ring.

📞 Murojaat uchun: @" . ADMIN_USERNAME);
        return false;
    }

    return true;
}

// ==========================================
// FOYDALANUVCHINI RO'YXATDAN O'TKAZISH
// ==========================================

/**
 * Foydalanuvchi mavjudligini tekshirish va kerak bo'lsa ro'yxatga olish
 */
function registerUser(string $userId): void
{
    // users jadvaliga tekshirish
    $user = Database::fetchOne("SELECT id FROM users WHERE id = ?", [$userId]);

    if (!$user) {
        $apiKey = generateApiKey();
        $referal = generateKey();

        Database::execute(
            "INSERT INTO users (id, status, balance, outing, api_key, referal, registration_date) 
             VALUES (?, 'active', 0, 0, ?, ?, NOW())",
            [$userId, $apiKey, $referal]
        );
    }

    // user_id jadvaliga tekshirish
    $userIdRow = Database::fetchOne("SELECT user_id FROM user_id WHERE user_id = ?", [$userId]);
    if (!$userIdRow) {
        $date = currentDate();
        Database::execute("INSERT INTO user_id (user_id, reg) VALUES (?, ?)", [$userId, $date]);
    }

    // kabinet jadvaliga tekshirish
    $kabinet = Database::fetchOne("SELECT user_id FROM kabinet WHERE user_id = ?", [$userId]);
    if (!$kabinet) {
        Database::execute(
            "INSERT INTO kabinet (user_id, pul, pul2, odam, ban, status) VALUES (?, '0', '0', '0', 'unban', 'Oddiy')",
            [$userId]
        );
    }

    // card jadvaliga tekshirish
    $card = Database::fetchOne("SELECT user_id FROM card WHERE user_id = ?", [$userId]);
    if (!$card) {
        Database::execute("INSERT INTO card (user_id, cc, fc) VALUES (?, '0', '0')", [$userId]);
    }

    // api jadvaliga tekshirish
    $api = Database::fetchOne("SELECT user_id FROM api WHERE user_id = ?", [$userId]);
    if (!$api) {
        $key = generateApiKey();
        Database::execute("INSERT INTO api (user_id, api) VALUES (?, ?)", [$userId, $key]);
    }

    // uid jadvaliga tekshirish
    $uid = Database::fetchOne("SELECT user_id FROM uid WHERE user_id = ?", [$userId]);
    if (!$uid) {
        Database::execute("INSERT INTO uid (user_id) VALUES (?)", [$userId]);
    }
}

// ==========================================
// BAN TEKSHIRISH
// ==========================================

/**
 * Foydalanuvchi bloklangan yoki deaktivmi tekshirish
 */
function isUserBanned(string $userId): bool
{
    $user = Database::fetchOne("SELECT status FROM users WHERE id = ?", [$userId]);
    return $user && $user['status'] === 'deactive';
}

// ==========================================
// KANAL OBUNA TEKSHIRISH
// ==========================================

/**
 * Foydalanuvchi majburiy kanallarga obuna bo'lganmi tekshirish
 * 
 * @return true — obuna bo'lgan, false — obuna emas (xabar yuboriladi)
 */
function checkSubscription(string $userId): bool
{
    $channelFile = SET_DIR . 'channel';

    if (!file_exists($channelFile)) {
        return true;
    }

    $content = trim(file_get_contents($channelFile));
    if (empty($content)) {
        return true;
    }

    $channels = explode("\n", $content);
    $unsubscribed = false;
    $buttons = [];

    foreach ($channels as $i => $channel) {
        $channel = trim($channel);
        if (empty($channel)) continue;

        $channelName = str_replace('@', '', $channel);

        // Obuna holatini tekshirish
        $member = bot('getChatMember', [
            'chat_id' => $channel,
            'user_id' => $userId,
        ]);

        $status = $member->result->status ?? '';
        $isMember = in_array($status, ['creator', 'administrator', 'member']);

        if (!$isMember) {
            // Kanal nomini olish
            $chatInfo = bot('getChat', ['chat_id' => $channel]);
            $title = $chatInfo->result->title ?? $channelName;

            $buttons[] = [['text' => $title, 'url' => "https://t.me/$channelName"]];
            $unsubscribed = true;
        }
    }

    if ($unsubscribed) {
        $buttons[] = [['text' => '✅ Tekshirish', 'callback_data' => 'check_subscription']];

        sms($userId, "⛔ <b>Botdan foydalanish uchun, quyidagi kanallarga obuna bo'ling:</b>", 
            inlineKeyboard($buttons)
        );
        return false;
    }

    return true;
}

// ==========================================
// BOT BLOKLASH HODISASI
// ==========================================

/**
 * Foydalanuvchi botni bloklagan/blokladan chiqargan holatni boshqarish
 */
function handleBotBlocked(object $update): bool
{
    $myChatMember = $update->my_chat_member ?? null;
    if (!$myChatMember) {
        return false;
    }

    $userId = $myChatMember->from->id ?? '';
    $userName = $myChatMember->from->first_name ?? '';
    $newStatus = $myChatMember->new_chat_member->status ?? '';

    if ($newStatus === 'kicked') {
        // Foydalanuvchi botni blokladi
        $existing = Database::fetchOne("SELECT id FROM soxta WHERE user_id = ?", [$userId]);

        if ($existing) {
            Database::execute("UPDATE soxta SET come = 'gone' WHERE user_id = ?", [$userId]);
            sms(ADMIN_ID, "<b>Foydalanuvchi botni yana blokladi!</b>", 
                inlineKeyboard([[['text' => $userName, 'url' => "tg://user?id=$userId"]]])
            );
        } else {
            Database::execute("DELETE FROM myorder WHERE user_id = ?", [$userId]);
            Database::execute("DELETE FROM users WHERE id = ?", [$userId]);
            Database::execute("INSERT INTO soxta (user_id, come) VALUES (?, 'gone')", [$userId]);

            sms(ADMIN_ID, "<b>Foydalanuvchi botni blokladi</b>", 
                inlineKeyboard([[['text' => $userName, 'url' => "tg://user?id=$userId"]]])
            );
        }

        // Foydalanuvchi fayllarini o'chirish
        clearStep($userId);

        return true;
    }

    if ($newStatus === 'member') {
        // Foydalanuvchi botni blokdan chiqardi
        $existing = Database::fetchOne("SELECT id FROM soxta WHERE user_id = ?", [$userId]);
        if ($existing) {
            Database::execute("UPDATE soxta SET come = 'come' WHERE user_id = ?", [$userId]);
        }
    }

    return false;
}

// ==========================================
// MIDDLEWARE ISHGA TUSHIRISH
// ==========================================

/**
 * Barcha middleware tekshiruvlarni bajarish
 * 
 * @return true — davom etish mumkin, false — to'xtatish
 */
function runMiddleware(object $update): bool
{
    // 1. Bot bloklash hodisasini tekshirish
    if (handleBotBlocked($update)) {
        return false;
    }

    // Chat ID ni aniqlash
    $chatId = $update->message->from->id 
        ?? $update->callback_query->from->id 
        ?? '';

    if (empty($chatId)) {
        return false;
    }

    // 2. Ban tekshirish
    if (isUserBanned($chatId)) {
        return false;
    }

    // 3. Bot holati tekshirish
    if (!checkBotStatus($chatId)) {
        return false;
    }

    // 4. Foydalanuvchini ro'yxatdan o'tkazish (faqat message uchun)
    if (isset($update->message)) {
        registerUser($chatId);
    }

    return true;
}
