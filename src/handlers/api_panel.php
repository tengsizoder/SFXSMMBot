<?php
/**
 * SFXSMM Bot — API Panel Handler
 * 
 * Foydalanuvchi API boshqaruvi va Admin API sozlamalari.
 */

// ==========================================
// 🔑 FOYDALANUVCHI API PANELI
// ==========================================

function handleApiPanel(string $chatId): void
{
    sendPhoto($chatId, 'https://t.me/SmmGlobalRasmlari/28',
        "<b>🔑 Hamkorlik (API) bo'limi

Sizning ham botingiz bormi? Bizning xizmatlarimizni o'z botingizga ulang va daromad ishlang!

💎 Hamkorlar uchun 5-15% gacha chegirmalar mavjud.

Quyidagilardan birini tanlang 👇</b>",
        inlineKeyboard([
            [['text' => '🔑 API kalitni olish', 'callback_data' => 'api_getkey']],
            [['text' => '📖 Qo\'llanma', 'callback_data' => 'api_guide']],
        ])
    );
}

function handleApiGetKey(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);
    deleteMessage($chatId, $messageId);

    $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$chatId]);
    if (!$user) {
        sms($chatId, "✗ Xatolik!");
        return;
    }

    $fullKey = $user['api_key'];
    $hiddenKey = substr($fullKey, 0, 8) . '••••••••' . substr($fullKey, -6);
    $balance = formatNumber($user['balance']);
    $apiUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.uz') . '/api/v2';
    $apiStatus = ($user['api_status'] ?? 'active') === 'paused' ? '⏸ To\'xtatish' : '⏸ API ni to\'xtatish';
    $toggleText = ($user['api_status'] ?? 'active') === 'paused' ? '▶️ API ni yoqish' : $apiStatus;

    sms($chatId, "🔑 <b>Sizning API ma'lumotlaringiz</b>

🌐 <b>API manzil:</b>
<code>$apiUrl</code>

🔑 <b>API kalit:</b>
<code>$hiddenKey</code>

💰 <b>API balans:</b> $balance so'm

⚠️ API kalitni hech kimga bermang!", inlineKeyboard([
        [['text' => '▪ Kalitni to\'liq nusxalash', 'copy_text' => ['text' => $fullKey]]],
        [['text' => '🌐 API manzilni nusxalash', 'copy_text' => ['text' => $apiUrl]]],
        [['text' => '▪ Statistika', 'callback_data' => 'api_stats']],
        [['text' => $toggleText, 'callback_data' => 'api_toggle']],
        [['text' => '🔄 Yangi kalit olish', 'callback_data' => 'api_newkey']],
        [['text' => '📖 Qo\'llanma', 'callback_data' => 'api_methods']],
        [['text' => '⬅️ Orqaga', 'callback_data' => 'api_home']],
    ]));
}

function handleApiStats(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$chatId]);
    $userId = $user['user_id'] ?? $chatId;

    $totalOrders = Database::count("SELECT COUNT(*) FROM myorder WHERE user_id = ?", [$chatId]);
    $completed = Database::count("SELECT COUNT(*) FROM myorder WHERE user_id = ? AND status = 'Completed'", [$chatId]);
    $pending = Database::count("SELECT COUNT(*) FROM myorder WHERE user_id = ? AND status IN ('Pending','In progress','Processing')", [$chatId]);
    $canceled = Database::count("SELECT COUNT(*) FROM myorder WHERE user_id = ? AND status IN ('Canceled','Failed')", [$chatId]);
    $todayOrders = Database::count("SELECT COUNT(*) FROM myorder WHERE user_id = ? AND DATE(order_create) = CURDATE()", [$chatId]);

    $totalSpent = Database::sum("SELECT SUM(retail) FROM myorder WHERE user_id = ?", [$chatId]);
    $todaySpent = Database::sum("SELECT SUM(retail) FROM myorder WHERE user_id = ? AND DATE(order_create) = CURDATE()", [$chatId]);
    $monthSpent = Database::sum("SELECT SUM(retail) FROM myorder WHERE user_id = ? AND MONTH(order_create) = MONTH(CURDATE()) AND YEAR(order_create) = YEAR(CURDATE())", [$chatId]);

    $apiStatus = ($user['api_status'] ?? 'active') === 'paused' ? '⏸ To\'xtatilgan' : '✓ Faol';

    edit($chatId, $messageId, "▪ <b>API statistikasi</b>

📌 <b>API holati:</b> $apiStatus
💰 <b>Joriy balans:</b> " . formatNumber($user['balance']) . " so'm

<blockquote>📦 <b>Buyurtmalar:</b>

Jami:  <b>$totalOrders</b> ta
Bugungi:  <b>$todayOrders</b> ta
Bajarilgan:  <b>$completed</b> ta
Jarayonda:  <b>$pending</b> ta
Bekor/xato:  <b>$canceled</b> ta</blockquote>

<blockquote>💰 <b>Sarflar:</b>

Bugungi:  <b>" . formatNumber($todaySpent) . "</b> so'm
Shu oydagi:  <b>" . formatNumber($monthSpent) . "</b> so'm
Jami:  <b>" . formatNumber($totalSpent) . "</b> so'm</blockquote>", inlineKeyboard([
        [['text' => '🔄 Yangilash', 'callback_data' => 'api_stats']],
        [['text' => '🔑 API ma\'lumotlarim', 'callback_data' => 'api_getkey']],
        [['text' => '⬅️ Orqaga', 'callback_data' => 'api_home']],
    ]));
}

function handleApiNewKey(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    edit($chatId, $messageId, "🔄 <b>API kalitni yangilash</b>

⚠️ Yangi kalit olgandan keyin eski kalit ishlamay qoladi.

Davom etasizmi?", inlineKeyboard([
        [
            ['text' => '✓ Ha, yangilash', 'callback_data' => 'api_newkey_yes'],
            ['text' => '✗ Bekor', 'callback_data' => 'api_getkey'],
        ],
    ]));
}

function handleApiNewKeyConfirm(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId, '🔄 Yangilanmoqda...');

    $newKey = generateApiKey();
    Database::execute("UPDATE users SET api_key = ? WHERE id = ?", [$newKey, $chatId]);

    $hiddenKey = substr($newKey, 0, 8) . '••••••••' . substr($newKey, -6);

    edit($chatId, $messageId, "✓ <b>API kalit muvaffaqiyatli yangilandi!</b>

🔑 <b>Yangi kalit:</b>
<code>$hiddenKey</code>

⚠️ Eski kalit endi ishlamaydi.", inlineKeyboard([
        [['text' => '▪ Yangi kalitni nusxalash', 'copy_text' => ['text' => $newKey]]],
        [['text' => '🔑 API ma\'lumotlarim', 'callback_data' => 'api_getkey']],
        [['text' => '⬅️ Orqaga', 'callback_data' => 'api_home']],
    ]));
}

function handleApiToggle(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $user = Database::fetchOne("SELECT api_status FROM users WHERE id = ?", [$chatId]);
    $current = ($user['api_status'] ?? 'active');

    if ($current === 'active') {
        edit($chatId, $messageId, "⏸ <b>API ni to'xtatish</b>

API to'xtatilganda hech kim sizning kalit orqali buyurtma bera olmaydi.

API ni to'xtatmoqchimisiz?", inlineKeyboard([
            [
                ['text' => '⏸ Ha, to\'xtatish', 'callback_data' => 'api_pause_yes'],
                ['text' => '✗ Bekor', 'callback_data' => 'api_getkey'],
            ],
        ]));
    } else {
        edit($chatId, $messageId, "▶️ <b>API ni yoqish</b>

API yoqilgandan keyin buyurtma berish imkoni qayta tiklanadi.

API ni yoqmoqchimisiz?", inlineKeyboard([
            [
                ['text' => '▶️ Ha, yoqish', 'callback_data' => 'api_resume_yes'],
                ['text' => '✗ Bekor', 'callback_data' => 'api_getkey'],
            ],
        ]));
    }
}

function handleApiPause(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId, '⏸ API to\'xtatildi!');
    Database::execute("UPDATE users SET api_status = 'paused' WHERE id = ?", [$chatId]);

    edit($chatId, $messageId, "⏸ <b>API to'xtatildi!</b>

Qayta yoqish uchun pastdagi tugmani bosing.", inlineKeyboard([
        [['text' => '▶️ API ni yoqish', 'callback_data' => 'api_toggle']],
        [['text' => '🔑 API ma\'lumotlarim', 'callback_data' => 'api_getkey']],
        [['text' => '⬅️ Orqaga', 'callback_data' => 'api_home']],
    ]));
}

function handleApiResume(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId, '▶️ API yoqildi!');
    Database::execute("UPDATE users SET api_status = 'active' WHERE id = ?", [$chatId]);

    edit($chatId, $messageId, "▶️ <b>API qayta yoqildi!</b>

Yaxshi savdo tilaymiz! →", inlineKeyboard([
        [['text' => '🔑 API ma\'lumotlarim', 'callback_data' => 'api_getkey']],
        [['text' => '⬅️ Orqaga', 'callback_data' => 'api_home']],
    ]));
}

// ==========================================
// ADMIN: API PROVIDER SOZLASH
// ==========================================

function handleAdminApiPanel(string $chatId): void
{
    $providers = Database::fetchAll("SELECT * FROM providers ORDER BY id ASC");
    $count = count($providers);

    $msg = "🔑 <b>API SOZLAMALAR</b>
━━━━━━━━━━━━━━━━━━━━

🌐 <b>Provayderlar:</b> $count ta\n\n";

    foreach ($providers as $i => $p) {
        $shortUrl = str_replace(['https://', '/api/v1', '/api/v2', '/api/adapter/default/index'], '', $p['api_url']);
        $num = $i + 1;
        $msg .= "$num. 🌐 <b>$shortUrl</b>\n";
    }

    sms($chatId, $msg, inlineKeyboard([
        [['text' => '➕ Yangi provider qo\'shish', 'callback_data' => 'api_add_provider']],
        [['text' => '🗑️ Provider o\'chirish', 'callback_data' => 'api_delete']],
        [['text' => '⬅️ Orqaga', 'callback_data' => 'api_main']],
    ]));
}
