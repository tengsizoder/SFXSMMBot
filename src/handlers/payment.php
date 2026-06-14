<?php
/**
 * SFXSMM Bot — To'lov Tizimi Handler
 * 
 * Hisob to'ldirish, to'lov tasdiqlash/bekor qilish.
 */

// ==========================================
// 💵 HISOB TO'LDIRISH
// ==========================================

/**
 * To'lov tizimini tanlash menyusi
 */
function handleDeposit(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $paymentsFile = SET_DIR . 'payments.txt';
    if (!file_exists($paymentsFile)) {
        edit($chatId, $messageId, "⚠️ <b>To'lov tizimlari sozlanmagan!</b>");
        return;
    }

    $content = trim(file_get_contents($paymentsFile));
    $methods = array_filter(explode("\n", $content));

    $buttons = [];
    foreach ($methods as $method) {
        $method = trim($method);
        if (!empty($method)) {
            $buttons[] = ['text' => $method, 'callback_data' => 'pay_method_' . base64_encode($method)];
        }
    }

    $keyboard = array_chunk($buttons, 2);
    $keyboard[] = [['text' => '☎️ Admin yordamida', 'url' => 'tg://user?id=' . ADMIN_ID]];

    edit($chatId, $messageId, "<b>🔰 Quyidagi to'lov tizimlardan birini tanlang:</b>",
        inlineKeyboard($keyboard)
    );
}

/**
 * To'lov tizimi tanlangandan keyin
 */
function handlePaymentMethodSelected(string $chatId, int $messageId, string $callbackId, string $method): void
{
    answerCallback($callbackId);
    deleteMessage($chatId, $messageId);

    // To'lov kartasini ko'rsatish
    $payDir = SET_DIR . "pay/$method/";
    $wallet = '';
    $addition = '';

    if (is_dir($payDir)) {
        $walletFile = $payDir . 'wallet.txt';
        $additionFile = $payDir . 'addition.txt';
        $wallet = file_exists($walletFile) ? trim(file_get_contents($walletFile)) : '';
        $addition = file_exists($additionFile) ? trim(file_get_contents($additionFile)) : '';
    }

    $text = "💳 <b>$method orqali to'lov</b>
━━━━━━━━━━━━━━━━━━━━";

    if (!empty($wallet)) {
        $text .= "\n\n💳 <b>Karta raqami:</b>\n<code>$wallet</code>";
    }
    if (!empty($addition)) {
        $text .= "\n\n📝 <b>Qo'shimcha:</b> $addition";
    }

    $text .= "\n\n━━━━━━━━━━━━━━━━━━━━
📤 <b>To'lov miqdorini kiriting (so'mda):</b>

🔹 Minimal: " . formatNumber(MIN_DEPOSIT) . " so'm";

    sms($chatId, $text, getBackKeyboard());
    setStep($chatId, "deposit_amount_" . base64_encode($method));
}

/**
 * To'lov miqdori kiritildi
 */
function handleDepositAmount(string $chatId, string $text, string $method): void
{
    if (!is_numeric($text)) {
        sms($chatId, "⛔ <b>Faqat raqamlardan foydalaning!</b>\n\n🔹 Minimal: " . formatNumber(MIN_DEPOSIT) . " so'm");
        return;
    }

    $amount = intval($text);
    if ($amount < MIN_DEPOSIT) {
        sms($chatId, "⛔ <b>Minimal to'lov miqdori:</b> " . formatNumber(MIN_DEPOSIT) . " so'm\n\nQaytadan kiriting:");
        return;
    }

    sms($chatId, "📑 <b>To'lov uchun chek rasmini sifatli va aniq yuboring.</b>", getBackKeyboard());
    setStep($chatId, "deposit_receipt_{$method}_$amount");
}

/**
 * To'lov cheki (screenshot) yuborildi
 */
function handleDepositReceipt(string $chatId, int $messageId, string $method, int $amount, object $message): void
{
    // Faqat rasm qabul qilinadi
    if (!isset($message->photo)) {
        sms($chatId, "⛔ <b>Faqat rasm (screenshot) qabul qilinadi!</b>");
        return;
    }

    $methodName = base64_decode($method);
    $name = $message->from->first_name ?? 'Foydalanuvchi';

    // Foydalanuvchi ma'lumotlari
    $user = Database::fetchOne("SELECT * FROM users WHERE id = ?", [$chatId]);
    $ordersCount = Database::count("SELECT COUNT(*) FROM myorder WHERE user_id = ?", [$chatId]);
    $balance = $user['balance'] ?? 0;
    $refnum = $user['refnum'] ?? 0;
    $outing = $user['outing'] ?? 0;
    $userId = $user['user_id'] ?? $chatId;

    // Foydalanuvchiga tasdiqlash xabari
    sms($chatId, "✅ <b>To'lovingiz muvaffaqiyatli qabul qilindi!</b>

Administratorga yuborildi. Tasdiqlash jarayoni o'rtacha 10-15 daqiqa.
⏳ Iltimos, kuting!", getUserMenu($chatId));

    // To'lov kanaliga yuborish
    $copied = bot('CopyMessage', [
        'chat_id'      => PAYMENT_CHANNEL,
        'message_id'   => $messageId,
        'from_chat_id' => $chatId,
    ]);

    $copiedId = $copied->result->message_id ?? 0;
    $date = currentDate();

    bot('sendMessage', [
        'chat_id'             => PAYMENT_CHANNEL,
        'reply_to_message_id' => $copiedId,
        'parse_mode'          => 'html',
        'text'                => "<b>📑 #chek | To'lov uchun chek

<blockquote>💳 To'lov tizimi: $methodName
🔢 To'lov miqdori: " . formatNumber($amount) . " so'm
✍️ Tartib raqami: $userId
🆔 Foydalanuvchi ID: $chatId
💰 Foydalanuvchi hisobi: " . formatNumber($balance) . " so'm
👥 Taklif qilganlari: $refnum ta
📊 Barcha buyurtmalari: $ordersCount ta
💵 To'lovlari umumiy: " . formatNumber($outing) . " so'm
⏰ Vaqt: $date</blockquote></b>",
        'reply_markup' => inlineKeyboard([
            [
                ['text' => '✅ Tasdiqlash', 'callback_data' => "confirm_pay_{$chatId}_{$amount}"],
                ['text' => '⛔ Bekor qilish', 'callback_data' => "reject_pay_{$chatId}_{$amount}"],
            ],
            [['text' => $name, 'url' => "tg://user?id=$chatId"]],
        ]),
    ]);

    clearStep($chatId);
}

// ==========================================
// TO'LOVNI TASDIQLASH / BEKOR QILISH (Admin)
// ==========================================

/**
 * To'lovni tasdiqlash
 */
function handlePaymentConfirm(string $chatId, int $messageId, string $callbackId, string $userId, int $amount): void
{
    $callerId = $chatId; // Kim bosgani

    // Faqat admin tasdiqlashi mumkin
    if (!isAdmin($callerId)) {
        answerCallback($callbackId, '⚠️ Siz administrator emassiz!');
        return;
    }

    answerCallback($callbackId, '✅ Tasdiqlandi!');

    // Foydalanuvchi balansini oshirish
    Database::execute(
        "UPDATE users SET balance = balance + ?, outing = outing + ? WHERE id = ?",
        [$amount, $amount, $userId]
    );

    // Foydalanuvchiga xabar
    sms($userId, "✅ <b>To'lovingiz tasdiqlandi!</b>

<blockquote>💰 Hisobingizga <b>" . formatNumber($amount) . "</b> so'm qo'shildi</blockquote>");

    // Kanaldagi xabarni yangilash
    edit($chatId, $messageId,
        "💵 <b>Foydalanuvchi ($userId) hisobi " . formatNumber($amount) . " so'mga to'ldirildi. || #done</b>"
    );
}

/**
 * To'lovni bekor qilish
 */
function handlePaymentReject(string $chatId, int $messageId, string $callbackId, string $userId, int $amount): void
{
    if (!isAdmin($chatId)) {
        answerCallback($callbackId, '⚠️ Siz administrator emassiz!');
        return;
    }

    answerCallback($callbackId, '⛔ Bekor qilindi!');

    // Foydalanuvchiga xabar
    sms($userId, "⛔ <b>Hisobingizni " . formatNumber($amount) . " so'mga to'ldirish so'rovi bekor qilindi!</b>

Sababi: soxta chek yuborgan bo'lishingiz mumkin. Iltimos, ma'lumotlaringizni tekshirib, qayta yuboring.");

    // Kanaldagi xabarni yangilash
    edit($chatId, $messageId,
        "⛔ <b>Foydalanuvchi ($userId) hisobini " . formatNumber($amount) . " so'mga to'ldirish bekor qilindi! || #canceled</b>"
    );
}
