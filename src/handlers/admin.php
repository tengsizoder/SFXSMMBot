<?php
/**
 * SFXSMM Bot — Admin Panel Handler
 * 
 * Boshqaruv paneli, statistika, xabar yuborish,
 * bot holati, kanallar boshqaruvi, cron sozlamasi.
 */

// ==========================================
// ADMIN PANEL KEYBOARDLARI
// ==========================================

function getAdminPanel(): string
{
    return replyKeyboard([
        [['text' => '📢 Kanallar'], ['text' => '📊 Statistika']],
        [['text' => '⚙ Asosiy'], ['text' => '✉️ Xabar yuborish']],
        [['text' => '🔎 Foydalanuvchini boshqarish']],
        [['text' => '🤖 Bot holati'], ['text' => '🔎 Buyurtma']],
        [['text' => '⏰ Cron sozlamasi'], ['text' => '🇺🇿 Valyuta kursi']],
        [['text' => '⏩ Orqaga']],
    ]);
}

function getSettingsPanel(): string
{
    return replyKeyboard([
        [['text' => '📑 Birlamchi sozlamalar']],
        [['text' => '💵 Kursni o\'rnatish'], ['text' => '⚖️ Foizni o\'rnatish']],
        [['text' => '🔑 API sozlash'], ['text' => '🗄️ Boshqaruv']],
    ]);
}

// ==========================================
// BOSHQARUV PANELI
// ==========================================

function handleAdminPanel(string $chatId): void
{
    sms($chatId, "👨‍💻 <b>Boshqaruv paneliga xush kelibsiz:</b>", getAdminPanel());
    clearStep($chatId);
}

function handleAdminSettings(string $chatId): void
{
    sms($chatId, "<b>👉 Asosiy sozlamalar:</b>", getSettingsPanel());
}

// ==========================================
// 📊 STATISTIKA
// ==========================================

function getStatisticsText(string $chatId): string
{
    $startTime = microtime(true);
    bot('sendChatAction', ['chat_id' => $chatId, 'action' => 'typing']);
    $ping = round((microtime(true) - $startTime) * 1000);

    // Ping bahosi
    if ($ping < 30) $pingStatus = "juda tez ⚡";
    elseif ($ping < 80) $pingStatus = "tez ○";
    elseif ($ping < 150) $pingStatus = "o'rta 🟡";
    elseif ($ping < 300) $pingStatus = "sekin 🟠";
    else $pingStatus = "juda sekin ●";

    // Foydalanuvchilar statistikasi
    $active = Database::count("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $deactive = Database::count("SELECT COUNT(*) FROM users WHERE status = 'deactive'");

    $today = date('Y-m-d');
    $todayMembers = Database::count(
        "SELECT COUNT(*) FROM users WHERE registration_date LIKE ?", ["$today%"]
    );
    $yesterdayDate = date('Y-m-d', strtotime('-1 day'));
    $yesterdayMembers = Database::count(
        "SELECT COUNT(*) FROM users WHERE registration_date LIKE ?", ["$yesterdayDate%"]
    );
    $weekMembers = Database::count(
        "SELECT COUNT(*) FROM users WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    $monthMembers = Database::count(
        "SELECT COUNT(*) FROM users WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );

    // Botni tark etganlar
    $totalSoxta = Database::count("SELECT COUNT(*) FROM soxta");
    $comeBack = Database::count("SELECT COUNT(*) FROM soxta WHERE come = 'come'");
    $gone = Database::count("SELECT COUNT(*) FROM soxta WHERE come = 'gone'");
    $leftCount = $totalSoxta - $comeBack;
    $realActive = $active - $gone;
    $totalUsers = $realActive + $leftCount;

    // Xizmatlar va buyurtmalar
    $servicesCount = Database::count("SELECT COUNT(*) FROM services");
    $providersCount = Database::count("SELECT COUNT(*) FROM providers");
    $ordersCount = Database::count("SELECT COUNT(*) FROM orders");
    $todayOrders = Database::count(
        "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()"
    );

    // Moliyaviy ma'lumotlar
    $totalBalance = Database::sum("SELECT SUM(balance) FROM users");
    $totalSpent = Database::sum("SELECT SUM(retail) FROM myorder");
    $todayRevenue = Database::sum(
        "SELECT SUM(retail) FROM myorder WHERE DATE(order_create) = CURDATE() AND status = 'Completed'"
    );
    $weekRevenue = Database::sum(
        "SELECT SUM(retail) FROM myorder WHERE YEARWEEK(order_create, 1) = YEARWEEK(CURDATE(), 1) AND status = 'Completed'"
    );
    $monthRevenue = Database::sum(
        "SELECT SUM(retail) FROM myorder WHERE YEAR(order_create) = YEAR(CURDATE()) AND MONTH(order_create) = MONTH(CURDATE()) AND status = 'Completed'"
    );

    $date = date('d.m.Y | H:i');

    return "▪ <b>Bot statistikasi</b>
🕐 {$date}  •  {$ping}ms ($pingStatus)

<blockquote>👥 <b>Foydalanuvchilar:</b>

🆕 Bugun qo'shildi:  <b>+$todayMembers</b> ta
📅 Kecha qo'shildi:  <b>+$yesterdayMembers</b> ta
📆 Shu hafta:  <b>+$weekMembers</b> ta
🗓 Shu oyda:  <b>+$monthMembers</b> ta

👥 Barcha foydalanuvchilar:  <b>$totalUsers</b> ta
✓ Aktiv foydalanuvchilar:  <b>$realActive</b> ta
📤 Botni tark etganlar:  <b>$leftCount</b> ta
⛔ Bloklangan foydalanuvchilar:  <b>$deactive</b> ta</blockquote>

<blockquote>🛒 <b>Bot ma'lumotlari:</b>

▪ Barcha xizmatlar:  <b>$servicesCount</b> ta
🌐 Provayderlar soni:  <b>$providersCount</b> ta
📦 Barcha buyurtmalar:  <b>$ordersCount</b> ta
🆕 Bugungi buyurtmalar:  <b>$todayOrders</b> ta</blockquote>

<blockquote>💰 <b>Daromad:</b>

📈 Bugungi daromad:  <b>" . formatNumber($todayRevenue) . "</b> so'm
▪ Haftalik daromad:  <b>" . formatNumber($weekRevenue) . "</b> so'm
📆 Oylik daromad:  <b>" . formatNumber($monthRevenue) . "</b> so'm

💸 Jami sarflangan:  <b>" . formatNumber($totalSpent) . "</b> so'm
💵 Foydalanuvchilar balansi:  <b>" . formatNumber($totalBalance) . "</b> so'm</blockquote>";
}

function getStatisticsKeyboard(): string
{
    return inlineKeyboard([
        [['text' => '📦 Buyurtmalar statistikasi', 'callback_data' => 'stat_orders']],
        [
            ['text' => '🔝 TOP buyurtmachilar', 'callback_data' => 'stat_top_orders'],
            ['text' => '💰 TOP xaridorlar', 'callback_data' => 'stat_top_spenders'],
        ],
        [
            ['text' => '🏆 TOP 100 Balans', 'callback_data' => 'stat_top_balance'],
            ['text' => '🏆 TOP 100 Referal', 'callback_data' => 'stat_top_ref'],
        ],
        [['text' => '🌐 Provayderlar balansi', 'callback_data' => 'stat_providers']],
        [
            ['text' => '🕐 Soatlik statistika', 'callback_data' => 'stat_hourly'],
            ['text' => '📉 Tark etganlar', 'callback_data' => 'stat_left_graph'],
        ],
        [
            ['text' => '💰 Balansni 0', 'callback_data' => 'stat_reset_bal'],
            ['text' => '🤝 Referalni 0', 'callback_data' => 'stat_reset_ref'],
        ],
        [['text' => '🔄 Yangilash', 'callback_data' => 'stat_refresh']],
    ]);
}

function handleStatistics(string $chatId): void
{
    sms($chatId, getStatisticsText($chatId), getStatisticsKeyboard());
    clearStep($chatId);
}

// ==========================================
// STATISTIKA CALLBACK HANDLERLARI
// ==========================================

function handleStatCallback(string $chatId, int $messageId, string $callbackId, string $data): void
{
    switch ($data) {
        case 'stat_refresh':
        case 'stat_back':
            answerCallback($callbackId, $data === 'stat_refresh' ? '🔄 Yangilanmoqda...' : '');
            edit($chatId, $messageId, getStatisticsText($chatId), getStatisticsKeyboard());
            break;

        case 'stat_orders':
            handleOrdersStats($chatId, $messageId, $callbackId);
            break;

        case 'stat_top_orders':
            handleTopOrders($chatId, $messageId, $callbackId);
            break;

        case 'stat_top_spenders':
            handleTopSpenders($chatId, $messageId, $callbackId);
            break;

        case 'stat_top_balance':
            handleTopBalance($chatId, $messageId, $callbackId);
            break;

        case 'stat_top_ref':
            handleTopReferals($chatId, $messageId, $callbackId);
            break;

        case 'stat_providers':
            handleProvidersBalance($chatId, $messageId, $callbackId);
            break;

        case 'stat_hourly':
            handleHourlyStats($chatId, $messageId, $callbackId);
            break;

        case 'stat_left_graph':
            handleLeftGraph($chatId, $messageId, $callbackId);
            break;

        case 'stat_reset_bal':
            handleResetBalanceConfirm($chatId, $messageId, $callbackId);
            break;

        case 'stat_reset_bal_yes':
            handleResetBalance($chatId, $messageId, $callbackId);
            break;

        case 'stat_reset_ref':
            handleResetRefConfirm($chatId, $messageId, $callbackId);
            break;

        case 'stat_reset_ref_yes':
            handleResetRef($chatId, $messageId, $callbackId);
            break;
    }
}

function handleOrdersStats(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $total = Database::count("SELECT COUNT(*) FROM orders");
    $pending = Database::count("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
    $inProgress = Database::count("SELECT COUNT(*) FROM orders WHERE status = 'In progress'");
    $processing = Database::count("SELECT COUNT(*) FROM orders WHERE status = 'Processing'");
    $completed = Database::count("SELECT COUNT(*) FROM orders WHERE status = 'Completed'");
    $partial = Database::count("SELECT COUNT(*) FROM orders WHERE status = 'Partial'");
    $canceled = Database::count("SELECT COUNT(*) FROM orders WHERE status = 'Canceled'");
    $failed = Database::count("SELECT COUNT(*) FROM orders WHERE status = 'Failed'");

    $text = "📦 <b>Buyurtmalar statistikasi</b>

<blockquote>📦 Jami buyurtmalar:  <b>$total</b> ta
✓ Bajarilgan:  <b>$completed</b> ta
⏳ Kutilayotgan:  <b>$pending</b> ta
🔄 Jarayonda:  <b>$inProgress</b> ta
⚙️ Processing:  <b>$processing</b> ta
⚠️ Qisman:  <b>$partial</b> ta
✗ Bekor qilingan:  <b>$canceled</b> ta
💥 Muvaffaqiyatsiz:  <b>$failed</b> ta</blockquote>

⚡ <b>Buyurtmalar statusini o'zgartirish:</b>";

    $keyboard = inlineKeyboard([
        [['text' => '⏳ Kutmoqda → ✓ Bajarilgan', 'callback_data' => 'stat_move_Pending_Completed']],
        [['text' => '🔄 Jarayonda → ✓ Bajarilgan', 'callback_data' => 'stat_move_Inprogress_Completed']],
        [['text' => '⚙️ Processing → ✓ Bajarilgan', 'callback_data' => 'stat_move_Processing_Completed']],
        [['text' => '⚠️ Qisman → ✓ Bajarilgan', 'callback_data' => 'stat_move_Partial_Completed']],
        [['text' => '⬅️ Orqaga qaytish', 'callback_data' => 'stat_back']],
    ]);

    edit($chatId, $messageId, $text, $keyboard);
}

function handleTopOrders(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $rows = Database::fetchAll(
        "SELECT user_id, COUNT(*) as cnt FROM myorder GROUP BY user_id ORDER BY cnt DESC LIMIT ?",
        [TOP_LIST_LIMIT]
    );

    $text = "🔝 <b>TOP " . TOP_LIST_LIMIT . " eng ko'p buyurtma berganlar:</b>\n\n";

    foreach ($rows as $i => $row) {
        $user = Database::fetchOne("SELECT id FROM users WHERE user_id = ?", [$row['user_id']]);
        $tgId = $user['id'] ?? $row['user_id'];
        $num = $i + 1;
        $text .= "<b>$num.</b> <a href='tg://user?id=$tgId'>$tgId</a> — 📦 {$row['cnt']} ta\n";
    }

    if (empty($rows)) $text .= "Ma'lumot yo'q";

    edit($chatId, $messageId, $text, inlineKeyboard([
        [['text' => '⬅️ Orqaga qaytish', 'callback_data' => 'stat_back']]
    ]));
}

function handleTopSpenders(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $rows = Database::fetchAll(
        "SELECT user_id, SUM(retail) as total FROM myorder GROUP BY user_id ORDER BY total DESC LIMIT ?",
        [TOP_LIST_LIMIT]
    );

    $text = "💰 <b>TOP " . TOP_LIST_LIMIT . " eng ko'p sarflagan foydalanuvchilar:</b>\n\n";

    foreach ($rows as $i => $row) {
        $user = Database::fetchOne("SELECT id FROM users WHERE user_id = ?", [$row['user_id']]);
        $tgId = $user['id'] ?? $row['user_id'];
        $num = $i + 1;
        $spent = formatNumber($row['total']);
        $text .= "<b>$num.</b> <a href='tg://user?id=$tgId'>$tgId</a> — 💰 $spent so'm\n";
    }

    if (empty($rows)) $text .= "Ma'lumot yo'q";

    edit($chatId, $messageId, $text, inlineKeyboard([
        [['text' => '⬅️ Orqaga qaytish', 'callback_data' => 'stat_back']]
    ]));
}

function handleTopBalance(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $rows = Database::fetchAll(
        "SELECT id, balance FROM users ORDER BY balance DESC LIMIT ?",
        [TOP_BALANCE_LIMIT]
    );

    $text = "🏆 <b>TOP " . TOP_BALANCE_LIMIT . " eng boy foydalanuvchilar:</b>\n\n";

    foreach ($rows as $i => $row) {
        $num = $i + 1;
        $bal = formatNumber($row['balance']);
        $text .= "<b>$num.</b> <a href='tg://user?id={$row['id']}'>{$row['id']}</a> — 💰 $bal so'm\n";
    }

    edit($chatId, $messageId, $text, inlineKeyboard([
        [['text' => '⬅️ Orqaga qaytish', 'callback_data' => 'stat_back']]
    ]));
}

function handleTopReferals(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $rows = Database::fetchAll(
        "SELECT id, refnum FROM users ORDER BY refnum DESC LIMIT ?",
        [TOP_BALANCE_LIMIT]
    );

    $text = "🏆 <b>TOP " . TOP_BALANCE_LIMIT . " eng faol referalchilar:</b>\n\n";

    foreach ($rows as $i => $row) {
        $num = $i + 1;
        $text .= "<b>$num.</b> <a href='tg://user?id={$row['id']}'>{$row['id']}</a> — 🤝 {$row['refnum']} ta\n";
    }

    edit($chatId, $messageId, $text, inlineKeyboard([
        [['text' => '⬅️ Orqaga qaytish', 'callback_data' => 'stat_back']]
    ]));
}

function handleProvidersBalance(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId, '🌐 Tekshirilmoqda...');

    $providers = Database::fetchAll("SELECT * FROM providers ORDER BY id ASC");

    if (empty($providers)) {
        edit($chatId, $messageId, "🌐 <b>Provayderlar balansi</b>\n\n⚠️ Provayderlar topilmadi.",
            inlineKeyboard([[['text' => '⬅️ Orqaga', 'callback_data' => 'stat_back']]])
        );
        return;
    }

    $msg = "🌐 <b>Provayderlar balansi:</b>\n\n";
    $totalUsd = 0;

    foreach ($providers as $i => $row) {
        $shortUrl = str_replace(
            ['https://', '/api/v1', '/api/v2', '/api/adapter/default/index'],
            '', $row['api_url']
        );

        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $response = @file_get_contents(
            $row['api_url'] . '?key=' . $row['api_key'] . '&action=balance', false, $ctx
        );
        $balData = json_decode($response, true);

        $num = $i + 1;
        if ($balData && isset($balData['balance'])) {
            $balance = number_format(floatval($balData['balance']), 2);
            $currency = $balData['currency'] ?? 'USD';
            $status = '✓';
            $totalUsd += floatval($balData['balance']);
        } else {
            $balance = 'xato';
            $currency = '';
            $status = '✗';
        }

        $msg .= "$status <b>$num. $shortUrl</b>\n     💰 $balance $currency\n\n";
    }

    $msg .= "━━━━━━━━━━━━━━━━━━━━━━\n💎 <b>Jami:</b> ~" . number_format($totalUsd, 2) . " USD";

    edit($chatId, $messageId, $msg, inlineKeyboard([
        [['text' => '🔄 Yangilash', 'callback_data' => 'stat_providers']],
        [['text' => '⬅️ Orqaga qaytish', 'callback_data' => 'stat_back']],
    ]));
}

function handleHourlyStats(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $today = date('Y-m-d');
    $hoursData = [];
    $maxCount = 1;

    for ($h = 0; $h < 24; $h++) {
        $hourStr = sprintf('%02d', $h);
        $count = Database::count(
            "SELECT COUNT(*) FROM users WHERE registration_date LIKE ?",
            ["$today $hourStr:%"]
        );
        $hoursData[$h] = $count;
        if ($count > $maxCount) $maxCount = $count;
    }

    $currentHour = intval(date('H'));
    $msg = "🕐 <b>Bugungi soatlik statistika:</b>\n\n";

    for ($h = 0; $h < 24; $h++) {
        $count = $hoursData[$h];
        $barLen = $maxCount > 0 ? round(($count / $maxCount) * 8) : 0;
        $bar = str_repeat('▓', $barLen) . str_repeat('░', 8 - $barLen);
        $hourLabel = sprintf('%02d:00', $h);
        $pointer = ($h === $currentHour) ? ' ◀️' : '';
        $msg .= "<code>$hourLabel</code> $bar <b>$count</b>$pointer\n";
    }

    $totalToday = array_sum($hoursData);
    $msg .= "\n▪ <b>Bugun jami:</b> $totalToday ta yangi foydalanuvchi";

    edit($chatId, $messageId, $msg, inlineKeyboard([
        [['text' => '⬅️ Orqaga qaytish', 'callback_data' => 'stat_back']]
    ]));
}

function handleLeftGraph(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $dayNames = ['Mon' => 'Du', 'Tue' => 'Se', 'Wed' => 'Ch', 'Thu' => 'Pa', 'Fri' => 'Ju', 'Sat' => 'Sh', 'Sun' => 'Ya'];
    $daysData = [];
    $maxCount = 1;
    $totalGone = Database::count("SELECT COUNT(*) FROM soxta WHERE come = 'gone'");
    $dailyAvg = max(1, round($totalGone / 7));

    for ($d = 6; $d >= 0; $d--) {
        $dateShort = date('d.m', strtotime("-$d days"));
        $dayKey = date('D', strtotime("-$d days"));
        $count = $dailyAvg;
        $daysData[] = ['date' => $dateShort, 'day' => $dayKey, 'count' => $count];
        if ($count > $maxCount) $maxCount = $count;
    }

    $msg = "📉 <b>Oxirgi 7 kunda tark etganlar:</b>\n\n";

    foreach ($daysData as $day) {
        $barLen = $maxCount > 0 ? round(($day['count'] / $maxCount) * 8) : 0;
        $bar = str_repeat('▓', $barLen) . str_repeat('░', 8 - $barLen);
        $dayName = $dayNames[$day['day']] ?? $day['day'];
        $msg .= "<code>{$day['date']} $dayName</code> $bar <b>{$day['count']}</b>\n";
    }

    $totalLeft = array_sum(array_column($daysData, 'count'));
    $weekNew = Database::count("SELECT COUNT(*) FROM users WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $diff = $weekNew - $totalLeft;
    $diffText = $diff >= 0 ? "📈 +$diff ta o'sish" : "📉 $diff ta kamayish";

    $msg .= "\n▪ <b>Jami 7 kunda:</b> ~$totalLeft ta tark etgan\n$diffText (yangi - ketgan)";

    edit($chatId, $messageId, $msg, inlineKeyboard([
        [['text' => '⬅️ Orqaga qaytish', 'callback_data' => 'stat_back']]
    ]));
}

function handleResetBalanceConfirm(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $totalBalance = Database::sum("SELECT SUM(balance) FROM users");
    $userCount = Database::count("SELECT COUNT(*) FROM users WHERE balance > 0");

    edit($chatId, $messageId, "⚠️ <b>Diqqat! Barcha balanslar 0 ga tushiriladi!</b>

👥 Balansi bor: <b>$userCount</b> ta
💰 Jami: <b>" . formatNumber($totalBalance) . "</b> so'm

❓ Davom etasizmi?", inlineKeyboard([
        [
            ['text' => '✓ Ha, 0 ga tushirish', 'callback_data' => 'stat_reset_bal_yes'],
            ['text' => '✗ Bekor', 'callback_data' => 'stat_back'],
        ],
    ]));
}

function handleResetBalance(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);
    Database::execute("UPDATE users SET balance = 0");
    edit($chatId, $messageId, "✓ <b>Barcha foydalanuvchilar balansi 0 ga tushirildi!</b>",
        inlineKeyboard([[['text' => '⬅️ Orqaga qaytish', 'callback_data' => 'stat_back']]])
    );
}

function handleResetRefConfirm(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);

    $refCount = Database::count("SELECT COUNT(*) FROM users WHERE refnum > 0");
    $totalRef = Database::sum("SELECT SUM(refnum) FROM users");

    edit($chatId, $messageId, "⚠️ <b>Diqqat! Barcha referallar 0 ga tushiriladi!</b>

👥 Referali bor: <b>$refCount</b> ta
🤝 Jami: <b>" . intval($totalRef) . "</b> ta

❓ Davom etasizmi?", inlineKeyboard([
        [
            ['text' => '✓ Ha, 0 ga tushirish', 'callback_data' => 'stat_reset_ref_yes'],
            ['text' => '✗ Bekor', 'callback_data' => 'stat_back'],
        ],
    ]));
}

function handleResetRef(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);
    Database::execute("UPDATE users SET refnum = 0");
    edit($chatId, $messageId, "✓ <b>Barcha foydalanuvchilar referallari 0 ga tushirildi!</b>",
        inlineKeyboard([[['text' => '⬅️ Orqaga qaytish', 'callback_data' => 'stat_back']]])
    );
}

// ==========================================
// 🤖 BOT HOLATI
// ==========================================

function handleBotStatusToggle(string $chatId, string $messageType = 'text', int $messageId = 0, string $callbackId = ''): void
{
    $settings = Database::fetchOne("SELECT bot_status FROM settings WHERE id = 1");
    $status = ($settings && $settings['bot_status'] === 'deactive') ? 'deactive' : 'active';

    if ($messageType === 'callback') {
        // Toggle holat
        answerCallback($callbackId);
        $newStatus = ($status === 'active') ? 'deactive' : 'active';
        Database::execute("UPDATE settings SET bot_status = ? WHERE id = 1", [$newStatus]);
        $status = $newStatus;
        $actionText = ($newStatus === 'active') ? '✓ Bot yoqildi!' : '● Bot o\'chirildi!';
    } else {
        $actionText = '';
    }

    $statusText = ($status === 'active') ? '○ Yoqilgan' : '● O\'chirilgan';
    $buttonText = ($status === 'active') ? '● O\'chirish' : '○ Yoqish';

    $text = "🤖 <b>BOT HOLATI</b>
━━━━━━━━━━━━━━━━━━━━

📌 <b>Joriy holat:</b> $statusText" .
        ($actionText ? "\n⚡ <b>$actionText</b>" : '') . "

━━━━━━━━━━━━━━━━━━━━
💡 Bot o'chirilganda foydalanuvchilar botdan foydalana olmaydi. Faqat admin ishlata oladi.";

    $keyboard = inlineKeyboard([[['text' => $buttonText, 'callback_data' => 'bot_toggle']]]);

    if ($messageType === 'callback') {
        edit($chatId, $messageId, $text, $keyboard);
    } else {
        sms($chatId, $text, $keyboard);
    }
}

// ==========================================
// ✉️ OMMAVIY XABAR YUBORISH
// ==========================================

function handleBroadcast(string $chatId): void
{
    $check = Database::fetchOne("SELECT * FROM send LIMIT 1");

    if ($check) {
        $sent = intval($check['start_id']);
        $total = intval($check['stop_id']);
        $percent = $total > 0 ? round(($sent / $total) * 100) : 0;

        sms($chatId, "⏳ <b>XABAR YUBORILMOQDA</b>
━━━━━━━━━━━━━━━━━━━━

▪ Jarayon: $sent / $total ($percent%)

❗ Yangi xabar uchun kuting yoki bekor qiling:",
            inlineKeyboard([[['text' => '✗ Bekor qilish', 'callback_data' => 'send_cancel']]])
        );
    } else {
        sms($chatId, "✉️ <b>OMMAVIY XABAR</b>
━━━━━━━━━━━━━━━━━━━━

📤 Foydalanuvchilarga yuboriladigan xabarni yuboring.

💡 <b>Qo'llab-quvvatlanadi:</b>
• Matn, rasm, video, audio, sticker
• Forward qilingan xabar

⚠️ Xabar barcha foydalanuvchilarga yuboriladi.", getBackKeyboard());

        setStep($chatId, 'broadcast');
    }
}

function handleBroadcastMessage(string $chatId, int $messageId): void
{
    $totalUsers = Database::count("SELECT COUNT(*) FROM users");

    Database::execute("DELETE FROM send");
    Database::execute(
        "INSERT INTO send (admin_id, message_id, start_id, stop_id) VALUES (?, ?, 0, ?)",
        [$chatId, $messageId, $totalUsers]
    );

    sms($chatId, "✅ <b>Xabar navbatga qo'shildi!</b>

📤 Jami: $totalUsers ta foydalanuvchiga yuboriladi.
⏰ Cron orqali har daqiqada " . CRON_SEND_LIMIT . " ta yuboriladi.

💡 Jarayonni kuzatish uchun \"✉️ Xabar yuborish\" tugmasini bosing.", getUserMenu($chatId));

    clearStep($chatId);
}

function handleBroadcastCancel(string $chatId, int $messageId, string $callbackId): void
{
    answerCallback($callbackId);
    Database::execute("DELETE FROM send");
    edit($chatId, $messageId, "✗ <b>Xabar yuborish bekor qilindi!</b>");
}

// ==========================================
// 📢 KANALLAR BOSHQARUVI
// ==========================================

function handleChannels(string $chatId): void
{
    sms($chatId, "<b>📢 Kanallar bo'limi:</b>", inlineKeyboard([
        [['text' => '➕ Qo\'shish', 'callback_data' => 'channel_add']],
        [
            ['text' => '*️⃣ Ro\'yxat', 'callback_data' => 'channel_list'],
            ['text' => '🗑️ O\'chirish', 'callback_data' => 'channel_delete'],
        ],
    ]));
}

function handleChannelCallback(string $chatId, int $messageId, string $callbackId, string $action): void
{
    $channelFile = SET_DIR . 'channel';
    $content = file_exists($channelFile) ? trim(file_get_contents($channelFile)) : '';
    $channels = array_filter(explode("\n", $content));

    switch ($action) {
        case 'list':
            answerCallback($callbackId);
            if (empty($channels)) {
                sms($chatId, "🤷‍♂️ Hechqanday kanal topilmadi.");
                return;
            }
            $buttons = [];
            foreach ($channels as $ch) {
                $chName = str_replace('@', '', trim($ch));
                $buttons[] = [['text' => $ch, 'url' => "https://t.me/$chName"]];
            }
            sms($chatId, "🌐 <b>Barcha kanallar:</b>", inlineKeyboard($buttons));
            break;

        case 'delete':
            answerCallback($callbackId);
            if (empty($channels)) {
                sms($chatId, "🤷‍♂️ Hechqanday kanal topilmadi.");
                return;
            }
            $buttons = [];
            foreach ($channels as $ch) {
                $buttons[] = [['text' => $ch, 'callback_data' => 'channel_del_' . trim($ch)]];
            }
            $buttons = array_chunk($buttons, 2);
            sms($chatId, "🗑️ <b>O'chiriladigan kanalni tanlang:</b>", inlineKeyboard($buttons));
            break;

        case 'add':
            answerCallback($callbackId);
            sms($chatId, "♻️ <b>Kanal userini kiriting</b>\n\nNamuna: @username", getBackKeyboard());
            setStep($chatId, 'channel_add');
            break;
    }
}

function handleChannelAdd(string $chatId, string $text): void
{
    if (strpos($text, '@') === false) {
        sms($chatId, "⚠️ <b>Noto'g'ri format!</b>\n\nNamuna: @username");
        return;
    }

    $channelFile = SET_DIR . 'channel';
    $current = file_exists($channelFile) ? trim(file_get_contents($channelFile)) : '';

    if (empty($current)) {
        file_put_contents($channelFile, $text);
    } else {
        file_put_contents($channelFile, "$current\n$text");
    }

    sms($chatId, "✅ <b>Kanal saqlandi!</b>", getAdminPanel());
    clearStep($chatId);
}

function handleChannelDelete(string $chatId, string $channel): void
{
    $channelFile = SET_DIR . 'channel';
    $content = file_exists($channelFile) ? trim(file_get_contents($channelFile)) : '';
    $channels = array_filter(explode("\n", $content));

    $channels = array_filter($channels, fn($ch) => trim($ch) !== $channel);

    if (empty($channels)) {
        @unlink($channelFile);
    } else {
        file_put_contents($channelFile, implode("\n", $channels));
    }

    sms($chatId, "✅ <b>$channel o'chirildi!</b>");
}

// ==========================================
// ⏰ CRON SOZLAMASI
// ==========================================

function handleCronSettings(string $chatId): void
{
    $domain = $_SERVER['SERVER_NAME'] ?? 'yourdomain.uz';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/src/index.php';

    $urlSend = "https://$domain$script?update=send";
    $urlStatus = "https://$domain$script?update=status";

    sms($chatId, "⏰ <b>CRON SOZLAMASI</b>
━━━━━━━━━━━━━━━━━━━━

Quyidagi manzillarni hostingda <b>Cron Job</b> ga qo'shing:

━━━━━━━━━━━━━━━━━━━━
1️⃣ <b>Xabar yuborish (ommaviy)</b>
<code>$urlSend</code>

2️⃣ <b>Buyurtma statusini yangilash</b>
<code>$urlStatus</code>

━━━━━━━━━━━━━━━━━━━━
⚙️ <b>Tavsiya vaqt oralig'i:</b>
┣ 1️⃣ — Har 1 daqiqada
┗ 2️⃣ — Har 5 daqiqada

💡 <i>Manzilni bosib nusxa oling.</i>", getAdminPanel());
}

// ==========================================
// 🇺🇿 VALYUTA KURSI
// ==========================================

function handleCurrencyRates(string $chatId, string $type = 'text', int $messageId = 0, string $callbackId = ''): void
{
    if ($type === 'callback') {
        answerCallback($callbackId, '🔄 Yangilanmoqda...');
    }

    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents('https://cbu.uz/uz/arkhiv-kursov-valyut/json/', false, $ctx);

    if (!$response) {
        $errMsg = "✗ <b>Valyuta kurslarini olishda xatolik!</b>";
        $type === 'callback' ? edit($chatId, $messageId, $errMsg) : sms($chatId, $errMsg);
        return;
    }

    $data = json_decode($response, true);
    if (!$data) {
        $errMsg = "✗ <b>Ma'lumotlarni qayta ishlashda xatolik!</b>";
        $type === 'callback' ? edit($chatId, $messageId, $errMsg) : sms($chatId, $errMsg);
        return;
    }

    $currencies = [
        'USD' => ['symbol' => '$', 'flag' => '🇺🇸', 'name' => 'AQSH dollari'],
        'EUR' => ['symbol' => '€', 'flag' => '🇪🇺', 'name' => 'Yevro'],
        'RUB' => ['symbol' => '₽', 'flag' => '🇷🇺', 'name' => 'Rossiya rubli'],
        'GBP' => ['symbol' => '£', 'flag' => '🇬🇧', 'name' => 'Angliya funti'],
        'TRY' => ['symbol' => '₺', 'flag' => '🇹🇷', 'name' => 'Turk lirasi'],
        'CNY' => ['symbol' => '¥', 'flag' => '🇨🇳', 'name' => 'Xitoy yuani'],
        'KRW' => ['symbol' => '₩', 'flag' => '🇰🇷', 'name' => 'Koreys voni'],
    ];

    $rates = [];
    foreach ($data as $item) {
        if (isset($currencies[$item['Ccy']])) {
            $rates[$item['Ccy']] = $item['Rate'];
        }
    }

    $msg = "🇺🇿 <b>VALYUTA KURSLARI</b>
━━━━━━━━━━━━━━━━━━━━
📅 <b>Yangilangan:</b> " . date('d.m.Y H:i') . "\n\n";

    foreach ($currencies as $code => $info) {
        if (isset($rates[$code])) {
            $rate = number_format(floatval($rates[$code]), 2, '.', ' ');
            $msg .= "{$info['flag']} 1 {$info['symbol']} ({$code}) = <code>$rate</code> so'm\n";
        }
    }

    $msg .= "\n━━━━━━━━━━━━━━━━━━━━\n▪ <i>Manba: Markaziy bank (cbu.uz)</i>";

    $keyboard = inlineKeyboard([[['text' => '🔄 Yangilash', 'callback_data' => 'refresh_currency']]]);

    if ($type === 'callback') {
        edit($chatId, $messageId, $msg, $keyboard);
    } else {
        sms($chatId, $msg, $keyboard);
    }
}

// ==========================================
// BAHOLASH TIZIMI
// ==========================================

function handleRating(string $chatId): void
{
    $avgRow = Database::fetchOne("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM ratings");
    $avg = ($avgRow && $avgRow['cnt'] > 0) ? number_format(floatval($avgRow['avg_r']), 1) : '0.0';
    $totalRatings = $avgRow['cnt'] ?? 0;

    $oldText = '';
    $oldRating = Database::fetchOne("SELECT rating FROM ratings WHERE user_id = ?", [$chatId]);
    if ($oldRating) {
        $oldText = "\n🔄 Sizning bahoyingiz: " . str_repeat('★', $oldRating['rating']) . str_repeat('☆', 5 - $oldRating['rating']);
    }

    sms($chatId, "┏━━━━━━━━━━━━━━━━━━━┓
         ·  <b>BAHOLASH</b>  ·
┗━━━━━━━━━━━━━━━━━━━┛

Botimiz haqida fikringiz qanday?
Pastdagi tugmalar orqali baholang:
$oldText

┌─────────────────────┐
│  ▪ Reyting: <b>$avg</b> / 5.0  │  👥 $totalRatings ta baho
└─────────────────────┘", inlineKeyboard([
        [
            ['text' => '😡 1', 'callback_data' => 'rating_1'],
            ['text' => '😕 2', 'callback_data' => 'rating_2'],
            ['text' => '😐 3', 'callback_data' => 'rating_3'],
            ['text' => '😊 4', 'callback_data' => 'rating_4'],
            ['text' => '🤩 5', 'callback_data' => 'rating_5'],
        ],
    ]));
}

function handleRatingCallback(string $chatId, int $messageId, string $callbackId, int $score): void
{
    answerCallback($callbackId);

    // Saqlash
    $existing = Database::fetchOne("SELECT id FROM ratings WHERE user_id = ?", [$chatId]);
    if ($existing) {
        Database::execute("UPDATE ratings SET rating = ?, created_at = NOW() WHERE user_id = ?", [$score, $chatId]);
    } else {
        Database::execute("INSERT INTO ratings (user_id, rating) VALUES (?, ?)", [$chatId, $score]);
    }

    // O'rtacha
    $avgRow = Database::fetchOne("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM ratings");
    $avg = number_format(floatval($avgRow['avg_r']), 1);
    $totalRatings = $avgRow['cnt'];

    // Vizual
    $filled = str_repeat('★', $score);
    $empty = str_repeat('☆', 5 - $score);
    $faces = ['', '😡', '😕', '😐', '😊', '🤩'];
    $face = $faces[$score];
    $barFilled = str_repeat('▰', $score);
    $barEmpty = str_repeat('▱', 5 - $score);

    edit($chatId, $messageId, "┏━━━━━━━━━━━━━━━━━━━┓
      ✓  <b>BAHO QABUL QILINDI</b>
┗━━━━━━━━━━━━━━━━━━━┛

$face  <b>$filled$empty</b>  ($score / 5)

$barFilled$barEmpty

┌─────────────────────┐
│  ▪ Bot reytingi: <b>$avg</b> / 5.0
│  👥 Jami: <b>$totalRatings</b> ta baho
└─────────────────────┘

🙏 Fikringiz uchun rahmat!");

    // Adminga xabar
    sms(ADMIN_ID, "· <b>Yangi baho</b>

👤 <a href='tg://user?id=$chatId'>Foydalanuvchi</a>
$face $filled$empty ($score/5)
▪ O'rtacha: $avg ($totalRatings ta)");
}
