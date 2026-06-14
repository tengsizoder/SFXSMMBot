<?php
/**
 * ═══════════════════════════════════════════════════
 * SFXSMM Bot — Yordamchi Funksiyalar
 * ═══════════════════════════════════════════════════
 * 
 * Bu faylda:
 * - Konfiguratsiya
 * - Database class (Prepared Statements)
 * - Telegram API funksiyalar
 * - Ko'p tilli tizim
 * - Yordamchi funksiyalar
 */

// ══════════════════════════════════════════
// KONFIGURATSIYA
// ══════════════════════════════════════════

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Tashkent');

define('BOT_TOKEN', '7576059347:AAFDDOX4oXq2fIo7hiymerxKt6Rjiq8Ivy8');
define('BOT_API', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// Adminlar ro'yxati (bir nechta admin qo'shish mumkin)
define('ADMINS', [
    '6929970231',   // Asosiy admin
    // '123456789', // Qo'shimcha admin qo'shish uchun shu yerga ID qo'shing
]);
define('MAIN_ADMIN', '6929970231');
define('ADMIN_USERNAME', 'SFXSMMHelp');
define('ORDERS_CHANNEL', '@SFXSMMBaza');
define('PAYMENT_CHANNEL', '@SFXSMMTolov');

// Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sfxsmm_bot');

// Sozlamalar
define('MIN_DEPOSIT', 1000);
define('MIN_TRANSFER', 1000);
define('CRON_LIMIT', 150);

// Fayl yo'llari
define('BOT_DIR', __DIR__ . '/../bot/');
define('USER_DIR', BOT_DIR . 'user/');
define('SET_DIR', BOT_DIR . 'set/');

// Papkalar yaratish
foreach ([USER_DIR, SET_DIR, USER_DIR . 'mt/'] as $d) {
    if (!is_dir($d)) mkdir($d, 0777, true);
}

// ══════════════════════════════════════════
// 🌐 KO'P TILLI TIZIM
// ══════════════════════════════════════════

function getLangs(): array
{
    return [
        'uz' => [
            'name' => "🇺🇿 O'zbekcha",
            // Asosiy menyu
            'menu_orders' => "🗂 Xizmatlarga buyurtma berish",
            'menu_my_orders' => "🔎 Buyurtmalarim",
            'menu_earn' => "🚀 Mablag' yig'ish",
            'menu_deposit' => "💵 Hisob to'ldirish",
            'menu_account' => "💳 Mening hisobim",
            'menu_virtual' => "💎 Vertual xizmatlar",
            'menu_support' => "☎️ Qo'llab-Quvvatlash",
            'menu_admin' => "🗄️ Boshqaruv",
            'menu_back' => "⏩ Orqaga",
            'menu_lang' => "🌐 Tilni o'zgartirish",
            // Xabarlar
            'welcome' => "🖥️ <b>@%s — SMM xizmatlar botiga xush kelibsiz!</b>\n\n✅ Eng arzon va tezkor SMM xizmatlari!\n\n👇 Quyidagi menyudan foydalaning:",
            'main_menu' => "🖥️ <b>Asosiy menyudasiz</b>",
            'choose_category' => "<b>✅️ Bizning xizmatlar eng arzon va tezkor!\n\n👇 Quyidagi ijtimoiy tarmoqlardan birini tanlang:</b>",
            'choose_subcategory' => "<b>«%s» — bo'limlaridan birini tanlang.</b>",
            'choose_service' => "<b>«%s» — xizmatlaridan birini tanlang.</b>\n\n<b><i>💴 Narxlar 1000 tasi uchun berilgan:</i></b>",
            'service_info' => "🛍 <b>%s</b>\n\n🔑 ID: %d\n💰 Narx (x1000): %s so'm\n📉 Chegirma: %s%%\n\n<blockquote>🧮 Ushbu xizmat %d marotaba buyurtma berilgan!\n⏳ Bajarish vaqti: %s\n♻️ Qayta tiklash: %s\n🚫 Bekor qilish: %s</blockquote>\n\n🔽 Minimal buyurtma miqdori: %s ta\n🔼 Maksimal buyurtma miqdori: %s ta",
            'enter_link' => "🔗 <b>Havolani yuboring:</b>\n\n⚠️ Havola to'g'ri va profil ochiq (public) bo'lishi shart!",
            'enter_quantity' => "📊 <b>Miqdorni kiriting:</b>\n\n🔹 Minimum: %s ta\n🔹 Maksimum: %s ta",
            'confirm_order' => "📋 <b>Buyurtmani tasdiqlang:</b>\n━━━━━━━━━━━━━━━━━━━━\n\n📌 <b>Xizmat:</b> %s\n🔗 <b>Havola:</b> <code>%s</code>\n📊 <b>Miqdor:</b> %s ta\n💰 <b>Narx:</b> %s so'm\n📉 <b>Chegirma:</b> %s%%\n\n━━━━━━━━━━━━━━━━━━━━\n✅ Tasdiqlaysizmi?",
            'order_success' => "✅ <b>Buyurtma muvaffaqiyatli berildi!</b>\n━━━━━━━━━━━━━━━━━━━━\n\n📋 <b>Buyurtma raqami:</b> <code>%s</code>\n📌 <b>Xizmat:</b> %s\n🔗 <b>Havola:</b> %s\n📊 <b>Miqdor:</b> %s ta\n💰 <b>Summa:</b> %s so'm\n📌 <b>Status:</b> Kutilmoqda ⏳",
            'no_balance' => "⛔ <b>Hisobingizda mablag' yetarli emas!</b>\n\n💰 Kerakli: %s so'm\n💳 Hisobingiz: %s so'm\n📉 Yetishmaydi: %s so'm",
            'my_orders_title' => "🆔 <b>Buyurtma IDsi - 🔖 Holati</b>",
            'no_orders' => "📦 <b>Sizda hali buyurtma yo'q.</b>",
            'order_page' => "%s/%s",
            'repeat_order' => "♻️ Qayta tiklash",
            'cancel_order' => "🚫 Bekor qilish",
            'order_info' => "🔍 Buyurtma ma'lumoti",
            'subscribe_required' => "⛔ <b>Botdan foydalanish uchun, quyidagi kanallarga obuna bo'ling:</b>",
            'check_sub' => "✅ Tekshirish",
            'bot_maintenance' => "⚠️ <b>Texnik ishlar olib borilmoqda</b>\n\n⏳ Iltimos, biroz kutib qayta urinib ko'ring.\n\n📞 Murojaat: @" . ADMIN_USERNAME,
            'invalid_link' => "⛔ <b>Noto'g'ri havola!</b>\n\nHavola http:// yoki https:// bilan boshlanishi kerak.",
            'only_numbers' => "⛔ <b>Faqat raqam kiriting!</b>",
            'wrong_quantity' => "⛔ <b>Noto'g'ri miqdor!</b>\n\n🔹 Min: %s\n🔹 Max: %s",
        ],
        'ru' => [
            'name' => "🇷🇺 Русский",
            'menu_orders' => "🗂 Заказать услуги",
            'menu_my_orders' => "🔎 Мои заказы",
            'menu_earn' => "🚀 Заработать",
            'menu_deposit' => "💵 Пополнить баланс",
            'menu_account' => "💳 Мой аккаунт",
            'menu_virtual' => "💎 Виртуальные услуги",
            'menu_support' => "☎️ Поддержка",
            'menu_admin' => "🗄️ Управление",
            'menu_back' => "⏩ Назад",
            'menu_lang' => "🌐 Сменить язык",
            'welcome' => "🖥️ <b>@%s — Добро пожаловать в SMM бот!</b>\n\n✅ Самые дешёвые и быстрые SMM услуги!\n\n👇 Выберите из меню:",
            'main_menu' => "🖥️ <b>Главное меню</b>",
            'choose_category' => "<b>✅️ Наши услуги самые дешёвые и быстрые!\n\n👇 Выберите социальную сеть:</b>",
            'choose_subcategory' => "<b>«%s» — выберите раздел.</b>",
            'choose_service' => "<b>«%s» — выберите услугу.</b>\n\n<b><i>💴 Цены указаны за 1000 шт:</i></b>",
            'service_info' => "🛍 <b>%s</b>\n\n🔑 ID: %d\n💰 Цена (x1000): %s сум\n📉 Скидка: %s%%\n\n<blockquote>🧮 Эта услуга заказана %d раз!\n⏳ Время выполнения: %s\n♻️ Восстановление: %s\n🚫 Отмена: %s</blockquote>\n\n🔽 Минимум: %s шт\n🔼 Максимум: %s шт",
            'enter_link' => "🔗 <b>Отправьте ссылку:</b>\n\n⚠️ Ссылка должна быть правильной, профиль — открытым!",
            'enter_quantity' => "📊 <b>Введите количество:</b>\n\n🔹 Минимум: %s\n🔹 Максимум: %s",
            'confirm_order' => "📋 <b>Подтвердите заказ:</b>\n━━━━━━━━━━━━━━━━━━━━\n\n📌 <b>Услуга:</b> %s\n🔗 <b>Ссылка:</b> <code>%s</code>\n📊 <b>Кол-во:</b> %s шт\n💰 <b>Цена:</b> %s сум\n📉 <b>Скидка:</b> %s%%\n\n━━━━━━━━━━━━━━━━━━━━\n✅ Подтверждаете?",
            'order_success' => "✅ <b>Заказ успешно создан!</b>\n━━━━━━━━━━━━━━━━━━━━\n\n📋 <b>Номер заказа:</b> <code>%s</code>\n📌 <b>Услуга:</b> %s\n🔗 <b>Ссылка:</b> %s\n📊 <b>Кол-во:</b> %s шт\n💰 <b>Сумма:</b> %s сум\n📌 <b>Статус:</b> Ожидание ⏳",
            'no_balance' => "⛔ <b>Недостаточно средств!</b>\n\n💰 Нужно: %s сум\n💳 Баланс: %s сум\n📉 Не хватает: %s сум",
            'my_orders_title' => "🆔 <b>ID заказа - 🔖 Статус</b>",
            'no_orders' => "📦 <b>У вас нет заказов.</b>",
            'order_page' => "%s/%s",
            'repeat_order' => "♻️ Повторить",
            'cancel_order' => "🚫 Отменить",
            'order_info' => "🔍 Инфо заказа",
            'subscribe_required' => "⛔ <b>Для использования бота подпишитесь на каналы:</b>",
            'check_sub' => "✅ Проверить",
            'bot_maintenance' => "⚠️ <b>Ведутся технические работы</b>\n\n⏳ Попробуйте позже.\n\n📞 Связь: @" . ADMIN_USERNAME,
            'invalid_link' => "⛔ <b>Неверная ссылка!</b>\n\nСсылка должна начинаться с http:// или https://",
            'only_numbers' => "⛔ <b>Только цифры!</b>",
            'wrong_quantity' => "⛔ <b>Неверное количество!</b>\n\n🔹 Мин: %s\n🔹 Макс: %s",
        ],
    ];
}

function t(string $key, string $userId = ''): string
{
    $lang = getUserLang($userId);
    $langs = getLangs();
    return $langs[$lang][$key] ?? $langs['uz'][$key] ?? $key;
}

function getUserLang(string $userId): string
{
    if (empty($userId)) return 'uz';
    $file = USER_DIR . "$userId.lang";
    return file_exists($file) ? trim(file_get_contents($file)) : 'uz';
}

function setUserLang(string $userId, string $lang): void
{
    file_put_contents(USER_DIR . "$userId.lang", $lang);
}

// ══════════════════════════════════════════
// DATABASE CLASS
// ══════════════════════════════════════════

class DB
{
    private static ?mysqli $c = null;

    public static function conn(): mysqli
    {
        if (!self::$c) {
            self::$c = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if (self::$c->connect_error) die('DB error');
            self::$c->set_charset('utf8mb4');
        }
        return self::$c;
    }

    public static function one(string $q, array $p = []): ?array
    {
        $s = self::prep($q, $p);
        if (!$s) return null;
        $r = $s->get_result()->fetch_assoc();
        $s->close();
        return $r;
    }

    public static function all(string $q, array $p = []): array
    {
        $s = self::prep($q, $p);
        if (!$s) return [];
        $res = $s->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $s->close();
        return $rows;
    }

    public static function exec(string $q, array $p = []): bool
    {
        $s = self::prep($q, $p);
        if (!$s) return false;
        $ok = $s->affected_rows >= 0;
        $s->close();
        return $ok;
    }

    public static function count(string $q, array $p = []): int
    {
        $r = self::one($q, $p);
        return $r ? intval(reset($r)) : 0;
    }

    public static function sum(string $q, array $p = []): float
    {
        $r = self::one($q, $p);
        return $r ? floatval(reset($r)) : 0;
    }

    public static function lastId(): int { return self::conn()->insert_id; }

    private static function prep(string $q, array $p): ?mysqli_stmt
    {
        $c = self::conn();
        $s = $c->prepare($q);
        if (!$s) { error_log("SQL: {$c->error} | $q"); return null; }
        if ($p) {
            $types = '';
            foreach ($p as $v) {
                if (is_int($v)) $types .= 'i';
                elseif (is_float($v)) $types .= 'd';
                else $types .= 's';
            }
            $s->bind_param($types, ...$p);
        }
        $s->execute();
        return $s;
    }

    public static function init(): void
    {
        $c = self::conn();
        $c->query("CREATE TABLE IF NOT EXISTS ratings(id INT AUTO_INCREMENT PRIMARY KEY, user_id VARCHAR(50) UNIQUE, rating TINYINT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $c->query("CREATE TABLE IF NOT EXISTS send(id INT AUTO_INCREMENT PRIMARY KEY, admin_id VARCHAR(50), message_id VARCHAR(50), start_id INT DEFAULT 0, stop_id INT DEFAULT 0)");
        $c->query("CREATE TABLE IF NOT EXISTS soxta(id INT AUTO_INCREMENT PRIMARY KEY, user_id VARCHAR(50), come VARCHAR(20), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $c->query("CREATE TABLE IF NOT EXISTS weekly_contest(id INT AUTO_INCREMENT PRIMARY KEY, user_id VARCHAR(50), orders_count INT DEFAULT 0, week_num INT, year INT)");
        // Chegirma ustunini qo'shish (agar yo'q bo'lsa)
        @$c->query("ALTER TABLE services ADD COLUMN discount FLOAT DEFAULT 0");
        @$c->query("ALTER TABLE services ADD COLUMN avg_time VARCHAR(50) DEFAULT '0-60 daqiqa'");
        @$c->query("ALTER TABLE services ADD COLUMN refill VARCHAR(10) DEFAULT 'no'");
        @$c->query("ALTER TABLE services ADD COLUMN cancel_allowed VARCHAR(10) DEFAULT 'yes'");
        @$c->query("ALTER TABLE services ADD COLUMN order_count INT DEFAULT 0");
        @$c->query("ALTER TABLE users ADD COLUMN api_status VARCHAR(20) DEFAULT 'active'");
        @$c->query("ALTER TABLE users ADD COLUMN registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        @$c->query("ALTER TABLE settings ADD COLUMN bot_status VARCHAR(20) DEFAULT 'active'");
    }
}

// ══════════════════════════════════════════
// TELEGRAM API
// ══════════════════════════════════════════

function bot(string $method, array $p = []): ?object
{
    $ch = curl_init(BOT_API . $method);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($p),
        CURLOPT_TIMEOUT => 30,
    ]);
    $r = curl_exec($ch);
    curl_close($ch);
    return json_decode($r);
}

function sms(string $id, string $tx, $kb = null): ?object
{
    $p = ['chat_id' => $id, 'text' => $tx, 'parse_mode' => 'HTML'];
    if ($kb) $p['reply_markup'] = is_string($kb) ? $kb : json_encode($kb);
    return bot('sendMessage', $p);
}

function edit(string $id, int $mid, string $tx, $kb = null): ?object
{
    $p = ['chat_id' => $id, 'message_id' => $mid, 'text' => $tx, 'parse_mode' => 'HTML'];
    if ($kb) $p['reply_markup'] = is_string($kb) ? $kb : json_encode($kb);
    return bot('editMessageText', $p);
}

function del(string $id, int $mid): ?object
{
    return bot('deleteMessage', ['chat_id' => $id, 'message_id' => $mid]);
}

function answer(string $qid, string $tx = '', bool $alert = false): ?object
{
    return bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => $tx, 'show_alert' => $alert]);
}

function sendPhoto(string $id, string $photo, string $cap = '', $kb = null): ?object
{
    $p = ['chat_id' => $id, 'photo' => $photo, 'caption' => $cap, 'parse_mode' => 'HTML'];
    if ($kb) $p['reply_markup'] = is_string($kb) ? $kb : json_encode($kb);
    return bot('sendPhoto', $p);
}

// ══════════════════════════════════════════
// KEYBOARD HELPERS
// ══════════════════════════════════════════

function ikb(array $buttons): string { return json_encode(['inline_keyboard' => $buttons]); }
function rkb(array $buttons): string { return json_encode(['resize_keyboard' => true, 'keyboard' => $buttons]); }

// ══════════════════════════════════════════
// UMUMIY YORDAMCHI FUNKSIYALAR
// ══════════════════════════════════════════

function enc(string $a, string $d): string { return $a === 'encode' ? base64_encode($d) : base64_decode($d); }
function fmt($n): string { return number_format(floatval($n), 0, '', ' '); }
function genKey(int $len = 7): string { $c = 'ABCDEFGHIJKLMNOPRSTUVXYZ1234567890'; $k = ''; for ($i = 0; $i < $len; $i++) $k .= $c[random_int(0, strlen($c) - 1)]; return $k; }
function genApiKey(): string { return md5(uniqid(random_int(0, 999999), true)); }
function now(): string { return date('d/m/Y | H:i'); }

function isAdmin(string $id): bool { return in_array($id, ADMINS); }

function setStep(string $id, string $step): void { file_put_contents(USER_DIR . "$id.step", $step); }
function getStep(string $id): string { $f = USER_DIR . "$id.step"; return file_exists($f) ? file_get_contents($f) : ''; }
function clearStep(string $id): void {
    foreach (['step', 'ur', 'params', 'qu', 'si'] as $ext)
        @unlink(USER_DIR . "$id.$ext");
}

// ══════════════════════════════════════════
// MENYU KEYBOARD
// ══════════════════════════════════════════

function getMenu(string $uid): string
{
    $l = getUserLang($uid);
    $langs = getLangs();
    $la = $langs[$l] ?? $langs['uz'];

    $kb = [
        [['text' => $la['menu_orders']]],
        [['text' => $la['menu_my_orders']], ['text' => $la['menu_earn']]],
        [['text' => $la['menu_deposit']], ['text' => $la['menu_account']]],
        [['text' => $la['menu_virtual']], ['text' => $la['menu_support']]],
        [['text' => $la['menu_lang']]],
    ];
    if (isAdmin($uid)) $kb[] = [['text' => $la['menu_admin']]];
    return rkb($kb);
}

function getBackKb(string $uid): string
{
    return rkb([[['text' => t('menu_back', $uid)]]]);
}

// ══════════════════════════════════════════
// KANAL OBUNA TEKSHIRISH
// ══════════════════════════════════════════

function checkSub(string $uid): bool
{
    $f = SET_DIR . 'channel';
    if (!file_exists($f)) return true;
    $content = trim(file_get_contents($f));
    if (empty($content)) return true;

    $channels = explode("\n", $content);
    $unsub = false;
    $btns = [];

    foreach ($channels as $ch) {
        $ch = trim($ch);
        if (empty($ch)) continue;
        $chName = str_replace('@', '', $ch);
        $member = bot('getChatMember', ['chat_id' => $ch, 'user_id' => $uid]);
        $st = $member->result->status ?? '';
        if (!in_array($st, ['creator', 'administrator', 'member'])) {
            $info = bot('getChat', ['chat_id' => $ch]);
            $title = $info->result->title ?? $chName;
            $btns[] = [['text' => $title, 'url' => "https://t.me/$chName"]];
            $unsub = true;
        }
    }

    if ($unsub) {
        $btns[] = [['text' => t('check_sub', $uid), 'callback_data' => 'check_sub']];
        sms($uid, t('subscribe_required', $uid), ikb($btns));
        return false;
    }
    return true;
}

// ══════════════════════════════════════════
// FOYDALANUVCHI RO'YXATDAN O'TKAZISH
// ══════════════════════════════════════════

function registerUser(string $uid): void
{
    $u = DB::one("SELECT id FROM users WHERE id = ?", [$uid]);
    if (!$u) {
        $key = genApiKey();
        $ref = genKey();
        DB::exec("INSERT INTO users (id, status, balance, outing, api_key, referal, registration_date) VALUES (?, 'active', 0, 0, ?, ?, NOW())", [$uid, $key, $ref]);
    }
}

// ══════════════════════════════════════════
// CHEGIRMA HISOBLASH
// ══════════════════════════════════════════

function calcDiscount(float $price, float $discount): float
{
    if ($discount <= 0) return $price;
    return $price - ($price * $discount / 100);
}

// ══════════════════════════════════════════
// HAFTALIK MUSOBAQA
// ══════════════════════════════════════════

function getWeeklyTop(int $limit = 10): array
{
    $week = intval(date('W'));
    $year = intval(date('Y'));
    return DB::all(
        "SELECT user_id, orders_count FROM weekly_contest WHERE week_num = ? AND year = ? ORDER BY orders_count DESC LIMIT ?",
        [$week, $year, $limit]
    );
}

function incrementWeeklyOrder(string $userId): void
{
    $week = intval(date('W'));
    $year = intval(date('Y'));
    $existing = DB::one("SELECT id FROM weekly_contest WHERE user_id = ? AND week_num = ? AND year = ?", [$userId, $week, $year]);
    if ($existing) {
        DB::exec("UPDATE weekly_contest SET orders_count = orders_count + 1 WHERE user_id = ? AND week_num = ? AND year = ?", [$userId, $week, $year]);
    } else {
        DB::exec("INSERT INTO weekly_contest (user_id, orders_count, week_num, year) VALUES (?, 1, ?, ?)", [$userId, $week, $year]);
    }
}

// ══════════════════════════════════════════
// BUYURTMA STATUS EMOJI
// ══════════════════════════════════════════

function statusEmoji(string $status): string
{
    return match ($status) {
        'Completed'   => '✅',
        'Pending'     => '⏳',
        'In progress' => '🔄',
        'Processing'  => '⚙️',
        'Partial'     => '⚠️',
        'Canceled'    => '❌',
        'Failed'      => '💥',
        default       => '📦',
    };
}

function statusText(string $status, string $lang = 'uz'): string
{
    if ($lang === 'ru') {
        return match ($status) {
            'Completed'   => 'Выполнен',
            'Pending'     => 'Ожидание',
            'In progress' => 'В процессе',
            'Processing'  => 'Обработка',
            'Partial'     => 'Частично',
            'Canceled'    => 'Отменён',
            'Failed'      => 'Ошибка',
            default       => $status,
        };
    }
    return match ($status) {
        'Completed'   => 'Bajarildi',
        'Pending'     => 'Kutilmoqda',
        'In progress' => 'Jarayonda',
        'Processing'  => 'Qayta ishlanmoqda',
        'Partial'     => 'Qisman bajarildi',
        'Canceled'    => 'Bekor qilingan',
        'Failed'      => 'Muvaffaqiyatsiz',
        default       => $status,
    };
}
