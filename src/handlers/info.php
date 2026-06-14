<?php
/**
 * SFXSMM Bot — Vertual xizmatlar va Bot haqida Handler
 * 
 * Informatsion sahifalar: qo'llanmalar, qoidalar, premium.
 */

// ==========================================
// 💎 VERTUAL XIZMATLAR
// ==========================================

function handleVirtualServices(string $chatId): void
{
    $botUsername = bot('getMe')->result->username ?? 'SFXSMMBot';

    sendPhoto($chatId, 'https://t.me/SmmGlobalRasmlari/89',
        "<b>💎 @$botUsername — Vertual xizmatlar

Quyidagi bo'limlardan birini tanlang 👇</b>",
        inlineKeyboard([
            [
                ['text' => '🗂 Buyurtma berish', 'callback_data' => 'info_orders'],
                ['text' => '📞 Nomerlar olish', 'callback_data' => 'info_numbers'],
            ],
            [
                ['text' => '🤝 Referal tizimi', 'callback_data' => 'info_referal'],
                ['text' => '💵 Hisob to\'ldirish', 'callback_data' => 'info_deposit'],
            ],
            [
                ['text' => '🔑 Hamkorlik (API)', 'callback_data' => 'info_api'],
                ['text' => '🌟 Premium olish', 'callback_data' => 'info_premium'],
            ],
            [['text' => '📜 Bot qoidalari', 'callback_data' => 'info_rules']],
        ])
    );
}

function handleInfoCallback(string $chatId, int $messageId, string $callbackId, string $section): void
{
    answerCallback($callbackId);
    deleteMessage($chatId, $messageId);

    $botUsername = bot('getMe')->result->username ?? 'SFXSMMBot';

    $infoTexts = [
        'orders' => "🗂 <b>Buyurtma berish qo'llanmasi</b>

Botdan buyurtma berish juda oson:

1️⃣ \"🗂 Xizmatlarga buyurtma berish\" tugmasini bosing
2️⃣ Ijtimoiy tarmoqni tanlang
3️⃣ Xizmat turini tanlang
4️⃣ Havolani kiriting
5️⃣ Miqdorni kiriting
6️⃣ Buyurtmani tasdiqlang

⚠️ <b>Muhim:</b>
• Havolani to'g'ri kiriting
• Profilingiz ochiq (public) bo'lishi kerak

✓ @$botUsername",

        'numbers' => "📞 <b>Virtual nomerlar xizmati</b>

Virtual telefon raqamlarini admin orqali sotib olishingiz mumkin.

▪ <b>Qanday ishlaydi:</b>

1️⃣ Admin bilan bog'laning
2️⃣ Kerakli mamlakat va xizmatni ayting
3️⃣ Narxi balansingizdan yechiladi
4️⃣ Nomer va kod sizga yuboriladi",

        'referal' => "🤝 <b>Referal tizimi — bepul pul ishlang!</b>

Do'stlaringizni botga taklif qiling va bonus oling.

1️⃣ \"🚀 Mablag' yig'ish\" tugmasini bosing
2️⃣ Taklif havolangizni yuboring
3️⃣ Do'stingiz kirishi bilan bonus tushadi

⚠️ Ishlangan pullar faqat bot ichida sarflanadi.",

        'deposit' => "💵 <b>Hisob to'ldirish qo'llanmasi</b>

1️⃣ \"💵 Hisob to'ldirish\" tugmasini bosing
2️⃣ To'lov tizimini tanlang
3️⃣ Karta raqamiga pul o'tkazing
4️⃣ Screenshot yuboring
5️⃣ 5-15 daqiqa ichida to'ldiriladi

⚠️ Kiritilgan pullar qaytarib berilmaydi.",

        'api' => "🔑 <b>API — Hamkorlik qo'llanmasi</b>

API orqali o'z botingizga xizmatlarni ulashingiz mumkin.

1️⃣ \"/api\" bo'limiga kiring
2️⃣ API kalitni oling
3️⃣ Dokumentatsiyaga qarang

📖 <b>Imkoniyatlar:</b>
• Buyurtma berish
• Status tekshirish
• Balans ko'rish
• Xizmatlar ro'yxati

⚠️ API kalitni hech kimga bermang!",

        'premium' => "🌟 <b>Premium obuna</b>

💎 <b>Afzalliklari:</b>
• Barcha xizmatlarga chegirma
• Tezroq buyurtma bajarilishi
• Maxsus xizmatlar
• Ustuvor yordam",

        'rules' => "📜 <b>Bot qoidalari</b>

⛔ <b>Taqiqlanadi:</b>

1️⃣ Soxta ma'lumot yuborish → Ban
2️⃣ Adminga haqorat qilish → Ban
3️⃣ Bir linkga qayta buyurtma → Pul qaytmaydi
4️⃣ Tekinga so'rash → Ko'rib chiqilmaydi

⚠️ <b>Muhim:</b>

5️⃣ Kiritilgan pul qaytarilmaydi
6️⃣ Havolani to'g'ri kiriting
7️⃣ Profil ochiq bo'lishi shart",
    ];

    $text = $infoTexts[$section] ?? "Ma'lumot topilmadi.";
    $backButton = inlineKeyboard([[['text' => '⬅️ Orqaga', 'callback_data' => 'info_back']]]);

    // Premium uchun qo'shimcha tugmalar
    if ($section === 'premium') {
        $backButton = inlineKeyboard([
            [['text' => '🌟 1 oy — 45 000 so\'m', 'url' => 'tg://user?id=' . ADMIN_ID]],
            [['text' => '🌟 3 oy — 170 000 so\'m', 'url' => 'tg://user?id=' . ADMIN_ID]],
            [['text' => '🌟 6 oy — 225 000 so\'m', 'url' => 'tg://user?id=' . ADMIN_ID]],
            [['text' => '⬅️ Orqaga', 'callback_data' => 'info_back']],
        ]);
    }

    // Nomerlar uchun admin tugmasi
    if ($section === 'numbers') {
        $backButton = inlineKeyboard([
            [['text' => '📞 Admin bilan bog\'lanish', 'url' => 'https://t.me/' . ADMIN_USERNAME]],
            [['text' => '⬅️ Orqaga', 'callback_data' => 'info_back']],
        ]);
    }

    sms($chatId, $text, $backButton);
}

function handleInfoBack(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);
    deleteMessage($chatId, $messageId);
    handleVirtualServices($chatId);
}
