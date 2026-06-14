<?php
/**
 * SFXSMM Bot — Buyurtma berish tizimi Handler
 * 
 * Kategoriyalar, xizmatlar tanlash, buyurtma berish jarayoni.
 */

// ==========================================
// 🗂 XIZMATLARGA BUYURTMA BERISH
// ==========================================

/**
 * Kategoriyalar ro'yxati
 */
function handleOrderCategories(string $chatId, string $type = 'text', int $messageId = 0): void
{
    if (isAdmin($chatId)) {
        $categories = Database::fetchAll("SELECT * FROM categorys");
    } else {
        $categories = Database::fetchAll("SELECT * FROM categorys WHERE category_status = 'ON'");
    }

    if (empty($categories) && !isAdmin($chatId)) {
        sms($chatId, "⚠️ Bu bo'lim qayta tiklanmoqda, biroz kuting.");
        return;
    }

    $buttons = [];
    foreach ($categories as $cat) {
        $buttons[] = ['text' => enc('decode', $cat['category_name']), 'callback_data' => 'cat_' . $cat['category_id']];
    }

    $keyboard = array_chunk($buttons, 2);

    if (isAdmin($chatId)) {
        $keyboard[] = [['text' => '➕', 'callback_data' => 'cat_new']];
    }
    $keyboard[] = [['text' => '🔍 Xizmatni qidirish (ID orqali)', 'callback_data' => 'order_search']];

    $text = "<b>✅️ Bizning xizmatlar eng arzon va tezkor!

👇 Quyidagi ijtimoiy tarmoqlardan birini tanlang:</b>";

    if ($type === 'callback') {
        edit($chatId, $messageId, $text, inlineKeyboard($keyboard));
    } else {
        sms($chatId, $text, inlineKeyboard($keyboard));
    }
}

/**
 * Kategoriya tanlanganda — ichki bo'limlar
 */
function handleCategorySelected(string $chatId, int $messageId, string $callbackId, int $categoryId): void
{
    answerCallback($callbackId);

    $category = Database::fetchOne("SELECT * FROM categorys WHERE category_id = ?", [$categoryId]);
    if (!$category) {
        answerCallback($callbackId, "⚠️ Kategoriya topilmadi!", true);
        return;
    }

    $subcategories = Database::fetchAll("SELECT * FROM cates WHERE category_id = ?", [$categoryId]);

    if (empty($subcategories) && !isAdmin($chatId)) {
        answerCallback($callbackId, "⚠️ Bu tarmoq uchun xizmat turlari topilmadi!", true);
        return;
    }

    $buttons = [];
    $seen = [];
    foreach ($subcategories as $sub) {
        $name = enc('decode', $sub['name']);
        if (!in_array($name, $seen)) {
            $seen[] = $name;
            $buttons[] = ['text' => $name, 'callback_data' => 'subcat_' . $sub['cate_id']];
        }
    }

    $keyboard = array_chunk($buttons, 1);

    // Admin uchun boshqaruv tugmalari
    if (isAdmin($chatId)) {
        $keyboard[] = [
            ['text' => '📝', 'callback_data' => "cat_edit_$categoryId"],
            ['text' => '➕', 'callback_data' => "cat_addsub_$categoryId"],
            ['text' => '🗑', 'callback_data' => "cat_del_$categoryId"],
        ];
    }
    $keyboard[] = [['text' => '⏪ Orqaga', 'callback_data' => 'categories_back']];

    $catName = enc('decode', $category['category_name']);
    edit($chatId, $messageId, "<b>«$catName» — tarmoq bo'limlaridan birini tanlang.</b>", inlineKeyboard($keyboard));
}

/**
 * Ichki bo'lim tanlanganda — xizmatlar ro'yxati
 */
function handleSubcategorySelected(string $chatId, int $messageId, string $callbackId, int $subcatId): void
{
    answerCallback($callbackId);

    $subcat = Database::fetchOne("SELECT * FROM cates WHERE cate_id = ?", [$subcatId]);
    if (!$subcat) return;

    if (isAdmin($chatId)) {
        $services = Database::fetchAll("SELECT * FROM services WHERE category_id = ?", [$subcatId]);
    } else {
        $services = Database::fetchAll("SELECT * FROM services WHERE category_id = ? AND service_status = 'on'", [$subcatId]);
    }

    if (empty($services) && !isAdmin($chatId)) {
        answerCallback($callbackId, "⚠️ Bu bo'limda xizmatlar topilmadi!", true);
        return;
    }

    $buttons = [];
    foreach ($services as $svc) {
        $name = base64_decode($svc['service_name']);
        $price = $svc['service_price'];
        $buttons[] = ['text' => "$name - $price so'm", 'callback_data' => "svc_{$svc['service_id']}_{$subcatId}"];
    }

    $keyboard = array_chunk($buttons, 1);

    // Admin tugmalari
    if (isAdmin($chatId)) {
        $keyboard[] = [['text' => 'Xizmatlarni yuklab olish', 'callback_data' => "svc_upload_$subcatId"]];
        $keyboard[] = [
            ['text' => '📝', 'callback_data' => "subcat_edit_$subcatId"],
            ['text' => '➕', 'callback_data' => "svc_add_$subcatId"],
            ['text' => '🗑', 'callback_data' => "subcat_del_$subcatId"],
        ];
    }

    // Orqaga qaytish (parent category)
    $keyboard[] = [['text' => '⏪ Orqaga', 'callback_data' => 'cat_' . $subcat['category_id']]];

    $subcatName = enc('decode', $subcat['name']);
    edit($chatId, $messageId, "<b>«$subcatName» — bo'lim xizmatlaridan birini tanlang.</b>

<b><i>💴 Narxlar 1000 tasi uchun berilgan:</i></b>", inlineKeyboard($keyboard));
}

/**
 * Xizmat tanlanganda — buyurtma berish
 */
function handleServiceSelected(string $chatId, int $messageId, string $callbackId, int $serviceId, int $subcatId): void
{
    answerCallback($callbackId);

    $service = Database::fetchOne("SELECT * FROM services WHERE service_id = ?", [$serviceId]);
    if (!$service) {
        answerCallback($callbackId, "⚠️ Xizmat topilmadi!", true);
        return;
    }

    $name = base64_decode($service['service_name']);
    $price = $service['service_price'];
    $min = $service['service_min'];
    $max = $service['service_max'];
    $desc = $service['service_desc'] ? base64_decode($service['service_desc']) : 'Tavsif yo\'q';

    deleteMessage($chatId, $messageId);

    sms($chatId, "🛒 <b>Buyurtma berish</b>
━━━━━━━━━━━━━━━━━━━━

📌 <b>Xizmat:</b> $name
💰 <b>Narx (1000 ta):</b> " . formatNumber($price) . " so'm
📊 <b>Minimum:</b> $min ta
📊 <b>Maksimum:</b> " . formatNumber($max) . " ta

📝 <b>Tavsif:</b> $desc

━━━━━━━━━━━━━━━━━━━━
🔗 <b>Havolani yuboring:</b>

⚠️ Havola to'g'ri va profil ochiq (public) bo'lishi shart!", getBackKeyboard());

    setStep($chatId, "order_link_{$serviceId}_{$subcatId}");
}

/**
 * Havola kiritildi
 */
function handleOrderLink(string $chatId, string $text, int $serviceId, int $subcatId): void
{
    // Havola validatsiya
    if (strpos($text, 'http') === false && strpos($text, 't.me') === false && strpos($text, '@') === false) {
        sms($chatId, "⛔ <b>Noto'g'ri havola!</b>\n\nHavolani to'g'ri kiriting (http://... yoki https://... bilan boshlanishi kerak)");
        return;
    }

    $service = Database::fetchOne("SELECT * FROM services WHERE service_id = ?", [$serviceId]);
    $min = $service['service_min'] ?? 10;
    $max = $service['service_max'] ?? 10000;

    sms($chatId, "📊 <b>Miqdorni kiriting:</b>

🔹 Minimum: $min ta
🔹 Maksimum: " . formatNumber($max) . " ta");

    setStep($chatId, "order_qty_{$serviceId}_{$subcatId}_" . base64_encode($text));
}

/**
 * Miqdor kiritildi — buyurtma tasdiqlash
 */
function handleOrderQuantity(string $chatId, string $text, int $serviceId, int $subcatId, string $link): void
{
    if (!is_numeric($text)) {
        sms($chatId, "⛔ <b>Faqat raqam kiriting!</b>");
        return;
    }

    $qty = intval($text);
    $service = Database::fetchOne("SELECT * FROM services WHERE service_id = ?", [$serviceId]);

    if (!$service) {
        sms($chatId, "⛔ Xizmat topilmadi!");
        clearStep($chatId);
        return;
    }

    $min = intval($service['service_min']);
    $max = intval($service['service_max']);
    $pricePerK = floatval($service['service_price']);

    if ($qty < $min || $qty > $max) {
        sms($chatId, "⛔ <b>Noto'g'ri miqdor!</b>\n\n🔹 Minimum: $min\n🔹 Maksimum: " . formatNumber($max));
        return;
    }

    // Narxni hisoblash
    $totalPrice = ($pricePerK * $qty) / 1000;
    $serviceName = base64_decode($service['service_name']);
    $linkDecoded = base64_decode($link);

    // Balans tekshirish
    $user = Database::fetchOne("SELECT balance FROM users WHERE id = ?", [$chatId]);
    $balance = floatval($user['balance'] ?? 0);

    if ($balance < $totalPrice) {
        sms($chatId, "⛔ <b>Hisobingizda mablag' yetarli emas!</b>

💰 Kerakli summa: " . formatNumber($totalPrice) . " so'm
💳 Hisobingiz: " . formatNumber($balance) . " so'm
📉 Yetishmaydi: " . formatNumber($totalPrice - $balance) . " so'm

💵 Hisobni to'ldirish uchun asosiy menyudagi \"💵 Hisob to'ldirish\" tugmasini bosing.", getUserMenu($chatId));
        clearStep($chatId);
        return;
    }

    // Tasdiqlash
    sms($chatId, "📋 <b>Buyurtmani tasdiqlang:</b>
━━━━━━━━━━━━━━━━━━━━

📌 <b>Xizmat:</b> $serviceName
🔗 <b>Havola:</b> <code>$linkDecoded</code>
📊 <b>Miqdor:</b> " . formatNumber($qty) . " ta
💰 <b>Narx:</b> " . formatNumber($totalPrice) . " so'm

━━━━━━━━━━━━━━━━━━━━
✅ Tasdiqlaysizmi?", inlineKeyboard([
        [
            ['text' => '✅ Tasdiqlash', 'callback_data' => "order_confirm_{$serviceId}_{$qty}_$link"],
            ['text' => '❌ Bekor', 'callback_data' => 'order_cancel'],
        ],
    ]));

    clearStep($chatId);
}

/**
 * Buyurtma tasdiqlandi
 */
function handleOrderConfirm(string $chatId, int $messageId, string $callbackId, int $serviceId, int $qty, string $link): void
{
    answerCallback($callbackId, '⏳ Buyurtma berilmoqda...');

    $service = Database::fetchOne("SELECT * FROM services WHERE service_id = ?", [$serviceId]);
    if (!$service) {
        edit($chatId, $messageId, "⛔ <b>Xizmat topilmadi!</b>");
        return;
    }

    $pricePerK = floatval($service['service_price']);
    $totalPrice = ($pricePerK * $qty) / 1000;
    $linkDecoded = base64_decode($link);

    // Balans tekshirish
    $user = Database::fetchOne("SELECT balance, user_id FROM users WHERE id = ?", [$chatId]);
    $balance = floatval($user['balance'] ?? 0);

    if ($balance < $totalPrice) {
        edit($chatId, $messageId, "⛔ <b>Hisobingizda mablag' yetarli emas!</b>");
        return;
    }

    // Provayderga buyurtma yuborish
    $providerId = $service['api_service_id'] ?? $service['provider_id'] ?? null;
    $apiServiceId = $service['api_service'] ?? null;

    $provider = Database::fetchOne("SELECT * FROM providers WHERE id = ?", [$apiServiceId]);

    $orderId = null;
    if ($provider) {
        $ctx = stream_context_create(['http' => ['timeout' => 30]]);
        $apiResponse = @file_get_contents(
            $provider['api_url'] . "?key=" . $provider['api_key'] .
            "&action=add&service=" . $service['service_api_id'] .
            "&link=" . urlencode($linkDecoded) .
            "&quantity=$qty",
            false, $ctx
        );
        $apiData = json_decode($apiResponse, true);
        $orderId = $apiData['order'] ?? null;
    }

    if (!$orderId) {
        $orderId = time() . rand(100, 999);
    }

    // Balansdan yechish
    Database::execute("UPDATE users SET balance = balance - ? WHERE id = ?", [$totalPrice, $chatId]);

    // Buyurtmani saqlash
    $serviceName = base64_decode($service['service_name']);
    $date = date('Y-m-d H:i:s');

    Database::execute(
        "INSERT INTO myorder (user_id, order_id, service_name, link, quantity, retail, status, order_create) 
         VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)",
        [$chatId, $orderId, $serviceName, $linkDecoded, $qty, $totalPrice, $date]
    );

    Database::execute(
        "INSERT INTO orders (order_id, user_id, service_id, link, quantity, status, created_at) 
         VALUES (?, ?, ?, ?, ?, 'Pending', ?)",
        [$orderId, $chatId, $serviceId, $linkDecoded, $qty, $date]
    );

    // Foydalanuvchiga xabar
    edit($chatId, $messageId, "✅ <b>Buyurtma muvaffaqiyatli berildi!</b>
━━━━━━━━━━━━━━━━━━━━

📋 <b>Buyurtma raqami:</b> <code>$orderId</code>
📌 <b>Xizmat:</b> $serviceName
🔗 <b>Havola:</b> $linkDecoded
📊 <b>Miqdor:</b> " . formatNumber($qty) . " ta
💰 <b>Summa:</b> " . formatNumber($totalPrice) . " so'm
📌 <b>Status:</b> Kutilmoqda ⏳

━━━━━━━━━━━━━━━━━━━━
🔎 Buyurtma holatini \"🔎 Buyurtmalarim\" bo'limidan kuzatishingiz mumkin.");

    // Buyurtmalar kanaliga xabar
    sms(ORDERS_CHANNEL, "🛒 <b>Yangi buyurtma!</b>

📋 ID: <code>$orderId</code>
👤 Foydalanuvchi: <a href='tg://user?id=$chatId'>$chatId</a>
📌 Xizmat: $serviceName
📊 Miqdor: " . formatNumber($qty) . " ta
💰 Summa: " . formatNumber($totalPrice) . " so'm");
}

/**
 * Buyurtma bekor qilindi
 */
function handleOrderCancel(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);
    edit($chatId, $messageId, "❌ <b>Buyurtma bekor qilindi.</b>");
    clearStep($chatId);
}

// ==========================================
// 🔎 BUYURTMALARIM
// ==========================================

function handleMyOrders(string $chatId): void
{
    $orders = Database::fetchAll(
        "SELECT * FROM myorder WHERE user_id = ? ORDER BY id DESC LIMIT 10",
        [$chatId]
    );

    if (empty($orders)) {
        sms($chatId, "📦 <b>Sizda hali buyurtma yo'q.</b>

🗂 Buyurtma berish uchun asosiy menyudagi \"🗂 Xizmatlarga buyurtma berish\" tugmasini bosing.");
        return;
    }

    $text = "🔎 <b>Oxirgi 10 ta buyurtmangiz:</b>\n\n";

    foreach ($orders as $order) {
        $statusEmoji = match ($order['status']) {
            'Completed' => '✅',
            'Pending'   => '⏳',
            'In progress' => '🔄',
            'Processing'  => '⚙️',
            'Partial'     => '⚠️',
            'Canceled'    => '❌',
            'Failed'      => '💥',
            default       => '📦',
        };

        $text .= "$statusEmoji <b>#{$order['order_id']}</b>
├ 📌 {$order['service_name']}
├ 📊 {$order['quantity']} ta
├ 💰 " . formatNumber($order['retail']) . " so'm
└ 📅 {$order['order_create']}\n\n";
    }

    $text .= "💡 <i>Status: ✅ Bajarildi | ⏳ Kutmoqda | 🔄 Jarayonda | ❌ Bekor</i>";

    sms($chatId, $text);
}

// ==========================================
// ADMIN: KATEGORIYA BOSHQARUVI
// ==========================================

function handleNewCategory(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);
    deleteMessage($chatId, $messageId);
    sms($chatId, "<b>Yangi bo'lim uchun nom yuboring:</b>", getBackKeyboard());
    setStep($chatId, 'new_category');
}

function handleNewCategoryName(string $chatId, string $name): void
{
    $encoded = enc('encode', $name);
    Database::execute("INSERT INTO categorys (category_name, category_status) VALUES (?, 'ON')", [$encoded]);
    sms($chatId, "<b>$name</b> bo'limi qo'shildi!", getUserMenu($chatId));
    clearStep($chatId);

    sms($chatId, "<b>Yana bo'lim qo'shish uchun '➕' tugmasini bosing!</b>", inlineKeyboard([
        [['text' => '➕', 'callback_data' => 'cat_new']],
    ]));
}

function handleNewSubcategory(string $chatId, int $messageId, string $callbackId, int $categoryId): void
{
    answerCallback($callbackId);
    deleteMessage($chatId, $messageId);
    sms($chatId, "<b>Yangi ichki bo'lim uchun nom yuboring:</b>", getBackKeyboard());
    setStep($chatId, "new_subcategory_$categoryId");
}

function handleNewSubcategoryName(string $chatId, string $name, int $categoryId): void
{
    $encoded = enc('encode', $name);
    Database::execute("INSERT INTO cates (name, category_id) VALUES (?, ?)", [$encoded, $categoryId]);
    sms($chatId, "<b>$name</b> — ichki bo'lim qo'shildi!", getUserMenu($chatId));
    clearStep($chatId);
}

function handleDeleteCategory(string $chatId, int $messageId, string $callbackId, int $categoryId): void
{
    answerCallback($callbackId);
    $cat = Database::fetchOne("SELECT * FROM categorys WHERE category_id = ?", [$categoryId]);
    $catName = $cat ? enc('decode', $cat['category_name']) : 'Noma\'lum';

    edit($chatId, $messageId, "<b>$catName</b> — bo'limni o'chirishga rozimisiz?

<i>Bo'lim o'chirilsa qayta tiklash imkoni bo'lmaydi!</i>", inlineKeyboard([
        [['text' => '🗑 O\'chirish', 'callback_data' => "cat_del_confirm_$categoryId"]],
        [['text' => '⏪ Orqaga', 'callback_data' => "cat_$categoryId"]],
    ]));
}

function handleDeleteCategoryConfirm(string $chatId, int $messageId, string $callbackId, int $categoryId): void
{
    answerCallback($callbackId);

    $cat = Database::fetchOne("SELECT * FROM categorys WHERE category_id = ?", [$categoryId]);
    $catName = $cat ? enc('decode', $cat['category_name']) : '';

    // O'chirish
    $subcats = Database::fetchAll("SELECT cate_id FROM cates WHERE category_id = ?", [$categoryId]);
    foreach ($subcats as $sub) {
        Database::execute("DELETE FROM services WHERE category_id = ?", [$sub['cate_id']]);
    }
    Database::execute("DELETE FROM cates WHERE category_id = ?", [$categoryId]);
    Database::execute("DELETE FROM categorys WHERE category_id = ?", [$categoryId]);

    edit($chatId, $messageId, "<b>$catName</b> — bo'limi o'chirildi!");
}
