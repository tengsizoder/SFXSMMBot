<?php
/**
 * ==========================================
 * SFXSMM Bot — Asosiy Webhook Entry Point
 * ==========================================
 * 
 * Bu fayl Telegram webhook orqali kelgan barcha so'rovlarni
 * qabul qilib, mos handler ga yo'naltiradi.
 * 
 * Arxitektura:
 * - config.php    — Sozlamalar
 * - database.php  — Database ulanish (prepared statements)
 * - helpers.php   — Telegram API va yordamchi funksiyalar
 * - middleware.php — Foydalanuvchi tekshiruvlari
 * - handlers/     — Har bir bo'lim uchun alohida handler
 * - cron.php      — Cron vazifalari (alohida URL orqali)
 */

ob_start();

// ==========================================
// FAYLLARNI YUKLASH
// ==========================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/handlers/start.php';
require_once __DIR__ . '/handlers/admin.php';
require_once __DIR__ . '/handlers/payment.php';
require_once __DIR__ . '/handlers/orders.php';
require_once __DIR__ . '/handlers/account.php';
require_once __DIR__ . '/handlers/support.php';
require_once __DIR__ . '/handlers/api_panel.php';
require_once __DIR__ . '/handlers/info.php';

// ==========================================
// CRON SO'ROVLARINI TEKSHIRISH
// ==========================================
if (isset($_GET['update'])) {
    require_once __DIR__ . '/cron.php';
    exit;
}

// ==========================================
// DATABASE ULANISH VA JADVALLAR
// ==========================================
Database::connect();
Database::createTables();

// ==========================================
// WEBHOOK MA'LUMOTLARINI OLISH
// ==========================================
$update = json_decode(file_get_contents('php://input'));

if (!$update) {
    die('No update received');
}

// ==========================================
// MIDDLEWARE — TEKSHIRUVLAR
// ==========================================
if (!runMiddleware($update)) {
    exit;
}

// ==========================================
// ASOSIY O'ZGARUVCHILARNI ANIQLASH
// ==========================================
$message    = $update->message ?? null;
$callback   = $update->callback_query ?? null;

// Message ma'lumotlari
$chatId     = $message->from->id ?? '';
$text       = $message->text ?? '';
$name       = $message->from->first_name ?? '';
$username   = $message->from->username ?? '';
$messageId  = $message->message_id ?? 0;

// Callback ma'lumotlari
$cbChatId   = $callback->from->id ?? '';
$cbMsgId    = $callback->message->message_id ?? 0;
$cbData     = $callback->data ?? '';
$cbId       = $callback->id ?? '';
$cbChatIdMsg = $callback->message->chat->id ?? '';

// Step (qadam) ni olish
$step = getStep($chatId ?: $cbChatId);

// ==========================================
// 📨 MESSAGE HANDLERLARI
// ==========================================
if ($message) {

    // --- /start ---
    if (strpos($text, '/start') === 0) {
        handleStart($chatId, $text, $name);
        exit;
    }

    // --- /baholash ---
    if ($text === '/baholash') {
        handleRating($chatId);
        exit;
    }

    // --- /api ---
    if ($text === '/api' && checkSubscription($chatId)) {
        handleApiPanel($chatId);
        exit;
    }

    // --- Orqaga ---
    if ($text === '⏩ Orqaga') {
        handleBack($chatId);
        exit;
    }

    // ==========================================
    // ADMIN TUGMALARI
    // ==========================================
    if (isAdmin($chatId)) {

        if ($text === '🗄️ Boshqaruv') {
            handleAdminPanel($chatId);
            exit;
        }

        if ($text === '⚙ Asosiy') {
            handleAdminSettings($chatId);
            exit;
        }

        if (mb_stripos($text, 'Statistika') !== false) {
            handleStatistics($chatId);
            exit;
        }

        if (mb_stripos($text, 'Xabar yuborish') !== false) {
            handleBroadcast($chatId);
            exit;
        }

        if (mb_stripos($text, 'Bot holati') !== false) {
            handleBotStatusToggle($chatId);
            exit;
        }

        if (mb_stripos($text, 'Cron sozlamasi') !== false) {
            handleCronSettings($chatId);
            exit;
        }

        if ($text === '📢 Kanallar') {
            handleChannels($chatId);
            exit;
        }

        if (mb_stripos($text, 'Valyuta kursi') !== false) {
            handleCurrencyRates($chatId);
            exit;
        }
    }

    // ==========================================
    // FOYDALANUVCHI TUGMALARI
    // ==========================================
    if (checkSubscription($chatId)) {

        if ($text === '🗂 Xizmatlarga buyurtma berish') {
            handleOrderCategories($chatId);
            exit;
        }

        if ($text === '🔎 Buyurtmalarim') {
            handleMyOrders($chatId);
            exit;
        }

        if ($text === '💵 Hisob to\'ldirish') {
            sms($chatId, "<b>💵 Hisob to'ldirish</b>

To'lov tizimini tanlang:", inlineKeyboard([
                [['text' => '💳 To\'lov tizimlari', 'callback_data' => 'menu_deposit']],
            ]));
            exit;
        }

        if ($text === '💳 Mening hisobim' || $text === '/meninghisobim') {
            handleMyAccount($chatId);
            exit;
        }

        if ($text === '🚀 Mablag\' yig\'ish') {
            handleReferalInfo($chatId);
            exit;
        }

        if ($text === '💎 Vertual xizmatlar') {
            handleVirtualServices($chatId);
            exit;
        }

        if ($text === '☎️ Qo\'llab-Quvvatlash' || $text === '/qollabquvatlash') {
            handleSupport($chatId);
            exit;
        }
    }

    // ==========================================
    // STEP (QADAM) HANDLERLARI
    // ==========================================
    if (!empty($step)) {

        // --- Ommaviy xabar ---
        if ($step === 'broadcast' && isAdmin($chatId)) {
            handleBroadcastMessage($chatId, $messageId);
            exit;
        }

        // --- Kanal qo'shish ---
        if ($step === 'channel_add' && isAdmin($chatId)) {
            handleChannelAdd($chatId, $text);
            exit;
        }

        // --- Pul o'tkazish ---
        if ($step === 'transfer_amount') {
            handleTransferAmount($chatId, $text);
            exit;
        }

        if (strpos($step, 'transfer_to_') === 0) {
            $amount = intval(str_replace('transfer_to_', '', $step));
            handleTransferTo($chatId, $text, $amount);
            exit;
        }

        // --- To'lov miqdori ---
        if (strpos($step, 'deposit_amount_') === 0) {
            $method = str_replace('deposit_amount_', '', $step);
            handleDepositAmount($chatId, $text, $method);
            exit;
        }

        // --- To'lov cheki ---
        if (strpos($step, 'deposit_receipt_') === 0) {
            $parts = explode('_', str_replace('deposit_receipt_', '', $step));
            $method = $parts[0] ?? '';
            $amount = intval($parts[1] ?? 0);
            handleDepositReceipt($chatId, $messageId, $method, $amount, $message);
            exit;
        }

        // --- Murojaat yuborish ---
        if ($step === 'ticket_message') {
            handleTicketMessage($chatId, $messageId, $name);
            exit;
        }

        // --- Admin javob berish ---
        if (strpos($step, 'ticket_answer_') === 0 && isAdmin($chatId)) {
            $ticketUser = str_replace('ticket_answer_', '', $step);
            handleTicketAnswer($chatId, $messageId, $ticketUser);
            exit;
        }

        // --- Yangi kategoriya ---
        if ($step === 'new_category' && isAdmin($chatId)) {
            handleNewCategoryName($chatId, $text);
            exit;
        }

        // --- Yangi ichki bo'lim ---
        if (strpos($step, 'new_subcategory_') === 0 && isAdmin($chatId)) {
            $catId = intval(str_replace('new_subcategory_', '', $step));
            handleNewSubcategoryName($chatId, $text, $catId);
            exit;
        }

        // --- Buyurtma havola ---
        if (strpos($step, 'order_link_') === 0) {
            $parts = explode('_', str_replace('order_link_', '', $step));
            $serviceId = intval($parts[0] ?? 0);
            $subcatId = intval($parts[1] ?? 0);
            handleOrderLink($chatId, $text, $serviceId, $subcatId);
            exit;
        }

        // --- Buyurtma miqdor ---
        if (strpos($step, 'order_qty_') === 0) {
            $stepData = str_replace('order_qty_', '', $step);
            $parts = explode('_', $stepData, 3);
            $serviceId = intval($parts[0] ?? 0);
            $subcatId = intval($parts[1] ?? 0);
            $link = $parts[2] ?? '';
            handleOrderQuantity($chatId, $text, $serviceId, $subcatId, $link);
            exit;
        }
    }
}

// ==========================================
// 📲 CALLBACK QUERY HANDLERLARI
// ==========================================
if ($callback) {
    $chatId = $cbChatIdMsg ?: $cbChatId;
    $messageId = $cbMsgId;
    $callbackId = $cbId;
    $data = $cbData;

    // --- Obuna tekshirish ---
    if ($data === 'check_subscription') {
        handleSubscriptionCheck($cbChatId, $messageId, $callbackId);
        exit;
    }

    // --- Bot holati toggle ---
    if ($data === 'bot_toggle' && isAdmin($cbChatId)) {
        handleBotStatusToggle($chatId, 'callback', $messageId, $callbackId);
        exit;
    }

    // --- Statistika ---
    if (strpos($data, 'stat_') === 0 && isAdmin($cbChatId)) {
        handleStatCallback($chatId, $messageId, $callbackId, $data);
        exit;
    }

    // --- Valyuta kursi yangilash ---
    if ($data === 'refresh_currency') {
        handleCurrencyRates($chatId, 'callback', $messageId, $callbackId);
        exit;
    }

    // --- Ommaviy xabar bekor qilish ---
    if ($data === 'send_cancel' && isAdmin($cbChatId)) {
        handleBroadcastCancel($chatId, $messageId, $callbackId);
        exit;
    }

    // --- Kanallar ---
    if (strpos($data, 'channel_') === 0 && isAdmin($cbChatId)) {
        $action = str_replace('channel_', '', $data);
        if (in_array($action, ['list', 'delete', 'add'])) {
            handleChannelCallback($chatId, $messageId, $callbackId, $action);
        } elseif (strpos($action, 'del_') === 0) {
            $channel = str_replace('del_', '', $action);
            answerCallback($callbackId);
            deleteMessage($chatId, $messageId);
            handleChannelDelete($chatId, $channel);
        }
        exit;
    }

    // --- Baholash ---
    if (preg_match('/^rating_([1-5])$/', $data, $matches)) {
        handleRatingCallback($cbChatId, $messageId, $callbackId, intval($matches[1]));
        exit;
    }

    // --- To'lov menyusi ---
    if ($data === 'menu_deposit') {
        handleDeposit($chatId, $messageId, $callbackId);
        exit;
    }

    // --- To'lov tizimi tanlash ---
    if (strpos($data, 'pay_method_') === 0) {
        $method = str_replace('pay_method_', '', $data);
        handlePaymentMethodSelected($chatId, $messageId, $callbackId, $method);
        exit;
    }

    // --- To'lov tasdiqlash ---
    if (preg_match('/^confirm_pay_(\d+)_(\d+)$/', $data, $matches)) {
        handlePaymentConfirm($cbChatId, $messageId, $callbackId, $matches[1], intval($matches[2]));
        exit;
    }

    // --- To'lov bekor qilish ---
    if (preg_match('/^reject_pay_(\d+)_(\d+)$/', $data, $matches)) {
        handlePaymentReject($cbChatId, $messageId, $callbackId, $matches[1], intval($matches[2]));
        exit;
    }

    // --- Pul o'tkazish ---
    if ($data === 'transfer') {
        handleTransferStart($cbChatId, $messageId, $callbackId);
        exit;
    }

    // --- Referal ---
    if ($data === 'referal') {
        handleReferalInfo($cbChatId, 'callback', $messageId, $callbackId);
        exit;
    }

    // --- Kategoriyalar ---
    if ($data === 'categories_back') {
        handleOrderCategories($chatId, 'callback', $messageId);
        exit;
    }

    if ($data === 'cat_new' && isAdmin($cbChatId)) {
        handleNewCategory($chatId, $messageId, $callbackId);
        exit;
    }

    if (preg_match('/^cat_(\d+)$/', $data, $matches)) {
        handleCategorySelected($chatId, $messageId, $callbackId, intval($matches[1]));
        exit;
    }

    if (preg_match('/^cat_addsub_(\d+)$/', $data, $matches) && isAdmin($cbChatId)) {
        handleNewSubcategory($chatId, $messageId, $callbackId, intval($matches[1]));
        exit;
    }

    if (preg_match('/^cat_del_(\d+)$/', $data, $matches) && isAdmin($cbChatId)) {
        handleDeleteCategory($chatId, $messageId, $callbackId, intval($matches[1]));
        exit;
    }

    if (preg_match('/^cat_del_confirm_(\d+)$/', $data, $matches) && isAdmin($cbChatId)) {
        handleDeleteCategoryConfirm($chatId, $messageId, $callbackId, intval($matches[1]));
        exit;
    }

    if (preg_match('/^subcat_(\d+)$/', $data, $matches)) {
        handleSubcategorySelected($chatId, $messageId, $callbackId, intval($matches[1]));
        exit;
    }

    if (preg_match('/^svc_(\d+)_(\d+)$/', $data, $matches)) {
        handleServiceSelected($chatId, $messageId, $callbackId, intval($matches[1]), intval($matches[2]));
        exit;
    }

    // --- Buyurtma tasdiqlash ---
    if (preg_match('/^order_confirm_(\d+)_(\d+)_(.+)$/', $data, $matches)) {
        handleOrderConfirm($chatId, $messageId, $callbackId, intval($matches[1]), intval($matches[2]), $matches[3]);
        exit;
    }

    if ($data === 'order_cancel') {
        handleOrderCancel($chatId, $messageId, $callbackId);
        exit;
    }

    // --- API Panel ---
    if ($data === 'api_home') {
        answerCallback($callbackId);
        deleteMessage($chatId, $messageId);
        handleApiPanel($cbChatId);
        exit;
    }

    if ($data === 'api_getkey') {
        handleApiGetKey($cbChatId, $messageId, $callbackId);
        exit;
    }

    if ($data === 'api_stats') {
        handleApiStats($chatId, $messageId, $callbackId);
        exit;
    }

    if ($data === 'api_newkey') {
        handleApiNewKey($chatId, $messageId, $callbackId);
        exit;
    }

    if ($data === 'api_newkey_yes') {
        handleApiNewKeyConfirm($chatId, $messageId, $callbackId);
        exit;
    }

    if ($data === 'api_toggle') {
        handleApiToggle($chatId, $messageId, $callbackId);
        exit;
    }

    if ($data === 'api_pause_yes') {
        handleApiPause($chatId, $messageId, $callbackId);
        exit;
    }

    if ($data === 'api_resume_yes') {
        handleApiResume($chatId, $messageId, $callbackId);
        exit;
    }

    // --- Murojaat yozish (admin) ---
    if (preg_match('/^ticket_reply_(\d+)$/', $data, $matches) && isAdmin($cbChatId)) {
        handleTicketReplyStart($chatId, $messageId, $callbackId, $matches[1]);
        exit;
    }

    // --- FAQ ---
    if ($data === 'faq_main') {
        handleFaqMain($cbChatId, $messageId, $callbackId);
        exit;
    }

    if (preg_match('/^faq_([1-6])$/', $data, $matches)) {
        handleFaqAnswer($cbChatId, $messageId, $callbackId, $matches[1]);
        exit;
    }

    // --- Support ---
    if ($data === 'support_back') {
        handleSupportBack($cbChatId, $messageId, $callbackId);
        exit;
    }

    if ($data === 'ticket_send') {
        handleTicketSend($cbChatId, $messageId, $callbackId);
        exit;
    }

    if ($data === 'ticket_status') {
        handleTicketStatus($cbChatId, $messageId, $callbackId);
        exit;
    }

    // --- Vertual xizmatlar ---
    if ($data === 'info_back') {
        handleInfoBack($cbChatId, $messageId, $callbackId);
        exit;
    }

    if (strpos($data, 'info_') === 0) {
        $section = str_replace('info_', '', $data);
        handleInfoCallback($cbChatId, $messageId, $callbackId, $section);
        exit;
    }
}

ob_end_clean();
