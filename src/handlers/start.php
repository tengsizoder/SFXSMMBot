<?php
/**
 * SFXSMM Bot — Start va Asosiy Menyu Handler
 * 
 * /start komandasi, asosiy menyu tugmalari va navigatsiya.
 */

// ==========================================
// MENYU KEYBOARDLARI
// ==========================================

/**
 * Asosiy menyu (oddiy foydalanuvchi)
 */
function getMainMenu(): string
{
    return replyKeyboard([
        [['text' => '🗂 Xizmatlarga buyurtma berish']],
        [['text' => '🔎 Buyurtmalarim'], ['text' => '🚀 Mablag\' yig\'ish']],
        [['text' => '💵 Hisob to\'ldirish'], ['text' => '💳 Mening hisobim']],
        [['text' => '💎 Vertual xizmatlar'], ['text' => '☎️ Qo\'llab-Quvvatlash']],
    ]);
}

/**
 * Admin menyusi (qo'shimcha "Boshqaruv" tugmasi)
 */
function getAdminMenu(): string
{
    return replyKeyboard([
        [['text' => '🗂 Xizmatlarga buyurtma berish']],
        [['text' => '🔎 Buyurtmalarim'], ['text' => '🚀 Mablag\' yig\'ish']],
        [['text' => '💵 Hisob to\'ldirish'], ['text' => '💳 Mening hisobim']],
        [['text' => '💎 Vertual xizmatlar'], ['text' => '☎️ Qo\'llab-Quvvatlash']],
        [['text' => '🗄️ Boshqaruv']],
    ]);
}

/**
 * Foydalanuvchiga mos menyuni qaytarish
 */
function getUserMenu(string $userId): string
{
    return isAdmin($userId) ? getAdminMenu() : getMainMenu();
}

/**
 * Orqaga tugmasi
 */
function getBackKeyboard(): string
{
    return replyKeyboard([[['text' => '⏩ Orqaga']]]);
}

// ==========================================
// /START HANDLER
// ==========================================

/**
 * /start komandasi va referal tizimi
 */
function handleStart(string $chatId, string $text, string $name): void
{
    // Kanal obunasini tekshirish
    if (!checkSubscription($chatId)) {
        return;
    }

    // Referal tekshirish (/start ref_XXXXXX)
    if (strpos($text, '/start ') === 0) {
        $refCode = trim(str_replace('/start ', '', $text));
        processReferral($chatId, $refCode);
    }

    // Xush kelibsiz xabari
    $botUsername = bot('getMe')->result->username ?? 'SFXSMMBot';

    sms($chatId, "🖥️ <b>@{$botUsername} — SMM xizmatlar botiga xush kelibsiz!</b>

✅ Eng arzon va tezkor SMM xizmatlari shu yerda!

👇 Quyidagi menyudan foydalaning:", getUserMenu($chatId));
}

/**
 * Referal linkini qayta ishlash
 */
function processReferral(string $newUserId, string $refCode): void
{
    // O'ziga o'zi referal bo'lishini oldini olish
    $referrer = Database::fetchOne("SELECT id, refnum FROM users WHERE referal = ?", [$refCode]);

    if (!$referrer || $referrer['id'] === $newUserId) {
        return;
    }

    // Foydalanuvchi allaqachon ro'yxatdan o'tganmi tekshirish
    $existing = Database::fetchOne("SELECT id FROM users WHERE id = ?", [$newUserId]);
    if ($existing) {
        return; // Eski foydalanuvchi — referal hisoblanmaydi
    }

    // Referal bonusni qo'shish
    $settings = Database::fetchOne("SELECT ref_bonus FROM settings WHERE id = 1");
    $bonus = $settings['ref_bonus'] ?? 0;

    if ($bonus > 0) {
        Database::execute(
            "UPDATE users SET balance = balance + ?, refnum = refnum + 1 WHERE id = ?",
            [$bonus, $referrer['id']]
        );

        sms($referrer['id'], "🎉 <b>Yangi referal!</b>

Siz taklif qilgan foydalanuvchi botga qo'shildi.
💰 Hisobingizga <b>" . formatNumber($bonus) . "</b> so'm qo'shildi!");
    } else {
        Database::execute(
            "UPDATE users SET refnum = refnum + 1 WHERE id = ?",
            [$referrer['id']]
        );
    }
}

// ==========================================
// ORQAGA TUGMASI
// ==========================================

/**
 * "Orqaga" tugmasi bosilganda
 */
function handleBack(string $chatId): void
{
    if (!checkSubscription($chatId)) {
        return;
    }

    clearStep($chatId);
    sms($chatId, "🖥️ <b>Asosiy menyudasiz</b>", getUserMenu($chatId));
}

// ==========================================
// KANAL OBUNA TEKSHIRISH CALLBACK
// ==========================================

/**
 * "Tekshirish" tugmasi bosilganda (callback)
 */
function handleSubscriptionCheck(string $chatId, int $messageId, string $callbackId): void
{
    if (checkSubscription($chatId)) {
        answerCallback($callbackId, '✅ Obuna tasdiqlandi!');
        deleteMessage($chatId, $messageId);
        sms($chatId, "🖥️ <b>Asosiy menyudasiz</b>", getUserMenu($chatId));
    } else {
        answerCallback($callbackId, '⛔ Hali obuna bo\'lmagansiz!', true);
    }
}
