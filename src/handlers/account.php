<?php
/**
 * SFXSMM Bot — Foydalanuvchi Hisobi Handler
 * 
 * Balans ko'rish, pul o'tkazish, referal tizimi.
 */

// ==========================================
// 💳 MENING HISOBIM
// ==========================================

function handleMyAccount(string $chatId): void
{
    // Foydalanuvchi ma'lumotlari
    $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$chatId]);
    if (!$user) {
        sms($chatId, "⛔ <b>Xatolik! Qaytadan /start bosing.</b>");
        return;
    }

    // Telegram profil ma'lumotlari
    $chatInfo = bot('getChat', ['chat_id' => $chatId]);
    $firstName = $chatInfo->result->first_name ?? 'Ism mavjud emas';
    $lastName = $chatInfo->result->last_name ?? '';
    $username = $chatInfo->result->username ?? 'Kiritilmagan';

    // Profil rasmi
    $photoData = bot('getUserProfilePhotos', ['user_id' => $chatId, 'limit' => 1]);
    $photo = $photoData->result->photos[0][0]->file_id ?? null;

    // Bazadan ma'lumotlar
    $balance = $user['balance'] ?? 0;
    $outing = $user['outing'] ?? 0;
    $userId = $user['user_id'] ?? $chatId;
    $status = $user['status'] ?? 'active';
    $referrals = $user['refnum'] ?? 0;
    $registrationDate = $user['registration_date'] ?? null;

    $ordersCount = Database::count("SELECT COUNT(*) FROM myorder WHERE user_id = ?", [$chatId]);
    $totalSpent = Database::sum(
        "SELECT SUM(retail) FROM myorder WHERE user_id = ? AND status = 'Completed'",
        [$chatId]
    );

    // Ro'yxatdan o'tgan vaqtni hisoblash
    $regInfo = 'Mavjud emas';
    $timeWith = '';
    if ($registrationDate) {
        $regInfo = date('d.m.Y | H:i', strtotime($registrationDate));
        $diff = time() - strtotime($registrationDate);
        $days = floor($diff / 86400);
        $hours = floor(($diff % 86400) / 3600);
        $minutes = floor(($diff % 3600) / 60);
        $timeWith = "{$days} kun, {$hours} soat, {$minutes} minut";
    }

    $statusText = ($status === 'active') ? 'Aktiv ✅' : 'Bloklangan ⛔';
    $botUsername = bot('getMe')->result->username ?? 'SFXSMMBot';

    $message = "<b><blockquote>🖥 Sizning hisobingiz haqida ma'lumot</blockquote></b>\n\n" .
        "<blockquote><b>├✍️ Ismingiz:</b> {$firstName} {$lastName}\n" .
        "<b>├🫂 Username:</b> @{$username}\n" .
        "<b>├🔢 Tartib raqamingiz:</b> {$userId}\n" .
        "<b>├🆔 ID raqamingiz:</b> {$chatId}\n" .
        "<b>├💰 Hisobingiz:</b> " . formatNumber($balance) . " so'm\n" .
        "<b>├💵 To'lovlar umumiy:</b> " . formatNumber($outing) . " so'm\n" .
        "<b>├♻️ Holatingiz:</b> {$statusText}\n" .
        "<b>├👥 Taklif qilganlar:</b> {$referrals} ta\n" .
        "<b>├📊 Buyurtmalaringiz:</b> {$ordersCount} ta\n" .
        "<b>├💸 Sarflagan pullaringiz:</b> " . formatNumber($totalSpent) . " so'm\n" .
        "<b>├⏰ Hozirgi vaqt:</b> " . currentDate() . "\n";

    if (!empty($timeWith)) {
        $message .= "<b>├⏳ Siz biz bilansiz:</b> {$timeWith}\n";
    }

    $message .= "<b>├📅 Ro'yxatdan o'tgan:</b> {$regInfo}\n" .
        "<b>└🤖 @{$botUsername} - Sifatli va arzon SMM Bot ✅</b></blockquote>";

    $keyboard = inlineKeyboard([
        [
            ['text' => '🔁 Pul o\'tkazish', 'callback_data' => 'transfer'],
            ['text' => '🚀 Mablag\' yig\'ish', 'callback_data' => 'referal'],
        ],
    ]);

    if ($photo) {
        sendPhoto($chatId, $photo, $message, $keyboard);
    } else {
        sms($chatId, $message, $keyboard);
    }
}

// ==========================================
// 🔁 PUL O'TKAZISH
// ==========================================

function handleTransferStart(string $chatId, int $messageId, string $callbackId): void
{
    $user = Database::fetchOne("SELECT balance FROM users WHERE id = ?", [$chatId]);
    $balance = $user['balance'] ?? 0;

    if ($balance < MIN_TRANSFER) {
        answerCallback($callbackId, "Hisobingizda mablag' yetarli emas\n\nMinimal o'tkazma: " . formatNumber(MIN_TRANSFER) . " so'm", true);
        return;
    }

    answerCallback($callbackId);
    deleteMessage($chatId, $messageId);

    sms($chatId, "<b>Qancha mablag'ingizni o'tkazmoqchisiz?</b>

<i><blockquote>Minimal o'tkazma miqdori: " . formatNumber(MIN_TRANSFER) . " so'm</blockquote></i>", getBackKeyboard());

    setStep($chatId, 'transfer_amount');
}

function handleTransferAmount(string $chatId, string $text): void
{
    if (!is_numeric($text)) {
        sms($chatId, "<b>🤷🏻‍♂ Faqat raqamlardan foydalaning!</b>\n\n<i>Minimal o'tkazma:</i> " . formatNumber(MIN_TRANSFER) . " so'm");
        return;
    }

    $amount = intval($text);

    if ($amount < MIN_TRANSFER) {
        sms($chatId, "<b>Minimal o'tkazma miqdori:</b> " . formatNumber(MIN_TRANSFER) . " so'm\n\nQayta yuboring:");
        return;
    }

    $user = Database::fetchOne("SELECT balance FROM users WHERE id = ?", [$chatId]);
    if ($user['balance'] < $amount) {
        sms($chatId, "<b>🤷🏻‍♂ Hisobingizda mablag' yetarli emas!</b>\n\nQayta yuboring:");
        return;
    }

    sms($chatId, "<b>Kerakli foydalanuvchi ID raqamini yuboring:</b>");
    setStep($chatId, "transfer_to_$amount");
}

function handleTransferTo(string $chatId, string $text, int $amount): void
{
    // O'ziga o'zi o'tkazma
    if ($chatId === $text) {
        sms($chatId, "<b>🤷🏻‍♂ O'zingizga pul o'tkazolmaysiz!</b>\n\nQayta urinib ko'ring:");
        return;
    }

    // Qabul qiluvchi mavjudmi
    $recipient = Database::fetchOne("SELECT id, balance FROM users WHERE id = ?", [$text]);
    if (!$recipient) {
        sms($chatId, "<b>🤷🏻‍♂ Foydalanuvchi topilmadi</b>\n\nQayta urinib ko'ring:");
        return;
    }

    // Balans yetarlimi
    $sender = Database::fetchOne("SELECT balance FROM users WHERE id = ?", [$chatId]);
    if ($sender['balance'] < $amount) {
        sms($chatId, "<b>🤷🏻‍♂ Hisobingizda mablag' yetarli emas!</b>\n\nQayta urinib ko'ring:");
        return;
    }

    // O'tkazma
    Database::execute("UPDATE users SET balance = balance - ? WHERE id = ?", [$amount, $chatId]);
    Database::execute("UPDATE users SET balance = balance + ? WHERE id = ?", [$amount, $text]);

    // Qabul qiluvchiga xabar
    sms($text, "📳 <a href='tg://user?id=$chatId'>$chatId</a> <b>hisobingizga " . formatNumber($amount) . " so'm o'tkazdi</b>");

    // Yuboruvchiga tasdiqlash
    sms($chatId, "<b>✅</b> <a href='tg://user?id=$text'>Foydalanuvchiga</a> <b>" . formatNumber($amount) . " so'm o'tkazildi</b>", getBackKeyboard());

    clearStep($chatId);
}

// ==========================================
// 🚀 MABLAG' YIG'ISH (REFERAL)
// ==========================================

function handleReferalInfo(string $chatId, string $type = 'text', int $messageId = 0, string $callbackId = ''): void
{
    if ($type === 'callback') {
        answerCallback($callbackId);
        deleteMessage($chatId, $messageId);
    }

    $user = Database::fetchOne("SELECT referal, refnum, balance FROM users WHERE id = ?", [$chatId]);
    $refCode = $user['referal'] ?? generateKey();
    $refCount = $user['refnum'] ?? 0;

    $settings = Database::fetchOne("SELECT ref_bonus FROM settings WHERE id = 1");
    $bonus = $settings['ref_bonus'] ?? 0;

    $botUsername = bot('getMe')->result->username ?? 'SFXSMMBot';
    $refLink = "https://t.me/{$botUsername}?start=$refCode";

    sms($chatId, "🚀 <b>Mablag' yig'ish — Referal tizimi</b>
━━━━━━━━━━━━━━━━━━━━

👥 <b>Siz taklif qilganlar:</b> $refCount ta
💰 <b>Har bir taklif uchun:</b> " . formatNumber($bonus) . " so'm

━━━━━━━━━━━━━━━━━━━━
🔗 <b>Sizning taklif havolangiz:</b>
<code>$refLink</code>

━━━━━━━━━━━━━━━━━━━━
💡 Havolani do'stlaringizga yuboring. Ular botga kirishi bilan hisobingizga bonus tushadi!

⚠️ <b>Eslatma:</b> Ishlangan pullarni faqat bot ichida xizmatlarga sarflash mumkin.", getUserMenu($chatId));
}
