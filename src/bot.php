<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * SFXSMM Bot — Asosiy Webhook Handler
 * ═══════════════════════════════════════════════════════════════
 * 
 * Webhook URL: https://yourdomain.uz/src/bot.php
 * Cron: https://yourdomain.uz/src/bot.php?update=send (har 1 daqiqa)
 *       https://yourdomain.uz/src/bot.php?update=status (har 5 daqiqa)
 * 
 * Funksiyalar:
 * - 👥 Ko'p admin tizimi
 * - 🏆 Haftalik musobaqa
 * - 🔄 Buyurtma takrorlash
 * - 🌐 Ko'p tilli bot (UZ/RU)
 * - 📉 Xizmatlarga chegirma
 * - 🧮 Xizmat statistikasi (necha marta buyurtma, vaqt, qayta tiklash)
 * - 📋 Buyurtmalarim (sahifalash, bekor qilish, takrorlash)
 */

ob_start();
require_once __DIR__ . '/functions.php';

// ══════════════════════════════════════════
// CRON VAZIFALARI
// ══════════════════════════════════════════
if (isset($_GET['update'])) {
    DB::conn();
    $action = $_GET['update'];

    if ($action === 'send') {
        $row = DB::one("SELECT * FROM send LIMIT 1");
        if (!$row) die("Navbat bo'sh");
        $users = DB::all("SELECT id FROM users LIMIT ?, ?", [intval($row['start_id']), CRON_LIMIT]);
        if (empty($users)) {
            sms($row['admin_id'], "✓ <b>Xabar barcha foydalanuvchilarga yuborildi!</b>\n▪ Jami: {$row['start_id']} ta");
            DB::exec("DELETE FROM send");
            die("Tugadi");
        }
        $sent = 0;
        foreach ($users as $u) { bot('forwardMessage', ['chat_id' => $u['id'], 'from_chat_id' => $row['admin_id'], 'message_id' => $row['message_id']]); $sent++; }
        $new = intval($row['start_id']) + $sent;
        DB::exec("UPDATE send SET start_id = ?", [$new]);
        if ($new >= intval($row['stop_id'])) { sms($row['admin_id'], "✓ <b>Xabar yuborildi!</b> Jami: $new ta"); DB::exec("DELETE FROM send"); }
        die("Yuborildi: $new");
    }

    if ($action === 'status') {
        $orders = DB::all("SELECT o.*, s.api_service FROM orders o LEFT JOIN services s ON s.service_id = o.service_id WHERE o.status IN ('Pending','In progress','Processing') LIMIT 50");
        $upd = 0;
        foreach ($orders as $o) {
            $prov = DB::one("SELECT * FROM providers WHERE id = ?", [$o['api_service'] ?? 0]);
            if (!$prov) continue;
            $r = @file_get_contents($prov['api_url'] . "?key=" . $prov['api_key'] . "&action=status&order=" . $o['order_id']);
            $d = json_decode($r, true);
            if (!$d || !isset($d['status'])) continue;
            if ($d['status'] !== $o['status']) {
                DB::exec("UPDATE orders SET status = ? WHERE order_id = ?", [$d['status'], $o['order_id']]);
                DB::exec("UPDATE myorder SET status = ?, last_check = NOW() WHERE order_id = ?", [$d['status'], $o['order_id']]);
                if ($d['status'] === 'Completed') {
                    $mo = DB::one("SELECT user_id FROM myorder WHERE order_id = ?", [$o['order_id']]);
                    if ($mo) sms($mo['user_id'], "✓ <b>{$o['order_id']} raqamli buyurtmangiz bajarildi!</b>\n\n🔥 Rahmat!");
                }
                $upd++;
            }
        }
        die("Updated: $upd");
    }

    // Haftalik musobaqa natijalarini tekshirish (har dushanba)
    if ($action === 'contest') {
        $dayOfWeek = date('N'); // 1=Monday
        if ($dayOfWeek != 1) die("Not Monday");
        $prevWeek = intval(date('W')) - 1;
        $year = intval(date('Y'));
        $top = DB::all("SELECT user_id, orders_count FROM weekly_contest WHERE week_num = ? AND year = ? ORDER BY orders_count DESC LIMIT 3", [$prevWeek, $year]);
        if (empty($top)) die("No data");
        $prizes = [5000, 3000, 1000]; // 1-o'rin, 2-o'rin, 3-o'rin
        $msg = "🏆 <b>HAFTALIK MUSOBAQA NATIJALARI</b>\n━━━━━━━━━━━━━━━━━━━━\n\n";
        foreach ($top as $i => $t) {
            $medal = ['🥇', '🥈', '🥉'][$i];
            $prize = $prizes[$i] ?? 0;
            $msg .= "$medal <a href='tg://user?id={$t['user_id']}'>{$t['user_id']}</a> — 📦 {$t['orders_count']} ta buyurtma (+{$prize} so'm)\n";
            if ($prize > 0) DB::exec("UPDATE users SET balance = balance + ? WHERE id = ?", [$prize, $t['user_id']]);
            sms($t['user_id'], "🏆 <b>Tabriklaymiz!</b>\n\nSiz haftalik musobaqada $medal o'rin egallading!\n💰 Hisobingizga <b>$prize</b> so'm bonus qo'shildi!");
        }
        // Barcha foydalanuvchilarga xabar
        sms(MAIN_ADMIN, $msg);
        die("Contest done");
    }

    die("Unknown: $action");
}

// ══════════════════════════════════════════
// WEBHOOK
// ══════════════════════════════════════════
$update = json_decode(file_get_contents('php://input'));
if (!$update) die('No data');

DB::conn();
DB::init();

// Bot bloklash
$myChatMember = $update->my_chat_member ?? null;
if ($myChatMember) {
    $uid = $myChatMember->from->id ?? '';
    $st = $myChatMember->new_chat_member->status ?? '';
    if ($st === 'kicked' && $uid) {
        $ex = DB::one("SELECT id FROM soxta WHERE user_id = ?", [$uid]);
        if ($ex) DB::exec("UPDATE soxta SET come = 'gone' WHERE user_id = ?", [$uid]);
        else { DB::exec("DELETE FROM users WHERE id = ?", [$uid]); DB::exec("INSERT INTO soxta(user_id,come) VALUES(?,'gone')", [$uid]); }
        clearStep($uid);
    }
    exit;
}

$message = $update->message ?? null;
$callback = $update->callback_query ?? null;

$cid = $message->from->id ?? '';
$text = $message->text ?? '';
$name = $message->from->first_name ?? '';
$mid = $message->message_id ?? 0;

$cbCid = $callback->from->id ?? '';
$cbMid = $callback->message->message_id ?? 0;
$cbData = $callback->data ?? '';
$cbQid = $callback->id ?? '';
$cbChat = $callback->message->chat->id ?? '';

$userId = $cid ?: $cbCid;

// Ban tekshirish
if ($userId) {
    $u = DB::one("SELECT status FROM users WHERE id = ?", [$userId]);
    if ($u && $u['status'] === 'deactive') exit;
}

// Bot holati
if (!isAdmin($userId)) {
    $st = DB::one("SELECT bot_status FROM settings WHERE id = 1");
    if ($st && $st['bot_status'] === 'deactive') { sms($userId, t('bot_maintenance', $userId)); exit; }
}

// Ro'yxatdan o'tkazish
if ($message) registerUser($cid);

$step = getStep($userId);

// ══════════════════════════════════════════
// 📨 MESSAGE HANDLER
// ══════════════════════════════════════════
if ($message) {

    // /start
    if (strpos($text, '/start') === 0) {
        if (!checkSub($cid)) exit;
        // Referal
        if (strpos($text, '/start ') === 0) {
            $ref = trim(str_replace('/start ', '', $text));
            $referrer = DB::one("SELECT id, refnum FROM users WHERE referal = ?", [$ref]);
            if ($referrer && $referrer['id'] !== $cid) {
                $ex = DB::one("SELECT id FROM users WHERE id = ? AND registration_date > DATE_SUB(NOW(), INTERVAL 1 MINUTE)", [$cid]);
                if ($ex) {
                    $bonus = DB::one("SELECT ref_bonus FROM settings WHERE id = 1")['ref_bonus'] ?? 0;
                    if ($bonus > 0) DB::exec("UPDATE users SET balance = balance + ?, refnum = refnum + 1 WHERE id = ?", [$bonus, $referrer['id']]);
                    else DB::exec("UPDATE users SET refnum = refnum + 1 WHERE id = ?", [$referrer['id']]);
                }
            }
        }
        $botUser = bot('getMe')->result->username ?? 'SFXSMMBot';
        sms($cid, sprintf(t('welcome', $cid), $botUser), getMenu($cid));
        exit;
    }

    // 🌐 Til o'zgartirish
    if (mb_stripos($text, "Tilni o'zgartirish") !== false || mb_stripos($text, "Сменить язык") !== false || $text === '🌐') {
        sms($cid, "🌐 <b>Tilni tanlang / Выберите язык:</b>", ikb([
            [['text' => "🇺🇿 O'zbekcha", 'callback_data' => 'lang_uz']],
            [['text' => '🇷🇺 Русский', 'callback_data' => 'lang_ru']],
        ]));
        exit;
    }

    // Orqaga
    if ($text === '⏩ Orqaga' || $text === '⏩ Назад') {
        clearStep($cid);
        sms($cid, t('main_menu', $cid), getMenu($cid));
        exit;
    }

    // ══════════════════════════════════════════
    // ADMIN BUYRUQLARI
    // ══════════════════════════════════════════
    if (isAdmin($cid)) {
        // Boshqaruv
        if (mb_stripos($text, 'Boshqaruv') !== false || mb_stripos($text, 'Управление') !== false) {
            $adminKb = rkb([
                [['text' => '📢 Kanallar'], ['text' => '📊 Statistika']],
                [['text' => '⚙ Asosiy'], ['text' => '✉️ Xabar yuborish']],
                [['text' => '🔎 Foydalanuvchini boshqarish']],
                [['text' => '🤖 Bot holati'], ['text' => '🔎 Buyurtma']],
                [['text' => '👥 Adminlar'], ['text' => '🏆 Musobaqa']],
                [['text' => '🇺🇿 Valyuta kursi'], ['text' => '⏰ Cron']],
                [['text' => t('menu_back', $cid)]],
            ]);
            sms($cid, "👨‍💻 <b>Boshqaruv paneli:</b>", $adminKb);
            clearStep($cid);
            exit;
        }

        // 👥 Adminlar ro'yxati
        if ($text === '👥 Adminlar') {
            $msg = "👥 <b>ADMINLAR RO'YXATI</b>\n━━━━━━━━━━━━━━━━━━━━\n\n";
            foreach (ADMINS as $i => $aid) {
                $num = $i + 1;
                $info = bot('getChat', ['chat_id' => $aid]);
                $aname = $info->result->first_name ?? $aid;
                $mark = ($aid === MAIN_ADMIN) ? " 👑" : "";
                $msg .= "$num. <a href='tg://user?id=$aid'>$aname</a>$mark\n   🆔 <code>$aid</code>\n\n";
            }
            $msg .= "━━━━━━━━━━━━━━━━━━━━\n💡 <i>Yangi admin qo'shish uchun src/functions.php dagi ADMINS massiviga ID qo'shing.</i>";
            sms($cid, $msg);
            exit;
        }

        // 🏆 Musobaqa
        if ($text === '🏆 Musobaqa') {
            $top = getWeeklyTop(10);
            $msg = "🏆 <b>HAFTALIK MUSOBAQA</b>\n━━━━━━━━━━━━━━━━━━━━\n📅 Hafta: " . date('W') . " / " . date('Y') . "\n\n";
            if (empty($top)) {
                $msg .= "📭 Hali buyurtmalar yo'q.";
            } else {
                $medals = ['🥇', '🥈', '🥉'];
                foreach ($top as $i => $t) {
                    $medal = $medals[$i] ?? ($i + 1) . ".";
                    $info = bot('getChat', ['chat_id' => $t['user_id']]);
                    $uname = $info->result->first_name ?? $t['user_id'];
                    $msg .= "$medal <a href='tg://user?id={$t['user_id']}'>$uname</a> — 📦 {$t['orders_count']} ta\n";
                }
            }
            $msg .= "\n━━━━━━━━━━━━━━━━━━━━\n💰 <b>Mukofotlar:</b>\n🥇 1-o'rin: 5,000 so'm\n🥈 2-o'rin: 3,000 so'm\n🥉 3-o'rin: 1,000 so'm\n\n⏰ Natijalar har dushanba e'lon qilinadi.";
            sms($cid, $msg);
            exit;
        }

        // 📊 Statistika
        if (mb_stripos($text, 'Statistika') !== false) {
            $ac = DB::count("SELECT COUNT(*) FROM users WHERE status = 'active'");
            $dc = DB::count("SELECT COUNT(*) FROM users WHERE status = 'deactive'");
            $svc = DB::count("SELECT COUNT(*) FROM services");
            $ords = DB::count("SELECT COUNT(*) FROM orders");
            $todayOrd = DB::count("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
            $provs = DB::count("SELECT COUNT(*) FROM providers");
            $totalBal = DB::sum("SELECT SUM(balance) FROM users");
            $todayRev = DB::sum("SELECT SUM(retail) FROM myorder WHERE DATE(order_create) = CURDATE() AND status = 'Completed'");
            $monthRev = DB::sum("SELECT SUM(retail) FROM myorder WHERE MONTH(order_create) = MONTH(CURDATE()) AND YEAR(order_create) = YEAR(CURDATE()) AND status = 'Completed'");
            $todayNew = DB::count("SELECT COUNT(*) FROM users WHERE DATE(registration_date) = CURDATE()");

            sms($cid, "▪ <b>Bot statistikasi</b>\n🕐 " . date('d.m.Y | H:i') . "\n\n<blockquote>👥 <b>Foydalanuvchilar:</b>\n\n🆕 Bugun qo'shildi: <b>+$todayNew</b> ta\n👥 Jami: <b>$ac</b> ta\n⛔ Bloklangan: <b>$dc</b> ta</blockquote>\n\n<blockquote>🛒 <b>Bot:</b>\n\n▪ Xizmatlar: <b>$svc</b> ta\n🌐 Provayderlar: <b>$provs</b> ta\n📦 Buyurtmalar: <b>$ords</b> ta\n🆕 Bugungi: <b>$todayOrd</b> ta</blockquote>\n\n<blockquote>💰 <b>Daromad:</b>\n\n📈 Bugungi: <b>" . fmt($todayRev) . "</b> so'm\n📆 Oylik: <b>" . fmt($monthRev) . "</b> so'm\n💵 Balanslar: <b>" . fmt($totalBal) . "</b> so'm</blockquote>", ikb([
                [['text' => '🔄 Yangilash', 'callback_data' => 'stat_refresh']],
            ]));
            exit;
        }

        // ✉️ Xabar yuborish
        if (mb_stripos($text, 'Xabar yuborish') !== false) {
            $check = DB::one("SELECT * FROM send LIMIT 1");
            if ($check) {
                sms($cid, "⏳ <b>Xabar yuborilmoqda:</b> {$check['start_id']} / {$check['stop_id']}", ikb([[['text' => '✗ Bekor', 'callback_data' => 'send_cancel']]]));
            } else {
                sms($cid, "✉️ <b>Foydalanuvchilarga yuboriladigan xabarni yuboring:</b>", getBackKb($cid));
                setStep($cid, 'broadcast');
            }
            exit;
        }

        // 🤖 Bot holati
        if (mb_stripos($text, 'Bot holati') !== false) {
            $st = DB::one("SELECT bot_status FROM settings WHERE id = 1");
            $status = ($st && $st['bot_status'] === 'deactive') ? 'deactive' : 'active';
            $stText = $status === 'active' ? '○ Yoqilgan' : '● O\'chirilgan';
            $btnText = $status === 'active' ? '● O\'chirish' : '○ Yoqish';
            sms($cid, "🤖 <b>BOT HOLATI:</b> $stText", ikb([[['text' => $btnText, 'callback_data' => 'bot_toggle']]]));
            exit;
        }

        // Step: broadcast
        if ($step === 'broadcast') {
            $total = DB::count("SELECT COUNT(*) FROM users");
            DB::exec("DELETE FROM send");
            DB::exec("INSERT INTO send(admin_id, message_id, start_id, stop_id) VALUES(?,?,0,?)", [$cid, $mid, $total]);
            sms($cid, "✅ <b>Xabar navbatga qo'shildi!</b>\n📤 Jami: $total ta foydalanuvchiga yuboriladi.", getMenu($cid));
            clearStep($cid);
            exit;
        }
    }

    // ══════════════════════════════════════════
    // FOYDALANUVCHI TUGMALARI
    // ══════════════════════════════════════════
    if (!checkSub($cid)) exit;

    // 🗂 Xizmatlarga buyurtma berish
    if (mb_stripos($text, 'Xizmatlarga') !== false || mb_stripos($text, 'Заказать') !== false) {
        $cats = isAdmin($cid) ? DB::all("SELECT * FROM categorys") : DB::all("SELECT * FROM categorys WHERE category_status = 'ON'");
        if (empty($cats)) { sms($cid, "⚠️ Xizmatlar topilmadi."); exit; }
        $btns = [];
        foreach ($cats as $c) $btns[] = ['text' => enc('decode', $c['category_name']), 'callback_data' => 'cat_' . $c['category_id']];
        $kb = array_chunk($btns, 2);
        $kb[] = [['text' => '🔍 ID orqali qidirish', 'callback_data' => 'search_id']];
        sms($cid, t('choose_category', $cid), ikb($kb));
        exit;
    }

    // 🔎 Buyurtmalarim
    if (mb_stripos($text, 'Buyurtmalarim') !== false || mb_stripos($text, 'Мои заказы') !== false) {
        showMyOrders($cid, 0);
        exit;
    }

    // 💳 Mening hisobim
    if (mb_stripos($text, 'Mening hisobim') !== false || mb_stripos($text, 'Мой аккаунт') !== false) {
        $u = DB::one("SELECT * FROM users WHERE id = ?", [$cid]);
        $info = bot('getChat', ['chat_id' => $cid]);
        $fname = $info->result->first_name ?? '';
        $uname = $info->result->username ?? '-';
        $ords = DB::count("SELECT COUNT(*) FROM myorder WHERE user_id = ?", [$cid]);
        $spent = DB::sum("SELECT SUM(retail) FROM myorder WHERE user_id = ? AND status = 'Completed'", [$cid]);
        $regDate = $u['registration_date'] ? date('d.m.Y', strtotime($u['registration_date'])) : '-';

        $msg = "<b><blockquote>🖥 Hisobingiz haqida ma'lumot</blockquote></b>\n\n<blockquote>"
            . "<b>├✍️ Ism:</b> $fname\n"
            . "<b>├🫂 Username:</b> @$uname\n"
            . "<b>├🆔 ID:</b> <code>$cid</code>\n"
            . "<b>├💰 Balans:</b> " . fmt($u['balance']) . " so'm\n"
            . "<b>├💵 To'lovlar:</b> " . fmt($u['outing']) . " so'm\n"
            . "<b>├👥 Taklif:</b> " . ($u['refnum'] ?? 0) . " ta\n"
            . "<b>├📊 Buyurtmalar:</b> $ords ta\n"
            . "<b>├💸 Sarflangan:</b> " . fmt($spent) . " so'm\n"
            . "<b>├📅 Ro'yxatdan o'tgan:</b> $regDate\n"
            . "<b>└♻️ Holat:</b> " . ($u['status'] === 'active' ? 'Aktiv ✅' : '⛔') . "</blockquote>";

        sms($cid, $msg, ikb([
            [['text' => '🔁 Pul o\'tkazish', 'callback_data' => 'transfer'], ['text' => '🚀 Referal', 'callback_data' => 'referal']],
            [['text' => '🏆 Haftalik reyting', 'callback_data' => 'my_contest']],
        ]));
        exit;
    }

    // 🚀 Mablag' yig'ish
    if (mb_stripos($text, "Mablag'") !== false || mb_stripos($text, 'Заработать') !== false) {
        $u = DB::one("SELECT referal, refnum FROM users WHERE id = ?", [$cid]);
        $botUser = bot('getMe')->result->username ?? 'SFXSMMBot';
        $link = "https://t.me/$botUser?start=" . ($u['referal'] ?? '');
        $bonus = DB::one("SELECT ref_bonus FROM settings WHERE id = 1")['ref_bonus'] ?? 0;
        sms($cid, "🚀 <b>Mablag' yig'ish</b>\n━━━━━━━━━━━━━━━━━━━━\n\n👥 Taklif qilganlar: <b>" . ($u['refnum'] ?? 0) . "</b> ta\n💰 Har bir taklif: <b>" . fmt($bonus) . "</b> so'm\n\n🔗 <b>Havolangiz:</b>\n<code>$link</code>\n\n💡 Do'stlaringizga yuboring — ular kirishi bilan bonus olasiz!");
        exit;
    }

    // 💵 Hisob to'ldirish
    if (mb_stripos($text, "Hisob to'ldirish") !== false || mb_stripos($text, 'Пополнить') !== false) {
        $payFile = SET_DIR . 'payments.txt';
        if (!file_exists($payFile)) { sms($cid, "⚠️ To'lov tizimlari sozlanmagan!"); exit; }
        $methods = array_filter(explode("\n", trim(file_get_contents($payFile))));
        $btns = [];
        foreach ($methods as $m) { $m = trim($m); if ($m) $btns[] = ['text' => $m, 'callback_data' => 'pay_' . base64_encode($m)]; }
        $kb = array_chunk($btns, 2);
        $kb[] = [['text' => '☎️ Admin', 'url' => 'tg://user?id=' . MAIN_ADMIN]];
        sms($cid, "<b>💵 To'lov tizimini tanlang:</b>", ikb($kb));
        exit;
    }

    // ☎️ Qo'llab-Quvvatlash
    if (mb_stripos($text, "Qo'llab") !== false || mb_stripos($text, 'Поддержка') !== false) {
        sms($cid, "<b>📞 Qo'llab-Quvvatlash</b>\n\n❓ Sizga qanday yordam kerak?", ikb([
            [['text' => '❓ FAQ — Savollar', 'callback_data' => 'faq_main']],
            [['text' => '📨 Murojaat yuborish', 'callback_data' => 'ticket_send']],
            [['text' => '📞 Admin', 'url' => 'https://t.me/' . ADMIN_USERNAME]],
        ]));
        exit;
    }

    // 💎 Vertual xizmatlar
    if (mb_stripos($text, 'Vertual') !== false || mb_stripos($text, 'Виртуальные') !== false) {
        $botUser = bot('getMe')->result->username ?? 'SFXSMMBot';
        sms($cid, "<b>💎 @$botUser — Vertual xizmatlar</b>\n\nQuyidagi bo'limlardan tanlang 👇", ikb([
            [['text' => '🗂 Buyurtma berish', 'callback_data' => 'info_orders'], ['text' => '📞 Nomerlar', 'callback_data' => 'info_numbers']],
            [['text' => '🤝 Referal', 'callback_data' => 'info_referal'], ['text' => '💵 To\'ldirish', 'callback_data' => 'info_deposit']],
            [['text' => '📜 Qoidalar', 'callback_data' => 'info_rules']],
        ]));
        exit;
    }

    // ══════════════════════════════════════════
    // STEP HANDLERLARI
    // ══════════════════════════════════════════

    // Buyurtma havola
    if (strpos($step, 'order_link_') === 0) {
        $parts = explode('_', str_replace('order_link_', '', $step));
        $svcId = intval($parts[0] ?? 0);
        if (strpos($text, 'http') === false && strpos($text, 't.me') === false) {
            sms($cid, t('invalid_link', $cid));
            exit;
        }
        $svc = DB::one("SELECT * FROM services WHERE service_id = ?", [$svcId]);
        sms($cid, sprintf(t('enter_quantity', $cid), fmt($svc['service_min']), fmt($svc['service_max'])));
        setStep($cid, "order_qty_{$svcId}_" . base64_encode($text));
        exit;
    }

    // Buyurtma miqdor
    if (strpos($step, 'order_qty_') === 0) {
        $stepData = str_replace('order_qty_', '', $step);
        $parts = explode('_', $stepData, 2);
        $svcId = intval($parts[0]);
        $link = base64_decode($parts[1] ?? '');
        if (!is_numeric($text)) { sms($cid, t('only_numbers', $cid)); exit; }
        $qty = intval($text);
        $svc = DB::one("SELECT * FROM services WHERE service_id = ?", [$svcId]);
        $min = intval($svc['service_min']); $max = intval($svc['service_max']);
        if ($qty < $min || $qty > $max) { sms($cid, sprintf(t('wrong_quantity', $cid), fmt($min), fmt($max))); exit; }

        $pricePerK = floatval($svc['service_price']);
        $discount = floatval($svc['discount'] ?? 0);
        $discountedPrice = calcDiscount($pricePerK, $discount);
        $total = ($discountedPrice * $qty) / 1000;
        $svcName = base64_decode($svc['service_name']);

        $u = DB::one("SELECT balance FROM users WHERE id = ?", [$cid]);
        if ($u['balance'] < $total) {
            sms($cid, sprintf(t('no_balance', $cid), fmt($total), fmt($u['balance']), fmt($total - $u['balance'])), getMenu($cid));
            clearStep($cid);
            exit;
        }

        sms($cid, sprintf(t('confirm_order', $cid), $svcName, $link, fmt($qty), fmt($total), $discount), ikb([
            [['text' => '✅ Buyurtma berish', 'callback_data' => "confirm_{$svcId}_{$qty}_" . base64_encode($link)]],
            [['text' => '❌ Bekor', 'callback_data' => 'order_cancel']],
            [['text' => '🔙 Orqaga', 'callback_data' => 'categories_back']],
        ]));
        clearStep($cid);
        exit;
    }

    // To'lov miqdori
    if (strpos($step, 'deposit_amt_') === 0) {
        $method = str_replace('deposit_amt_', '', $step);
        if (!is_numeric($text) || intval($text) < MIN_DEPOSIT) { sms($cid, "⛔ Minimal: " . fmt(MIN_DEPOSIT) . " so'm. Qayta kiriting:"); exit; }
        sms($cid, "📑 <b>To'lov chekining screenshotini yuboring:</b>", getBackKb($cid));
        setStep($cid, "deposit_img_{$method}_$text");
        exit;
    }

    // To'lov rasmi
    if (strpos($step, 'deposit_img_') === 0 && isset($message->photo)) {
        $parts = explode('_', str_replace('deposit_img_', '', $step), 2);
        $method = base64_decode($parts[0] ?? '');
        $amount = intval($parts[1] ?? 0);
        $u = DB::one("SELECT * FROM users WHERE id = ?", [$cid]);

        sms($cid, "✅ <b>To'lovingiz qabul qilindi!</b>\n\n⏳ Tasdiqlash 10-15 daqiqa.", getMenu($cid));

        $cp = bot('CopyMessage', ['chat_id' => PAYMENT_CHANNEL, 'message_id' => $mid, 'from_chat_id' => $cid]);
        $cpId = $cp->result->message_id ?? 0;
        bot('sendMessage', [
            'chat_id' => PAYMENT_CHANNEL, 'reply_to_message_id' => $cpId, 'parse_mode' => 'html',
            'text' => "<b>📑 #chek\n\n<blockquote>💳 Tizim: $method\n🔢 Miqdor: " . fmt($amount) . " so'm\n🆔 ID: $cid\n💰 Balans: " . fmt($u['balance']) . " so'm\n⏰ Vaqt: " . now() . "</blockquote></b>",
            'reply_markup' => ikb([
                [['text' => '✅ Tasdiqlash', 'callback_data' => "pay_ok_{$cid}_{$amount}"], ['text' => '⛔ Bekor', 'callback_data' => "pay_no_{$cid}_{$amount}"]],
                [['text' => $name, 'url' => "tg://user?id=$cid"]],
            ]),
        ]);
        clearStep($cid);
        exit;
    }

    // Murojaat
    if ($step === 'ticket_msg') {
        sms($cid, "✓ <b>Murojaatingiz qabul qilindi!</b>\n⏰ Javob: 1-24 soat.", getMenu($cid));
        file_put_contents(USER_DIR . "mt/$cid.mt", time());
        $fwd = bot('forwardMessage', ['chat_id' => MAIN_ADMIN, 'from_chat_id' => $cid, 'message_id' => $mid]);
        sms(MAIN_ADMIN, "📨 <b>Yangi murojaat!</b>\n👤 <a href='tg://user?id=$cid'>$name</a>\n🆔 <code>$cid</code>", ikb([
            [['text' => '✍️ Javob', 'callback_data' => "ticket_reply_$cid"]],
        ]));
        clearStep($cid);
        exit;
    }

    // Admin javob
    if (strpos($step, 'ticket_ans_') === 0 && isAdmin($cid)) {
        $tUser = str_replace('ticket_ans_', '', $step);
        bot('copyMessage', ['chat_id' => $tUser, 'from_chat_id' => $cid, 'message_id' => $mid]);
        sms($tUser, "💬 <b>Admin javob berdi!</b> ⬆️");
        sms($cid, "✓ Javob yuborildi!", getMenu($cid));
        @unlink(USER_DIR . "mt/$tUser.mt");
        clearStep($cid);
        exit;
    }

    // Pul o'tkazish miqdor
    if ($step === 'transfer_amt') {
        if (!is_numeric($text) || intval($text) < MIN_TRANSFER) { sms($cid, "⛔ Minimal: " . fmt(MIN_TRANSFER) . " so'm"); exit; }
        $u = DB::one("SELECT balance FROM users WHERE id = ?", [$cid]);
        if ($u['balance'] < intval($text)) { sms($cid, "⛔ Mablag' yetarli emas!"); exit; }
        sms($cid, "<b>Foydalanuvchi ID raqamini yuboring:</b>");
        setStep($cid, "transfer_to_$text");
        exit;
    }

    // Pul o'tkazish ID
    if (strpos($step, 'transfer_to_') === 0) {
        $amt = intval(str_replace('transfer_to_', '', $step));
        if ($cid === $text) { sms($cid, "⛔ O'zingizga o'tkazolmaysiz!"); exit; }
        $rcpt = DB::one("SELECT id FROM users WHERE id = ?", [$text]);
        if (!$rcpt) { sms($cid, "⛔ Foydalanuvchi topilmadi!"); exit; }
        $u = DB::one("SELECT balance FROM users WHERE id = ?", [$cid]);
        if ($u['balance'] < $amt) { sms($cid, "⛔ Mablag' yetarli emas!"); exit; }
        DB::exec("UPDATE users SET balance = balance - ? WHERE id = ?", [$amt, $cid]);
        DB::exec("UPDATE users SET balance = balance + ? WHERE id = ?", [$amt, $text]);
        sms($text, "📳 <a href='tg://user?id=$cid'>$cid</a> <b>hisobingizga " . fmt($amt) . " so'm o'tkazdi</b>");
        sms($cid, "✅ <b>" . fmt($amt) . " so'm o'tkazildi!</b>", getMenu($cid));
        clearStep($cid);
        exit;
    }
}

// ══════════════════════════════════════════
// 📲 CALLBACK HANDLER
// ══════════════════════════════════════════
if ($callback) {
    $cid = $cbChat ?: $cbCid;
    $data = $cbData;

    // Til tanlash
    if (strpos($data, 'lang_') === 0) {
        $lang = str_replace('lang_', '', $data);
        setUserLang($cbCid, $lang);
        answer($cbQid, '✅');
        del($cid, $cbMid);
        $botUser = bot('getMe')->result->username ?? 'SFXSMMBot';
        sms($cbCid, sprintf(t('welcome', $cbCid), $botUser), getMenu($cbCid));
        exit;
    }

    // Obuna tekshirish
    if ($data === 'check_sub') {
        if (checkSub($cbCid)) { answer($cbQid, '✅'); del($cid, $cbMid); sms($cbCid, t('main_menu', $cbCid), getMenu($cbCid)); }
        else answer($cbQid, '⛔ Obuna bo\'ling!', true);
        exit;
    }

    // Bot toggle
    if ($data === 'bot_toggle' && isAdmin($cbCid)) {
        answer($cbQid);
        $st = DB::one("SELECT bot_status FROM settings WHERE id = 1");
        $new = ($st && $st['bot_status'] === 'deactive') ? 'active' : 'deactive';
        DB::exec("UPDATE settings SET bot_status = ? WHERE id = 1", [$new]);
        $stText = $new === 'active' ? '○ Yoqilgan' : '● O\'chirilgan';
        $btnText = $new === 'active' ? '● O\'chirish' : '○ Yoqish';
        edit($cid, $cbMid, "🤖 <b>BOT HOLATI:</b> $stText\n⚡ O'zgartirildi!", ikb([[['text' => $btnText, 'callback_data' => 'bot_toggle']]]));
        exit;
    }

    // Send cancel
    if ($data === 'send_cancel' && isAdmin($cbCid)) { answer($cbQid); DB::exec("DELETE FROM send"); edit($cid, $cbMid, "✗ Bekor qilindi!"); exit; }

    // Stat refresh
    if ($data === 'stat_refresh' && isAdmin($cbCid)) { answer($cbQid, '🔄'); exit; }

    // To'lov tasdiqlash
    if (preg_match('/^pay_ok_(\d+)_(\d+)$/', $data, $m)) {
        if (!isAdmin($cbCid)) { answer($cbQid, '⚠️ Siz admin emassiz!'); exit; }
        answer($cbQid, '✅');
        DB::exec("UPDATE users SET balance = balance + ?, outing = outing + ? WHERE id = ?", [intval($m[2]), intval($m[2]), $m[1]]);
        sms($m[1], "✅ <b>To'lovingiz tasdiqlandi!</b>\n💰 +" . fmt($m[2]) . " so'm");
        edit($cid, $cbMid, "✅ #{$m[1]} — " . fmt($m[2]) . " so'm to'ldirildi. #done");
        exit;
    }
    if (preg_match('/^pay_no_(\d+)_(\d+)$/', $data, $m)) {
        if (!isAdmin($cbCid)) { answer($cbQid, '⚠️ Siz admin emassiz!'); exit; }
        answer($cbQid, '⛔');
        sms($m[1], "⛔ <b>To'lovingiz bekor qilindi!</b>");
        edit($cid, $cbMid, "⛔ #{$m[1]} — bekor. #canceled");
        exit;
    }

    // To'lov tizimi tanlash
    if (strpos($data, 'pay_') === 0 && !strpos($data, 'pay_ok') && !strpos($data, 'pay_no')) {
        $method = str_replace('pay_', '', $data);
        answer($cbQid);
        del($cid, $cbMid);
        $methodName = base64_decode($method);
        $payDir = SET_DIR . "pay/$methodName/";
        $wallet = is_dir($payDir) && file_exists($payDir . 'wallet.txt') ? trim(file_get_contents($payDir . 'wallet.txt')) : '';
        $msg = "💳 <b>$methodName orqali to'lov</b>\n━━━━━━━━━━━━━━━━━━━━";
        if ($wallet) $msg .= "\n\n💳 <b>Karta:</b>\n<code>$wallet</code>";
        $msg .= "\n\n📤 <b>Miqdorni kiriting (so'mda):</b>\n🔹 Minimal: " . fmt(MIN_DEPOSIT) . " so'm";
        sms($cbCid, $msg, getBackKb($cbCid));
        setStep($cbCid, "deposit_amt_$method");
        exit;
    }

    // Transfer
    if ($data === 'transfer') {
        $u = DB::one("SELECT balance FROM users WHERE id = ?", [$cbCid]);
        if ($u['balance'] < MIN_TRANSFER) { answer($cbQid, "Mablag' yetarli emas! Min: " . fmt(MIN_TRANSFER), true); exit; }
        answer($cbQid); del($cid, $cbMid);
        sms($cbCid, "<b>Qancha o'tkazmoqchisiz?</b>\n\nMinimal: " . fmt(MIN_TRANSFER) . " so'm", getBackKb($cbCid));
        setStep($cbCid, 'transfer_amt');
        exit;
    }

    // Referal
    if ($data === 'referal') { answer($cbQid); del($cid, $cbMid); /* handled by text */ exit; }

    // Haftalik reyting
    if ($data === 'my_contest') {
        answer($cbQid);
        $week = intval(date('W')); $year = intval(date('Y'));
        $my = DB::one("SELECT orders_count FROM weekly_contest WHERE user_id = ? AND week_num = ? AND year = ?", [$cbCid, $week, $year]);
        $myCount = $my['orders_count'] ?? 0;
        $top = getWeeklyTop(5);
        $msg = "🏆 <b>Haftalik musobaqa</b>\n━━━━━━━━━━━━━━━━━━━━\n\n📊 <b>Sizning buyurtmalaringiz:</b> $myCount ta\n\n<b>TOP-5:</b>\n";
        $medals = ['🥇', '🥈', '🥉', '4.', '5.'];
        foreach ($top as $i => $t) {
            $mark = ($t['user_id'] == $cbCid) ? ' ← siz' : '';
            $msg .= ($medals[$i] ?? ($i+1).'.') . " {$t['user_id']} — {$t['orders_count']} ta$mark\n";
        }
        $msg .= "\n💰 Mukofotlar: 🥇5000 | 🥈3000 | 🥉1000 so'm";
        edit($cid, $cbMid, $msg);
        exit;
    }

    // Kategoriya
    if (preg_match('/^cat_(\d+)$/', $data, $m)) {
        answer($cbQid);
        $catId = intval($m[1]);
        $cat = DB::one("SELECT * FROM categorys WHERE category_id = ?", [$catId]);
        $subs = DB::all("SELECT * FROM cates WHERE category_id = ?", [$catId]);
        if (empty($subs) && !isAdmin($cbCid)) { answer($cbQid, "⚠️ Xizmatlar topilmadi!", true); exit; }
        $btns = [];
        foreach ($subs as $s) $btns[] = [['text' => enc('decode', $s['name']), 'callback_data' => 'sub_' . $s['cate_id']]];
        $btns[] = [['text' => '⏪ Orqaga', 'callback_data' => 'categories_back']];
        edit($cid, $cbMid, sprintf(t('choose_subcategory', $cbCid), enc('decode', $cat['category_name'])), ikb($btns));
        exit;
    }

    // Kategoriyalarga qaytish
    if ($data === 'categories_back') {
        answer($cbQid);
        $cats = isAdmin($cbCid) ? DB::all("SELECT * FROM categorys") : DB::all("SELECT * FROM categorys WHERE category_status = 'ON'");
        $btns = [];
        foreach ($cats as $c) $btns[] = ['text' => enc('decode', $c['category_name']), 'callback_data' => 'cat_' . $c['category_id']];
        $kb = array_chunk($btns, 2);
        $kb[] = [['text' => '🔍 ID orqali qidirish', 'callback_data' => 'search_id']];
        edit($cid, $cbMid, t('choose_category', $cbCid), ikb($kb));
        exit;
    }

    // Ichki bo'lim
    if (preg_match('/^sub_(\d+)$/', $data, $m)) {
        answer($cbQid);
        $subId = intval($m[1]);
        $sub = DB::one("SELECT * FROM cates WHERE cate_id = ?", [$subId]);
        $svcs = DB::all("SELECT * FROM services WHERE category_id = ? AND service_status = 'on'", [$subId]);
        if (empty($svcs)) { answer($cbQid, "⚠️ Xizmatlar topilmadi!", true); exit; }
        $btns = [];
        foreach ($svcs as $s) {
            $sName = base64_decode($s['service_name']);
            $price = $s['service_price'];
            $disc = floatval($s['discount'] ?? 0);
            $discText = $disc > 0 ? " (-{$disc}%)" : "";
            $btns[] = [['text' => "$sName - {$price} so'm$discText", 'callback_data' => 'svc_' . $s['service_id'] . '_' . $subId]];
        }
        $btns[] = [['text' => '⏪ Orqaga', 'callback_data' => 'cat_' . $sub['category_id']]];
        edit($cid, $cbMid, sprintf(t('choose_service', $cbCid), enc('decode', $sub['name'])), ikb($btns));
        exit;
    }

    // 🛍 Xizmat tanlandi — TO'LIQ MA'LUMOT
    if (preg_match('/^svc_(\d+)_(\d+)$/', $data, $m)) {
        answer($cbQid);
        $svcId = intval($m[1]); $subId = intval($m[2]);
        $svc = DB::one("SELECT * FROM services WHERE service_id = ?", [$svcId]);
        if (!$svc) exit;
        $sName = base64_decode($svc['service_name']);
        $price = $svc['service_price'];
        $discount = floatval($svc['discount'] ?? 0);
        $orderCount = intval($svc['order_count'] ?? 0);
        $avgTime = $svc['avg_time'] ?? '0-60 daqiqa';
        $refill = ($svc['refill'] ?? 'no') === 'yes' ? 'Mavjud !' : 'Mavjud emas !';
        $cancel = ($svc['cancel_allowed'] ?? 'yes') === 'yes' ? 'Mavjud !' : 'Mavjud emas !';
        $min = $svc['service_min']; $max = $svc['service_max'];
        $discText = $discount > 0 ? "$discount" : "0";

        $text = sprintf(t('service_info', $cbCid), $sName, $svcId, fmt($price), $discText, $orderCount, $avgTime, $refill, $cancel, fmt($min), fmt($max));

        del($cid, $cbMid);
        sms($cbCid, $text, ikb([
            [['text' => '✅ Buyurtma berish', 'callback_data' => "order_start_$svcId"]],
            [['text' => '📖 Qo\'llanma', 'callback_data' => 'info_orders']],
            [['text' => '🔙 Orqaga', 'callback_data' => "sub_$subId"]],
        ]));
        exit;
    }

    // Buyurtma boshlash
    if (preg_match('/^order_start_(\d+)$/', $data, $m)) {
        answer($cbQid);
        del($cid, $cbMid);
        sms($cbCid, t('enter_link', $cbCid), getBackKb($cbCid));
        setStep($cbCid, "order_link_{$m[1]}");
        exit;
    }

    // Buyurtma tasdiqlash
    if (preg_match('/^confirm_(\d+)_(\d+)_(.+)$/', $data, $m)) {
        answer($cbQid, '⏳ Buyurtma berilmoqda...');
        $svcId = intval($m[1]); $qty = intval($m[2]); $link = base64_decode($m[3]);
        $svc = DB::one("SELECT * FROM services WHERE service_id = ?", [$svcId]);
        $pricePerK = floatval($svc['service_price']);
        $discount = floatval($svc['discount'] ?? 0);
        $discounted = calcDiscount($pricePerK, $discount);
        $total = ($discounted * $qty) / 1000;
        $u = DB::one("SELECT balance FROM users WHERE id = ?", [$cbCid]);
        if ($u['balance'] < $total) { edit($cid, $cbMid, "⛔ Mablag' yetarli emas!"); exit; }

        // API ga yuborish
        $provId = $svc['api_service'] ?? null;
        $prov = $provId ? DB::one("SELECT * FROM providers WHERE id = ?", [$provId]) : null;
        $orderId = null;
        if ($prov) {
            $r = @file_get_contents($prov['api_url'] . "?key=" . $prov['api_key'] . "&action=add&service=" . ($svc['service_api_id'] ?? $svcId) . "&link=" . urlencode($link) . "&quantity=$qty");
            $d = json_decode($r, true);
            $orderId = $d['order'] ?? null;
        }
        if (!$orderId) $orderId = time() . rand(100, 999);

        DB::exec("UPDATE users SET balance = balance - ? WHERE id = ?", [$total, $cbCid]);
        $svcName = base64_decode($svc['service_name']);
        $date = date('Y-m-d H:i:s');
        DB::exec("INSERT INTO myorder(user_id, order_id, service_name, link, quantity, retail, status, order_create) VALUES(?,?,?,?,?,?,'Pending',?)", [$cbCid, $orderId, $svcName, $link, $qty, $total, $date]);
        DB::exec("INSERT INTO orders(order_id, user_id, service_id, link, quantity, status, created_at) VALUES(?,?,?,?,?,'Pending',?)", [$orderId, $cbCid, $svcId, $link, $qty, $date]);

        // Xizmat buyurtma counti
        DB::exec("UPDATE services SET order_count = order_count + 1 WHERE service_id = ?", [$svcId]);

        // Haftalik musobaqa
        incrementWeeklyOrder($cbCid);

        edit($cid, $cbMid, sprintf(t('order_success', $cbCid), $orderId, $svcName, $link, fmt($qty), fmt($total)));

        sms(ORDERS_CHANNEL, "🛒 <b>Yangi buyurtma!</b>\n\n📋 ID: <code>$orderId</code>\n👤 <a href='tg://user?id=$cbCid'>$cbCid</a>\n📌 $svcName\n📊 " . fmt($qty) . " ta\n💰 " . fmt($total) . " so'm");
        exit;
    }

    // Buyurtma bekor
    if ($data === 'order_cancel') { answer($cbQid); edit($cid, $cbMid, "❌ <b>Bekor qilindi.</b>"); clearStep($cbCid); exit; }

    // ══════════════════════════════════════════
    // 🔎 BUYURTMALARIM — SAHIFALASH
    // ══════════════════════════════════════════
    if (preg_match('/^orders_page_(\d+)$/', $data, $m)) {
        answer($cbQid);
        showMyOrdersEdit($cbCid, $cid, $cbMid, intval($m[1]));
        exit;
    }

    // 🔄 Buyurtma takrorlash
    if (preg_match('/^repeat_(\d+)$/', $data, $m)) {
        answer($cbQid, '🔄 Takrorlanmoqda...');
        $order = DB::one("SELECT * FROM myorder WHERE id = ? AND user_id = ?", [intval($m[1]), $cbCid]);
        if (!$order) { answer($cbQid, "⛔ Topilmadi!", true); exit; }
        // Avvalgi buyurtma parametrlari bilan yangi buyurtma
        del($cid, $cbMid);
        sms($cbCid, "🔄 <b>Buyurtma takrorlash</b>\n━━━━━━━━━━━━━━━━━━━━\n\n📌 <b>Xizmat:</b> {$order['service_name']}\n🔗 <b>Havola:</b> {$order['link']}\n📊 <b>Miqdor:</b> {$order['quantity']} ta\n💰 <b>Narx:</b> " . fmt($order['retail']) . " so'm\n\n✅ Tasdiqlaysizmi?", ikb([
            [['text' => '✅ Tasdiqlash', 'callback_data' => "repeat_yes_{$m[1]}"]],
            [['text' => '❌ Bekor', 'callback_data' => 'order_cancel']],
        ]));
        exit;
    }

    // Takrorlash tasdiqlandi
    if (preg_match('/^repeat_yes_(\d+)$/', $data, $m)) {
        answer($cbQid, '⏳');
        $order = DB::one("SELECT * FROM myorder WHERE id = ? AND user_id = ?", [intval($m[1]), $cbCid]);
        if (!$order) exit;
        $u = DB::one("SELECT balance FROM users WHERE id = ?", [$cbCid]);
        $total = floatval($order['retail']);
        if ($u['balance'] < $total) { edit($cid, $cbMid, "⛔ Mablag' yetarli emas!"); exit; }

        $orderId = time() . rand(100, 999);
        DB::exec("UPDATE users SET balance = balance - ? WHERE id = ?", [$total, $cbCid]);
        $date = date('Y-m-d H:i:s');
        DB::exec("INSERT INTO myorder(user_id, order_id, service_name, link, quantity, retail, status, order_create) VALUES(?,?,?,?,?,?,'Pending',?)", [$cbCid, $orderId, $order['service_name'], $order['link'], $order['quantity'], $total, $date]);
        DB::exec("INSERT INTO orders(order_id, user_id, service_id, link, quantity, status, created_at) VALUES(?,?,?,?,?,'Pending',?)", [$orderId, $cbCid, 0, $order['link'], $order['quantity'], $date]);
        incrementWeeklyOrder($cbCid);
        edit($cid, $cbMid, "✅ <b>Buyurtma takrorlandi!</b>\n📋 ID: <code>$orderId</code>\n📌 {$order['service_name']}\n💰 " . fmt($total) . " so'm");
        exit;
    }

    // 🚫 Buyurtma bekor qilish (foydalanuvchi)
    if (preg_match('/^cancel_order_(\d+)$/', $data, $m)) {
        $order = DB::one("SELECT * FROM myorder WHERE id = ? AND user_id = ?", [intval($m[1]), $cbCid]);
        if (!$order || !in_array($order['status'], ['Pending'])) { answer($cbQid, "⛔ Bekor qilib bo'lmaydi!", true); exit; }
        answer($cbQid, '🚫 Bekor qilinmoqda...');
        DB::exec("UPDATE myorder SET status = 'Canceled' WHERE id = ?", [intval($m[1])]);
        DB::exec("UPDATE orders SET status = 'Canceled' WHERE order_id = ?", [$order['order_id']]);
        DB::exec("UPDATE users SET balance = balance + ? WHERE id = ?", [floatval($order['retail']), $cbCid]);
        edit($cid, $cbMid, "🚫 <b>Buyurtma bekor qilindi!</b>\n📋 ID: <code>{$order['order_id']}</code>\n💰 " . fmt($order['retail']) . " so'm qaytarildi.");
        exit;
    }

    // 🔍 Buyurtma ma'lumoti
    if (preg_match('/^order_detail_(\d+)$/', $data, $m)) {
        answer($cbQid);
        $order = DB::one("SELECT * FROM myorder WHERE id = ? AND user_id = ?", [intval($m[1]), $cbCid]);
        if (!$order) exit;
        $emoji = statusEmoji($order['status']);
        $stText = statusText($order['status'], getUserLang($cbCid));
        $msg = "🔍 <b>Buyurtma ma'lumoti</b>\n━━━━━━━━━━━━━━━━━━━━\n\n"
            . "📋 <b>ID:</b> <code>{$order['order_id']}</code>\n"
            . "📌 <b>Xizmat:</b> {$order['service_name']}\n"
            . "🔗 <b>Havola:</b> {$order['link']}\n"
            . "📊 <b>Miqdor:</b> {$order['quantity']} ta\n"
            . "💰 <b>Summa:</b> " . fmt($order['retail']) . " so'm\n"
            . "$emoji <b>Status:</b> $stText\n"
            . "📅 <b>Sana:</b> {$order['order_create']}";
        $btns = [];
        if ($order['status'] === 'Pending') $btns[] = [['text' => '🚫 Bekor qilish', 'callback_data' => "cancel_order_{$m[1]}"]];
        $btns[] = [['text' => '♻️ Qayta buyurtma', 'callback_data' => "repeat_{$m[1]}"]];
        $btns[] = [['text' => '⏪ Orqaga', 'callback_data' => 'orders_page_0']];
        edit($cid, $cbMid, $msg, ikb($btns));
        exit;
    }

    // Murojaat
    if ($data === 'ticket_send') {
        answer($cbQid); del($cid, $cbMid);
        sms($cbCid, "📨 <b>Murojaatingizni yozing:</b>\n\n✍️ Matn, rasm, video — istalganini yuboring.", getBackKb($cbCid));
        setStep($cbCid, 'ticket_msg');
        exit;
    }

    // Admin javob
    if (preg_match('/^ticket_reply_(\d+)$/', $data, $m) && isAdmin($cbCid)) {
        answer($cbQid);
        sms($cbCid, "✍️ Javobingizni yuboring:", getBackKb($cbCid));
        setStep($cbCid, "ticket_ans_{$m[1]}");
        exit;
    }

    // FAQ
    if ($data === 'faq_main') {
        answer($cbQid); del($cid, $cbMid);
        sms($cbCid, "❓ <b>Savollar:</b>", ikb([
            [['text' => '1️⃣ Qancha vaqtda bajariladi?', 'callback_data' => 'faq_1']],
            [['text' => '2️⃣ Nega bekor qilinadi?', 'callback_data' => 'faq_2']],
            [['text' => '3️⃣ Bepul foydalansa bo\'ladimi?', 'callback_data' => 'faq_3']],
            [['text' => '4️⃣ Pulim qaytariladimi?', 'callback_data' => 'faq_4']],
            [['text' => '⏪ Orqaga', 'callback_data' => 'support_back']],
        ]));
        exit;
    }

    if (preg_match('/^faq_(\d)$/', $data, $m)) {
        answer($cbQid);
        $faqs = [
            '1' => "⏱ <b>Bajarilish vaqti:</b>\n\n👥 Follower: 1-24 soat\n❤️ Like: 10 daqiqa - 6 soat\n👁 View: 5 daqiqa - 2 soat",
            '2' => "🔗 Noto'g'ri havola\n🔒 Yopiq profil\n📈 Server yuklanishi\n\n💰 Bekor buyurtma puli qaytariladi!",
            '3' => "Ha! Referal tizimi orqali bepul foydalaning.\n🚀 \"Mablag' yig'ish\" bo'limiga o'ting.",
            '4' => "✓ Bekor/bajarilmagan → avtomatik qaytadi\n🚫 To'liq bajarilgan → qaytmaydi",
        ];
        edit($cid, $cbMid, "📌 " . ($faqs[$m[1]] ?? ''), ikb([[['text' => '⏪ Orqaga', 'callback_data' => 'faq_main']]]));
        exit;
    }

    if ($data === 'support_back') { answer($cbQid); del($cid, $cbMid);
        sms($cbCid, "<b>📞 Qo'llab-Quvvatlash</b>", ikb([
            [['text' => '❓ FAQ', 'callback_data' => 'faq_main']],
            [['text' => '📨 Murojaat', 'callback_data' => 'ticket_send']],
            [['text' => '📞 Admin', 'url' => 'https://t.me/' . ADMIN_USERNAME]],
        ])); exit;
    }

    // Info callbacks
    if (strpos($data, 'info_') === 0) {
        answer($cbQid); del($cid, $cbMid);
        $infos = [
            'orders' => "🗂 <b>Buyurtma berish:</b>\n\n1️⃣ Tarmoqni tanlang\n2️⃣ Xizmatni tanlang\n3️⃣ Havolani yuboring\n4️⃣ Miqdor kiriting\n5️⃣ Tasdiqlang\n\n⚠️ Profil ochiq bo'lishi shart!",
            'numbers' => "📞 <b>Virtual nomerlar:</b>\n\nAdmin orqali sotib olasiz.\n📞 @" . ADMIN_USERNAME,
            'referal' => "🤝 <b>Referal:</b>\n\nDo'stlaringizni taklif qiling va bonus oling!",
            'deposit' => "💵 <b>To'ldirish:</b>\n\n1️⃣ Tizimni tanlang\n2️⃣ Pul o'tkazing\n3️⃣ Screenshot yuboring",
            'rules' => "📜 <b>Qoidalar:</b>\n\n⛔ Soxta chek → Ban\n⛔ Haqorat → Ban\n⚠️ Pul qaytarilmaydi\n⚠️ Havola to'g'ri bo'lsin",
        ];
        $section = str_replace('info_', '', $data);
        sms($cbCid, $infos[$section] ?? "Ma'lumot yo'q", ikb([[['text' => '⏪ Orqaga', 'callback_data' => 'info_back']]]));
        exit;
    }
    if ($data === 'info_back') { answer($cbQid); del($cid, $cbMid);
        sms($cbCid, "<b>💎 Vertual xizmatlar</b>", ikb([
            [['text' => '🗂 Buyurtma', 'callback_data' => 'info_orders'], ['text' => '📞 Nomer', 'callback_data' => 'info_numbers']],
            [['text' => '🤝 Referal', 'callback_data' => 'info_referal'], ['text' => '💵 To\'ldirish', 'callback_data' => 'info_deposit']],
            [['text' => '📜 Qoidalar', 'callback_data' => 'info_rules']],
        ])); exit;
    }
}

// ══════════════════════════════════════════
// 🔎 BUYURTMALARIM FUNKSIYA (SAHIFALASH)
// ══════════════════════════════════════════

function showMyOrders(string $uid, int $page): void
{
    $perPage = 5;
    $total = DB::count("SELECT COUNT(*) FROM myorder WHERE user_id = ?", [$uid]);
    if ($total === 0) { sms($uid, t('no_orders', $uid)); return; }

    $totalPages = ceil($total / $perPage);
    $offset = $page * $perPage;
    $orders = DB::all("SELECT * FROM myorder WHERE user_id = ? ORDER BY id DESC LIMIT ?, ?", [$uid, $offset, $perPage]);

    $msg = t('my_orders_title', $uid) . "\n\n";
    foreach ($orders as $o) {
        $emoji = statusEmoji($o['status']);
        $stText = statusText($o['status'], getUserLang($uid));
        $msg .= "{$o['order_id']} - $emoji $stText\n";
    }

    $pageText = ($page + 1) . "/$totalPages";
    $btns = [];
    $navRow = [];
    if ($page > 0) $navRow[] = ['text' => '⬅️', 'callback_data' => 'orders_page_' . ($page - 1)];
    $navRow[] = ['text' => $pageText, 'callback_data' => 'noop'];
    if ($page < $totalPages - 1) $navRow[] = ['text' => '➡️', 'callback_data' => 'orders_page_' . ($page + 1)];
    $btns[] = $navRow;

    // Tugmalar: takrorlash, bekor qilish, ma'lumot
    if (!empty($orders)) {
        $first = $orders[0];
        $btns[] = [['text' => '♻️ Qayta tiklash', 'callback_data' => 'repeat_' . $first['id']], ['text' => '🚫 Bekor qilish', 'callback_data' => 'cancel_order_' . $first['id']]];
        $btns[] = [['text' => '🔍 Buyurtma ma\'lumoti', 'callback_data' => 'order_detail_' . $first['id']]];
    }

    sms($uid, $msg, ikb($btns));
}

function showMyOrdersEdit(string $uid, string $chatId, int $msgId, int $page): void
{
    $perPage = 5;
    $total = DB::count("SELECT COUNT(*) FROM myorder WHERE user_id = ?", [$uid]);
    if ($total === 0) { edit($chatId, $msgId, t('no_orders', $uid)); return; }

    $totalPages = ceil($total / $perPage);
    if ($page >= $totalPages) $page = $totalPages - 1;
    $offset = $page * $perPage;
    $orders = DB::all("SELECT * FROM myorder WHERE user_id = ? ORDER BY id DESC LIMIT ?, ?", [$uid, $offset, $perPage]);

    $msg = t('my_orders_title', $uid) . "\n\n";
    foreach ($orders as $o) {
        $emoji = statusEmoji($o['status']);
        $stText = statusText($o['status'], getUserLang($uid));
        $msg .= "{$o['order_id']} - $emoji $stText\n";
    }

    $pageText = ($page + 1) . "/$totalPages";
    $btns = [];
    $navRow = [];
    if ($page > 0) $navRow[] = ['text' => '⬅️', 'callback_data' => 'orders_page_' . ($page - 1)];
    $navRow[] = ['text' => $pageText, 'callback_data' => 'noop'];
    if ($page < $totalPages - 1) $navRow[] = ['text' => '➡️', 'callback_data' => 'orders_page_' . ($page + 1)];
    $btns[] = $navRow;

    if (!empty($orders)) {
        $first = $orders[0];
        $btns[] = [['text' => '♻️ Qayta tiklash', 'callback_data' => 'repeat_' . $first['id']], ['text' => '🚫 Bekor qilish', 'callback_data' => 'cancel_order_' . $first['id']]];
        $btns[] = [['text' => '🔍 Buyurtma ma\'lumoti', 'callback_data' => 'order_detail_' . $first['id']]];
    }

    edit($chatId, $msgId, $msg, ikb($btns));
}

ob_end_clean();
