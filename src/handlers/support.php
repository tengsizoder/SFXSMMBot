<?php
/**
 * SFXSMM Bot — Qo'llab-Quvvatlash va FAQ Handler
 * 
 * FAQ savollari, murojaat yuborish, admin javob berish.
 */

// ==========================================
// ☎️ QO'LLAB-QUVVATLASH ASOSIY SAHIFA
// ==========================================

function handleSupport(string $chatId): void
{
    sendPhoto($chatId, 'https://t.me/SmmGlobalRasmlari/33',
        "<b>📞 SFXSMM | Bot <a href='https://t.me/" . ADMIN_USERNAME . "'>Qo'llab-Quvvatlash.</a>

❓ Sizga qanday yordam kerak.</b>",
        inlineKeyboard([
            [['text' => '❓ Eng ko\'p beriladigan savollar', 'callback_data' => 'faq_main']],
            [['text' => '📨 Murojaat yuborish', 'callback_data' => 'ticket_send']],
            [['text' => '📜 Murojaatim holati', 'callback_data' => 'ticket_status']],
            [['text' => '📞 Admin bilan bog\'lanish', 'url' => 'https://t.me/' . ADMIN_USERNAME]],
        ])
    );
}

function handleSupportBack(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);
    deleteMessage($chatId, $messageId);
    handleSupport($chatId);
}

// ==========================================
// ❓ FAQ — SAVOLLAR
// ==========================================

function getFaqAnswers(): array
{
    return [
        '1' => [
            'question' => 'Buyurtma qancha vaqtda bajariladi?',
            'answer' => "📌 <b>Savol:</b> Buyurtma qancha vaqtda bajariladi?

Buyurtma bajarilish vaqti siz tanlagan xizmat turiga bog'liq bo'ladi.

⏱ <b>O'rtacha bajarilish vaqtlari:</b>

👥 Obunachi (follower) — 1 soatdan 24 soatgacha
❤️ Like (yoqtirish) — 10 daqiqadan 6 soatgacha
👁 Ko'rish (view) — 5 daqiqadan 2 soatgacha
💬 Izoh (comment) — 1 soatdan 12 soatgacha

💡 <b>Eslatma:</b> Har bir xizmatning aniq tezligi tarif ma'lumotida ko'rsatiladi.",
        ],
        '2' => [
            'question' => 'Buyurtma nega bekor qilinadi?',
            'answer' => "📌 <b>Savol:</b> Buyurtma nega bekor qilinadi?

🔗 Noto'g'ri havola kiritilgan bo'lsa
🔒 Profilingiz yopiq (private) bo'lsa
📈 Xizmatga juda ko'p buyurtma tushgan bo'lsa
🔧 Texnik nosozlik yuz bergan bo'lsa

💰 <b>Pulingiz qaytariladimi?</b>
Albatta! Bekor qilingan buyurtma uchun pul avtomatik qaytariladi.",
        ],
        '3' => [
            'question' => 'Botdan bepul foydalansa bo\'ladimi?',
            'answer' => "📌 <b>Savol:</b> Botdan bepul foydalansa bo'ladimi?

Ha! Referal tizimi orqali bepul foydalanishingiz mumkin.

🤝 <b>Qanday ishlaydi:</b>

1️⃣ \"🚀 Mablag' yig'ish\" bo'limiga o'ting
2️⃣ Taklif havolangizni do'stlarga yuboring
3️⃣ Do'stingiz botga kirsa — sizga bonus tushadi

📈 Qancha ko'p taklif qilsangiz — shuncha ko'p pul ishlaysiz.",
        ],
        '4' => [
            'question' => 'Pulim qaytarib beriladimi?',
            'answer' => "📌 <b>Savol:</b> Pulim qaytarib beriladimi?

✓ <b>Pul qaytariladigan hollar:</b>
• Buyurtma bekor qilinsa — darhol qaytadi
• Buyurtma umuman bajarilmasa — darhol qaytadi
• Qisman bajarilsa — bajarilmagan qismi qaytadi

🚫 <b>Pul qaytarilmaydigan hollar:</b>
• Buyurtma to'liq bajarilgan bo'lsa
• Noto'g'ri havola kiritgan bo'lsangiz",
        ],
        '5' => [
            'question' => 'Buyurtma boshlanmayapti?',
            'answer' => "📌 <b>Savol:</b> Buyurtma boshlanmayapti?

✓ <b>Nima qilishingiz kerak:</b>

1️⃣ 15-30 daqiqa kutib turing
2️⃣ Profilingiz ochiq ekanligini tekshiring
3️⃣ Havolani qaytadan tekshiring
4️⃣ 1 soatdan keyin ham boshlanmasa — murojaat yuboring",
        ],
        '6' => [
            'question' => 'Minimal buyurtma miqdori qancha?',
            'answer' => "📌 <b>Savol:</b> Minimal buyurtma miqdori qancha?

▪ <b>O'rtacha chegaralar:</b>

👥 Obunachi: kamida 10-100 ta
❤️ Like: kamida 10-50 ta
👁 Ko'rish: kamida 100-500 ta
💬 Izoh: kamida 5-10 ta

💡 Aniq miqdorni buyurtma berish vaqtida ko'rishingiz mumkin.",
        ],
    ];
}

function handleFaqMain(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);
    deleteMessage($chatId, $messageId);

    $faq = getFaqAnswers();
    $buttons = [];
    foreach ($faq as $num => $item) {
        $buttons[] = [['text' => "{$num}️⃣ {$item['question']}", 'callback_data' => "faq_$num"]];
    }
    $buttons[] = [['text' => '⬅️ Orqaga', 'callback_data' => 'support_back']];

    sendPhoto($chatId, 'https://t.me/SmmGlobalRasmlari/33',
        "<b>📞 SFXSMM | Bot Qo'llab-Quvvatlash

❓ Quyidagi savollardan birini tanlang.</b>",
        inlineKeyboard($buttons)
    );
}

function handleFaqAnswer(string $chatId, int $messageId, string $callbackId, string $num): void
{
    answerCallback($callbackId);
    deleteMessage($chatId, $messageId);

    $faq = getFaqAnswers();
    $answer = $faq[$num]['answer'] ?? 'Javob topilmadi.';

    sms($chatId, $answer, inlineKeyboard([
        [['text' => '⬅️ Savollarga qaytish', 'callback_data' => 'faq_main']],
    ]));
}

// ==========================================
// 📨 MUROJAAT YUBORISH
// ==========================================

function handleTicketSend(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $mtFile = USER_DIR . "mt/$chatId.mt";

    if (file_exists($mtFile)) {
        deleteMessage($chatId, $messageId);
        sms($chatId, "⚠️ <b>Sizda faol murojaat mavjud!</b>

Avvalgi murojaatingizga javob berilgandan so'ng yangi murojaat yuborishingiz mumkin.

⏰ O'rtacha javob vaqti: 1-24 soat.", inlineKeyboard([
            [['text' => '📜 Murojaat holati', 'callback_data' => 'ticket_status']],
            [['text' => '⬅️ Orqaga', 'callback_data' => 'support_back']],
        ]));
        return;
    }

    deleteMessage($chatId, $messageId);
    sms($chatId, "📨 <b>Murojaat yuborish</b>

Murojaatingizni yozing yoki fayl yuboring.

✍️ <b>Qo'llab-quvvatlanadi:</b>
📝 Matn | 📸 Rasm | 🎥 Video | 🎤 Ovoz | 📎 Fayl

⚠️ Muammoingizni batafsil yozishingizni so'raymiz.", getBackKeyboard());

    setStep($chatId, 'ticket_message');
}

function handleTicketStatus(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $mtFile = USER_DIR . "mt/$chatId.mt";

    if (file_exists($mtFile)) {
        $sentTime = date('d.m.Y H:i', intval(file_get_contents($mtFile)));
        $passed = time() - intval(file_get_contents($mtFile));
        $hours = floor($passed / 3600);
        $minutes = floor(($passed % 3600) / 60);

        deleteMessage($chatId, $messageId);
        sms($chatId, "📜 <b>Murojaatingiz holati</b>

📌 <b>Status:</b> Kutilmoqda ⏳
📅 <b>Yuborilgan:</b> $sentTime
⏱ <b>O'tgan vaqt:</b> $hours soat $minutes daqiqa

💡 Murojaatlar 1-24 soat ichida ko'rib chiqiladi.", inlineKeyboard([
            [['text' => '⬅️ Orqaga', 'callback_data' => 'support_back']],
        ]));
    } else {
        deleteMessage($chatId, $messageId);
        sms($chatId, "📜 <b>Murojaatingiz holati</b>

✓ Sizda hozircha faol murojaat yo'q.", inlineKeyboard([
            [['text' => '📨 Murojaat yuborish', 'callback_data' => 'ticket_send']],
            [['text' => '⬅️ Orqaga', 'callback_data' => 'support_back']],
        ]));
    }
}

/**
 * Murojaat xabari yuborilganda
 */
function handleTicketMessage(string $chatId, int $messageId, string $name): void
{
    // Foydalanuvchiga tasdiqlash
    sms($chatId, "✓ <b>Murojaatingiz qabul qilindi!</b>

▪ Admin tez orada ko'rib chiqadi.
⏰ O'rtacha javob vaqti: 1-24 soat.", getUserMenu($chatId));

    // Vaqtni saqlash
    file_put_contents(USER_DIR . "mt/$chatId.mt", time());

    // Adminga forward
    $fwd = bot('forwardMessage', [
        'chat_id'      => ADMIN_ID,
        'from_chat_id' => $chatId,
        'message_id'   => $messageId,
    ]);
    $fwdId = $fwd->result->message_id ?? 0;

    bot('sendMessage', [
        'chat_id'             => ADMIN_ID,
        'text'                => "📨 <b>Yangi murojaat!</b>

👤 Foydalanuvchi: <a href='tg://user?id=$chatId'>$name</a>
🆔 ID: <code>$chatId</code>
📅 Vaqt: " . currentDate(),
        'parse_mode'          => 'html',
        'reply_to_message_id' => $fwdId,
        'reply_markup'        => inlineKeyboard([
            [
                ['text' => "👤 $name", 'url' => "tg://user?id=$chatId"],
                ['text' => '✍️ Javob yozish', 'callback_data' => "ticket_reply_$chatId"],
            ],
        ]),
    ]);

    clearStep($chatId);
}

// ==========================================
// ✍️ ADMIN JAVOB BERISH
// ==========================================

function handleTicketReplyStart(string $chatId, int $messageId, string $callbackId, string $ticketUserId): void
{
    answerCallback($callbackId);

    sms($chatId, "✍️ <b>Javob yozish</b>

👤 Foydalanuvchi: <a href='tg://user?id=$ticketUserId'>$ticketUserId</a>

📝 Javob xabaringizni yuboring.", getBackKeyboard());

    setStep($chatId, "ticket_answer_$ticketUserId");
}

function handleTicketAnswer(string $adminId, int $messageId, string $ticketUserId): void
{
    $result = bot('copyMessage', [
        'chat_id'      => $ticketUserId,
        'from_chat_id' => $adminId,
        'message_id'   => $messageId,
    ]);

    if (isset($result->ok) && $result->ok) {
        sms($ticketUserId, "💬 <b>Admin murojaatingizga javob berdi!</b> ⬆️

Qo'shimcha savolingiz bo'lsa — ☎️ Qo'llab-Quvvatlash bo'limiga murojaat qiling.");

        sms($adminId, "✓ <b>Javob yuborildi!</b>

👤 <a href='tg://user?id=$ticketUserId'>$ticketUserId</a> ga xabar yetkazildi.", getAdminPanel());
    } else {
        sms($adminId, "✗ <b>Xabar yuborilmadi!</b>

Foydalanuvchi botni bloklagan bo'lishi mumkin.", getAdminPanel());
    }

    // Murojaat faylini o'chirish
    @unlink(USER_DIR . "mt/$ticketUserId.mt");
    clearStep($adminId);
}
