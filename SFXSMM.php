<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();
define("API_KEY", "7576059347:AAFDDOX4oXq2fIo7hiymerxKt6Rjiq8Ivy8"); // Bot tokeningiz

$orderschannel = "@SFXSMMBaza";
$paychannel = "@SFXSMMTolov";
$admin = "6929970231";
$bot = bot('getMe')->result->username;
$aduser = "SFXSMMHelp"; //admin user @ bu siz

session_start();
date_default_timezone_set("Asia/Tashkent");
$time = date('H:i');
$timeq = date('H:i:s');

function enc($var, $exception)
{
    if ($var == "encode") {
        return base64_encode($exception);
    } elseif ($var == "decode") {
        return base64_decode($exception);
    }
}

function keyboard($a = [])
{
    $d = json_encode([
        'inline_keyboard' => $a
    ]);
    return $d;
}

function api_query($s)
{
    $qas = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false));
    $content = file_get_contents($s, false, stream_context_create($qas));
    return $content ? $content : json_encode(['balance' => " ?"]);
}

function arr($p)
{
    global $connect;
    $s = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `providers` WHERE id = $p"));
    $data = json_decode(file_get_contents($s['api_url'] . "?key=" . $s['api_key'] . "&action=services"), 1);
    $values = [];
    $new_arr = [];
    $co = 0;
    foreach ($data as $value) {

        if (!in_array($value['category'], $new_arr)) {
            $new_arr[] = $value['category'];
            $co++;
            $values[] = ['id' => $co, 'name' => $value['category']];
        } else {
            continue;
        }
    }
    $val = ['count' => $co, 'results' => $values];
    return $values ? json_encode($val) : json_encode(["error" => 1]);
}

require("../app/controller/sql_connect.php");

// bot() funksiya — sizniki
function bot($method, $datas = [])
{
    $url = "https://api.telegram.org/bot" . API_KEY . "/$method";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => count($datas),
        CURLOPT_POSTFIELDS => http_build_query($datas)
    ]);
    $mh = curl_multi_init();
    curl_multi_add_handle($mh, $ch);
    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while ($running);
    $res = curl_multi_getcontent($ch);
    curl_multi_remove_handle($mh, $ch);
    curl_multi_close($mh);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}

// ==========================================
// ⚡ CRON — ?update=send (ENG BOSHIDA!)
// ==========================================
if (isset($_GET['update']) && $_GET['update'] == "send") {

    $result = mysqli_query($connect, "SELECT * FROM send LIMIT 1");
    if (!$result || mysqli_num_rows($result) == 0) {
        die("Navbat bo'sh");
    }

    $row = mysqli_fetch_assoc($result);
    $start_id  = intval($row['start_id']);
    $stop_id   = intval($row['stop_id']);
    $admin_id  = $row['admin_id'];
    $msg_id    = $row['message_id'];
    $limit     = 150;

    $res = mysqli_query($connect, "SELECT id FROM users LIMIT $start_id, $limit");

    if (!$res || mysqli_num_rows($res) == 0) {
        bot('sendMessage', [
            'chat_id' => $admin_id,
            'text' => "✓ <b>Xabar barcha foydalanuvchilarga yuborildi!</b>
▪ Jami: $start_id ta",
            'parse_mode' => 'html',
        ]);
        mysqli_query($connect, "DELETE FROM send");
        die("Tugadi: $start_id");
    }

    $sent = 0;
    while ($user = mysqli_fetch_assoc($res)) {
        bot('forwardMessage', [
            'chat_id'      => $user['id'],
            'from_chat_id' => $admin_id,
            'message_id'   => $msg_id,
        ]);
        $sent++;
    }

    $new_start = $start_id + $sent;
    mysqli_query($connect, "UPDATE send SET start_id = '$new_start'");

    if ($new_start >= $stop_id) {
        bot('sendMessage', [
            'chat_id' => $admin_id,
            'text' => "✓ <b>Xabar yuborildi!</b> Jami: $new_start ta",
            'parse_mode' => 'html',
        ]);
        mysqli_query($connect, "DELETE FROM send");
    }

    die("Yuborildi: $new_start / $stop_id");
}

// ⚡ CRON — ?update=status
if (isset($_GET['update']) && $_GET['update'] == "status") {
    // ... buyurtma status yangilash kodi ...
    die("Status updated");
}

// ==========================================
// WEBHOOK — BU YERDAN KEYIN
// ==========================================
$update = json_decode(file_get_contents("php://input"));
$text = $update->message->text ?? "";
$cid = $update->message->from->id ?? "";
// ... qolgan kod ...

function deleteFolder($path)
{
    if (is_dir($path) === true) {
        $files = array_diff(scandir($path), array('.', '..'));
        foreach ($files as $file)
            deleteFolder(realpath($path) . '/' . $file);
        return rmdir($path);
    } else if (is_file($path) === true)
        return unlink($path);
    return false;
}

function token($str, $begin, $end)
{
    for ($i = $begin; $i < $end; $i++) $str[$i] = '*';
    return $str;
}

function rmdirPro($path)
{
    $scan = array_diff(scandir($path), ['.', '..']);
    foreach ($scan as $value) {
        if (is_dir("{$path}/{$value}"))
            rmdirPro("{$path}/{$value}");
        else
            @unlink("{$path}/{$value}");
    }
    rmdir($path);
}

function trans($x)
{
    $e = json_decode(file_get_contents("http://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=uz&dt=t&q=" . urlencode($x) . ""), 1);
    return $e[0][0][0];
}

function number($a)
{
    $form = number_format($a, 00, ' ', ' ');
    return $form;
}

function del()
{
    global $cid, $mid, $chat_id, $message_id;
    return bot('deleteMessage', [
        'chat_id' => $chat_id . $cid,
        'message_id' => $message_id . $mid,
    ]);
}


function edit($id, $mid, $tx, $m)
{
    return bot('editMessageText', [
        'chat_id' => $id,
        'message_id' => $mid,
        'text' => $tx,
        'parse_mode' => "HTML",
        'reply_markup' => $m,
    ]);
}

function sms($id, $tx, $m)
{
    return bot('sendMessage', [
        'chat_id' => $id,
        'text' => $tx,
        'parse_mode' => "HTML",
        'reply_markup' => $m,
    ]);
}

function referal($hi)
{
    $daten = [];
    $rev = [];
    $fayllar = glob("./user/*.*");
    foreach ($fayllar as $file) {
        if (mb_stripos($file, ".users") !== false) {
            $value = file_get_contents($file);
            $id = str_replace(["./user/", ".users"], ["", ""], $file);
            $daten[$value] = $id;
            $rev[$id] = $value;
        }
        echo $file;
    }

    asort($rev);
    $reversed = array_reverse($rev);
    for ($i = 0; $i < $hi; $i += 1) {
        $order = $i + 1;
        $id = $daten["$reversed[$i]"];
        $ism = bot('getChat', [
            'chat_id' => $id,
        ])->result->first_name;

        $text .= "<b>{$order}</b>. <a href='tg://user?id={$id}'>" . str_replace(["<", ">", "𒐫"], ["", "", ""], $ism) . "</a> - " . "<code>" . floor($reversed[$i]) . "</code>" . " <b> ta</b>" . "\n";
    }
    return $text;
}


function get($h)
{
    return file_get_contents($h);
}

function put($h, $r)
{
    file_put_contents($h, $r);
}

if (get("set/xolat.txt")) {
} else {
    if (put("set/xolat.txt", "✅"));
}


function adduser($cid)
{
    global $connect;
    $result = mysqli_query($connect, "SELECT * FROM users WHERE id = $cid");
    $row = mysqli_fetch_assoc($result);
    if ($row) {
    } else {
        $key = md5(uniqid());
        $referal = generate();
        $rew = mysqli_num_rows(mysqli_query($connect, "SELECT * FROM users"));
        $news = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users ORDER BY user_id DESC LIMIT 1"))['user_id'];
        $new = $news + 1;
        if (empty($key) or empty($referal) or empty($new)) {
            sms($cid, "<b>⛔ Bazaga saqlanishda xatolik yuz berdi!\n\n✅ Qaytadan /start bosing</b>", null);
        } else {
            mysqli_query($connect, "INSERT INTO users(`user_id`,`id`,`status`,`balance`,`outing`,`api_key`,`referal`) VALUES ('$new','$cid','active','0','0','$key','$referal');");
        }
    }
}



function joinchat($id)
{
    $array = array("inline_keyboard");
    $get = file_get_contents("set/channel");
    $ex = explode("\n", $get);
    $soni = substr_count($get, "@");
    if ($get == null) {
        return true;
    } else {
        for ($i = 0; $i <= count($ex) - 1; $i++) {
            $first_line = $ex[$i];
            $kanall = str_replace("@", "", $first_line);
            $ret = bot("getChatMember", [
                "chat_id" => $first_line,
                "user_id" => $id,
            ]);
            $reti = bot("getChat", [
                "chat_id" => $first_line,
            ]);
            $ch_namee = $reti->result->title;
            $stat = $ret->result->status;
            if ((($stat == "creator" or $stat == "administrator" or $stat == "member"))) {
                $array['inline_keyboard']["$i"][0]['text'] = "";
                $array['inline_keyboard']["$i"][0]['url'] = "https://t.me/$kanall";
            } else {
                $array['inline_keyboard']["$i"][0]['text'] = $ch_namee;
                $array['inline_keyboard']["$i"][0]['url'] = "https://t.me/$kanall";
                $uns = true;
            }
        }
        $array['inline_keyboard']["$i"][0]['text'] = "✅ Tekshirish";
        $array['inline_keyboard']["$i"][0]['callback_data'] = "result";
        if ($uns == true) {
            bot('sendMessage', [
                'chat_id' => $id,
                'text' => "⛔ <b>Botdan foydalanish uchun, quyidagi kanallarga obuna bo'ling:</b>",
                'parse_mode' => 'html',
                'reply_markup' => json_encode($array),
            ]);
        } else {
            return true;
        }
    }
}

$update = json_decode(file_get_contents('php://input'));
$message = $update->message;
$edituz = $update->callback_query->message->from->id;
$mesuz = $update->callback_query->message->message_id;
$cid = $message->chat->id;
$cidtyp = $message->chat->type;
$miid = $message->message_id;
$name = $message->chat->first_name;
$user1 = $message->from->username;
$tx = $message->text;
$callback = $update->callback_query;
$mmid = $callback->inline_message_id;
$mes = $callback->message;
$mid = $mes->message_id;
$cmtx = $mes->text;
$mmid = $callback->inline_message_id;
$idd = $callback->message->chat->id;
$cbid = $callback->from->id;
$cbuser = $callback->from->username;
$data = $callback->data;
$ida = $callback->id;
$cqid = $update->callback_query->id;
$qid = $cqid;
$cbins = $callback->chat_instance;
$cbchtyp = $callback->message->chat->type;
$step = file_get_contents("step/$from_id.step");
$message = $update->message;
$mid = $message->message_id;
$msgs = json_decode(file_get_contents('msgs.json'), true);
$data = $update->callback_query->data;
$type = $message->chat->type;
$text = $message->text;
$sd = $message->text;
$uid = $message->from->id;
$gname = $message->chat->title;
$left = $message->left_chat_member;
$new = $message->new_chat_member;
$name = $message->from->first_name;
$bio = $message->from->about;
$repid = $message->reply_to_message->from->id;
$repname = $message->reply_to_message->from->first_name;
$newid = $message->new_chat_member->id;
$leftid = $message->left_chat_member->id;

$botdel = $update->my_chat_member->new_chat_member;
$botdel_id = $update->my_chat_member->from->id;
$userstatus = $botdel->status;

$newname = $message->new_chat_member->first_name;
$leftname = $message->left_chat_member->first_name;
$username = $message->from->username;
$cmid = $update->callback_query->message->message_id;
$cusername = $message->chat->username;
$repmid = $message->reply_to_message->message_id;
$ccid = $update->callback_query->message->chat->id;
$cuid = $update->callback_query->message->from->id;
$from_id = $message->from->id;
$chat_id = $update->callback_query->message->chat->id;
$message_id = $update->callback_query->message->message_id;
$call = $update->callback_query;
$mes = $call->message;
$data = $call->data;
$qid = $call->id;
$callbackdata = $update->callback_query->data;
$callcid = $mes->chat->id;
$callmid = $mes->message_id;
$callfrid = $call->from->id;
$calluser = $mes->chat->username;
$callfname = $call->from->first_name;
$photo = $message->photo;
$gif = $message->animation;
$video = $message->video;
$music = $message->audio;
$voice = $message->voice;
$sticker = $message->sticker;
$document = $message->document;
$for = $message->forward_from;
$for_id = $for->id;
$contact = $message->contact;
$nomer_id = $contact->user_id;
$nomer_user = $contact->username;
$nomet_name = $contact->first_name;
$nomer_ph = $contact->phone_number;
$Tc = $update->callback_query->message->chat->type;
$cid2 = $chat_id;
$mid2 = $message_id;
$sana = date("d/m/Y | H:i");

$res = mysqli_query($connect, "SELECT*FROM user_id WHERE user_id=$cid");
while ($a = mysqli_fetch_assoc($res)) {
    $user_id = $a['user_id'];
    $reg = $a['reg'];
}

$res = mysqli_query($connect, "SELECT*FROM kabinet WHERE user_id = $cid");
while ($a = mysqli_fetch_assoc($res)) {
    $kab_id = $a['user_id'];
    $pul = $a['pul'];
    $pul2 = $a['pul2'];
    $odam = $a['odam'];
    $ban = $a['ban'];
    $status = $a['status'];
}

$res = mysqli_query($connect, "SELECT*FROM card WHERE user_id = $cid");
while ($a = mysqli_fetch_assoc($res)) {
    $cc = $a['cc'];
    $fc = $a['fc'];
}

$res = mysqli_query($connect, "SELECT*FROM uid WHERE user_id = $cid");
while ($a = mysqli_fetch_assoc($res)) {
    $fid = $a['uid'];
}

$res = mysqli_query($connect, "SELECT*FROM api WHERE user_id = $cid");
while ($a = mysqli_fetch_assoc($res)) {
    $api_id = $a['user_id'];
    $api = $a['api'];
}

function generate()
{
    $arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'X', 'Y', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
    $pass = "";
    for ($i = 0; $i < 7; $i++) {
        $index = rand(0, count($arr) - 1);
        $pass .= $arr[$index];
    }
    return $pass;
}

if ($text) {
    $reres = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM soxta WHERE user_id = '$cid'"));
    if ($reres) {
        mysqli_query($connect, "UPDATE soxta SET come = 'come' WHERE user_id = $cid");
    }
}

$delname = $update->my_chat_member->from->first_name;
if ($botdel) {
    $reres = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM soxta WHERE user_id = '$botdel_id'"))['id'];
    if ($userstatus == "kicked") {
        $res = mysqli_query($connect, "SELECT * FROM users WHERE user_id = $botdel_id");
        $rew = mysqli_fetch_assoc($res);
        if ($rew['status'] == "deactive") {
            exit();
        } else {
            if ($reres) {
                sms($admin, "<b>Foydalanuvchi botni yana blokladi !</b>", json_encode(['inline_keyboard' => [[['text' => "$delname", 'url' => "tg://user?id=$botdel_id"]]]]));
                mysqli_query($connect, "DELETE FROM myorder WHERE user_id = '$botdel_id'");
                mysqli_query($connect, "UPDATE soxta SET come = 'gone' WHERE user_id = $botdel_id");
            } else {
                mysqli_query($connect, "DELETE FROM myorder WHERE user_id = '$botdel_id'");
                mysqli_query($connect, "DELETE FROM users WHERE id = '$botdel_id'");
                $ok = mysqli_query($connect, "INSERT INTO soxta(`user_id`,`come`) VALUES ('$botdel_id','gone')")->$ok;
                if ($ok) {
                    $ax = "1";
                } else {
                    $ax = "0";
                }
                sms($admin, "<b>Foydalanuvchi botni blokladi $ax</b>", json_encode(['inline_keyboard' => [[['text' => "$delname", 'url' => "tg://user?id=$botdel_id"]]]]));
            }
        }
        unlink("user/$botdel_id.users");
        unlink("user/$botdel_id.step");
        unlink("user/$botdel_id.ur");
        unlink("user/$botdel_id.params");
        unlink("user/$botdel_id.qu");
        unlink("user/$botdel_id.si");
    }
}

$taklif = file_get_contents("tizim/taklif.txt");
$baza = file_get_contents("step/$cid.txt");
$cid3 = file_get_contents("step/$cid.id");
$qoida = file_get_contents("tizim/qoida.txt");
$cashback = file_get_contents("tizim/cashback.txt");
$cVip = file_get_contents("tizim/cvip.txt");
$holat = file_get_contents("tizim/holat.txt");
$promo = file_get_contents("tizim/kanal2.txt");
$kanal = file_get_contents("tizim/kanal.txt");
$card_cc = file_get_contents("tizim/cc.txt");
$card_fc = file_get_contents("tizim/fc.txt");
$vazi = file_get_contents("tizim/vazifachilar.txt");
$vazbonus = file_get_contents("tizim/vazbonus.txt");
$bonusmiqdor = file_get_contents("tizim/bonusmiqdor.txt");
$taklif = file_get_contents("tizim/taklif.txt");
$spc = file_get_contents("tizim/kodspc.txt");
$promo = file_get_contents("tizim/kanal2.txt");
$guruh1 = file_get_contents("tizim/guruh1.txt");
$gr1_id = file_get_contents("tizim/gr1.txt");
$gpul = file_get_contents("tizim/gpul.txt");
$payme = file_get_contents("tizim/payme.txt");
$paymeapi = file_get_contents("tizim/paymeapi.txt");
$paymeparol = file_get_contents("tizim/paymeparol.txt");
$check = file_get_contents("tizim/check.txt");
$user = file_get_contents("tizim/user.txt");
$valyuta = file_get_contents("tizim/valyuta.txt");
$key = uniqid(uniqid());

$kategoriya = file_get_contents("bot/kategoriya.txt");
$royxat = file_get_contents("bot/$kategoriya/royxat.txt");
$type = file_get_contents("bot/$kategoriya/$royxat/turi.txt");
$narx = file_get_contents("bot/$kategoriya/$royxat/narx.txt");
$kunlik = file_get_contents("bot/$kategoriya/$royxat/kunlik.txt");
$tavsif = file_get_contents("bot/$kategoriya/$royxat/tavsif.txt");
$til = file_get_contents("bot/$kategoriya/$royxat/til.txt");
$versiya = file_get_contents("bot/$kategoriya/$royxat/versiya.txt");


if (isset($update)) {
    $result = mysqli_query($connect, "SELECT * FROM users WHERE id = $cid$chat_id");
    $rew = mysqli_fetch_assoc($result);
    if ($rew['status'] == "deactive") {
        exit();
    }
}


if (isset($message)) {
    $result = mysqli_query($connect, "SELECT * FROM user_id WHERE user_id = $cid");
    $rew = mysqli_fetch_assoc($result);
    if ($rew) {
    } else {
        mysqli_query($connect, "INSERT INTO user_id(`user_id`,`reg`) VALUES ('$cid','$sana | $soat')");
    }
}

if (isset($message)) {
    $result = mysqli_query($connect, "SELECT * FROM kabinet WHERE user_id = $cid");
    $rew = mysqli_fetch_assoc($result);
    if ($rew) {
    } else {
        mysqli_query($connect, "INSERT INTO kabinet(`user_id`,`pul`,`pul2`,`odam`,`ban`,`status`) VALUES ('$cid','0','0','0','unban','Oddiy')");
    }
}

if (isset($message)) {
    $result = mysqli_query($connect, "SELECT * FROM card WHERE user_id = $cid");
    $rew = mysqli_fetch_assoc($result);
    if ($rew) {
    } else {
        mysqli_query($connect, "INSERT INTO card(`user_id`,`cc`,`fc`) VALUES ('$cid','0','0')");
    }
}

if (isset($message)) {
    $result = mysqli_query($connect, "SELECT * FROM api WHERE user_id = $cid");
    $rew = mysqli_fetch_assoc($result);
    if ($rew) {
    } else {
        mysqli_query($connect, "INSERT INTO api(`user_id`,`api`) VALUES ('$cid','$key')");
    }
}

if (isset($message)) {
    $result = mysqli_query($connect, "SELECT * FROM uid WHERE user_id = $cid");
    $rew = mysqli_fetch_assoc($result);
    if ($rew) {
    } else {
        mysqli_query($connect, "INSERT INTO uid(user_id) VALUES ('$cid')");
    }
}


$resu = mysqli_query($connect, "SELECT * FROM `settings`");
$setting = mysqli_fetch_assoc($resu);

mysqli_query($connect, " create table soxta(
id int(20) auto_increment primary key,
user_id varchar(100),
come varchar(100)
)");


mkdir("user");
mkdir("set");

$pul = get("user/$chat_id.pul");
$step = get("user/$cid.step");
$stepc = get("user/$chat_id.step");
$xolati = get("set/xolat.txt");

$ort = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "⏩ Orqaga"]],
    ]
]);

$aort = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "🗄️ Boshqaruv"]],
    ]
]);

$panel = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "📢 Kanallar"], ['text' => "📊 Statistika"]],
        [['text' => "⚙ Asosiy"], ['text' => "✉️ Xabar yuborish"]],
        [['text' => "🔎 Foydalanuvchini boshqarish"]],
        [['text' => "🤖 Bot holati"], ['text' => "🔎 Buyurtma"]],
        [['text' => "⏰ Cron sozlamasi"], ['text' => "🇺🇿 Valyuta kursi"]],
        [['text' => "⏩ Orqaga"]],
    ]
]);

$panel2 = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "📑 Birlamchi sozlamalar"]],
        [['text' => "💵 Kursni o‘rnatish"], ['text' => "⚖️ Foizni o‘rnatish"]],
        [['text' => "🔑 API sozlash"], ['text' => "🗄️ Boshqaruv"]],
    ]
]);

// Kodingizning eng boshida — admin tekshiruvidan KEYIN:
$settings = mysqli_fetch_assoc(mysqli_query($connect, "SELECT bot_status FROM settings WHERE id = 1"));

// Bot o'chirilgan — foydalanuvchiga xabar
if (isset($settings['bot_status']) && $settings['bot_status'] == 'deactive' && $cid != $admin) {
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "⚠️ <b>Texnik ishlar olib borilmoqda</b>

Hurmatli foydalanuvchi, hozirda botda texnik ishlar olib borilmoqda. Tez orada xizmat qayta tiklanadi.

⏳ Iltimos, biroz kutib qayta urinib ko'ring.

📞 Murojaat uchun: @SFXSMMHelp",
        'parse_mode' => 'html',
    ]);
    exit;
}

// ==========================================
// ⏰ CRON SOZLAMASI
// ==========================================

if (isset($text) && mb_stripos($text, "Cron sozlamasi") !== false && $cid == $admin) {

    $domain = $_SERVER['SERVER_NAME'];
    $script = $_SERVER['SCRIPT_NAME'];
    $dir = dirname($script);

    $url_send   = "https://$domain$script?update=send";
    $url_status = "https://$domain$script?update=status";
    $url_update = "https://$domain$dir/update.php";

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "⏰ <b>CRON SOZLAMASI</b>
━━━━━━━━━━━━━━━━━━━━

Quyidagi manzillarni hostingda <b>Cron Job</b> ga qo'shing:

━━━━━━━━━━━━━━━━━━━━
1️⃣ <b>Xabar yuborish (ommaviy)</b>
<code>$url_send</code>

2️⃣ <b>Buyurtma statusini yangilash</b>
<code>$url_status</code>

3️⃣ <b>Botni yangilash</b>
<code>$url_update</code>

━━━━━━━━━━━━━━━━━━━━
⚙️ <b>Tavsiya vaqt oralig'i:</b>
┣ 1️⃣ — Har 1 daqiqada
┣ 2️⃣ — Har 5 daqiqada
┗ 3️⃣ — Har 1 soatda

💡 <i>Manzilni bosib nusxa oling va hostingdagi Cron bo'limiga qo'shing.</i>",
        'parse_mode' => 'html',
        'disable_web_page_preview' => true,
        'reply_markup' => $panel,
    ]);
    exit;
}

if ($xolati == "❌") {
    if ($data) {
        if ($cid2 == $admin) {
        } else {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => "⛔️ Bot vaqtinchalik o'chirilgan!

Botda ta'mirlash ishlari olib borilayotgan bo'lishi mumkin!",
                'show_alert' => true,
            ]);
            exit();
        }
    } elseif ($text) {
        if ($cid == $admin) {
        } else {
            sms($cid, "<b>⛔️ Bot vaqtinchalik o'chirilgan!</b>

<i>Botda ta'mirlash ishlari olib borilayotgan bo'lishi mumkin!</i>", null);
            exit();
        }
    }
}

$menu = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "🗂 Xizmatlarga buyurtma berish"]],
        [['text' => "🔎 Buyurtmalarim"], ['text' => "🚀 Mablag' yig'ish"]],
        [['text' => "💵 Hisob to'ldirish"], ['text' => "💳 Mening hisobim"]],
        [['text' => "💎 Vertual xizmatlar"], ['text' => "☎️ Qo'llab-Quvvatlash"]],
    ]
]);

$menu_p = json_encode([
    'resize_keyboard' => true,
    'keyboard' => [
        [['text' => "🗂 Xizmatlarga buyurtma berish"]],
        [['text' => "🔎 Buyurtmalarim"], ['text' => "🚀 Mablag' yig'ish"]],
        [['text' => "💵 Hisob to'ldirish"], ['text' => "💳 Mening hisobim"]],
        [['text' => "💎 Vertual xizmatlar"], ['text' => "☎️ Qo'llab-Quvvatlash"]],
        [['text' => "🗄️ Boshqaruv"]],
    ]
]);

if ($cid == $admin or $chat_id == $admin) {
    $m = $menu_p;
} else {
    $m = $menu;
}

function delete($cid2, $mid2)
{
    return bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
}


if ($text == "🗄️ Boshqaruv" and $cid == $admin) {
    sms($cid, "👨‍💻 <b>Boshqaruv paneliga xush kelibsiz:</b>", $panel);
    unlink("user/$cid.step");
    exit;
}

if ($text == "⚙ Asosiy" and $cid == $admin) {
    sms($cid, "<b>👉 Asosiy sozlamalar:</b>", $panel2);
    exit();
}

if ($text == "⏩ Orqaga" and joinchat($cid) == 1) {
    sms($cid, "🖥️ <b>Asosiy menyudasiz</b>", $m);
    unlink("user/$cid.step");
    unlink("user/$cid.ur");
    unlink("user/$cid.params");
    unlink("user/$cid.qu");
    unlink("user/$cid.si");
    exit();
}

// ==========================================
// 🤖 BOT HOLATI (Yoqish / O'chirish)
// ==========================================
// settings jadvalida: bot_status ustuni (active / deactive)
// Agar yo'q bo'lsa: ALTER TABLE settings ADD COLUMN bot_status VARCHAR(20) DEFAULT 'active';
// ==========================================

if (isset($text) && mb_stripos($text, "Bot holati") !== false && $cid == $admin) {
    $settings = mysqli_fetch_assoc(mysqli_query($connect, "SELECT bot_status FROM settings WHERE id = 1"));
    $status = (isset($settings['bot_status']) && $settings['bot_status'] == 'deactive') ? 'deactive' : 'active';

    if ($status == 'active') {
        $status_text = "○ Yoqilgan";
        $button_text = "● O'chirish";
    } else {
        $status_text = "● O'chirilgan";
        $button_text = "○ Yoqish";
    }

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "🤖 <b>BOT HOLATI</b>
━━━━━━━━━━━━━━━━━━━━

📌 <b>Joriy holat:</b> $status_text

━━━━━━━━━━━━━━━━━━━━
💡 Bot o'chirilganda foydalanuvchilar botdan foydalana olmaydi. Faqat admin ishlata oladi.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => $button_text, 'callback_data' => "bot_toggle"]],
            ]
        ])
    ]);
    exit;
}

// 🤖 BOT HOLATINI O'ZGARTIRISH
if (isset($data) && $data == "bot_toggle") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $settings = mysqli_fetch_assoc(mysqli_query($connect, "SELECT bot_status FROM settings WHERE id = 1"));
    $current = (isset($settings['bot_status']) && $settings['bot_status'] == 'deactive') ? 'deactive' : 'active';

    // Toggle
    $new_status = ($current == 'active') ? 'deactive' : 'active';
    mysqli_query($connect, "UPDATE settings SET bot_status = '$new_status' WHERE id = 1");

    if ($new_status == 'active') {
        $status_text = "○ Yoqilgan";
        $button_text = "● O'chirish";
        $action_text = "✓ Bot yoqildi!";
    } else {
        $status_text = "● O'chirilgan";
        $button_text = "○ Yoqish";
        $action_text = "● Bot o'chirildi!";
    }

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "🤖 <b>BOT HOLATI</b>
━━━━━━━━━━━━━━━━━━━━

📌 <b>Joriy holat:</b> $status_text
⚡ <b>$action_text</b>

━━━━━━━━━━━━━━━━━━━━
💡 Bot o'chirilganda foydalanuvchilar botdan foydalana olmaydi. Faqat admin ishlata oladi.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => $button_text, 'callback_data' => "bot_toggle"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// · BOTNI BAHOLASH
// ==========================================

if (isset($text) && $text == "/baholash") {

    // O'rtacha baho
    $avg_q = mysqli_query($connect, "SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM ratings");
    $avg_row = mysqli_fetch_assoc($avg_q);
    $avg = $avg_row['cnt'] > 0 ? number_format(floatval($avg_row['avg_r']), 1) : "0.0";
    $total_ratings = $avg_row['cnt'] ?? 0;

    // Oldin baholagan mi
    $check = mysqli_query($connect, "SELECT * FROM ratings WHERE user_id = '$cid'");
    $old_text = "";
    if ($check && mysqli_num_rows($check) > 0) {
        $old = mysqli_fetch_assoc($check);
        $old_text = "
🔄 Sizning bahoyingiz: " . str_repeat("★", $old['rating']) . str_repeat("☆", 5 - $old['rating']);
    }

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "
┏━━━━━━━━━━━━━━━━━━━┓
         ·  <b>BAHOLASH</b>  ·
┗━━━━━━━━━━━━━━━━━━━┛

Botimiz haqida fikringiz qanday?
Pastdagi tugmalar orqali baholang:
$old_text

┌─────────────────────┐
│  ▪ Reyting: <b>$avg</b> / 5.0  │  👥 $total_ratings ta baho
└─────────────────────┘",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => '😡 1', 'callback_data' => 'rating_1'],
                    ['text' => '😕 2', 'callback_data' => 'rating_2'],
                    ['text' => '😐 3', 'callback_data' => 'rating_3'],
                    ['text' => '😊 4', 'callback_data' => 'rating_4'],
                    ['text' => '🤩 5', 'callback_data' => 'rating_5'],
                ],
            ]
        ])
    ]);
    exit;
}

// · BAHO TANLANDI
if (isset($data) && preg_match('/^rating_([1-5])$/', $data, $matches)) {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $ball = intval($matches[1]);
    $user_id = mysqli_real_escape_string($connect, $callfrid);

    // Saqlash
    $check = mysqli_query($connect, "SELECT * FROM ratings WHERE user_id = '$user_id'");
    if ($check && mysqli_num_rows($check) > 0) {
        mysqli_query($connect, "UPDATE ratings SET rating = '$ball', created_at = NOW() WHERE user_id = '$user_id'");
    } else {
        mysqli_query($connect, "INSERT INTO ratings (user_id, rating) VALUES ('$user_id', '$ball')");
    }

    // O'rtacha
    $avg_q = mysqli_query($connect, "SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM ratings");
    $avg_row = mysqli_fetch_assoc($avg_q);
    $avg = number_format(floatval($avg_row['avg_r']), 1);
    $total_ratings = $avg_row['cnt'];

    // Yulduzlar
    $filled = str_repeat("★", $ball);
    $empty = str_repeat("☆", 5 - $ball);

    // Emoji
    $faces = ['', '😡', '😕', '😐', '😊', '🤩'];
    $face = $faces[$ball];

    // Progress bar
    $bar_filled = str_repeat("▰", $ball);
    $bar_empty = str_repeat("▱", 5 - $ball);

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "
┏━━━━━━━━━━━━━━━━━━━┓
      ✓  <b>BAHO QABUL QILINDI</b>
┗━━━━━━━━━━━━━━━━━━━┛

$face  <b>$filled$empty</b>  ($ball / 5)

$bar_filled$bar_empty

┌─────────────────────┐
│  ▪ Bot reytingi: <b>$avg</b> / 5.0
│  👥 Jami: <b>$total_ratings</b> ta baho
└─────────────────────┘

🙏 Fikringiz uchun rahmat!",
        'parse_mode' => 'html',
    ]);

    // Adminga
    bot('sendMessage', [
        'chat_id' => $admin,
        'text' => "· <b>Yangi baho</b>

👤 <a href='tg://user?id=$user_id'>Foydalanuvchi</a>
$face $filled$empty ($ball/5)
▪ O'rtacha: $avg ($total_ratings ta)",
        'parse_mode' => 'html',
    ]);
    exit;
}

// ==========================================
// ▪ STATISTIKA — TO'LIQ KOD (BARCHA FUNKSIYALAR)
// ==========================================

function get_stat_text($connect, $chat_id_param) {
    $start_time = microtime(true);
    bot('sendChatAction', ['chat_id' => $chat_id_param, 'action' => 'typing']);
    $ping = round((microtime(true) - $start_time) * 1000);

    if ($ping < 30) $x = "juda tez ⚡";
    elseif ($ping < 80) $x = "tez ○";
    elseif ($ping < 150) $x = "o'rta 🟡";
    elseif ($ping < 300) $x = "sekin 🟠";
    else $x = "juda sekin ●";

    $ac = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM users WHERE status = 'active'"))['c'];
    $dc = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM users WHERE status = 'deactive'"))['c'];

    $today1 = date("Y-m-d"); $today2 = date("d.m.Y");
    $today_member = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM users WHERE registration_date LIKE '$today1%' OR registration_date LIKE '%$today2%'"))['c'];
    $yesterday1 = date("Y-m-d", strtotime("-1 day")); $yesterday2 = date("d.m.Y", strtotime("-1 day"));
    $yesterday_member = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM users WHERE registration_date LIKE '$yesterday1%' OR registration_date LIKE '%$yesterday2%'"))['c'];
    $week_member = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM users WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['c'];
    $month_member = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM users WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"))['c'];

    $seco0 = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM soxta"))['c'] ?? 0;
    $tw = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM soxta WHERE come = 'come'"))['c'] ?? 0;
    $gone = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM soxta WHERE come = 'gone'"))['c'] ?? 0;
    $tarketgan = $seco0 - $tw; $azola = $ac - $gone; $jam = $azola + $tarketgan;

    $seco = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM services"))['c'];
    $stati = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders"))['c'];
    $today_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders WHERE DATE(created_at) = CURDATE()"))['c'] ?? 0;
    $providers_count = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM providers"))['c'];

    $total_balance = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(balance) as s FROM users"))['s'] ?? 0;
    $xishlatilganlar = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(retail) as s FROM myorder"))['s'] ?? 0;
    $todayRevenue = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(retail) as s FROM myorder WHERE DATE(order_create) = CURDATE() AND status = 'Completed'"))['s'] ?? 0;
    $weekRevenue = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(retail) as s FROM myorder WHERE YEARWEEK(order_create, 1) = YEARWEEK(CURDATE(), 1) AND status = 'Completed'"))['s'] ?? 0;
    $monthRevenue = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(retail) as s FROM myorder WHERE YEAR(order_create) = YEAR(CURDATE()) AND MONTH(order_create) = MONTH(CURDATE()) AND status = 'Completed'"))['s'] ?? 0;

    $total_balance_f = number_format($total_balance, 0, '', ' ');
    $xishlatilganlar_f = number_format($xishlatilganlar, 0, '', ' ');
    $todayRevenue_f = number_format($todayRevenue, 0, '', ' ');
    $weekRevenue_f = number_format($weekRevenue, 0, '', ' ');
    $monthRevenue_f = number_format($monthRevenue, 0, '', ' ');

    return "▪ <b>Bot statistikasi</b>
🕐 " . date("d.m.Y | H:i") . "  •  {$ping}ms ($x)

<blockquote>👥 <b>Foydalanuvchilar:</b>

🆕 Bugun qo'shildi:  <b>+$today_member</b> ta
📅 Kecha qo'shildi:  <b>+$yesterday_member</b> ta
📆 Shu hafta:  <b>+$week_member</b> ta
🗓 Shu oyda:  <b>+$month_member</b> ta

👥 Barcha foydalanuvchilar:  <b>$jam</b> ta
✓ Aktiv foydalanuvchilar:  <b>$azola</b> ta
📤 Botni tark etganlar:  <b>$tarketgan</b> ta
⛔ Bloklangan foydalanuvchilar:  <b>$dc</b> ta</blockquote>

<blockquote>🛒 <b>Bot ma'lumotlari:</b>

▪ Barcha xizmatlar:  <b>$seco</b> ta
🌐 Provayderlar soni:  <b>$providers_count</b> ta
📦 Barcha buyurtmalar:  <b>$stati</b> ta
🆕 Bugungi buyurtmalar:  <b>$today_orders</b> ta</blockquote>

<blockquote>💰 <b>Daromad:</b>

📈 Bugungi daromad:  <b>$todayRevenue_f</b> so'm
▪ Haftalik daromad:  <b>$weekRevenue_f</b> so'm
📆 Oylik daromad:  <b>$monthRevenue_f</b> so'm

💸 Jami sarflangan:  <b>$xishlatilganlar_f</b> so'm
💵 Foydalanuvchilar balansi:  <b>$total_balance_f</b> so'm</blockquote>";
}

function get_stat_keyboard() {
    return json_encode([
        'inline_keyboard' => [
            [['text' => "📦 Buyurtmalar statistikasi", 'callback_data' => "stat_orders"]],
            [
                ['text' => "🔝 TOP buyurtmachilar", 'callback_data' => "stat_top_orders"],
                ['text' => "💰 TOP xaridorlar", 'callback_data' => "stat_top_spenders"],
            ],
            [
                ['text' => "🏆 TOP 100 Balans", 'callback_data' => "stat_top_balance"],
                ['text' => "🏆 TOP 100 Referal", 'callback_data' => "stat_top_ref"],
            ],
            [['text' => "🌐 Provayderlar balansi", 'callback_data' => "stat_providers"]],
            [
                ['text' => "🕐 Soatlik statistika", 'callback_data' => "stat_hourly"],
                ['text' => "📉 Tark etganlar", 'callback_data' => "stat_left_graph"],
            ],
            [
                ['text' => "💰 Balansni 0", 'callback_data' => "stat_reset_bal"],
                ['text' => "🤝 Referalni 0", 'callback_data' => "stat_reset_ref"],
            ],
            [['text' => "🔄 Yangilash", 'callback_data' => "stat_refresh"]],
        ]
    ]);
}

// ==========================================
// ▪ STATISTIKA — TUGMA
// ==========================================

if (isset($text) && mb_stripos($text, "Statistika") !== false && $cid == $admin) {
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => get_stat_text($connect, $cid),
        'parse_mode' => 'html',
        'reply_markup' => get_stat_keyboard(),
    ]);
    @unlink("user/$cid.step");
    if (function_exists('clear_step')) clear_step($connect, $cid);
    exit;
}

// 🔄 YANGILASH
if (isset($data) && $data == "stat_refresh") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "🔄 Yangilanmoqda..."]);
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => get_stat_text($connect, $chat_id),
        'parse_mode' => 'html',
        'reply_markup' => get_stat_keyboard(),
    ]);
    exit;
}

// ⬅️ ORQAGA
if (isset($data) && $data == "stat_back") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => get_stat_text($connect, $chat_id),
        'parse_mode' => 'html',
        'reply_markup' => get_stat_keyboard(),
    ]);
    exit;
}

// ==========================================
// 📦 BUYURTMALAR STATISTIKASI
// ==========================================

if (isset($data) && $data == "stat_orders") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $total = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders"))['c'];
    $pc = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders WHERE status = 'Pending'"))['c'];
    $jc = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders WHERE status = 'In progress'"))['c'];
    $cp = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders WHERE status = 'Processing'"))['c'];
    $cc = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders WHERE status = 'Completed'"))['c'];
    $ppc = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders WHERE status = 'Partial'"))['c'];
    $bc = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders WHERE status = 'Canceled'"))['c'];
    $fc = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders WHERE status = 'Failed'"))['c'];

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "📦 <b>Buyurtmalar statistikasi</b>

<blockquote>📦 Jami buyurtmalar:  <b>$total</b> ta
✓ Bajarilgan buyurtmalar:  <b>$cc</b> ta
⏳ Kutilayotgan buyurtmalar:  <b>$pc</b> ta
🔄 Jarayondagi buyurtmalar:  <b>$jc</b> ta
⚙️ Qayta ishlanayotgan:  <b>$cp</b> ta
⚠️ Qisman bajarilgan:  <b>$ppc</b> ta
✗ Bekor qilingan:  <b>$bc</b> ta
💥 Muvaffaqiyatsiz:  <b>$fc</b> ta</blockquote>

⚡ <b>Buyurtmalar statusini o'zgartirish:</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⏳ Kutmoqda → ✓ Bajarilgan", 'callback_data' => "stat_move_Pending_Completed"]],
                [['text' => "🔄 Jarayonda → ✓ Bajarilgan", 'callback_data' => "stat_move_In progress_Completed"]],
                [['text' => "⚙️ Processing → ✓ Bajarilgan", 'callback_data' => "stat_move_Processing_Completed"]],
                [['text' => "⚠️ Qisman → ✓ Bajarilgan", 'callback_data' => "stat_move_Partial_Completed"]],
                [['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]],
            ]
        ])
    ]);
    exit;
}

// 📦 STATUS O'ZGARTIRISH — TASDIQLASH
if (isset($data) && preg_match('/^stat_move_(.+)_([A-Za-z]+)$/', $data, $matches)) {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    $from = $matches[1]; $to = $matches[2];
    $from_esc = mysqli_real_escape_string($connect, $from);
    $count = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM orders WHERE status = '$from_esc'"))['c'];

    bot('editMessageText', [
        'chat_id' => $chat_id, 'message_id' => $message_id,
        'text' => "⚠️ <b>Tasdiqlang</b>

📦 <b>$count</b> ta buyurtmani
«$from» → «$to» holatiga o'tkazilsinmi?",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [
            [['text' => "✓ Ha, o'tkazish", 'callback_data' => "stat_yes_{$from}_{$to}"], ['text' => "✗ Bekor", 'callback_data' => "stat_orders"]],
        ]])
    ]);
    exit;
}

// 📦 STATUS — TASDIQLANDI
if (isset($data) && preg_match('/^stat_yes_(.+)_([A-Za-z]+)$/', $data, $matches)) {
    bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "⏳ Bajarilmoqda..."]);
    $from = mysqli_real_escape_string($connect, $matches[1]);
    $to = mysqli_real_escape_string($connect, $matches[2]);

    $orders = mysqli_query($connect, "SELECT order_id FROM orders WHERE status = '$from'");
    $count = 0;
    while ($row = mysqli_fetch_assoc($orders)) {
        $oid = $row['order_id'];
        mysqli_query($connect, "UPDATE orders SET status = '$to' WHERE order_id = '$oid'");
        if ($to == "Completed") {
            $sav = date("Y.m.d H:i:s");
            mysqli_query($connect, "UPDATE myorder SET status = '$to', last_check = '$sav' WHERE order_id = '$oid'");
            $myorder = mysqli_fetch_assoc(mysqli_query($connect, "SELECT user_id FROM myorder WHERE order_id = '$oid'"));
            if ($myorder) {
                bot('sendMessage', ['chat_id' => $myorder['user_id'], 'text' => "✓ <b>$oid raqamli buyurtmangiz bajarildi!</b>

🔥 Xizmatlarimizdan foydalanganingiz uchun rahmat.", 'parse_mode' => 'html']);
            }
        } else {
            mysqli_query($connect, "UPDATE myorder SET status = '$to' WHERE order_id = '$oid'");
        }
        $count++;
    }

    bot('editMessageText', [
        'chat_id' => $chat_id, 'message_id' => $message_id,
        'text' => "✓ <b>Jarayon tugallandi!</b>

📦 «$from» → «$to»
🔢 O'zgartirildi: <b>$count</b> ta buyurtma",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [
            [['text' => "📦 Buyurtmalar", 'callback_data' => "stat_orders"]],
            [['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]],
        ]])
    ]);
    exit;
}

// ==========================================
// 🔝 TOP BUYURTMACHILAR (eng ko'p buyurtma berganlar)
// ==========================================

if (isset($data) && $data == "stat_top_orders") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $res = mysqli_query($connect, "SELECT user_id, COUNT(*) as cnt FROM myorder GROUP BY user_id ORDER BY cnt DESC LIMIT 20");
    $top = "";
    $i = 1;
    while ($row = mysqli_fetch_assoc($res)) {
        $uid = $row['user_id'];
        // user_id dan Telegram ID olish
        $user = mysqli_fetch_assoc(mysqli_query($connect, "SELECT id FROM users WHERE user_id = '$uid'"));
        $tg_id = $user['id'] ?? $uid;
        $top .= "<b>$i.</b> <a href='tg://user?id=$tg_id'>$tg_id</a> — 📦 {$row['cnt']} ta buyurtma
";
        $i++;
    }

    if (empty($top)) $top = "Ma'lumot yo'q";

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "🔝 <b>TOP 20 eng ko'p buyurtma berganlar:</b>

$top",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]]]])
    ]);
    exit;
}

// ==========================================
// 💰 TOP XARIDORLAR (eng ko'p sarflaganlar)
// ==========================================

if (isset($data) && $data == "stat_top_spenders") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $res = mysqli_query($connect, "SELECT user_id, SUM(retail) as total FROM myorder GROUP BY user_id ORDER BY total DESC LIMIT 20");
    $top = "";
    $i = 1;
    while ($row = mysqli_fetch_assoc($res)) {
        $uid = $row['user_id'];
        $user = mysqli_fetch_assoc(mysqli_query($connect, "SELECT id FROM users WHERE user_id = '$uid'"));
        $tg_id = $user['id'] ?? $uid;
        $spent = number_format($row['total'], 0, '', ' ');
        $top .= "<b>$i.</b> <a href='tg://user?id=$tg_id'>$tg_id</a> — 💰 $spent so'm
";
        $i++;
    }

    if (empty($top)) $top = "Ma'lumot yo'q";

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "💰 <b>TOP 20 eng ko'p sarflagan foydalanuvchilar:</b>

$top",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]]]])
    ]);
    exit;
}

// ==========================================
// 🌐 PROVAYDERLAR BALANSI
// ==========================================

if (isset($data) && $data == "stat_providers") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "🌐 Tekshirilmoqda..."]);

    $result = mysqli_query($connect, "SELECT * FROM providers ORDER BY id ASC");
    $count = mysqli_num_rows($result);

    if ($count == 0) {
        bot('editMessageText', [
            'chat_id' => $chat_id, 'message_id' => $message_id,
            'text' => "🌐 <b>Provayderlar balansi</b>

⚠️ Provayderlar topilmadi.",
            'parse_mode' => 'html',
            'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]]]])
        ]);
        exit;
    }

    $msg = "🌐 <b>Provayderlar balansi:</b>

";
    $i = 1;
    $total_usd = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $row['api_url']);

        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $response = @file_get_contents($row['api_url'] . "?key=" . $row['api_key'] . "&action=balance", false, $ctx);
        $bal_data = json_decode($response, true);

        if ($bal_data && isset($bal_data['balance'])) {
            $balance = number_format(floatval($bal_data['balance']), 2);
            $currency = $bal_data['currency'] ?? "USD";
            $status = "✓";
            $total_usd += floatval($bal_data['balance']);
        } else {
            $balance = "xato";
            $currency = "";
            $status = "✗";
        }

        $msg .= "$status <b>$i. $short_url</b>
     💰 $balance $currency

";
        $i++;
    }

    $msg .= "━━━━━━━━━━━━━━━━━━━━━━
💎 <b>Jami:</b> ~" . number_format($total_usd, 2) . " USD";

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [
            [['text' => "🔄 Yangilash", 'callback_data' => "stat_providers"]],
            [['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]],
        ]])
    ]);
    exit;
}

// ==========================================
// 🕐 SOATLIK STATISTIKA
// ==========================================

if (isset($data) && $data == "stat_hourly") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $msg = "🕐 <b>Bugungi soatlik statistika:</b>

";

    // Bugungi soatlik ro'yxatdan o'tishlar
    $today = date("Y-m-d");
    $max_count = 1;

    $hours_data = [];
    for ($h = 0; $h < 24; $h++) {
        $hour_str = sprintf("%02d", $h);
        $count = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM users WHERE registration_date LIKE '$today $hour_str:%'"))['c'] ?? 0;
        $hours_data[$h] = $count;
        if ($count > $max_count) $max_count = $count;
    }

    // Grafik
    $current_hour = intval(date("H"));
    for ($h = 0; $h < 24; $h++) {
        $count = $hours_data[$h];
        $bar_len = $max_count > 0 ? round(($count / $max_count) * 8) : 0;
        $bar = str_repeat("▓", $bar_len) . str_repeat("░", 8 - $bar_len);

        $hour_label = sprintf("%02d:00", $h);
        $pointer = ($h == $current_hour) ? " ◀️" : "";
        $msg .= "<code>$hour_label</code> $bar <b>$count</b>$pointer
";
    }

    $total_today = array_sum($hours_data);
    $msg .= "
▪ <b>Bugun jami:</b> $total_today ta yangi foydalanuvchi";

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]]]])
    ]);
    exit;
}

// ==========================================
// 📉 TARK ETGANLAR GRAFIGI (oxirgi 7 kun)
// ==========================================

if (isset($data) && $data == "stat_left_graph") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $msg = "📉 <b>Oxirgi 7 kunda tark etganlar:</b>

";

    $days_data = [];
    $max_count = 1;

    for ($d = 6; $d >= 0; $d--) {
        $date = date("Y-m-d", strtotime("-$d days"));
        $date2 = date("d.m.Y", strtotime("-$d days"));

        // soxta jadvalidan ketganlarni sanash
        $gone = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM soxta WHERE come = 'gone' AND (date LIKE '$date%' OR date LIKE '%$date2%')"))['c'] ?? 0;

        // Agar date ustuni yo'q bo'lsa — umumiy hisob
        if ($gone == 0) {
            $gone = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM soxta WHERE come = 'gone'"))['c'] ?? 0;
            $gone = round($gone / 7); // O'rtacha
        }

        $days_data[] = ['date' => date("d.m", strtotime("-$d days")), 'day' => date("D", strtotime("-$d days")), 'count' => $gone];
        if ($gone > $max_count) $max_count = $gone;
    }

    // Kun nomlari
    $day_names = ['Mon' => 'Du', 'Tue' => 'Se', 'Wed' => 'Ch', 'Thu' => 'Pa', 'Fri' => 'Ju', 'Sat' => 'Sh', 'Sun' => 'Ya'];

    foreach ($days_data as $day) {
        $bar_len = $max_count > 0 ? round(($day['count'] / $max_count) * 8) : 0;
        $bar = str_repeat("▓", $bar_len) . str_repeat("░", 8 - $bar_len);
        $day_name = $day_names[$day['day']] ?? $day['day'];
        $msg .= "<code>{$day['date']} $day_name</code> $bar <b>{$day['count']}</b>
";
    }

    $total_left = array_sum(array_column($days_data, 'count'));
    $msg .= "
▪ <b>Jami 7 kunda:</b> ~$total_left ta tark etgan";

    // Yangi qo'shilganlar bilan solishtirish
    $week_new = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM users WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)"))['c'];
    $diff = $week_new - $total_left;
    $diff_text = $diff >= 0 ? "📈 +$diff ta o'sish" : "📉 $diff ta kamayish";
    $msg .= "
$diff_text (yangi - ketgan)";

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]]]])
    ]);
    exit;
}

// ==========================================
// 🏆 TOP 100 BALANS
// ==========================================

if (isset($data) && $data == "stat_top_balance") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $res = mysqli_query($connect, "SELECT id, balance FROM users ORDER BY balance DESC LIMIT 100");
    $top = "";
    $i = 1;
    while ($row = mysqli_fetch_assoc($res)) {
        $bal = number_format($row['balance'], 0, '', ' ');
        $top .= "<b>$i.</b> <a href='tg://user?id={$row['id']}'>{$row['id']}</a> — 💰 $bal so'm
";
        $i++;
    }

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "🏆 <b>TOP 100 eng boy foydalanuvchilar:</b>

$top",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]]]])
    ]);
    exit;
}

// ==========================================
// 🏆 TOP 100 REFERAL
// ==========================================

if (isset($data) && $data == "stat_top_ref") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $res = mysqli_query($connect, "SELECT id, refnum FROM users ORDER BY refnum DESC LIMIT 100");
    $top = "";
    $i = 1;
    while ($row = mysqli_fetch_assoc($res)) {
        $top .= "<b>$i.</b> <a href='tg://user?id={$row['id']}'>{$row['id']}</a> — 🤝 {$row['refnum']} ta
";
        $i++;
    }

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "🏆 <b>TOP 100 eng faol referalchilar:</b>

$top",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]]]])
    ]);
    exit;
}

// ==========================================
// 💰 BALANSNI 0 QILISH
// ==========================================

if (isset($data) && $data == "stat_reset_bal") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    $total_balance = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(balance) as s FROM users"))['s'] ?? 0;
    $total_balance_f = number_format($total_balance, 0, '', ' ');
    $user_count = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM users WHERE balance > 0"))['c'];

    bot('editMessageText', [
        'chat_id' => $chat_id, 'message_id' => $message_id,
        'text' => "⚠️ <b>Diqqat! Barcha balanslar 0 ga tushiriladi!</b>

👥 Balansi bor: <b>$user_count</b> ta
💰 Jami: <b>$total_balance_f</b> so'm

❓ Davom etasizmi?",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [
            [['text' => "✓ Ha, 0 ga tushirish", 'callback_data' => "stat_reset_bal_yes"], ['text' => "✗ Bekor", 'callback_data' => "stat_back"]],
        ]])
    ]);
    exit;
}

if (isset($data) && $data == "stat_reset_bal_yes") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    mysqli_query($connect, "UPDATE users SET balance = '0'");
    bot('editMessageText', [
        'chat_id' => $chat_id, 'message_id' => $message_id,
        'text' => "✓ <b>Barcha foydalanuvchilar balansi 0 ga tushirildi!</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]]]])
    ]);
    exit;
}

// ==========================================
// 🤝 REFERALNI 0 QILISH
// ==========================================

if (isset($data) && $data == "stat_reset_ref") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    $ref_count = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM users WHERE refnum > 0"))['c'];
    $total_ref = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(refnum) as s FROM users"))['s'] ?? 0;

    bot('editMessageText', [
        'chat_id' => $chat_id, 'message_id' => $message_id,
        'text' => "⚠️ <b>Diqqat! Barcha referallar 0 ga tushiriladi!</b>

👥 Referali bor: <b>$ref_count</b> ta
🤝 Jami: <b>$total_ref</b> ta

❓ Davom etasizmi?",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [
            [['text' => "✓ Ha, 0 ga tushirish", 'callback_data' => "stat_reset_ref_yes"], ['text' => "✗ Bekor", 'callback_data' => "stat_back"]],
        ]])
    ]);
    exit;
}

if (isset($data) && $data == "stat_reset_ref_yes") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    mysqli_query($connect, "UPDATE users SET refnum = '0'");
    bot('editMessageText', [
        'chat_id' => $chat_id, 'message_id' => $message_id,
        'text' => "✓ <b>Barcha foydalanuvchilar referallari 0 ga tushirildi!</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "⬅️ Orqaga qaytish", 'callback_data' => "stat_back"]]]])
    ]);
    exit;
}

// ==========================================
// ✉️ OMMAVIY XABAR YUBORISH
// ==========================================

if (isset($text) && mb_stripos($text, "Xabar yuborish") !== false && $cid == $admin) {

    $check = mysqli_query($connect, "SELECT * FROM send LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $sent = intval($row['start_id']);
        $total = intval($row['stop_id']);
        $percent = $total > 0 ? round(($sent / $total) * 100) : 0;

        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⏳ <b>XABAR YUBORILMOQDA</b>
━━━━━━━━━━━━━━━━━━━━

▪ Jarayon: $sent / $total ($percent%)

❗ Yangi xabar uchun kuting yoki bekor qiling:",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "✗ Bekor qilish", 'callback_data' => "send_cancel"]],
                ]
            ])
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✉️ <b>OMMAVIY XABAR</b>
━━━━━━━━━━━━━━━━━━━━

📤 Foydalanuvchilarga yuboriladigan xabarni yuboring.

💡 <b>Qo'llab-quvvatlanadi:</b>
┣ Matn
┣ Rasm
┣ Video
┣ Sticker
┗ Inline tugmali xabar

━━━━━━━━━━━━━━━━━━━━
⚠️ Xabar barcha foydalanuvchilarga yuboriladi!",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'keyboard' => [[['text' => '✗ Bekor qilish']]],
                'resize_keyboard' => true
            ]),
        ]);

        file_put_contents("user/$cid.step", "broadcast_msg");
        if (function_exists('set_step')) {
            set_step($connect, $cid, "broadcast_msg");
        }
    }
    exit;
}

// ✉️ XABAR KIRITILDI — SAQLASH
$step_check = "";
if (isset($cid) && file_exists("user/$cid.step")) {
    $step_check = trim(file_get_contents("user/$cid.step"));
}
if (empty($step_check) && function_exists('get_step') && isset($cid)) {
    $step_check = get_step($connect, $cid);
}

if ($step_check == "broadcast_msg" && $cid == $admin) {

    // ⚠️ Agar panel tugmalaridan birini bossa — bekor qilish
    $panel_texts = ["Boshqaruv", "Asosiy", "Statistika", "Kanallar",
                    "Bot holati", "Buyurtma", "Foydalanuvchini", "API sozlash",
                    "Birlamchi", "Valyuta", "Cron", "Foizni", "Kursni"];

    $is_panel = false;
    foreach ($panel_texts as $btn) {
        if (isset($text) && mb_stripos($text, $btn) !== false) {
            $is_panel = true;
            break;
        }
    }

    if ($is_panel) {
        // Panel tugmasi bosildi — stepni tozalab, boshqa handlerga o'tkazamiz
        @unlink("user/$cid.step");
        if (function_exists('clear_step')) clear_step($connect, $cid);
        // exit QILMAYMIZ — keyingi handler ishlashi uchun
    } else {
        // Haqiqiy xabar kiritildi — yuborishga tayyorlash
        $total_q = mysqli_query($connect, "SELECT COUNT(*) as cnt FROM users");
        $total = mysqli_fetch_assoc($total_q)['cnt'];

        if ($total == 0) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✗ <b>Foydalanuvchilar topilmadi!</b>",
                'parse_mode' => 'html',
                'reply_markup' => $panel,
            ]);
            @unlink("user/$cid.step");
            if (function_exists('clear_step')) clear_step($connect, $cid);
            exit;
        }

        $reply_markup = "";
        if (isset($update->message->reply_markup)) {
            $reply_markup = base64_encode(json_encode($update->message->reply_markup));
        }

        $msg_id = $mid;
        $admin_id = mysqli_real_escape_string($connect, $cid);
        $reply_esc = mysqli_real_escape_string($connect, $reply_markup);

        mysqli_query($connect, "DELETE FROM send");
        mysqli_query($connect, "INSERT INTO send (start_id, stop_id, admin_id, message_id, reply_markup) VALUES ('0', '$total', '$admin_id', '$msg_id', '$reply_esc')");

        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✓ <b>XABAR SAQLANDI!</b>
━━━━━━━━━━━━━━━━━━━━

▪ Yuboriladi: <code>$total</code> ta foydalanuvchiga
⏱ Keyingi cron ishga tushganda boshlanadi.

💡 Har cron da 150 tadan yuboriladi.",
            'parse_mode' => 'html',
            'reply_markup' => $panel,
        ]);

        @unlink("user/$cid.step");
        if (function_exists('clear_step')) clear_step($connect, $cid);
        exit;
    }
}

// ✗ INLINE BEKOR QILISH (yuborilayotganni to'xtatish)
if (isset($data) && $data == "send_cancel") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    mysqli_query($connect, "DELETE FROM send");
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "✗ <b>Ommaviy xabar bekor qilindi!</b>",
        'parse_mode' => 'html',
    ]);
    exit;
}

// ==========================================
// STEP FUNKSIYALARI — MySQL orqali
// ==========================================

// Step ni o'qish
function get_step($connect, $telegram_id) {
    $telegram_id = mysqli_real_escape_string($connect, $telegram_id);
    $result = mysqli_query($connect, "SELECT step FROM users WHERE id = '$telegram_id'");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return trim($row['step']);
    }
    return "";
}

// Step ni yozish
function set_step($connect, $telegram_id, $step_value) {
    $telegram_id = mysqli_real_escape_string($connect, $telegram_id);
    $step_value  = mysqli_real_escape_string($connect, $step_value);
    mysqli_query($connect, "UPDATE users SET step = '$step_value' WHERE id = '$telegram_id'");
}

// Step ni tozalash
function clear_step($connect, $telegram_id) {
    $telegram_id = mysqli_real_escape_string($connect, $telegram_id);
    mysqli_query($connect, "UPDATE users SET step = '' WHERE id = '$telegram_id'");
}

// ==========================================
// ASOSIY O'ZGARUVCHILAR
// ==========================================

// Step ni bazadan o'qish (fayl emas!)
$step = get_step($connect, $from_id);

// Bekor qilish tugmasi
$aort = json_encode([
    'keyboard' => [
        [['text' => '✗ Bekor qilish']]
    ],
    'resize_keyboard' => true,
    'one_time_keyboard' => true
]);

// ==========================================
// ✗ BEKOR QILISH
// ==========================================

if ($text == "✗ Bekor qilish") {
    clear_step($connect, $from_id);
    bot('sendMessage', [
        'chat_id'      => $from_id,
        'text'         => "✗ <b>Amal bekor qilindi.</b>",
        'parse_mode'   => 'html',
        'reply_markup' => $panel,
    ]);
    exit();
}

// ==========================================
// 🔎 FOYDALANUVCHINI BOSHQARISH
// ==========================================

if ($text == "🔎 Foydalanuvchini boshqarish") {
    if ($from_id == $admin) {
        $keybot = json_encode([
            'inline_keyboard' => [
                [['text' => "🔹 Tartib raqam orqali", 'callback_data' => "orqali=user_id"]],
                [['text' => "🔹 Telegram ID orqali",  'callback_data' => "orqali=id"]],
            ]
        ]);
        bot('sendMessage', [
            'chat_id'      => $from_id,
            'text'         => "<b>▪ Quyidagilardan birini tanlang:</b>",
            'parse_mode'   => 'html',
            'reply_markup' => $keybot,
        ]);
    }
    exit();
}

// CALLBACK: orqali= tugmasi bosildi
if (isset($data) && $data && mb_stripos($data, "orqali=") !== false) {
    $by = explode("=", $data)[1];

    if (!in_array($by, ['id', 'user_id'])) {
        bot('answerCallbackQuery', ['callback_query_id' => $qid]);
        exit();
    }

    $k = ($by == "id") ? "Telegram ID" : "Tartib raqam";

    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('sendMessage', [
        'chat_id'      => $callfrid,
        'text'         => "<b>🔢 Foydalanuvchi $k raqamini kiriting:</b>",
        'parse_mode'   => 'html',
        'reply_markup' => $aort,
    ]);

    // ✓ MySQL ga yozamiz (faylga emas!)
    set_step($connect, $callfrid, "by-$by");
    exit();
}

// MESSAGE: foydalanuvchi raqam kiritdi
if ($step && strpos($step, "by-") === 0) {
    if ($from_id == $admin) {
        $bz = substr($step, 3);

        if (!in_array($bz, ['id', 'user_id'])) {
            clear_step($connect, $from_id);
            exit();
        }

        if (!is_numeric(trim($text))) {
            bot('sendMessage', [
                'chat_id'    => $from_id,
                'text'       => "⚠️ <b>Faqat raqam kiriting!</b>",
                'parse_mode' => 'html',
            ]);
            exit();
        }

        $text_clean = mysqli_real_escape_string($connect, trim($text));
        $query = mysqli_query($connect, "SELECT * FROM users WHERE $bz = '$text_clean'");

        if ($query && $rew = mysqli_fetch_assoc($query)) {
            $tg_id     = $rew['id'];
            $tartib    = $rew['user_id'];
            $pul       = $rew['balance'];
            $ban       = $rew['status'];
            $outing    = $rew['outing'];
            $ref_count = $rew['refnum'];
            $api_key   = $rew['api_key'];
            $reg_date  = $rew['registration_date'];
            $referal   = $rew['referal'];

            $reg_timestamp = strtotime($reg_date);
            $diff = time() - $reg_timestamp;
            $kunlar  = floor($diff / 86400);
            $soatlar = floor(($diff % 86400) / 3600);
            $minutlar = floor(($diff % 3600) / 60);

            $orders_count = 0;
            $ord = mysqli_query($connect, "SELECT COUNT(*) as cnt FROM orders WHERE user_id = '$tartib'");
            if ($ord) $orders_count = mysqli_fetch_assoc($ord)['cnt'];

            $total_spent = 0;
            $spent_q = mysqli_query($connect, "SELECT SUM(price) as total FROM myorder WHERE user_id = '$tartib'");
            if ($spent_q) $total_spent = mysqli_fetch_assoc($spent_q)['total'] ?? 0;

            // ✓ Admin uchun tanlangan user ni bazada saqlaymiz
            set_step($connect, $from_id, "selected_user=$tg_id");

            if ($ban == "active") {
                $bans = "🔕 Banlash"; $ban_txt = "✓ Faol";
            } else {
                $bans = "🔔 Bandan olish"; $ban_txt = "✗ Bloklangan";
            }

            $info_text = "
👤 <b>FOYDALANUVCHI MA'LUMOTLARI</b>
━━━━━━━━━━━━━━━━━━━━
🔢 <b>Tartib raqam:</b> <code>$tartib</code>
🆔 <b>Telegram ID:</b> <a href='tg://user?id=$tg_id'>$tg_id</a>
🔑 <b>Referal kodi:</b> <code>$referal</code>

━━━━━━━━━━━━━━━━━━━━
💰 <b>Balans:</b> <code>$pul</code> so'm
📤 <b>Jami to'ldirgan:</b> <code>$outing</code> so'm
💸 <b>Sarflagan pullari:</b> <code>$total_spent</code> so'm
🛒 <b>Buyurtmalar soni:</b> <code>$orders_count</code> ta
🤝 <b>Taklif qilganlari:</b> <code>$ref_count</code> ta

━━━━━━━━━━━━━━━━━━━━
🔐 <b>API key:</b> <code>$api_key</code>
📅 <b>Ro'yxat sanasi:</b> $reg_date
⏳ <b>Biz bilan:</b> $kunlar kun, $soatlar soat, $minutlar minut
🔰 <b>Holati:</b> $ban_txt
";

            bot('sendMessage', [
                'chat_id'      => $from_id,
                'text'         => $info_text,
                'parse_mode'   => 'html',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => $bans, 'callback_data' => "ban"]],
                        [
                            ['text' => "➕ Pul qo'shish", 'callback_data' => "plus"],
                            ['text' => "➖ Pul ayirish",  'callback_data' => "minus"],
                        ],
                        [['text' => "📜 Buyurtmalar tarixi", 'callback_data' => "user_orders"]],
                    ]
                ])
            ]);
            clear_step($connect, $from_id);
        } else {
            bot('sendMessage', [
                'chat_id'    => $from_id,
                'text'       => "✗ <b>Foydalanuvchi topilmadi.</b>

Qayta urinib ko'ring:",
                'parse_mode' => 'html',
            ]);
        }
    }
    exit();
}

// ==========================================
// TANLANGAN FOYDALANUVCHINI OLISH FUNKSIYASI
// ==========================================

function get_selected_user($connect, $admin_id) {
    $step = get_step($connect, $admin_id);
    if (strpos($step, "selected_user=") === 0) {
        return substr($step, 14);
    }
    return null;
}

// ==========================================
// ➕ PUL QO'SHISH — CALLBACK
// ==========================================

if (isset($data) && $data == "plus") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $saved_tg_id = get_selected_user($connect, $callfrid);
    if (!$saved_tg_id) exit();

    bot('sendMessage', [
        'chat_id'      => $callfrid,
        'text'         => "➕ <a href='tg://user?id=$saved_tg_id'>$saved_tg_id</a> <b>ning hisobiga qancha pul qo'shmoqchisiz?</b>",
        'parse_mode'   => "html",
        'reply_markup' => $aort,
    ]);
    set_step($connect, $callfrid, "plus=$saved_tg_id");
    exit();
}

// PUL QO'SHISH — MESSAGE
if ($step && strpos($step, "plus=") === 0) {
    if ($from_id == $admin) {
        $saved_tg_id = substr($step, 5);

        if (is_numeric(trim($text))) {
            $amount = intval(trim($text));
            $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = '$saved_tg_id'"));

            if (!$rew) {
                bot('sendMessage', ['chat_id' => $from_id, 'text' => "✗ <b>Foydalanuvchi topilmadi.</b>", 'parse_mode' => 'html']);
                clear_step($connect, $from_id);
                exit();
            }

            $miqdor = $rew['balance'] + $amount;
            $p2 = $rew['outing'] + $amount;
            mysqli_query($connect, "UPDATE users SET balance='$miqdor', outing='$p2' WHERE id='$saved_tg_id'");

            add_payment($connect, $saved_tg_id, 'in', $amount, $miqdor, "Admin tomonidan to'ldirildi");

            bot('sendMessage', [
                'chat_id' => $saved_tg_id,
                'text' => "✓ <b>Adminlar tomonidan hisobingiz $amount so'm to'ldirildi!</b>",
                'parse_mode' => "html", 'reply_markup' => $menu,
            ]);
            bot('sendMessage', [
                'chat_id' => $from_id,
                'text' => "✓ <b>Foydalanuvchi hisobiga $amount so'm qo'shildi!</b>",
                'parse_mode' => "html", 'reply_markup' => $panel,
            ]);
            clear_step($connect, $from_id);
        } else {
            bot('sendMessage', ['chat_id' => $from_id, 'text' => "⚠️ <b>Faqat raqam kiriting!</b>", 'parse_mode' => 'html']);
        }
    }
    exit();
}

// ==========================================
// ➖ PUL AYIRISH — CALLBACK
// ==========================================

if (isset($data) && $data == "minus") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $saved_tg_id = get_selected_user($connect, $callfrid);
    if (!$saved_tg_id) exit();

    bot('sendMessage', [
        'chat_id'      => $callfrid,
        'text'         => "➖ <a href='tg://user?id=$saved_tg_id'>$saved_tg_id</a> <b>ning hisobidan qancha pul ayirmoqchisiz?</b>",
        'parse_mode'   => "html",
        'reply_markup' => $aort,
    ]);
    set_step($connect, $callfrid, "minus=$saved_tg_id");
    exit();
}

// PUL AYIRISH — MESSAGE
if ($step && strpos($step, "minus=") === 0) {
    if ($from_id == $admin) {
        $saved_tg_id = substr($step, 6);

        if (is_numeric(trim($text))) {
            $amount = intval(trim($text));
            $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = '$saved_tg_id'"));

            if (!$rew) {
                bot('sendMessage', ['chat_id' => $from_id, 'text' => "✗ <b>Foydalanuvchi topilmadi.</b>", 'parse_mode' => 'html']);
                clear_step($connect, $from_id);
                exit();
            }

            $miqdor = $rew['balance'] - $amount;
            $p2 = $rew['outing'] - $amount;
            mysqli_query($connect, "UPDATE users SET balance='$miqdor', outing='$p2' WHERE id='$saved_tg_id'");

            add_payment($connect, $saved_tg_id, 'out', $amount, $miqdor, "Admin tomonidan ayirildi");

            bot('sendMessage', [
                'chat_id' => $saved_tg_id,
                'text' => "⚠️ <b>Adminlar tomonidan hisobingizdan $amount so'm ayirildi.</b>",
                'parse_mode' => "html", 'reply_markup' => $menu,
            ]);
            bot('sendMessage', [
                'chat_id' => $from_id,
                'text' => "✓ <b>Foydalanuvchi hisobidan $amount so'm ayirildi!</b>",
                'parse_mode' => "html", 'reply_markup' => $panel,
            ]);
            clear_step($connect, $from_id);
        } else {
            bot('sendMessage', ['chat_id' => $from_id, 'text' => "⚠️ <b>Faqat raqam kiriting!</b>", 'parse_mode' => 'html']);
        }
    }
    exit();
}

// ==========================================
// 🔕 BAN / UNBAN — CALLBACK
// ==========================================

if (isset($data) && $data == "ban") {
    $saved_tg_id = get_selected_user($connect, $callfrid);
    if (!$saved_tg_id) {
        bot('answerCallbackQuery', ['callback_query_id' => $qid]);
        exit();
    }

    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = '$saved_tg_id'"));

    if (!$rew) {
        bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "✗ Foydalanuvchi topilmadi!", 'show_alert' => true]);
        exit();
    }

    if ($callfrid != $saved_tg_id) {
        if ($rew['status'] == "deactive") {
            mysqli_query($connect, "UPDATE users SET status='active' WHERE id='$saved_tg_id'");
            bot('sendMessage', ['chat_id' => $callfrid, 'text' => "✓ <b>Foydalanuvchi ($saved_tg_id) bandan olindi!</b>", 'parse_mode' => "html", 'reply_markup' => $panel]);
            bot('sendMessage', ['chat_id' => $saved_tg_id, 'text' => "✓ <b>Hisobingiz tiklandi!</b>", 'parse_mode' => 'html']);
        } else {
            mysqli_query($connect, "UPDATE users SET status='deactive' WHERE id='$saved_tg_id'");
            bot('sendMessage', ['chat_id' => $callfrid, 'text' => "🔕 <b>Foydalanuvchi ($saved_tg_id) banlandi!</b>", 'parse_mode' => "html", 'reply_markup' => $panel]);
            bot('sendMessage', ['chat_id' => $saved_tg_id, 'text' => "✗ <b>Hisobingiz bloklandi.</b>", 'parse_mode' => 'html']);
        }
        bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    } else {
        bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "✗ O'zingizni bloklash mumkin emas!", 'show_alert' => true]);
    }
    exit();
}

// ==========================================
// 🔎 BUYURTMA QIDIRISH
// ==========================================

if ($text == "🔎 Buyurtma" && $cid == $admin) {
    $all_orders      = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM orders"))['cnt'];
    $pending_orders  = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM orders WHERE status = 'Pending'"))['cnt'];
    $progress_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM orders WHERE status = 'In progress'"))['cnt'];
    $completed_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM orders WHERE status = 'Completed'"))['cnt'];
    $canceled_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM orders WHERE status IN ('Canceled','Cancelled','Refunded')"))['cnt'];

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "🔎 <b>BUYURTMALAR BOSHQARUVI</b>
━━━━━━━━━━━━━━━━━━━━

▪ <b>Statistika:</b>
┣ 📦 Jami: <code>$all_orders</code> ta
┣ ⏳ Kutmoqda: <code>$pending_orders</code> ta
┣ 🔄 Jarayonda: <code>$progress_orders</code> ta
┣ ✓ Bajarilgan: <code>$completed_orders</code> ta
┗ ✗ Bekor qilingan: <code>$canceled_orders</code> ta

━━━━━━━━━━━━━━━━━━━━
✍️ <b>Buyurtma ID sini kiriting:</b>",
        'parse_mode' => 'html',
        'reply_markup' => $aort,
    ]);
    set_step($connect, $cid, "order_search");
    exit;
}

// BUYURTMA ID KIRITILDI
if ($step == "order_search" && $cid == $admin) {
    if (!is_numeric(trim($text))) {
        bot('sendMessage', ['chat_id' => $cid, 'text' => "⚠️ <b>Faqat raqam kiriting!</b>", 'parse_mode' => 'html']);
        exit;
    }

    $order_id = intval(trim($text));
    $order = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM orders WHERE order_id = '$order_id'"));

    if (!$order) {
        bot('sendMessage', ['chat_id' => $cid, 'text' => "✗ <b>Buyurtma #$order_id topilmadi!</b>", 'parse_mode' => 'html']);
        exit;
    }

    $provider = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = '{$order['provider']}'"));

    if (!$provider) {
        bot('sendMessage', ['chat_id' => $cid, 'text' => "✗ <b>Provider topilmadi!</b>", 'parse_mode' => 'html', 'reply_markup' => $panel]);
        clear_step($connect, $cid);
        exit;
    }

    $api_response = @file_get_contents($provider['api_url'] . "?key=" . $provider['api_key'] . "&action=status&order=" . $order['api_order']);
    $api_data = json_decode($api_response, true);

    $short_url    = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $provider['api_url']);
    $status_api   = $api_data['status'] ?? "Noma'lum";
    $charge       = $api_data['charge'] ?? "—";
    $start_count  = $api_data['start_count'] ?? "—";
    $remains      = $api_data['remains'] ?? "—";

    $status_emoji = "❓";
    $s = strtolower($status_api);
    if (strpos($s, "completed") !== false) $status_emoji = "✓";
    elseif (strpos($s, "progress") !== false) $status_emoji = "🔄";
    elseif (strpos($s, "pending") !== false) $status_emoji = "⏳";
    elseif (strpos($s, "cancel") !== false || strpos($s, "refund") !== false) $status_emoji = "✗";
    elseif (strpos($s, "partial") !== false) $status_emoji = "⚠️";

    $msg = "🔎 <b>BUYURTMA MA'LUMOTLARI</b>
━━━━━━━━━━━━━━━━━━━━

🆔 <b>Bot ID:</b> <code>$order_id</code>
🔗 <b>API Order:</b> <code>{$order['api_order']}</code>
🌐 <b>Provider:</b> $short_url

━━━━━━━━━━━━━━━━━━━━
$status_emoji <b>Status:</b> <code>$status_api</code>
💰 <b>Narxi:</b> <code>$charge</code>
▪ <b>Boshlang'ich:</b> <code>$start_count</code>
📉 <b>Qoldi:</b> <code>$remains</code>
━━━━━━━━━━━━━━━━━━━━";

    if (!empty($order['link'])) {
        $msg .= "
🔗 <b>Link:</b> <code>{$order['link']}</code>";
    }

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg,
        'parse_mode' => 'html',
        'disable_web_page_preview' => true,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "🔄 Yangilash", 'callback_data' => "ord_refresh_$order_id"],
                    ['text' => "✗ Bekor qilish", 'callback_data' => "ord_cancel_$order_id"],
                ],
                [['text' => "🔄 Sinxronlash", 'callback_data' => "ord_sync_$order_id"]],
                [['text' => "🔎 Boshqa buyurtma", 'callback_data' => "ord_search_again"]],
            ]
        ]),
    ]);
    clear_step($connect, $cid);
    exit;
}

if ($text == "/speed") {
    $start_time = round(microtime(true) * 1000);
    bot('SendMessage', [
        'chat_id' => $cid,
        'text' => "",
        'parse_mode' => 'html',
    ]);
    $end_time = round(microtime(true) * 1000);
    $ping = $end_time - $start_time;
    $d = sms($cid, "<b>⏰ Kuting...</b>", null)->result->message_id;
    sleep(0.5);
    $s = edit($cid, $d, "<b>🤖 Bot</b>", null)->result->message_id;
    sleep(0.5);
    $e = edit($cid, $s, "<b>🤖 Bot tezligi</b>", null)->result->message_id;
    sleep(0.5);
    $se = edit($cid, $e, "<b>🤖 Bot tezligi:</b> $ping", null)->result->message_id;
    sleep(0.5);
    edit($cid, $se, "<b>🤖 Bot tezligi:</b> $ping m/s", null);
}

// ==========================================
// 🔑 API SOZLASH — TO'LIQ KOD (MySQL based)
// ==========================================
// providers jadvali: id | api_url | api_key
// users jadvalida: step ustuni (VARCHAR 255)
// ==========================================

if ($text == "🔑 API sozlash") {
    if ($cid == $admin) {
        // Provayderlar sonini olish
        $prov_count = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM providers"))['cnt'];

        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "🔑 <b>API BOSHQARUV PANELI</b>
━━━━━━━━━━━━━━━━━━━━
📡 Provayderlar soni: <code>$prov_count</code> ta

Quyidagi bo'limlardan birini tanlang:",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "➕ Yangi API qo'shish", 'callback_data' => "api_add"]],
                    [
                        ['text' => "💰 Balanslar", 'callback_data' => "api_balance"],
                        ['text' => "▪ Ro'yxat", 'callback_data' => "api_list"],
                    ],
                    [
                        ['text' => "✏️ Kalit o'zgartirish", 'callback_data' => "api_edit_key"],
                        ['text' => "🔗 URL o'zgartirish", 'callback_data' => "api_edit_url"],
                    ],
                    [['text' => "🗑️ API o'chirish", 'callback_data' => "api_delete"]],
                ]
            ])
        ]);
    }
    exit;
}

// ==========================================
// 🔑 API BOSH MENYUGA QAYTISH (callback)
// ==========================================

if (isset($data) && $data == "api_main") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $prov_count = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM providers"))['cnt'];

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "🔑 <b>API BOSHQARUV PANELI</b>
━━━━━━━━━━━━━━━━━━━━
📡 Provayderlar soni: <code>$prov_count</code> ta

Quyidagi bo'limlardan birini tanlang:",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "➕ Yangi API qo'shish", 'callback_data' => "api_add"]],
                [
                    ['text' => "💰 Balanslar", 'callback_data' => "api_balance"],
                    ['text' => "▪ Ro'yxat", 'callback_data' => "api_list"],
                ],
                [
                    ['text' => "✏️ Kalit o'zgartirish", 'callback_data' => "api_edit_key"],
                    ['text' => "🔗 URL o'zgartirish", 'callback_data' => "api_edit_url"],
                ],
                [['text' => "🗑️ API o'chirish", 'callback_data' => "api_delete"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// ➕ YANGI API QO'SHISH — 1-QADAM: URL so'rash
// ==========================================

if (isset($data) && $data == "api_add") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "➕ <b>YANGI API QO'SHISH</b>
━━━━━━━━━━━━━━━━━━━━

📎 API manzilini yuboring:

<b>Namuna:</b>
<code>https://smm-api.uz/api/v2</code>

⚠️ Manzil <code>https://</code> bilan boshlanishi shart!",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]],
            ]
        ])
    ]);

    set_step($connect, $callfrid, "api_url");
    exit;
}

// ==========================================
// ➕ YANGI API QO'SHISH — 2-QADAM: URL kiritildi
// ==========================================

if ($step == "api_url" && $cid == $admin) {
    $url = trim($text);

    if (stripos($url, "https://") === false) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✗ <b>Noto'g'ri format!</b>

Manzil <code>https://</code> bilan boshlanishi kerak.

<b>Namuna:</b> <code>https://smm-api.uz/api/v2</code>",
            'parse_mode' => 'html',
        ]);
        exit;
    }

    // URL ni step ichida saqlaymiz
    set_step($connect, $cid, "api_key|$url");

    $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $url);

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "✓ <b>API manzil qabul qilindi!</b>

🌐 <code>$short_url</code>
━━━━━━━━━━━━━━━━━━━━

🔑 Endi ushbu saytdan olingan <b>API KEY</b> ni kiriting:",
        'parse_mode' => 'html',
        'disable_web_page_preview' => true,
        'reply_markup' => $aort,
    ]);
    exit;
}

// ==========================================
// ➕ YANGI API QO'SHISH — 3-QADAM: KEY kiritildi
// ==========================================

if ($step && strpos($step, "api_key|") === 0 && $cid == $admin) {
    $api_url = substr($step, 8); // "api_key|" dan keyingi qism = URL
    $api_key = trim($text);

    // API ni tekshirish — balans so'rash
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($api_url . "?key=$api_key&action=balance", false, $ctx);
    $balans = json_decode($response, true);

    if (!$response || isset($balans['error']) || !isset($balans['balance'])) {
        $error = $balans['error'] ?? "Sayt javob bermadi";
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✗ <b>API tekshiruvdan o'tmadi!</b>

⚠️ Xato: <code>$error</code>

Qayta kiriting yoki bekor qiling:",
            'parse_mode' => 'html',
        ]);
        exit;
    }

    // Dublikat tekshirish
    $api_url_esc = mysqli_real_escape_string($connect, $api_url);
    $check = mysqli_query($connect, "SELECT id FROM providers WHERE api_url = '$api_url_esc'");
    if ($check && mysqli_num_rows($check) > 0) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ <b>Bu API allaqachon qo'shilgan!</b>

Kalitni o'zgartirish uchun \"✏️ Kalit o'zgartirish\" bo'limiga o'ting.",
            'parse_mode' => 'html',
            'reply_markup' => $panel,
        ]);
        clear_step($connect, $cid);
        exit;
    }

    // Bazaga qo'shish
    $api_key_esc = mysqli_real_escape_string($connect, $api_key);
    mysqli_query($connect, "INSERT INTO providers (api_url, api_key) VALUES ('$api_url_esc', '$api_key_esc')");

    $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $api_url);
    $bal = $balans['balance'];
    $cur = $balans['currency'] ?? "USD";

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "✓ <b>API MUVAFFAQIYATLI QO'SHILDI!</b>
━━━━━━━━━━━━━━━━━━━━

🌐 <b>Sayt:</b> $short_url
💰 <b>Balans:</b> $bal $cur
🔑 <b>Kalit:</b> <code>" . substr($api_key, 0, 10) . "...</code>

━━━━━━━━━━━━━━━━━━━━
✓ Tayyor! Endi xizmatlarni import qilishingiz mumkin.",
        'parse_mode' => 'html',
        'reply_markup' => $panel,
    ]);
    clear_step($connect, $cid);
    exit;
}

// ==========================================
// ▪ API RO'YXATI
// ==========================================

if (isset($data) && $data == "api_list") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $result = mysqli_query($connect, "SELECT * FROM providers ORDER BY id ASC");
    $count  = mysqli_num_rows($result);

    if ($count == 0) {
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "▪ <b>API RO'YXATI</b>
━━━━━━━━━━━━━━━━━━━━

⚠️ Hech qanday API qo'shilmagan.",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "➕ API qo'shish", 'callback_data' => "api_add"]],
                    [['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]],
                ]
            ])
        ]);
        exit;
    }

    $msg = "▪ <b>API RO'YXATI</b> ($count ta)
━━━━━━━━━━━━━━━━━━━━

";
    $i = 1;

    while ($row = mysqli_fetch_assoc($result)) {
        $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $row['api_url']);
        $short_key = substr($row['api_key'], 0, 10) . "...";
        $msg .= "$i. 🌐 <b>$short_url</b>
";
        $msg .= "   🔑 <code>$short_key</code>
";
        $msg .= "   🆔 ID: <code>{$row['id']}</code>

";
        $i++;
    }

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 💰 API BALANSLAR
// ==========================================

if (isset($data) && $data == "api_balance") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $result = mysqli_query($connect, "SELECT * FROM providers ORDER BY id ASC");
    $count  = mysqli_num_rows($result);

    if ($count == 0) {
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "💰 <b>API BALANSLAR</b>
━━━━━━━━━━━━━━━━━━━━

⚠️ Hech qanday API topilmadi.",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]]]
            ])
        ]);
        exit;
    }

    $msg = "💰 <b>API BALANSLAR</b>
━━━━━━━━━━━━━━━━━━━━

";
    $i = 1;
    $total_usd = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $row['api_url']);

        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $response = @file_get_contents($row['api_url'] . "?key=" . $row['api_key'] . "&action=balance", false, $ctx);
        $bal_data = json_decode($response, true);

        if ($bal_data && isset($bal_data['balance'])) {
            $balance  = number_format(floatval($bal_data['balance']), 2);
            $currency = $bal_data['currency'] ?? "USD";
            $status   = "✓";
            $total_usd += floatval($bal_data['balance']);
        } else {
            $balance  = "Xato";
            $currency = "";
            $status   = "✗";
        }

        $msg .= "$i. $status <b>$short_url</b>
";
        $msg .= "   💰 <code>$balance $currency</code>

";
        $i++;
    }

    $msg .= "━━━━━━━━━━━━━━━━━━━━
";
    $msg .= "💎 <b>Jami:</b> ~ " . number_format($total_usd, 2) . " UZS";

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔄 Yangilash", 'callback_data' => "api_balance"]],
                [['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// ✏️ KALIT O'ZGARTIRISH — PROVIDER TANLASH
// ==========================================

if (isset($data) && $data == "api_edit_key") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $result = mysqli_query($connect, "SELECT * FROM providers ORDER BY id ASC");
    $count  = mysqli_num_rows($result);

    if ($count == 0) {
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "✏️ <b>KALIT O'ZGARTIRISH</b>
━━━━━━━━━━━━━━━━━━━━

⚠️ Provayderlar topilmadi.",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]]]
            ])
        ]);
        exit;
    }

    $msg = "✏️ <b>KALIT O'ZGARTIRISH</b>
━━━━━━━━━━━━━━━━━━━━

Provayderni tanlang:

";
    $buttons = [];
    $i = 1;

    while ($row = mysqli_fetch_assoc($result)) {
        $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $row['api_url']);
        $msg .= "$i. 🌐 <b>$short_url</b>
";
        $buttons[] = ['text' => "$i", 'callback_data' => "api_chkey_" . $row['id']];
        $i++;
    }

    $keyboard = array_chunk($buttons, 3);
    $keyboard[] = [['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]];

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    exit;
}

// ✏️ KALIT — PROVIDER TANLANDI
if (isset($data) && preg_match('/^api_chkey_(\d+)$/', $data, $matches)) {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $provider_id = $matches[1];
    $row = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = '$provider_id'"));

    if (!$row) {
        bot('sendMessage', ['chat_id' => $chat_id, 'text' => "✗ <b>Provider topilmadi!</b>", 'parse_mode' => 'html']);
        exit;
    }

    $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $row['api_url']);

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "✏️ <b>KALIT O'ZGARTIRISH</b>
━━━━━━━━━━━━━━━━━━━━

🌐 <b>Provider:</b> $short_url
🔑 <b>Joriy kalit:</b> <code>" . substr($row['api_key'], 0, 10) . "...</code>

━━━━━━━━━━━━━━━━━━━━
📝 <b>Yangi API kalitni kiriting:</b>",
        'parse_mode' => 'html',
        'reply_markup' => $aort,
    ]);

    set_step($connect, $callfrid, "edit_key|$provider_id");
    exit;
}

// ✏️ KALIT — YANGI KEY KIRITILDI
if ($step && strpos($step, "edit_key|") === 0 && $cid == $admin) {
    $provider_id = substr($step, 9);
    $new_key = trim($text);

    $row = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = '$provider_id'"));

    if (!$row) {
        bot('sendMessage', ['chat_id' => $cid, 'text' => "✗ <b>Provider topilmadi!</b>", 'parse_mode' => 'html', 'reply_markup' => $panel]);
        clear_step($connect, $cid);
        exit;
    }

    // Yangi kalit bilan tekshirish
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($row['api_url'] . "?key=$new_key&action=balance", false, $ctx);
    $bal_data = json_decode($response, true);

    if (!$response || isset($bal_data['error']) || !isset($bal_data['balance'])) {
        $error = $bal_data['error'] ?? "Sayt javob bermadi";
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✗ <b>Kalit tekshiruvdan o'tmadi!</b>

⚠️ Xato: <code>$error</code>

Qayta kiriting yoki bekor qiling:",
            'parse_mode' => 'html',
        ]);
        exit;
    }

    // Yangilash
    $new_key_esc = mysqli_real_escape_string($connect, $new_key);
    mysqli_query($connect, "UPDATE providers SET api_key = '$new_key_esc' WHERE id = '$provider_id'");

    $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $row['api_url']);

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "✓ <b>API KALIT YANGILANDI!</b>
━━━━━━━━━━━━━━━━━━━━

🌐 <b>Provider:</b> $short_url
🔑 <b>Yangi kalit:</b> <code>" . substr($new_key, 0, 10) . "...</code>
💰 <b>Balans:</b> {$bal_data['balance']} {$bal_data['currency']}",
        'parse_mode' => 'html',
        'reply_markup' => $panel,
    ]);
    clear_step($connect, $cid);
    exit;
}

// ==========================================
// 🔗 URL O'ZGARTIRISH — PROVIDER TANLASH
// ==========================================

if (isset($data) && $data == "api_edit_url") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $result = mysqli_query($connect, "SELECT * FROM providers ORDER BY id ASC");
    $count  = mysqli_num_rows($result);

    if ($count == 0) {
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "🔗 <b>URL O'ZGARTIRISH</b>
━━━━━━━━━━━━━━━━━━━━

⚠️ Provayderlar topilmadi.",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]]]
            ])
        ]);
        exit;
    }

    $msg = "🔗 <b>URL O'ZGARTIRISH</b>
━━━━━━━━━━━━━━━━━━━━

Provayderni tanlang:

";
    $buttons = [];
    $i = 1;

    while ($row = mysqli_fetch_assoc($result)) {
        $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $row['api_url']);
        $msg .= "$i. 🌐 <b>$short_url</b>
";
        $buttons[] = ['text' => "$i", 'callback_data' => "api_churl_" . $row['id']];
        $i++;
    }

    $keyboard = array_chunk($buttons, 3);
    $keyboard[] = [['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]];

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    exit;
}

// 🔗 URL — PROVIDER TANLANDI
if (isset($data) && preg_match('/^api_churl_(\d+)$/', $data, $matches)) {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $provider_id = $matches[1];
    $row = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = '$provider_id'"));

    if (!$row) {
        bot('sendMessage', ['chat_id' => $chat_id, 'text' => "✗ <b>Provider topilmadi!</b>", 'parse_mode' => 'html']);
        exit;
    }

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "🔗 <b>URL O'ZGARTIRISH</b>
━━━━━━━━━━━━━━━━━━━━

🌐 <b>Joriy URL:</b>
<code>{$row['api_url']}</code>

━━━━━━━━━━━━━━━━━━━━
📝 <b>Yangi API URL manzilini kiriting:</b>

<i>Namuna: https://smmworld.com/api/v1</i>",
        'parse_mode' => 'html',
        'reply_markup' => $aort,
    ]);

    set_step($connect, $callfrid, "edit_url|$provider_id");
    exit;
}

// 🔗 URL — YANGI URL KIRITILDI
if ($step && strpos($step, "edit_url|") === 0 && $cid == $admin) {
    $provider_id = substr($step, 9);
    $new_url = trim($text);

    if (stripos($new_url, "https://") === false) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✗ <b>Noto'g'ri format!</b>

URL <code>https://</code> bilan boshlanishi kerak.",
            'parse_mode' => 'html',
        ]);
        exit;
    }

    $row = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = '$provider_id'"));

    if (!$row) {
        bot('sendMessage', ['chat_id' => $cid, 'text' => "✗ <b>Provider topilmadi!</b>", 'parse_mode' => 'html', 'reply_markup' => $panel]);
        clear_step($connect, $cid);
        exit;
    }

    // Yangi URL bilan tekshirish
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents($new_url . "?key=" . $row['api_key'] . "&action=balance", false, $ctx);
    $bal_data = json_decode($response, true);

    if (!$response || isset($bal_data['error']) || !isset($bal_data['balance'])) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✗ <b>Yangi URL tekshiruvdan o'tmadi!</b>

⚠️ Sayt javob bermadi yoki kalit mos kelmadi.

Qayta kiriting yoki bekor qiling:",
            'parse_mode' => 'html',
        ]);
        exit;
    }

    $new_url_esc = mysqli_real_escape_string($connect, $new_url);
    mysqli_query($connect, "UPDATE providers SET api_url = '$new_url_esc' WHERE id = '$provider_id'");

    $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $new_url);

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "✓ <b>API URL YANGILANDI!</b>
━━━━━━━━━━━━━━━━━━━━

🌐 <b>Yangi:</b> $short_url
💰 <b>Balans:</b> {$bal_data['balance']} {$bal_data['currency']}",
        'parse_mode' => 'html',
        'reply_markup' => $panel,
    ]);
    clear_step($connect, $cid);
    exit;
}

// ==========================================
// 🗑️ API O'CHIRISH — PROVIDER TANLASH
// ==========================================

if (isset($data) && $data == "api_delete") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $result = mysqli_query($connect, "SELECT * FROM providers ORDER BY id ASC");
    $count  = mysqli_num_rows($result);

    if ($count == 0) {
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "🗑️ <b>API O'CHIRISH</b>
━━━━━━━━━━━━━━━━━━━━

⚠️ Provayderlar topilmadi.",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]]]
            ])
        ]);
        exit;
    }

    $msg = "🗑️ <b>API O'CHIRISH</b>
━━━━━━━━━━━━━━━━━━━━

⚠️ O'chirilgan API qayta tiklanmaydi!

Provayderni tanlang:

";
    $buttons = [];
    $i = 1;

    while ($row = mysqli_fetch_assoc($result)) {
        $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $row['api_url']);
        $msg .= "$i. 🌐 <b>$short_url</b>
";
        $buttons[] = ['text' => "🗑️ $i", 'callback_data' => "api_del_" . $row['id']];
        $i++;
    }

    $keyboard = array_chunk($buttons, 3);
    $keyboard[] = [['text' => "⬅️ Orqaga", 'callback_data' => "api_main"]];

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    exit;
}

// 🗑️ API O'CHIRISH — TASDIQLASH
if (isset($data) && preg_match('/^api_del_(\d+)$/', $data, $matches)) {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $provider_id = $matches[1];
    $row = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = '$provider_id'"));

    if (!$row) {
        bot('editMessageText', [
            'chat_id' => $chat_id, 'message_id' => $message_id,
            'text' => "✗ <b>Provider topilmadi!</b>", 'parse_mode' => 'html',
        ]);
        exit;
    }

    $short_url = str_replace(["https://", "/api/v1", "/api/v2", "/api/adapter/default/index"], ["", "", "", ""], $row['api_url']);
    $svc_count = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as cnt FROM services WHERE api_service = '$provider_id'"))['cnt'] ?? 0;

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "⚠️ <b>TASDIQLASH</b>
━━━━━━━━━━━━━━━━━━━━

🌐 <b>Provider:</b> $short_url
🛍️ <b>Xizmatlar:</b> $svc_count ta

❗ Provider va barcha xizmatlari o'chiriladi!

<b>Rostdan o'chirmoqchimisiz?</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "✓ Ha, o'chirish", 'callback_data' => "api_del_yes_$provider_id"],
                    ['text' => "✗ Yo'q", 'callback_data' => "api_main"],
                ],
            ]
        ])
    ]);
    exit;
}

// 🗑️ API O'CHIRISH — TASDIQLANDI
if (isset($data) && preg_match('/^api_del_yes_(\d+)$/', $data, $matches)) {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $provider_id = mysqli_real_escape_string($connect, $matches[1]);

    mysqli_query($connect, "DELETE FROM providers WHERE id = '$provider_id'");
    mysqli_query($connect, "DELETE FROM services WHERE api_service = '$provider_id'");

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "✓ <b>Provider muvaffaqiyatli o'chirildi!</b>

🗑️ Barcha xizmatlari ham o'chirildi.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ API paneliga qaytish", 'callback_data' => "api_main"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 💎 VERTUAL XIZMATLAR / BOT HAQIDA
// ==========================================

if (isset($text) && $text == "💎 Vertual xizmatlar" && joinchat($cid) == "true") {
    bot('sendPhoto', [
        'chat_id' => $cid,
        'photo' => "https://t.me/SmmGlobalRasmlari/89",
        'caption' => "<b>💎 @$bot — Vertual xizmatlar

Quyidagi bo'limlardan birini tanlang 👇</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🗂 Buyurtma berish", 'callback_data' => "info_orders"], ['text' => "📞 Nomerlar olish", 'callback_data' => "info_numbers"]],
                [['text' => "🤝 Referal tizimi", 'callback_data' => "info_referal"], ['text' => "💵 Hisob to'ldirish", 'callback_data' => "info_deposit"]],
                [['text' => "🔑 Hamkorlik (API)", 'callback_data' => "info_api"], ['text' => "🌟 Premium olish", 'callback_data' => "info_premium"]],
                [['text' => "📜 Bot qoidalari", 'callback_data' => "info_rules"]],
            ]
        ])
    ]);
    exit;
}

// ⬅️ ORQAGA — BOSH SAHIFA
if (isset($data) && $data == "info_back") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendPhoto', [
        'chat_id' => $cid2,
        'photo' => "https://t.me/SmmGlobalRasmlari/89",
        'caption' => "<b>💎 @$bot — Vertual xizmatlar

Quyidagi bo'limlardan birini tanlang 👇</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🗂 Buyurtma berish", 'callback_data' => "info_orders"], ['text' => "📞 Nomerlar olish", 'callback_data' => "info_numbers"]],
                [['text' => "🤝 Referal tizimi", 'callback_data' => "info_referal"], ['text' => "💵 Hisob to'ldirish", 'callback_data' => "info_deposit"]],
                [['text' => "🔑 Hamkorlik (API)", 'callback_data' => "info_api"], ['text' => "🌟 Premium olish", 'callback_data' => "info_premium"]],
                [['text' => "📜 Bot qoidalari", 'callback_data' => "info_rules"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 🗂 BUYURTMA BERISH — QO'LLANMA
// ==========================================

if (isset($data) && $data == "info_orders") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "🗂 <b>Buyurtma berish qo'llanmasi</b>

Botdan buyurtma berish juda oson. Quyidagi qadamlarni bajaring:

<b>▪ Qadamlar:</b>

1️⃣ Asosiy menyudan \"🗂 Xizmatlarga buyurtma berish\" tugmasini bosing
2️⃣ Ijtimoiy tarmoqni tanlang (Instagram, Telegram, TikTok va h.k.)
3️⃣ Kerakli xizmat turini tanlang (like, follower, view)
4️⃣ Xizmat tarifini tanlang
5️⃣ Havolani kiriting
6️⃣ Miqdorni kiriting
7️⃣ Buyurtmani tasdiqlang

⚠️ <b>Muhim:</b>
• Havolani to'g'ri kiriting
• Profilingiz ochiq (public) bo'lishi kerak
• Noto'g'ri havola kiritilsa pul qaytarilmasligi mumkin

✓ @$bot",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Orqaga", 'callback_data' => "info_back"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 📞 NOMERLAR OLISH
// ==========================================

if (isset($data) && $data == "info_numbers") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "📞 <b>Virtual nomerlar xizmati</b>

Virtual telefon raqamlarini faqat admin orqali sotib olishingiz mumkin.

▪ <b>Qanday ishlaydi:</b>

1️⃣ Admin bilan bog'laning
2️⃣ Kerakli mamlakat va xizmatni ayting
3️⃣ Narxi bot balansingizdan yechib olinadi
4️⃣ Nomer va kod sizga yuboriladi

💰 <b>Narxlar:</b> Mamlakat va xizmatga qarab farq qiladi

⚠️ Nomer olishdan oldin balansingizda yetarli mablag' borligiga ishonch hosil qiling.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "📞 Admin bilan bog'lanish", 'url' => "https://t.me/SFXSMMHelp"]],
                [['text' => "⬅️ Orqaga", 'callback_data' => "info_back"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 🤝 REFERAL TIZIMI
// ==========================================

if (isset($data) && $data == "info_referal") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "🤝 <b>Referal tizimi — bepul pul ishlang!</b>

Do'stlaringizni botga taklif qiling va har bir taklif uchun bonus oling.

▪ <b>Qanday ishlaydi:</b>

1️⃣ Asosiy menyudan \"🚀 Mablag' yig'ish\" tugmasini bosing
2️⃣ Sizga maxsus taklif havolasi beriladi
3️⃣ Havolani do'stlaringizga yuboring
4️⃣ Do'stingiz botga kirishi bilan bonus hisobingizga tushadi

💰 <b>Qancha pul ishlash mumkin?</b>
Har bir taklif uchun referal narxida ko'rsatilgan summa beriladi. Cheklov yo'q — qancha ko'p taklif qilsangiz, shuncha ko'p ishlaysiz!

⚠️ <b>Eslatma:</b> Ishlangan pullarni yechib olish imkoni yo'q. Faqat bot ichida xizmatlarga sarflash mumkin.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Orqaga", 'callback_data' => "info_back"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 💵 HISOB TO'LDIRISH
// ==========================================

if (isset($data) && $data == "info_deposit") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "💵 <b>Hisob to'ldirish qo'llanmasi</b>

Bot hisobingizni to'ldirish juda oson va tez.

▪ <b>Qadamlar:</b>

1️⃣ Asosiy menyudan \"💵 Hisob to'ldirish\" tugmasini bosing
2️⃣ To'lov tizimini tanlang (Click, Payme, Uzum va h.k.)
3️⃣ Ko'rsatilgan karta raqamiga pul o'tkazing
4️⃣ To'lov chekining screenshotini yuboring
5️⃣ Hisobingiz 5-15 daqiqa ichida to'ldiriladi

💡 <b>Muhim eslatmalar:</b>

• To'lov miqdoriga diqqat bilan qarang
• Screenshot aniq va ravshan bo'lishi kerak
• Kiritilgan pullarni qaytarib olish imkoni yo'q
• Noto'g'ri summa kiritilsa admin bilan bog'laning",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Orqaga", 'callback_data' => "info_back"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 🔑 HAMKORLIK (API)
// ==========================================

if (isset($data) && $data == "info_api") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    $api_url = $_SERVER['HTTP_HOST'] . "/api/v2";

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "🔑 <b>API — Hamkorlik qo'llanmasi</b>

Bizning API orqali o'z botingiz yoki saytingizga xizmatlarni ulashingiz mumkin.

▪ <b>API ni qanday olish:</b>

1️⃣ \"🔑 /api \" bo'limiga kiring
2️⃣ \"API kalitni olish\" tugmasini bosing
3️⃣ Sizga maxsus kalit beriladi

🌐 <b>API manzil:</b>
<code>$api_url</code>

📖 <b>Imkoniyatlar:</b>
• Buyurtma berish
• Buyurtma holatini tekshirish
• Balansni ko'rish
• Xizmatlar ro'yxatini olish

⚠️ <b>Muhim:</b>
• API kalitni hech kimga bermang
• Kalit orqali balansingizdagi pullarni boshqarish mumkin
• Muammo bo'lsa admin bilan bog'laning",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Orqaga", 'callback_data' => "info_back"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 🌟 PREMIUM OLISH
// ==========================================

if (isset($data) && $data == "info_premium") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "🌟 <b>Premium obuna</b>

Premium foydalanuvchilar uchun maxsus imkoniyatlar:

💎 <b>Premium afzalliklari:</b>

• Barcha xizmatlarga chegirma
• Tezroq buyurtma bajarilishi
• Maxsus xizmatlar faqat premium uchun
• Ustuvor yordam (tez javob)

▪ <b>Tariflar:</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🌟 1 oy — 45 000 so'm", 'url' => "tg://user?id=$admin"]],
                [['text' => "🌟 3 oy — 170 000 so'm (12% tejash)", 'url' => "tg://user?id=$admin"]],
                [['text' => "🌟 6 oy — 225 000 so'm (17% tejash)", 'url' => "tg://user?id=$admin"]],
                [['text' => "🌟 12 oy — 300 000 so'm (45% tejash)", 'url' => "tg://user?id=$admin"]],
                [['text' => "⬅️ Orqaga", 'callback_data' => "info_back"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 📜 BOT QOIDALARI
// ==========================================

if (isset($data) && $data == "info_rules") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "📜 <b>Bot qoidalari</b>

Botdan foydalanishdan oldin quyidagi qoidalar bilan tanishib chiqing:

⛔ <b>Taqiqlanadi:</b>

1️⃣ Adminga yolg'on yoki soxta ma'lumot yuborish
     ➜ Jazosi: Botdan ban olish

2️⃣ Yordam bo'limida adminga haqorat qilish
     ➜ Jazosi: Botdan ban olish

3️⃣ Bajarilayotgan buyurtma linkiga qayta buyurtma berish
     ➜ Jazosi: Pul qaytarilmaydi

4️⃣ Admindan tekinga hisob to'ldirishni so'rash
     ➜ So'rov ko'rib chiqilmaydi

⚠️ <b>Muhim qoidalar:</b>

5️⃣ Kiritilgan pullar qaytarib berilmaydi
     ➜ To'lov qilishdan oldin summani tekshiring

6️⃣ Buyurtma berishda havolani to'g'ri kiriting
     ➜ Noto'g'ri havola uchun pul qaytarilmaydi

7️⃣ Profil ochiq (public) bo'lishi shart
     ➜ Yopiq profildagi buyurtma bekor qilinadi

💡 Qoidalarga rioya qilgan holda botdan foydalaning. Muammolar bo'lsa — yordam bo'limiga murojaat qiling.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Orqaga", 'callback_data' => "info_back"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 🔑 HAMKORLIK (API)
// ==========================================

if (isset($text) && $text == "/api" && joinchat($cid) == 1) {
    bot('sendPhoto', [
        'chat_id' => $cid,
        'photo' => "https://t.me/SmmGlobalRasmlari/28",
        'caption' => "<b>🔑 Hamkorlik (API) bo'limi

Sizning ham botingiz bormi? Bizning xizmatlarimizni o'z botingizga ulang va daromad ishlang!

💎 Hamkorlar uchun 5-15% gacha chegirmalar mavjud.

Quyidagilardan birini tanlang 👇</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔑 API kalitni olish", 'callback_data' => "api_getkey"]],
                [['text' => "📖 Qo'llanma", 'callback_data' => "api_guide"]],
            ]
        ])
    ]);
    exit;
}

// ⬅️ ORQAGA — API BOSH SAHIFA
if (isset($data) && $data == "api_home") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendPhoto', [
        'chat_id' => $cid2,
        'photo' => "https://t.me/SmmGlobalRasmlari/28",
        'caption' => "<b>🔑 Hamkorlik (API) bo'limi

Sizning ham botingiz bormi? Bizning xizmatlarimizni o'z botingizga ulang va daromad ishlang!

💎 Hamkorlar uchun 5-15% gacha chegirmalar mavjud.

Quyidagilardan birini tanlang 👇</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔑 API kalitni olish", 'callback_data' => "api_getkey"]],
                [['text' => "📖 Qo'llanma", 'callback_data' => "api_guide"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 🔑 API KALITNI OLISH
// ==========================================

if (isset($data) && $data == "api_getkey") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    $result = mysqli_query($connect, "SELECT * FROM users WHERE id = '$cid2'");
    $rew = mysqli_fetch_assoc($result);

    if (!$rew) {
        bot('sendMessage', ['chat_id' => $cid2, 'text' => "✗ Xatolik!", 'parse_mode' => 'html']);
        exit;
    }

    $full_key = $rew['api_key'];
    $hidden_key = substr($full_key, 0, 8) . "••••••••" . substr($full_key, -6);
    $balance = number_format($rew['balance'], 0, '', ' ');
    $api_url = "https://" . $_SERVER['HTTP_HOST'] . "/api/v2";

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "🔑 <b>Sizning API ma'lumotlaringiz</b>

🌐 <b>API manzil:</b>
<code>$api_url</code>

🔑 <b>API kalit:</b>
<code>$hidden_key</code>

💰 <b>API balans:</b> $balance so'm

⚠️ API kalitni hech kimga bermang! Agar kalit boshqalar qo'liga tushgan bo'lsa — darhol yangilang.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
          'inline_keyboard' => [
              [['text' => "▪ Kalitni to'liq nusxalash", 'copy_text' => ['text' => $full_key]]],
              [['text' => "🌐 API manzilni nusxalash", 'copy_text' => ['text' => $api_url]]],
              [['text' => "▪ Statistika", 'callback_data' => "api_stats"]],
              [['text' => ($rew['api_status'] ?? 'active') == 'paused' ? "▶️ API ni yoqish" : "⏸ API ni to'xtatish", 'callback_data' => "api_toggle"]],
              [['text' => "🔄 Yangi kalit olish", 'callback_data' => "api_newkey"]],
              [['text' => "📖 Qo'llanma", 'callback_data' => "api_methods"]],
              [['text' => "⬅️ Orqaga", 'callback_data' => "api_home"]],
          ]
      ])
    ]);
    exit;
}

// ==========================================
// 🔄 YANGI API KALIT OLISH — TASDIQLASH
// ==========================================

if (isset($data) && $data == "api_newkey") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "🔄 <b>API kalitni yangilash</b>

⚠️ Diqqat! Yangi kalit olgandan keyin eski kalit ishlamay qoladi.

Agar eski kalitni boshqa bot yoki saytga ulagan bo'lsangiz — u yerda ham yangilashingiz kerak bo'ladi.

Davom etasizmi?",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "✓ Ha, yangilash", 'callback_data' => "api_newkey_yes"],
                    ['text' => "✗ Bekor", 'callback_data' => "api_getkey"],
                ],
            ]
        ])
    ]);
    exit;
}

// 🔄 YANGI KALIT — TASDIQLANDI
if (isset($data) && $data == "api_newkey_yes") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "🔄 Yangilanmoqda..."]);

    $newkey = md5(uniqid(rand(), true));
    $cid_esc = mysqli_real_escape_string($connect, $chat_id);
    mysqli_query($connect, "UPDATE users SET api_key = '$newkey' WHERE id = '$cid_esc'");

    $hidden_key = substr($newkey, 0, 8) . "••••••••" . substr($newkey, -6);

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "✓ <b>API kalit muvaffaqiyatli yangilandi!</b>

🔑 <b>Yangi kalit:</b>
<code>$hidden_key</code>

⚠️ Eski kalit endi ishlamaydi. Yangi kalitni bot yoki saytingizga qo'yishni unutmang.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "▪ Yangi kalitni nusxalash", 'copy_text' => ['text' => $newkey]]],
                [['text' => "🔑 API ma'lumotlarim", 'callback_data' => "api_getkey"]],
                [['text' => "⬅️ Orqaga", 'callback_data' => "api_home"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 📖 QO'LLANMA — RASM BILAN
// ==========================================

if (isset($data) && $data == "api_guide") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendPhoto', [
        'chat_id' => $cid2,
        'photo' => "https://t.me/SmmGlobalRasmlari/29",
        'caption' => "<b>📖 API qo'llanma

API nima?
Botimizdagi barcha xizmatlarni siz ham o'z botingiz yoki saytingizga ulab ishlatishingiz mumkin. Bu tizim xavfsiz, oson va qulay.

🔗 API manzil: " . $_SERVER['HTTP_HOST'] . "/api/v2

⚠️ Muhim: API kalitni begona kishilarga bermang. Agar kalit boshqalar qo'liga tushsa — tezda yangilang. Begona orqali berilgan buyurtmalar uchun pul qaytarilmaydi.</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "▪ API metodlari", 'callback_data' => "api_methods"]],
                [['text' => "🔑 API kalitni olish", 'callback_data' => "api_getkey"]],
                [['text' => "⬅️ Orqaga", 'callback_data' => "api_home"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// ▪ API STATISTIKA
// ==========================================

if (isset($data) && $data == "api_stats") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $user = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = '$chat_id'"));
    $user_uid = $user['user_id']; // tartib raqam

    // API orqali berilgan buyurtmalar
    $total_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$user_uid'"))['c'] ?? 0;
    $completed_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$user_uid' AND status = 'Completed'"))['c'] ?? 0;
    $pending_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$user_uid' AND status IN ('Pending','In progress','Processing')"))['c'] ?? 0;
    $canceled_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$user_uid' AND status IN ('Canceled','Failed')"))['c'] ?? 0;

    // Jami sarflangan
    $total_spent = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(retail) as s FROM myorder WHERE user_id = '$user_uid'"))['s'] ?? 0;
    $today_spent = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(retail) as s FROM myorder WHERE user_id = '$user_uid' AND DATE(order_create) = CURDATE()"))['s'] ?? 0;
    $month_spent = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(retail) as s FROM myorder WHERE user_id = '$user_uid' AND MONTH(order_create) = MONTH(CURDATE()) AND YEAR(order_create) = YEAR(CURDATE())"))['s'] ?? 0;

    // Bugungi buyurtmalar
    $today_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$user_uid' AND DATE(order_create) = CURDATE()"))['c'] ?? 0;

    // Format
    $total_spent_f = number_format($total_spent, 0, '', ' ');
    $today_spent_f = number_format($today_spent, 0, '', ' ');
    $month_spent_f = number_format($month_spent, 0, '', ' ');
    $balance_f = number_format($user['balance'], 0, '', ' ');

    // API holati
    $api_status = (isset($user['api_status']) && $user['api_status'] == 'paused') ? "⏸ To'xtatilgan" : "✓ Faol";

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "▪ <b>API statistikasi</b>

📌 <b>API holati:</b> $api_status
💰 <b>Joriy balans:</b> $balance_f so'm

<blockquote>📦 <b>Buyurtmalar:</b>

Jami buyurtmalar:  <b>$total_orders</b> ta
Bugungi buyurtmalar:  <b>$today_orders</b> ta
Bajarilgan:  <b>$completed_orders</b> ta
Jarayonda:  <b>$pending_orders</b> ta
Bekor/xato:  <b>$canceled_orders</b> ta</blockquote>

<blockquote>💰 <b>Sarflar:</b>

Bugungi sarf:  <b>$today_spent_f</b> so'm
Shu oydagi sarf:  <b>$month_spent_f</b> so'm
Jami sarflangan:  <b>$total_spent_f</b> so'm</blockquote>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔄 Yangilash", 'callback_data' => "api_stats"]],
                [['text' => "🔑 API ma'lumotlarim", 'callback_data' => "api_getkey"]],
                [['text' => "⬅️ Orqaga", 'callback_data' => "api_home"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// ⏸ API NI TO'XTATISH / YOQISH
// ==========================================

if (isset($data) && $data == "api_toggle") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $user = mysqli_fetch_assoc(mysqli_query($connect, "SELECT api_status FROM users WHERE id = '$chat_id'"));
    $current = (isset($user['api_status']) && $user['api_status'] == 'paused') ? 'paused' : 'active';

    if ($current == 'active') {
        // To'xtatish uchun tasdiqlash
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "⏸ <b>API ni to'xtatish</b>

API to'xtatilganda:

• Hech kim sizning kalit orqali buyurtma bera olmaydi
• Balansdan pul yechilmaydi
• Mavjud buyurtmalarga ta'sir qilmaydi
• Istagan vaqtda qayta yoqishingiz mumkin

API ni to'xtatmoqchimisiz?",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => "⏸ Ha, to'xtatish", 'callback_data' => "api_pause_yes"],
                        ['text' => "✗ Bekor", 'callback_data' => "api_getkey"],
                    ],
                ]
            ])
        ]);
    } else {
        // Yoqish uchun tasdiqlash
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "▶️ <b>API ni yoqish</b>

API yoqilgandan keyin kalit orqali buyurtma berish imkoni qayta tiklanadi.

API ni yoqmoqchimisiz?",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => "▶️ Ha, yoqish", 'callback_data' => "api_resume_yes"],
                        ['text' => "✗ Bekor", 'callback_data' => "api_getkey"],
                    ],
                ]
            ])
        ]);
    }
    exit;
}

// ⏸ TO'XTATISH TASDIQLANDI
if (isset($data) && $data == "api_pause_yes") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "⏸ API to'xtatildi!"]);

    $cid_esc = mysqli_real_escape_string($connect, $chat_id);
    mysqli_query($connect, "UPDATE users SET api_status = 'paused' WHERE id = '$cid_esc'");

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "⏸ <b>API to'xtatildi!</b>

Sizning API kalitingiz orqali endi hech kim buyurtma bera olmaydi.

Qayta yoqish uchun pastdagi tugmani bosing.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "▶️ API ni yoqish", 'callback_data' => "api_toggle"]],
                [['text' => "🔑 API ma'lumotlarim", 'callback_data' => "api_getkey"]],
                [['text' => "⬅️ Orqaga", 'callback_data' => "api_home"]],
            ]
        ])
    ]);
    exit;
}

// ▶️ YOQISH TASDIQLANDI
if (isset($data) && $data == "api_resume_yes") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "▶️ API yoqildi!"]);

    $cid_esc = mysqli_real_escape_string($connect, $chat_id);
    mysqli_query($connect, "UPDATE users SET api_status = 'active' WHERE id = '$cid_esc'");

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "▶️ <b>API qayta yoqildi!</b>

Sizning API kalitingiz orqali endi buyurtma berish mumkin.

Yaxshi savdo tilaymiz! →",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔑 API ma'lumotlarim", 'callback_data' => "api_getkey"]],
                [['text' => "⬅️ Orqaga", 'callback_data' => "api_home"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// ▪ API METODLARI — YANGI FUNKSIYA
// ==========================================

if (isset($data) && $data == "api_methods") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $api_url = "https://" . $_SERVER['HTTP_HOST'] . "/api/v2";

    if (isset($mid2)) {
        bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);
    }

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "▪ <b>API metodlari</b>

🌐 <b>Manzil:</b> <code>$api_url</code>

Barcha so'rovlar <b>POST</b> yoki <b>GET</b> orqali yuboriladi.
Har bir so'rovda <code>key</code> parametri shart.

━━━━━━━━━━━━━━━━━━━━━━
💰 <b>Balansni ko'rish:</b>
<code>?key=API_KEY&action=balance</code>

▪ <b>Xizmatlar ro'yxati:</b>
<code>?key=API_KEY&action=services</code>

🛒 <b>Buyurtma berish:</b>
<code>?key=API_KEY&action=add&service=ID&link=LINK&quantity=100</code>

▪ <b>Buyurtma holati:</b>
<code>?key=API_KEY&action=status&order=ORDER_ID</code>

✗ <b>Buyurtma bekor qilish:</b>
<code>?key=API_KEY&action=cancel&order=ORDER_ID</code>

━━━━━━━━━━━━━━━━━━━━━━
💡 <b>Misol (balans):</b>
<code>{$api_url}?key=sizning_kalit&action=balance</code>

📎 <b>Javob:</b>
<code>{\"balance\":\"15000\",\"currency\":\"UZS\"}</code>",
        'parse_mode' => 'html',
        'disable_web_page_preview' => true,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🌐 API manzilni nusxalash", 'copy_text' => ['text' => $api_url]]],
                [['text' => "🔑 API kalitni olish", 'callback_data' => "api_getkey"]],
                [['text' => "⬅️ Orqaga", 'callback_data' => "api_home"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 📕 QO'LLANMA
// ==========================================

if (isset($text) && ($text == "/qollanma" || $text == "📕 Qo'llanma")) {
    bot('sendPhoto', [
        'chat_id' => $cid,
        'photo' => "https://t.me/SmmGlobalRasmlari/90",
        'caption' => "<b>📕 @$bot dan foydalanish qo'llanmasi

📜 Botdan to'g'ri foydalanish uchun quyidagi qoidalar bilan tanishib chiqing:

<blockquote>1️⃣ Buyurtma bekor qilinsa — pul hisobingizga avtomatik qaytariladi.

2️⃣ To'lov qilgandan so'ng pullar 5-15 daqiqa ichida hisobingizga tushadi.

3️⃣ Botga kiritilgan pullar qaytarib berilmaydi.

4️⃣ Bitta havolaga bir vaqtda faqat bitta xizmatdan buyurtma berish mumkin.

5️⃣ Referal orqali taklif qilgan odamingiz majburiy kanalga a'zo bo'lmasa — bonus berilmaydi.

6️⃣ Noto'g'ri havola kiritilsa yoki profil yopiq bo'lsa — pul qaytarilmaydi.</blockquote>

⌨️ Foydali buyruqlar:

/start — Botni qayta ishga tushirish
/api — Hamkorlik (API) bo'limi
/qollanma — Qo'llanma
/baholash — Botni baholash

❗️ Xatolik topdingizmi? → @SFXSMMHelp</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🗂 Xizmatlardan foydalanish", 'callback_data' => "guide_services"]],
                [['text' => "💵 Hisob to'ldirish", 'callback_data' => "guide_deposit"]],
                [['text' => "🤝 Referal tizimi", 'callback_data' => "guide_referal"]],
                [['text' => "📦 Buyurtma holati", 'callback_data' => "guide_status"]],
            ]
        ])
    ]);
    exit;
}

// ⬅️ ORQAGA — QO'LLANMA BOSH SAHIFA
if (isset($data) && $data == "guide_back") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);

    bot('sendPhoto', [
        'chat_id' => $chat_id,
        'photo' => "https://t.me/SmmGlobalRasmlari/90",
        'caption' => "<b>📕 @$bot dan foydalanish qo'llanmasi

📜 Botdan to'g'ri foydalanish uchun quyidagi qoidalar bilan tanishib chiqing:

<blockquote>1️⃣ Buyurtma bekor qilinsa — pul hisobingizga avtomatik qaytariladi.

2️⃣ To'lov qilgandan so'ng pullar 5-15 daqiqa ichida hisobingizga tushadi.

3️⃣ Botga kiritilgan pullar qaytarib berilmaydi.

4️⃣ Bitta havolaga bir vaqtda faqat bitta xizmatdan buyurtma berish mumkin.

5️⃣ Referal orqali taklif qilgan odamingiz majburiy kanalga a'zo bo'lmasa — bonus berilmaydi.

6️⃣ Noto'g'ri havola kiritilsa yoki profil yopiq bo'lsa — pul qaytarilmaydi.</blockquote>

⌨️ Foydali buyruqlar:

/start — Botni qayta ishga tushirish
/api — Hamkorlik (API) bo'limi
/qollanma — Qo'llanma
/baholash — Botni baholash

❗️ Xatolik topdingizmi? → @SFXSMMHelp</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🗂 Xizmatlardan foydalanish", 'callback_data' => "guide_services"]],
                [['text' => "💵 Hisob to'ldirish", 'callback_data' => "guide_deposit"]],
                [['text' => "🤝 Referal tizimi", 'callback_data' => "guide_referal"]],
                [['text' => "📦 Buyurtma holati", 'callback_data' => "guide_status"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 🗂 XIZMATLARDAN FOYDALANISH
// ==========================================

if (isset($data) && $data == "guide_services") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);

    bot('sendVideo', [
        'chat_id' => $chat_id,
        'video' => "https://t.me/SmmGlobalRasmlari/42",
        'caption' => "🗂 <b>Xizmatlardan foydalanish qo'llanmasi</b>

Buyurtma berish qadamlari:

1️⃣ \"🗂 Xizmatlarga buyurtma berish\" tugmasini bosing
2️⃣ Ijtimoiy tarmoqni tanlang
3️⃣ Kerakli xizmat turini tanlang
4️⃣ Tarifni tanlang va ma'lumotlarni o'qing
5️⃣ Havolani kiriting (to'g'ri kiritganingizni tekshiring!)
6️⃣ Miqdorni kiriting
7️⃣ Buyurtmani tasdiqlang

⚠️ <b>Eslatma:</b>
• Profil ochiq (public) bo'lishi shart
• Havolani to'g'ri kiriting
• Minimal miqdorga e'tibor bering",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "📞 Admin", 'url' => "https://t.me/SFXSMMHelp"],
                    ['text' => "⬅️ Orqaga", 'callback_data' => "guide_back"],
                ]
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 💵 HISOB TO'LDIRISH
// ==========================================

if (isset($data) && $data == "guide_deposit") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);

    bot('sendVideo', [
        'chat_id' => $chat_id,
        'video' => "https://t.me/SmmGlobalRasmlari/55",
        'caption' => "💵 <b>Hisob to'ldirish qo'llanmasi</b>

Balans to'ldirish qadamlari:

1️⃣ \"💳 Pul kiritish\" tugmasini bosing
2️⃣ To'lov tizimini tanlang (Click, Payme va h.k.)
3️⃣ Ko'rsatilgan karta raqamiga pul o'tkazing
4️⃣ To'lov chekining screenshotini yuboring
5️⃣ 5-15 daqiqa ichida balans to'ldiriladi

⚠️ <b>Eslatma:</b>
• To'lov summasini tekshirib kiriting
• Screenshot aniq va ravshan bo'lsin
• Kiritilgan pul qaytarilmaydi
• Muammo bo'lsa adminga yozing",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "📞 Admin", 'url' => "https://t.me/SFXSMMHelp"],
                    ['text' => "⬅️ Orqaga", 'callback_data' => "guide_back"],
                ]
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 🤝 REFERAL TIZIMI — YANGI
// ==========================================

if (isset($data) && $data == "guide_referal") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "🤝 <b>Referal tizimi qo'llanmasi</b>

Do'stlaringizni taklif qiling — bepul pul ishlang!

▪ <b>Qanday ishlaydi:</b>

1️⃣ Asosiy menyudan \"🤝 Referal\" bo'limiga o'ting
2️⃣ Sizga maxsus havola beriladi
3️⃣ Havolani do'stlaringizga yuboring
4️⃣ Do'stingiz botga kirsa — sizga bonus tushadi

💰 <b>Bonus olish shartlari:</b>

• Do'stingiz bot ga /start buyrug'ini berishi kerak
• Do'stingiz majburiy kanalga a'zo bo'lishi kerak
• Har bir yangi foydalanuvchi uchun 1 marta bonus beriladi
• O'zingizni o'zingiz taklif qila olmaysiz

📈 <b>Maslahat:</b>
Havolani guruh va kanallarga tarqating — ko'proq pul ishlaysiz!",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Orqaga", 'callback_data' => "guide_back"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 📦 BUYURTMA HOLATI — YANGI
// ==========================================

if (isset($data) && $data == "guide_status") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "📦 <b>Buyurtma holatlari haqida</b>

Buyurtma bergandan keyin u turli holatlarda bo'lishi mumkin:

⏳ <b>Pending</b> — Kutilmoqda
Buyurtma navbatda turibdi. Tez orada boshlanadi.

🔄 <b>In progress</b> — Jarayonda
Buyurtma bajarilmoqda. Natijani kuting.

✓ <b>Completed</b> — Bajarildi
Buyurtma to'liq muvaffaqiyatli bajarilgan.

⚠️ <b>Partial</b> — Qisman bajarildi
Buyurtmaning bir qismi bajarildi. Qolgan pul qaytariladi.

✗ <b>Canceled</b> — Bekor qilindi
Buyurtma bekor qilindi. Pul hisobingizga qaytarildi.

💥 <b>Failed</b> — Muvaffaqiyatsiz
Texnik xatolik. Pul avtomatik qaytariladi.

💡 <b>Buyurtmalaringizni ko'rish:</b>
\"🛒 Buyurtmalar\" tugmasini bosing.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Orqaga", 'callback_data' => "guide_back"]],
            ]
        ])
    ]);
    exit;
}

if (($text == "💵 Hisob to'ldirish") and joinchat($cid) == "true") {
    $ops = get("set/payments.txt");
    $s = explode("\n", $ops);
    $soni = substr_count($ops, "\n");
    for ($i = 1; $i <= $soni; $i++) {
        $k[] = ['text' => $s[$i], 'callback_data' => "payBot=" . $s[$i]];
    }
    $keyboard2 = array_chunk($k, 2);
    $keyboard2[] = [['text' => "☎️ Admin yordamida", 'url' => "tg://user?id=$admin"]];
    $kb = json_encode([
        'inline_keyboard' => $keyboard2,
    ]);
    sms($cid, "<b>🔰 Quyidagi to'lov tizimlardan birini tanlang:

👤 ID raqam:</b> <code>$cid</code>", $kb);
}

if ($text == "💵 Kursni o‘rnatish" and $cid == $admin) {
    $usd = get("set/usd");
    $rub = get("set/rub");
    $inr = get("set/inr");
    $try = get("set/try");
    sms($cid, "📑 <b>Kerakli valyutani tanlang:</b>", json_encode([
        'inline_keyboard' => [
            [['text' => "USD - $usd so'm", 'callback_data' => "course=usd"], ['text' => "RUB - $rub so'm", 'callback_data' => "course=rub"]],
            [['text' => "INR - $inr so'm", 'callback_data' => "course=inr"], ['text' => "TRY - $try so'm", 'callback_data' => "course=try"]],
        ]
    ]));
}

if ((stripos($data, "course=") !== false)) {
    $val = explode("=", $data)[1];
    if (get("set/" . $val . "")) {
        $VAL = get("set/" . $val);
    } else {
        $VAL = 0;
    }
    del();
    sms($chat_id, "
1 - " . strtoupper($val) . " narxini kiriting:

♻️ Joriy narx: " . $VAL . " so‘m", $aort);
    put("user/$chat_id.step", "course=$val");
}

if ((mb_stripos($step, "course=") !== false and is_numeric($text))) {
    $val = explode("=", $step)[1];
    put("set/" . $val, "$text");
    sms($cid, "
✅ 1 - " . strtoupper($val) . " narxi $text so‘mga o‘zgardi", $panel);
    unlink("user/$cid.step");
}


// Referal narxini ma'lumotlar bazasidan olish
function getReferalPrice($connect)
{
    $query = "SELECT value FROM settings WHERE name = 'referal_price'";
    $result = mysqli_query($connect, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['value']; // referal_price qiymatini qaytaradi
    }
    return 0; // Agar topilmasa, 0 so'mni qaytaradi
}

// Mablag' yig'ish bo'yicha menyu
if (($text == "🚀 Mablag' yig'ish") and joinchat($cid) == "true") {
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);

    // Tugmalar bilan matn yuborish
    bot('sendPhoto', [
        'chat_id' => $cid,
        'photo' => 'https://t.me/SmmGlobalRasmlari/84',
        'caption' => "<b>👋 Salom quyidagi tugmalardan birini tanlang:</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "💎 Haftalik referal konkurs", 'callback_data' => "kunlik"]],
                [['text' => "💎 Konkurs (🏆 TOP 10)", 'callback_data' => "konkurs"]],
                [['text' => "🔗 Dostlarimni taklif qilish", 'callback_data' => "referal"]],
            ]
        ])
    ]);
}

if ($data == "pulishla") {
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);

    // Rasm yuborish
    bot('sendPhoto', [
        'chat_id' => $cid2,
        'photo' => 'https://t.me/SmmGlobalRasmlari/84',
        'caption' => "<b>👋 Salom Quyidagi Menyulardan birini tanlang.</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "💎 Haftalik referal konkurs", 'callback_data' => "kunlik"]],
                [['text' => "💎 Konkurs (🏆 TOP 10)", 'callback_data' => "konkurs"]],
                [['text' => "🔗 Dostlarimni taklif qilish", 'callback_data' => "referal"]],
            ]
        ])
    ]);
}


if ($data == "referal" && joinchat($chat_id) == 1) {
    $result = mysqli_query($connect, "SELECT * FROM users WHERE id = $chat_id");
    $row = mysqli_fetch_assoc($result);
    $myid = $row['user_id'] ?? 0;
    $referralss = $row['refnum'] ?? 0;

    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);

    bot('SendPhoto', [
        'chat_id' => $cid2,
        'photo' => "https://t.me/SmmGlobalRasmlari/133",
        'caption' => "
🔗 <b>Sizning referal havolangiz:</b>

👉 <code>https://t.me/$bot?start=SFX$myid</code>

<b>🗣 Sizning referallaringiz: $referralss ta</b>

⚠️ <b>Soxta profillarni taklif qilish va yolg'on reklama block bo'lishiga sabab bo'ladi.</b>

👤 <b><i>Sizga har bir taklif qilgan referalingiz uchun " . enc("decode", $setting['referal']) . " so'm dan beriladi. ✅</i></b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => " Xavolani nusxalash", 'copy_text' => ['text' => "https://t.me/$bot?start=SFX$myid"]]],
                [['text' => "🔗 Do'stlarni Taklif qilish", 'url' => "https://t.me/share/url/?url=https://t.me/$bot?start=SFX$myid"]],
                [['text' => "⏪ Ortga qaytish", 'callback_data' => "pulishla"]],
            ]
        ])
    ]);
}

if ($data == "kunlik") {
    $result = mysqli_query($connect, "SELECT * FROM users WHERE id = $chat_id");
    $row = mysqli_fetch_assoc($result);

    $myid = $row['user_id'] ?? 0;
    $referralss = $row['refnum'] ?? 0;

    bot('editMessageMedia', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
        'media' => json_encode([
            'type' => 'photo',
            'media' => 'https://t.me/SmmGlobalRasmlari/85', // Rasm manzili
            'caption' => "
💎 <b>Haftalik referal konkurs</b>

<b>Ushbu konkursda siz ham ishtirok etayapsiz! Shartlar juda oddiy:
- Sizga berilgan referal havolani do'stlaringizga tarqating.
- Referallarni to'plang va haftalik referal uchun pul yig'ing!
- Har bir referal uchun 100 so'm beriladi.

Hafta oxirida eng ko'p referal yig'gan foydalanuvchilarga pul mukofotlari beriladi! 🎉</b>

🔵 <b>Sizning referallaringiz:</b> $referralss ta

🔗 <b>Sizning referal havolangiz:</b> 
👉 <code>https://t.me/$bot?start=SFX$myid</code>",
            'parse_mode' => 'html'
        ]),
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "💎 Konkurs (🏆 TOP 10)", 'callback_data' => "konkurs"]],
                [['text' => " Xavolani nusxalash", 'copy_text' => ['text' => "https://t.me/$bot?start=SFX$myid"]]],
                [['text' => "🔗 Dostlarimni taklif qilish", 'callback_data' => "referal"]],
                [['text' => "⏪ Ortga qaytish", 'callback_data' => "pulishla"]],
            ]
        ])
    ]);
}

if ($data == "konkurs" and joinchat($chat_id) == 1) {
    // Referallar soni bo'yicha eng ko'p referal to'plagan foydalanuvchilarni tanlash
    $result = mysqli_query($connect, "SELECT * FROM users ORDER BY refnum DESC LIMIT 10");
    $top_referrals = "";
    $rank = 1;

    // Eng ko'p referal to'plagan foydalanuvchilarni formatlash
    while ($row = mysqli_fetch_assoc($result)) {
        $user_id = $row['user_id'];   // Foydalanuvchining user_id
        $id = $row['id'];   // Foydalanuvchining user_id
        $refnum = $row['refnum'];     // Foydalanuvchining referal soni

        // Formatlangan referallar ro'yxati
        $top_referrals .= "<b>{$rank}.)</b> <b>ID: <a href='tg://user?id=$id'>{$user_id}</a></b> - <b>{$refnum} ta odam taklif qilgan</b>\n";
        $rank++;
    }

    // Foydalanuvchining o‘z o‘rnini aniqlash
    $user_position = "Siz hali hech kimni taklif qilmagansiz.";
    $user_result = mysqli_query($connect, "SELECT id, refnum FROM users ORDER BY refnum DESC");
    $rank = 1;

    while ($row = mysqli_fetch_assoc($user_result)) {
        if ($row['id'] == $chat_id) {
            $user_position = "<blockquote>📊 <b>Sizning o'rningiz:</b> <b>{$rank}-o‘rin</b> - <b>{$row['refnum']} ta odam taklif qilgansiz</b></blockquote>";
            break;
        }
        $rank++;
    }

    // Agar referallar topilmadi
    if (empty($top_referrals)) {
        $top_referrals = "<i>Hozirda referallar mavjud emas.</i>";
    }

    // Sana o'zgaruvchisini olish
    $sana = date("d-m-Y H:i:s");

    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);

    // Rasmni yuborish
    bot('sendPhoto', [
        'chat_id' => $cid2,
        'photo' => "https://t.me/SmmGlobalRasmlari/83", // Rasm manzili
        'caption' => "<b>🏆 Eng ko'p referal to'plagan 10 ta foydalanuvchi: \n\n$top_referrals\n<blockquote>⏰ Hozirgi sana va vaqt: $sana \n$user_position </blockquote></b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔗 Dostlarimni taklif qilish", 'callback_data' => "referal"]],
                [['text' => "↔️ Ortga", 'callback_data' => "pulishla"]],
            ]
        ])
    ]);
}


// ==========================================
// ⚖️ FOIZNI O'RNATISH
// ==========================================

if ($text == "⚖️ Foizni o‘rnatish" && $cid == $admin) {
    // Joriy foizni olish
    $query = mysqli_query($connect, "SELECT * FROM percent WHERE id = 1");
    $row = mysqli_fetch_assoc($query);
    $m = ($row && $row['percent']) ? $row['percent'] : 0;

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "⚖️ <b>FOIZ SOZLASH</b>
━━━━━━━━━━━━━━━━━━━━

▪ <b>Joriy foiz:</b> <code>$m%</code>

💡 <b>Misol:</b>
┣ API narx: 1000 so'm
┣ Foiz: {$m}%
┗ Bot narxi: " . (1000 + (1000 * $m / 100)) . " so'm

━━━━━━━━━━━━━━━━━━━━
✍️ <b>Yangi foiz miqdorini kiriting:</b>
<i>(faqat raqam, masalan: " . (5 + $m ) . ")</i>",
        'parse_mode' => 'html',
        'reply_markup' => $aort,
    ]);

    set_step($connect, $cid, "updFoiz");
    exit;
}

// FOIZ KIRITILDI
if ($step == "updFoiz" && $cid == $admin) {
    $text = trim($text);

    if (!is_numeric($text)) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✗ <b>Faqat raqam kiriting!</b>

<i>Masalan: 30, 50, 100</i>",
            'parse_mode' => 'html',
        ]);
        exit;
    }

    $foiz = intval($text);

    // Foizni yangilash
    $query = mysqli_query($connect, "UPDATE percent SET percent = '$foiz' WHERE id = 1");

    if ($query) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✓ <b>Foiz muvaffaqiyatli yangilandi!</b>

▪ <b>Yangi foiz:</b> <code>$foiz%</code>

💡 Endi barcha xizmatlar narxi API narx + $foiz% bo'ladi.",
            'parse_mode' => 'html',
            'reply_markup' => $panel,
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✗ <b>Xatolik yuz berdi!</b>

Qayta urinib ko'ring.",
            'parse_mode' => 'html',
            'reply_markup' => $panel,
        ]);
    }

    clear_step($connect, $cid);
    exit;
}


$saved = file_get_contents("user/us.id");

// ==========================================
// REFERAL TASDIQLASH (o'zgarishsiz)
// ==========================================

if ($data == "result" and joinchat($chat_id) == 1) {
    if (joinchat($chat_id) == 1) {
        $usid = get("user/$chat_id.id");

        $pul = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $usid"))['balance'];
        $a = $pul + enc("decode", $setting['referal']);
        mysqli_query($connect, "UPDATE users SET balance = $a WHERE id = $usid");

        $refnum = mysqli_fetch_assoc(mysqli_query($connect, "SELECT refnum FROM users WHERE id = $usid"))['refnum'];
        $newRefnum = $refnum + 1;
        mysqli_query($connect, "UPDATE users SET refnum = $newRefnum WHERE id = $usid");

        $text = "
✅ <b>Siz taklif qilgan <a href='tg://user?id=$chat_id'>foydalanuvchi</a> kanallarga obuna bo'ldi.</b>

➕ <b>Hisobingizga " . enc("decode", $setting['referal']) . " so'm qo'shildi!</b>";
        sms($usid, "$text", $m);

        $p = get("user/$usid.users");
        put("user/$usid.users", $p + 1);

        unlink("user/$chat_id.id");
    }

    del();
    sms($chat_id, "<b>✅ Obunangiz tasdiqlandi. Bosh menyudasiz!</b>", $m);

    unlink("user/$cid2.step");
    unlink("user/$cid2.ur");
    unlink("user/$cid2.params");
    unlink("user/$cid2.qu");
    unlink("user/$cid2.si");
}

// ==========================================
// 🔎 BUYURTMALARIM
// ==========================================

if (isset($text) && ($text == "🔎 Buyurtmalarim" || $text == "🛒 Buyurtmalar") && joinchat($cid) == "true") {

    $all_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$cid'"))['c'];

    if ($all_orders == 0) {
        bot('sendPhoto', [
            'chat_id' => $cid,
            'photo' => 'https://t.me/SmmGlobalRasmlari/86',
            'caption' => "🛒 <b>Buyurtmalarim</b>

😔 Sizda hali buyurtmalar yo'q.

Buyurtma berish uchun \"🗂 Xizmatlarga buyurtma berish\" bo'limiga o'ting.",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "🗂 Xizmatlarga buyurtma berish", 'callback_data' => "abds"]],
                ]
            ])
        ]);
    } else {
        // Statistika
        $done = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$cid' AND status = 'Completed'"))['c'];
        $pending = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$cid' AND status IN ('Pending','In progress','Processing')"))['c'];
        $canceled = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$cid' AND status IN ('Canceled','Failed')"))['c'];
        $partial = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$cid' AND status = 'Partial'"))['c'];

        // Oxirgi 10 ta buyurtma
        $rew = mysqli_query($connect, "SELECT order_id, status FROM myorder WHERE user_id = '$cid' ORDER BY order_id DESC LIMIT 10");
        $buttons = [];
        while ($my = mysqli_fetch_assoc($rew)) {
            $st_icon = "📦";
            if ($my['status'] == "Completed") $st_icon = "✓";
            elseif ($my['status'] == "Canceled" || $my['status'] == "Failed") $st_icon = "✗";
            elseif ($my['status'] == "Partial") $st_icon = "⚠️";
            elseif ($my['status'] == "In progress" || $my['status'] == "Processing" || $my['status'] == "Pending") $st_icon = "🔄";

            $buttons[] = ['text' => "$st_icon {$my['order_id']}", 'callback_data' => "myord_{$my['order_id']}"];
        }
        $keyboard = array_chunk($buttons, 3);
        $keyboard[] = [['text' => "🔎 ID bo'yicha qidirish", 'callback_data' => "order_search_user"]];

        bot('sendPhoto', [
            'chat_id' => $cid,
            'photo' => 'https://t.me/SmmGlobalRasmlari/87',
            'caption' => "🛒 <b>Buyurtmalarim</b>

<blockquote>📦 Jami buyurtmalar:  <b>$all_orders</b> ta
✓ Bajarilgan:  <b>$done</b> ta
🔄 Jarayonda:  <b>$pending</b> ta
⚠️ Qisman:  <b>$partial</b> ta
✗ Bekor/xato:  <b>$canceled</b> ta</blockquote>

👇 <b>Oxirgi buyurtmalar</b> (batafsil ko'rish uchun bosing):",
            'parse_mode' => 'html',
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
        ]);
    }
    exit;
}

// ==========================================
// 🔎 ID BO'YICHA QIDIRISH
// ==========================================

if (isset($data) && $data == "order_search_user") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "🔎 <b>Buyurtma qidirish</b>

Buyurtma ID raqamini kiriting:

<i>Masalan: 12345</i>",
        'parse_mode' => 'html',
        'reply_markup' => $aort,
    ]);

    file_put_contents("user/$cid2.step", "user_order_search");
    if (function_exists('set_step')) set_step($connect, $cid2, "user_order_search");
    exit;
}

// 🔎 QIDIRISH — ID KIRITILDI
$order_step = "";
if (isset($cid) && file_exists("user/$cid.step")) {
    $order_step = trim(file_get_contents("user/$cid.step"));
}
if (empty($order_step) && function_exists('get_step') && isset($cid)) {
    $order_step = get_step($connect, $cid);
}

if ($order_step == "user_order_search") {

    // Panel tugmalarini tekshirish
    $panel_btns = ["Xizmatlarga", "Pul ishlash", "Balans", "Yordam", "Pul kiritish", "Buyurtmalar", "/start", "Bot haqida", "Boshqaruv", "Asosiy", "Qo'llab", "Referal"];
    $is_panel = false;
    foreach ($panel_btns as $btn) {
        if (isset($text) && mb_stripos($text, $btn) !== false) { $is_panel = true; break; }
    }

    if ($is_panel) {
        @unlink("user/$cid.step");
        if (function_exists('clear_step')) clear_step($connect, $cid);
    } else {
        @unlink("user/$cid.step");
        if (function_exists('clear_step')) clear_step($connect, $cid);

        if (!is_numeric(trim($text))) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "✗ <b>Faqat raqam kiriting!</b>

Buyurtma ID ni qayta kiriting:",
                'parse_mode' => 'html',
            ]);
            file_put_contents("user/$cid.step", "user_order_search");
            if (function_exists('set_step')) set_step($connect, $cid, "user_order_search");
            exit;
        }

        $order_id = intval(trim($text));
        $order = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM myorder WHERE order_id = '$order_id' AND user_id = '$cid'"));

        if (!$order) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "😔 <b>Buyurtma topilmadi!</b>

Bu ID sizga tegishli emas yoki mavjud emas.",
                'parse_mode' => 'html',
                'reply_markup' => $m,
            ]);
            exit;
        }

        // Buyurtma topildi — batafsil ko'rsatish
        $status = get_order_status_text($order['status']);
        $price = number_format($order['retail'], 0, '', ' ');
        $service_id = $order['service'];
        $serv = mysqli_fetch_assoc(mysqli_query($connect, "SELECT service_name FROM services WHERE service_id = '$service_id'"));
        $serv_name = $serv ? base64_decode($serv['service_name']) : "Noma'lum";
        $tims = explode(" ", $order['order_create']);

        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "📦 <b>Buyurtma #$order_id</b>

<blockquote>🆔 ID:  <code>$order_id</code>
🛍 Xizmat:  $serv_name
💰 Narxi:  <b>$price</b> so'm
📅 Sana:  {$tims[0]}
🕐 Vaqt:  {$tims[1]}
📌 Holati:  $status</blockquote>",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "🔄 Yangilash", 'callback_data' => "myord_$order_id"]],
                ]
            ])
        ]);
        exit;
    }
}

// ==========================================
// 📦 BUYURTMA BATAFSIL KO'RISH
// ==========================================

if (isset($data) && preg_match('/^myord_(\d+)$/', $data, $matches)) {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $order_id = intval($matches[1]);

    // myorder dan
    $order = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM myorder WHERE order_id = '$order_id'"));
    if (!$order) {
        bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "✗ Buyurtma topilmadi!", 'show_alert' => true]);
        exit;
    }

    // orders dan API ma'lumot
    $ord = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM orders WHERE order_id = '$order_id'"));
    $api_status = "";

    if ($ord) {
        $prov = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = '{$ord['provider']}'"));
        if ($prov) {
            $ctx = stream_context_create(['http' => ['timeout' => 5]]);
            $api_resp = @file_get_contents($prov['api_url'] . "?key=" . $prov['api_key'] . "&action=status&order=" . $ord['api_order'], false, $ctx);
            $api_data = json_decode($api_resp, true);

            if ($api_data && isset($api_data['status'])) {
                // Lokal statusni yangilash
                $new_status = $api_data['status'];
                if ($new_status != $ord['status']) {
                    mysqli_query($connect, "UPDATE orders SET status = '$new_status' WHERE order_id = '$order_id'");
                    mysqli_query($connect, "UPDATE myorder SET status = '$new_status' WHERE order_id = '$order_id'");
                    $order['status'] = $new_status;
                }
                $remains = $api_data['remains'] ?? "—";
                $start_count = $api_data['start_count'] ?? "—";
            }
        }
    }

    $status = get_order_status_text($order['status']);
    $price = number_format($order['retail'], 0, '', ' ');
    $service_id = $order['service'];
    $serv = mysqli_fetch_assoc(mysqli_query($connect, "SELECT service_name FROM services WHERE service_id = '$service_id'"));
    $serv_name = $serv ? base64_decode($serv['service_name']) : "Noma'lum";

    $tims = explode(" ", $order['order_create']);
    $date_order = $tims[0] ?? "—";
    $time_order = $tims[1] ?? "—";

    // Qo'shimcha ma'lumot
    $extra = "";
    if (isset($start_count) && $start_count != "—") {
        $extra .= "
▪ Boshlang'ich:  <code>$start_count</code>";
    }
    if (isset($remains) && $remains != "—") {
        $extra .= "
📉 Qoldi:  <code>$remains</code>";
    }

    bot('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "📦 <b>Buyurtma #$order_id</b>

<blockquote>🆔 ID:  <code>$order_id</code>
🛍 Xizmat:  $serv_name
💰 Narxi:  <b>$price</b> so'm
📅 Sana:  $date_order
🕐 Vaqt:  $time_order
📌 Holati:  $status$extra</blockquote>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔄 Yangilash", 'callback_data' => "myord_$order_id"]],
                [['text' => "🛒 Barcha buyurtmalar", 'callback_data' => "myord_list"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 🛒 BARCHA BUYURTMALAR RO'YXATI (callback)
// ==========================================

if (isset($data) && $data == "myord_list") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $chat_id, 'message_id' => $message_id]);

    $all_orders = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$cid2'"))['c'];
    $done = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$cid2' AND status = 'Completed'"))['c'];
    $pending = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$cid2' AND status IN ('Pending','In progress','Processing')"))['c'];
    $canceled = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$cid2' AND status IN ('Canceled','Failed')"))['c'];
    $partial = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) as c FROM myorder WHERE user_id = '$cid2' AND status = 'Partial'"))['c'];

    $rew = mysqli_query($connect, "SELECT order_id, status FROM myorder WHERE user_id = '$cid2' ORDER BY order_id DESC LIMIT 10");
    $buttons = [];
    while ($my = mysqli_fetch_assoc($rew)) {
        $st_icon = "📦";
        if ($my['status'] == "Completed") $st_icon = "✓";
        elseif ($my['status'] == "Canceled" || $my['status'] == "Failed") $st_icon = "✗";
        elseif ($my['status'] == "Partial") $st_icon = "⚠️";
        else $st_icon = "🔄";

        $buttons[] = ['text' => "$st_icon {$my['order_id']}", 'callback_data' => "myord_{$my['order_id']}"];
    }
    $keyboard = array_chunk($buttons, 3);
    $keyboard[] = [['text' => "🔎 ID bo'yicha qidirish", 'callback_data' => "order_search_user"]];

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "🛒 <b>Buyurtmalarim</b>

<blockquote>📦 Jami:  <b>$all_orders</b> ta
✓ Bajarilgan:  <b>$done</b> ta
🔄 Jarayonda:  <b>$pending</b> ta
⚠️ Qisman:  <b>$partial</b> ta
✗ Bekor/xato:  <b>$canceled</b> ta</blockquote>

👇 <b>Oxirgi buyurtmalar:</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode(['inline_keyboard' => $keyboard])
    ]);
    exit;
}

// ==========================================
// 📌 STATUS MATNINI OLISH FUNKSIYASI
// ==========================================

function get_order_status_text($status) {
    switch ($status) {
        case "Completed": return "✓ Bajarilgan";
        case "In progress": return "🔄 Bajarilmoqda";
        case "Processing": return "⚙️ Qayta ishlanmoqda";
        case "Pending": return "⏳ Kutilmoqda";
        case "Partial": return "⚠️ Qisman bajarilgan";
        case "Canceled": return "✗ Bekor qilingan";
        case "Failed": return "💥 Muvaffaqiyatsiz";
        default: return "❓ Noma'lum";
    }
}

if ($text == "/start" and joinchat($cid) == 1) {
    // Foydalanuvchining ma'lumotlarini bazadan olish
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $cid"));

    // O'zgaruvchilarni tayyorlash
    $start = str_replace(
        ["{name}", "{balance}", "{time}"],
        ["$name", "" . $rew['balance'] . "", "$time"],
        enc("decode", $setting['start'])
    );

    // Bot profil ma'lumotlarini olish
    $getMe = file_get_contents("https://api.telegram.org/bot$BOT_TOKEN/getMe");
    $botInfo = json_decode($getMe, true);

    if (!$botInfo['ok']) {
        // Bot ma'lumotlari olinganda xatolik
        error_log("Bot ma'lumotlarini olishda xatolik: " . json_encode($botInfo));
        exit();
    }

    // Bot profil rasmi
    $getProfilePhotos = file_get_contents("https://api.telegram.org/bot$BOT_TOKEN/getUserProfilePhotos?user_id=" . $botInfo['result']['id'] . "&limit=1");
    $profileData = json_decode($getProfilePhotos, true);
    $photo = isset($profileData['result']['photos'][0][0]['file_id']) ? $profileData['result']['photos'][0][0]['file_id'] : null;

    // Admin yoki foydalanuvchini aniqlash
    $m = ($cid == $admin or $chat_id == $admin) ? $menu_p : $menu;

    if ($photo) {
        // Agar rasm mavjud bo'lsa, rasm bilan xabar yuborish
        bot('sendPhoto', [
            'chat_id' => $cid,
            'photo' => $photo,
            'caption' => "<b>$start</b>",
            'parse_mode' => 'html',
            'reply_markup' => $m
        ]);
    } else {
        // Agar rasm mavjud bo'lmasa, faqat matn va menyu yuborish
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>$start</b>",
            'parse_mode' => 'html',
            'reply_markup' => $m
        ]);
    }
}

// Antiflood uchun maxsus vaqtni tekshirish
$antiflood_time = 1; // Tugma bosish uchun maksimal vaqt (sekundlarda)

// Foydalanuvchi tugmani bosgan vaqti
$last_click_time = file_get_contents("user/$cid.last_click_time");

// Joriy vaqtni olish
$current_time = time();

// Agar foydalanuvchi tugmani juda tez bosgan bo'lsa
if ($current_time - $last_click_time < $antiflood_time) {
    sms($cid, "", null);
    return; // Tugmani qayta ishlamaslik
}

// Tugma bosilganda, foydalanuvchi vaqtini yangilash
file_put_contents("user/$cid.last_click_time", $current_time);

// Foydalanuvchi mavjudligini tekshirish va last_active ni yangilash
$check = mysqli_query($connect, "SELECT * FROM users WHERE user_id = '$cid'");

if (mysqli_num_rows($check) > 0) {
    // Mavjud foydalanuvchi bo‘lsa, so‘nggi faol vaqtni yangilash
    mysqli_query($connect, "UPDATE users SET last_active = '$current_time' WHERE user_id = '$cid'");
} else {
    // Yangi foydalanuvchi bo‘lsa, ro‘yxatga olish
    mysqli_query($connect, "INSERT INTO users (user_id, last_active) VALUES ('$cid', '$current_time')");
}

// ==========================================
// 🇺🇿 VALYUTA KURSI
// ==========================================

if (isset($text) && mb_stripos($text, "Valyuta kursi") !== false && $cid == $admin) {

    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents("https://cbu.uz/uz/arkhiv-kursov-valyut/json/", false, $ctx);

    if (!$response) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✗ <b>Kurslarni olishda xatolik!</b>

Markaziy bank saytiga ulanib bo'lmadi.",
            'parse_mode' => 'html',
            'reply_markup' => $panel,
        ]);
        exit;
    }

    $data_json = json_decode($response, true);

    if (!$data_json) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✗ <b>Ma'lumotlarni o'qishda xatolik!</b>",
            'parse_mode' => 'html',
            'reply_markup' => $panel,
        ]);
        exit;
    }

    // Kerakli valyutalar
    $currencies = [
        'USD' => ['symbol' => '$',  'flag' => '🇺🇸', 'name' => 'AQSH dollari'],
        'EUR' => ['symbol' => '€',  'flag' => '🇪🇺', 'name' => 'Yevro'],
        'RUB' => ['symbol' => '₽',  'flag' => '🇷🇺', 'name' => 'Rossiya rubli'],
        'GBP' => ['symbol' => '£',  'flag' => '🇬🇧', 'name' => 'Angliya funti'],
        'TRY' => ['symbol' => '₺',  'flag' => '🇹🇷', 'name' => 'Turk lirasi'],
        'CNY' => ['symbol' => '¥',  'flag' => '🇨🇳', 'name' => 'Xitoy yuani'],
        'KRW' => ['symbol' => '₩',  'flag' => '🇰🇷', 'name' => 'Koreys voni'],
    ];

    $rates = [];
    foreach ($data_json as $item) {
        if (isset($currencies[$item['Ccy']])) {
            $rates[$item['Ccy']] = $item['Rate'];
        }
    }

    // Sana
    $date = date("d.m.Y");

    $msg = "🇺🇿 <b>VALYUTA KURSLARI</b>
━━━━━━━━━━━━━━━━━━━━
📅 <b>Sana:</b> $date

";

    foreach ($currencies as $code => $info) {
        if (isset($rates[$code])) {
            $rate = number_format(floatval($rates[$code]), 2, '.', ' ');
            $msg .= "{$info['flag']} 1 {$info['symbol']} ({$code}) = <code>$rate</code> so'm
";
        }
    }

    $msg .= "
━━━━━━━━━━━━━━━━━━━━
▪ <i>Manba: Markaziy bank (cbu.uz)</i>";

    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => $msg,
        'parse_mode' => 'html',
        'disable_web_page_preview' => true,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔄 Yangilash", 'callback_data' => "refresh_currency"]],
            ]
        ])
    ]);
    exit;
}

// 🔄 VALYUTA KURSINI YANGILASH
if (isset($data) && $data == "refresh_currency") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "🔄 Yangilanmoqda..."]);

    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $response = @file_get_contents("https://cbu.uz/uz/arkhiv-kursov-valyut/json/", false, $ctx);

    if (!$response) {
        bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "✗ Xatolik!", 'show_alert' => true]);
        exit;
    }

    $data_json = json_decode($response, true);
    if (!$data_json) {
        bot('answerCallbackQuery', ['callback_query_id' => $qid, 'text' => "✗ Xatolik!", 'show_alert' => true]);
        exit;
    }

    $currencies = [
        'USD' => ['symbol' => '$',  'flag' => '🇺🇸', 'name' => 'AQSH dollari'],
        'EUR' => ['symbol' => '€',  'flag' => '🇪🇺', 'name' => 'Yevro'],
        'RUB' => ['symbol' => '₽',  'flag' => '🇷🇺', 'name' => 'Rossiya rubli'],
        'GBP' => ['symbol' => '£',  'flag' => '🇬🇧', 'name' => 'Angliya funti'],
        'TRY' => ['symbol' => '₺',  'flag' => '🇹🇷', 'name' => 'Turk lirasi'],
        'CNY' => ['symbol' => '¥',  'flag' => '🇨🇳', 'name' => 'Xitoy yuani'],
        'KRW' => ['symbol' => '₩',  'flag' => '🇰🇷', 'name' => 'Koreys voni'],
    ];

    $rates = [];
    foreach ($data_json as $item) {
        if (isset($currencies[$item['Ccy']])) {
            $rates[$item['Ccy']] = $item['Rate'];
        }
    }

    $date = date("d.m.Y H:i");

    $msg = "🇺🇿 <b>VALYUTA KURSLARI</b>
━━━━━━━━━━━━━━━━━━━━
📅 <b>Yangilangan:</b> $date

";

    foreach ($currencies as $code => $info) {
        if (isset($rates[$code])) {
            $rate = number_format(floatval($rates[$code]), 2, '.', ' ');
            $msg .= "{$info['flag']} 1 {$info['symbol']} ({$code}) = <code>$rate</code> so'm
";
        }
    }

    $msg .= "
━━━━━━━━━━━━━━━━━━━━
▪ <i>Manba: Markaziy bank (cbu.uz)</i>";

    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $msg,
        'parse_mode' => 'html',
        'disable_web_page_preview' => true,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🔄 Yangilash", 'callback_data' => "refresh_currency"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// ☎️ QO'LLAB-QUVVATLASH — YAXSHILANGAN
// ==========================================

if (isset($text) && ($text == "☎️ Qo'llab-Quvvatlash" || $text == "/qollabquvatlash") && joinchat($cid) == "true") {

    bot('sendPhoto', [
        'chat_id' => $cid,
        'photo' => "https://t.me/SmmGlobalRasmlari/33",
        'caption' => "<b>📞 SFXSMM | Bot <a href='https://t.me/SFXSMMHelp'>Qo'llab-Quvvatlash.</a>

❓ Sizga qanday yordam kerak.</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "❓ Eng ko'p beriladigan savollar", 'callback_data' => "faq_main"]],
                [['text' => "📨 Murojaat yuborish", 'callback_data' => "ticket_send"]],
                [['text' => "📜 Murojaatim holati", 'callback_data' => "ticket_status"]],
                [['text' => "📞 Admin bilan bog'lanish", 'url' => "https://t.me/SFXSMMHelp"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// ❓ FAQ — SAVOLLAR RO'YXATI
// ==========================================

if (isset($data) && $data == "faq_main") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendPhoto', [
        'chat_id' => $cid2,
        'photo' => "https://t.me/SmmGlobalRasmlari/33",
        'caption' => "<b>📞 SFXSMM | Bot <a href='https://t.me/SFXSMMHelp'>Qo'llab-Quvvatlash.</a>

❓ Quyidagi savollardan birini tanlang.</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "1️⃣ Buyurtma qancha vaqtda bajariladi?", 'callback_data' => "faq_1"]],
                [['text' => "2️⃣ Buyurtma nega bekor qilinadi?", 'callback_data' => "faq_2"]],
                [['text' => "3️⃣ Botdan bepul foydalansa bo'ladimi?", 'callback_data' => "faq_3"]],
                [['text' => "4️⃣ Pulim qaytarib beriladimi?", 'callback_data' => "faq_4"]],
                [['text' => "5️⃣ Buyurtma boshlanmayapti?", 'callback_data' => "faq_5"]],
                [['text' => "6️⃣ Minimal buyurtma miqdori qancha?", 'callback_data' => "faq_6"]],
                [['text' => "⬅️ Orqaga", 'callback_data' => "support_back"]],
            ]
        ])
    ]);
    exit;
}

// ❓ FAQ 1
if (isset($data) && $data == "faq_1") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "📌 <b>Savol:</b> Buyurtma qancha vaqtda bajariladi?

Buyurtma bajarilish vaqti siz tanlagan xizmat turiga bog'liq bo'ladi.

⏱ <b>O'rtacha bajarilish vaqtlari:</b>

👥 Obunachi (follower) — 1 soatdan 24 soatgacha
❤️ Like (yoqtirish) — 10 daqiqadan 6 soatgacha
👁 Ko'rish (view) — 5 daqiqadan 2 soatgacha
💬 Izoh (comment) — 1 soatdan 12 soatgacha

💡 <b>Eslatma:</b> Har bir xizmatning aniq tezligi buyurtma berish vaqtida tarif ma'lumotida ko'rsatiladi. Server yuklanishi sababli biroz kechikishi mumkin.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Savollarga qaytish", 'callback_data' => "faq_main"]],
            ]
        ])
    ]);
    exit;
}

// ❓ FAQ 2
if (isset($data) && $data == "faq_2") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "📌 <b>Savol:</b> Buyurtma nega bekor qilinadi?

Buyurtma quyidagi hollarda bekor qilinishi mumkin:

🔗 Noto'g'ri havola kiritilgan bo'lsa
🔒 Profilingiz yopiq (private) bo'lsa
📈 Xizmatga juda ko'p buyurtma tushgan bo'lsa
🔧 Texnik nosozlik yuz bergan bo'lsa

💰 <b>Pulingiz qaytariladimi?</b>
Albatta! Bekor qilingan buyurtma uchun sarflangan pul avtomatik ravishda hisobingizga qaytariladi.

💡 <b>Maslahat:</b> Buyurtma berishdan oldin havolangiz to'g'ri va profilingiz ochiq ekanligini tekshiring.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Savollarga qaytish", 'callback_data' => "faq_main"]],
            ]
        ])
    ]);
    exit;
}

// ❓ FAQ 3
if (isset($data) && $data == "faq_3") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "📌 <b>Savol:</b> Botdan bepul foydalansa bo'ladimi?

Ha! Referal tizimi orqali botdan mutlaqo bepul foydalanishingiz mumkin.

🤝 <b>Qanday ishlaydi:</b>

1️⃣ Asosiy menyudan \"🤝 Referal\" bo'limiga o'ting
2️⃣ Sizga maxsus taklif havolasi beriladi
3️⃣ Havolani do'stlaringizga yuboring
4️⃣ Do'stingiz botga kirsa — sizga bonus tushadi

💰 Yig'ilgan bonuslar orqali xizmatlardan bepul foydalanasiz!

📈 Qancha ko'p do'st taklif qilsangiz — shuncha ko'p pul ishlaysiz.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Savollarga qaytish", 'callback_data' => "faq_main"]],
            ]
        ])
    ]);
    exit;
}

// ❓ FAQ 4
if (isset($data) && $data == "faq_4") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "📌 <b>Savol:</b> Pulim qaytarib beriladimi?

✓ <b>Pul qaytariladigan hollar:</b>

• Buyurtma bekor qilinsa — darhol qaytadi
• Buyurtma umuman bajarilmasa — darhol qaytadi
• Qisman bajarilsa — bajarilmagan qismi qaytadi

🚫 <b>Pul qaytarilmaydigan hollar:</b>

• Buyurtma to'liq muvaffaqiyatli bajarilgan bo'lsa
• Siz noto'g'ri havola yoki ma'lumot kiritgan bo'lsangiz

⏱ <b>Qaytarish vaqti:</b> Bir zumda, avtomatik ravishda hisobingizga tushadi.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Savollarga qaytish", 'callback_data' => "faq_main"]],
            ]
        ])
    ]);
    exit;
}

// ❓ FAQ 5
if (isset($data) && $data == "faq_5") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "📌 <b>Savol:</b> Buyurtma berdim lekin boshlanmayapti?

Buyurtma boshlanmasligi uchun bir necha sabab bo'lishi mumkin:

⏳ Xizmat serverida navbat kutilmoqda
🔗 Kiritilgan havola noto'g'ri bo'lishi mumkin
🔒 Profilingiz yopiq (private) holatda
🛠 Texnik ishlar olib borilmoqda

✓ <b>Nima qilishingiz kerak:</b>

1️⃣ 15 — 30 daqiqa kutib turing
2️⃣ Profilingiz ochiq ekanligini tekshiring
3️⃣ Havolani qaytadan tekshiring
4️⃣ 1 soatdan keyin ham boshlanmasa — murojaat yuboring

💡 Buyurtma holatini \"🛒 Buyurtmalar\" bo'limidan kuzatishingiz mumkin.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Savollarga qaytish", 'callback_data' => "faq_main"]],
            ]
        ])
    ]);
    exit;
}

// ❓ FAQ 6
if (isset($data) && $data == "faq_6") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "📌 <b>Savol:</b> Minimal buyurtma miqdori qancha?

Har bir xizmatning minimal va maksimal buyurtma miqdori farq qiladi.

▪ <b>O'rtacha chegaralar:</b>

👥 Obunachi: kamida 10 — 100 ta
❤️ Like: kamida 10 — 50 ta
👁 Ko'rish: kamida 100 — 500 ta
💬 Izoh: kamida 5 — 10 ta

💡 <b>Eslatma:</b> Aniq miqdorni buyurtma berish vaqtida xizmat ma'lumotlarida ko'rishingiz mumkin. U yerda minimal, maksimal va narx ko'rsatiladi.",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⬅️ Savollarga qaytish", 'callback_data' => "faq_main"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// ⬅️ ORQAGA — YORDAM BOSH SAHIFA
// ==========================================

if (isset($data) && $data == "support_back") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);
    bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);

    bot('sendPhoto', [
        'chat_id' => $cid2,
        'photo' => "https://t.me/SmmGlobalRasmlari/33",
        'caption' => "<b>📞 SFXSMM | Bot <a href='https://t.me/SFXSMMHelp'>Qo'llab-Quvvatlash.</a>

❓ Sizga qanday yordam kerak.</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "❓ Eng ko'p beriladigan savollar", 'callback_data' => "faq_main"]],
                [['text' => "📨 Murojaat yuborish", 'callback_data' => "ticket_send"]],
                [['text' => "📜 Murojaatim holati", 'callback_data' => "ticket_status"]],
                [['text' => "📞 Admin bilan bog'lanish", 'url' => "https://t.me/SFXSMMHelp"]],
            ]
        ])
    ]);
    exit;
}

// ==========================================
// 📜 MUROJAAT HOLATI — YANGI FUNKSIYA
// ==========================================

if (isset($data) && $data == "ticket_status") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    if (!is_dir("user/mt")) mkdir("user/mt", 0777, true);
    $mt = @file_get_contents("user/mt/$cid2.mt");

    if ($mt) {
        $sent_time = date("d.m.Y H:i", intval($mt));
        $passed = time() - intval($mt);
        $hours = floor($passed / 3600);
        $minutes = floor(($passed % 3600) / 60);

        bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "📜 <b>Murojaatingiz holati</b>

📌 <b>Status:</b> Kutilmoqda ⏳
📅 <b>Yuborilgan vaqt:</b> $sent_time
⏱ <b>O'tgan vaqt:</b> $hours soat $minutes daqiqa

💡 Murojaatlar odatda 1-24 soat ichida ko'rib chiqiladi. Iltimos sabr qiling.",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "⬅️ Orqaga", 'callback_data' => "support_back"]],
                ]
            ])
        ]);
    } else {
        bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "📜 <b>Murojaatingiz holati</b>

✓ Sizda hozircha faol murojaat yo'q.

Agar muammoingiz bo'lsa — \"📨 Murojaat yuborish\" tugmasini bosing.",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "📨 Murojaat yuborish", 'callback_data' => "ticket_send"]],
                    [['text' => "⬅️ Orqaga", 'callback_data' => "support_back"]],
                ]
            ])
        ]);
    }
    exit;
}

// ==========================================
// 📨 MUROJAAT YUBORISH
// ==========================================

if (isset($data) && $data == "ticket_send") {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    if (!is_dir("user/mt")) mkdir("user/mt", 0777, true);
    $mt = @file_get_contents("user/mt/$cid2.mt");

    if ($mt) {
        bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "⚠️ <b>Sizda faol murojaat mavjud!</b>

Avvalgi murojaatingizga javob berilgandan so'ng yangi murojaat yuborishingiz mumkin.

⏰ Murojaatlar odatda 1-24 soat ichida ko'rib chiqiladi.
Iltimos biroz sabr qiling.",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "📜 Murojaat holati", 'callback_data' => "ticket_status"]],
                    [['text' => "⬅️ Orqaga", 'callback_data' => "support_back"]],
                ]
            ])
        ]);
    } else {
        bot('deleteMessage', ['chat_id' => $cid2, 'message_id' => $mid2]);
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "📨 <b>Murojaat yuborish</b>

Murojaatingizni yozing yoki fayl yuboring. Admin tez orada ko'rib chiqadi va javob beradi.

✍️ <b>Quyidagilarni yuborishingiz mumkin:</b>

📝 Matnli xabar
📸 Rasm yoki screenshot
🎥 Video
🎤 Ovozli xabar
📎 Fayl

⚠️ Muammoingizni imkon qadar batafsil yozishingizni so'raymiz. Bu bizga tezroq yordam berishga imkon beradi.",
            'parse_mode' => 'html',
            'reply_markup' => $aort,
        ]);

        file_put_contents("user/$cid2.step", "murojaat");
        if (function_exists('set_step')) set_step($connect, $cid2, "murojaat");
    }
    exit;
}

// ==========================================
// 📨 MUROJAAT — XABAR KIRITILDI
// ==========================================

if ($step == "murojaat") {

    // Panel tugmalarini tekshirish
    $panel_btns = ["Xizmatlarga", "Pul ishlash", "Balans", "Yordam", "Pul kiritish",
                   "Buyurtmalar", "/start", "Bot haqida", "Boshqaruv", "Asosiy",
                   "Qo'llab", "Statistika", "Kanallar", "Referal"];
    $is_panel = false;
    foreach ($panel_btns as $btn) {
        if (isset($text) && mb_stripos($text, $btn) !== false) { $is_panel = true; break; }
    }

    if ($is_panel) {
        @unlink("user/$cid.step");
        if (function_exists('clear_step')) clear_step($connect, $cid);
    } else {
        if (!is_dir("user/mt")) mkdir("user/mt", 0777, true);

        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✓ <b>Murojaatingiz qabul qilindi!</b>

▪ Admin tez orada ko'rib chiqadi va javob beradi.
⏰ O'rtacha javob vaqti: 1-24 soat.

📨 Javob kelganda sizga xabar yuboriladi.",
            'parse_mode' => 'html',
            'reply_markup' => $m,
        ]);

        file_put_contents("user/mt/$cid.mt", time());

        // Adminga
        $md = bot('forwardMessage', [
            'chat_id' => $admin,
            'from_chat_id' => $cid,
            'message_id' => $mid,
        ]);

        $fwd_id = isset($md->result->message_id) ? $md->result->message_id : 0;

        bot('sendMessage', [
            'chat_id' => $admin,
            'text' => "📨 <b>Yangi murojaat!</b>

👤 Foydalanuvchi: <a href='tg://user?id=$cid'>$name</a>
🆔 ID: <code>$cid</code>
📅 Vaqt: " . date("d.m.Y H:i"),
            'parse_mode' => 'html',
            'reply_to_message_id' => $fwd_id,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => "👤 $name", 'url' => "tg://user?id=$cid"],
                        ['text' => "✍️ Javob yozish", 'callback_data' => "ticket_reply_$cid"],
                    ],
                ]
            ]),
        ]);

        @unlink("user/$cid.step");
        if (function_exists('clear_step')) clear_step($connect, $cid);
        exit;
    }
}

// ==========================================
// ✍️ ADMIN — JAVOB YOZISH
// ==========================================

if (isset($data) && preg_match('/^ticket_reply_(\d+)$/', $data, $matches)) {
    bot('answerCallbackQuery', ['callback_query_id' => $qid]);

    $ticket_user = $matches[1];

    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "✍️ <b>Javob yozish</b>

👤 Foydalanuvchi: <a href='tg://user?id=$ticket_user'>$ticket_user</a>

📝 Javob xabaringizni yuboring.
Matn, rasm, video, ovoz — istalganini yuborishingiz mumkin.",
        'parse_mode' => 'html',
        'reply_markup' => $aort,
    ]);

    file_put_contents("user/$cid2.step", "ticket_answer_$ticket_user");
    if (function_exists('set_step')) set_step($connect, $cid2, "ticket_answer_$ticket_user");
    exit;
}

// ==========================================
// ✍️ ADMIN — JAVOB KIRITILDI
// ==========================================

if ($step && strpos($step, "ticket_answer_") === 0 && $cid == $admin) {
    $ticket_user = substr($step, 14);

    $result = bot('copyMessage', [
        'chat_id' => $ticket_user,
        'from_chat_id' => $admin,
        'message_id' => $mid,
    ]);

    if (isset($result->ok) && $result->ok) {
        bot('sendMessage', [
            'chat_id' => $ticket_user,
            'text' => "💬 <b>Admin murojaatingizga javob berdi!</b> ⬆️

Agar qo'shimcha savolingiz bo'lsa — ☎️ Qo'llab-Quvvatlash bo'limiga murojaat qiling.",
            'parse_mode' => 'html',
        ]);

        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✓ <b>Javob muvaffaqiyatli yuborildi!</b>

👤 <a href='tg://user?id=$ticket_user'>$ticket_user</a> ga xabar yetkazildi.",
            'parse_mode' => 'html',
            'reply_markup' => $panel,
        ]);

        @unlink("user/mt/$ticket_user.mt");
    } else {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "✗ <b>Xabar yuborilmadi!</b>

Foydalanuvchi botni bloklagan bo'lishi mumkin.",
            'parse_mode' => 'html',
            'reply_markup' => $panel,
        ]);
        @unlink("user/mt/$ticket_user.mt");
    }

    @unlink("user/$cid.step");
    if (function_exists('clear_step')) clear_step($connect, $cid);
    exit;
}

if (($text == "💳 Mening hisobim" or $text == "/meninghisobim") and joinchat($cid) == "true") {
    // PHP vaqt zonasini belgilash
    date_default_timezone_set('Asia/Tashkent');

    // Foydalanuvchi ma'lumotlarini olish
    $getChat = file_get_contents("https://api.telegram.org/bot$BOT_TOKEN/getChat?chat_id=$cid");
    $chatInfo = json_decode($getChat, true);

    if (!$getChat || !$chatInfo['ok']) {
        error_log("getChat xatolik: " . json_encode($chatInfo));
        exit();
    }

    // Profil rasmini olish
    $getProfilePhotos = file_get_contents("https://api.telegram.org/bot$BOT_TOKEN/getUserProfilePhotos?user_id=$cid&limit=1");
    $photoData = json_decode($getProfilePhotos, true);
    $photo = isset($photoData['result']['photos'][0][0]['file_id']) ? $photoData['result']['photos'][0][0]['file_id'] : null;

    // Foydalanuvchi ma'lumotlari
    $first_name = $chatInfo['result']['first_name'] ?? 'Ism mavjud emas';
    $last_name = $chatInfo['result']['last_name'] ?? '';
    $username = $chatInfo['result']['username'] ?? 'Not provided';

    // Bazadan ma'lumot olish
    $orders = mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `myorder` WHERE `user_id` = '$cid'"));
    $kabinet_data = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM kabinet WHERE user_id = '$cid'")) ?? [];
    $pul = $kabinet_data['pul'] ?? 0;

    $user_data = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = '$cid'")) ?? [];
    $outing = $user_data['outing'] ?? 0;
    $balance = $user_data['balance'] ?? 0;
    $user_id = $user_data['user_id'] ?? $cid;
    $id = $user_data['id'] ?? $cid;
    $registration_date = $user_data['registration_date'] ?? null; // Ro'yxatdan o'tish sanasi

    // Ro'yxatdan o'tish sanasini formatlash
    if ($registration_date) {
        $vaqt = date("d.m.Y | H:i", strtotime($registration_date)); // Format: kun.oy.yil | soat:minut

        // Foydalanuvchining ro'yxatdan o'tgan sanasi bilan hozirgi vaqt o'rtasidagi farqni hisoblash
        $registration_time = strtotime($registration_date); // Foydalanuvchi ro'yxatdan o'tgan vaqt
        $current_time = time(); // Hozirgi vaqt
        $diff = $current_time - $registration_time; // Vaqt farqi

        $days_diff = floor($diff / (60 * 60 * 24)); // Kunlar bo'yicha farqni hisoblash
        $hours_diff = floor(($diff % (60 * 60 * 24)) / (60 * 60)); // Soatlar bo'yicha farqni hisoblash
        $minutes_diff = floor(($diff % (60 * 60)) / 60); // Minutlar bo'yicha farqni hisoblash
    } else {
        $vaqt = "Mavjud emas"; // Agar bazada vaqt bo'lmasa
        $days_diff = 0;
        $hours_diff = 0;
        $minutes_diff = 0;
    }

    // Foydalanuvchining referallar sonini olish
    $referrals = $user_data['refnum'] ?? 0;
    $holatim = $user_data['status'] ?? 'Aktiv';

    // Foydalanuvchining xizmatlarga sarflagan pullarini hisoblash
    $totalSpent = mysqli_fetch_assoc(mysqli_query($connect, "SELECT SUM(retail) AS total_spent FROM myorder WHERE user_id = '$cid' AND status = 'Completed'"))['total_spent'] ?? 0;

    // Foydalanuvchiga ma'lumot yuborish
    $message = "<b><blockquote>🖥 Sizning hisobingiz haqida ma'lumot</blockquote></b>\n\n" .
        "<blockquote><b>├✍️ Ismingiz:</b> {$first_name} {$last_name}\n" .
        "<b>├🫂 Username:</b> @{$username}\n" .
        "<b>├🔢 Tartib raqamingiz:</b> {$user_id}\n" .
        "<b>├🆔 ID raqamingiz:</b> {$id}\n" .
        "<b>├💰 Hisobingiz:</b> {$balance} so'm\n" .
        "<b>├💵 To'lovlar umumiy:</b> {$outing} so'm\n" .
        "<b>├♻️ Hozirgi holatingiz:</b> {$holatim}\n" .
        "<b>├👥 Siz taklif qilganlar soni:</b> {$referrals} ta\n" .
        "<b>├📊 Barcha buyurtmalaringiz:</b> {$orders} ta\n" .
        "<b>├💸 Sarflagan pullaringiz:</b> {$totalSpent} so'm\n" .
        "<b>├⏰ Hozirgi vaqt:</b> {$sana}\n" .
        "<b>├⏳ Siz biz bilansiz {$days_diff} kun, {$hours_diff} soat, {$minutes_diff} minut</b>\n" .  // Bu yerda kunlar, soatlar va minutlarni ko'rsatamiz
        "<b>├📅 Ro'yxatdan o'tgan sana:</b> <b>{$vaqt}</b>\n" .
        "<b>└🤖 @$bot - Sifatli va arzon SMM Bot ✅</b></blockquote>";

    // Inline tugmalar
    $keyboard = json_encode([
        'inline_keyboard' => [
            [['text' => "🔁 Pul o'tkazish", 'callback_data' => "transfer"], ['text' => "🚀 Mablag' yig'ish", 'callback_data' => "referal"]],
        ]
    ]);

    // Profil rasmi bilan yoki faqat matn yuborish
    if ($photo) {
        bot('sendPhoto', [
            'chat_id' => $cid,
            'photo' => $photo,
            'caption' => $message,
            'parse_mode' => 'html',
            'reply_markup' => $keyboard
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => $message,
            'parse_mode' => 'html',
            'reply_markup' => $keyboard
        ]);
    }
}

if ($data == "transfer") {
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $cid2"));
    if ($rew['balance'] >= 1000) {
        del();
        sms($cid2, "<b>Qancha mablag'ingizni o'tkazmoqchisiz?</b>
	
	<i><blockquote>Minimal o'tkazma miqdori: 1000 </blockquote></i>", $ort);
        put("user/$cid2.step", "transfer");
    } else {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "Hisobingizda mablag' yetarli emas
	
	Minimal o'tkazma miqdori: 1000 so'm",
            'show_alert' => true,
        ]);
    }
}


if ($step == "transfer") {
    if (is_numeric($text) == "true") {
        if ($text >= 1000) {
            $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $cid"));
            if ($rew['balance'] >= $text) {
                bot('SendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>Kerakli foydalanuvchi ID raqamini yuboring:</b>",
                    'parse_mode' => 'html',
                ]);
                put("user/$cid.step", "transfer=$text");
            } else {
                bot('SendMessage', [
                    'chat_id' => $cid,
                    'text' => "<b>🤷🏻‍♂ Hisobingizda mablag' yetarli emas!</b>
	
	Qayta yuboring:",
                    'parse_mode' => 'html',
                ]);
            }
        } else {
            bot('SendMessage', [
                'chat_id' => $cid,
                'text' => "<b>Minimal o'tkazma miqdori:</b> 1000 so'm
	
	Qayta yuboring:",
                'parse_mode' => 'html',
            ]);
        }
    } else {
        bot('SendMessage', [
            'chat_id' => $cid,
            'text' => "<b>🤷🏻‍♂ Faqat raqamlardan foydalaning!</b>
	
	<i>Minimal o'tkazma miqdori:</i> 1000 so'm",
            'parse_mode' => 'html',
        ]);
    }
}


if ((stripos($step, "transfer=") !== false)) {
    $res = explode("=", $step)[1];
    $user = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $text"));

    // Foydalanuvchining o'z hisob raqamiga tekshiruv
    if ($cid == $text) {
        bot('SendMessage', [
            'chat_id' => $cid,
            'text' => "<b>🤷🏻‍♂ O'zingizga pul o'tkazolmaysiz!</b>\n\nQayta urinib ko'ring:",
            'parse_mode' => 'html',
        ]);
    } elseif ($user) {
        $balance = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $cid"))['balance'];
        if ($balance >= $res) {
            $m = $balance - $res;
            mysqli_query($connect, "UPDATE users SET balance=$m WHERE id =$cid");
            $miqdor = $user['balance'] + $res;
            mysqli_query($connect, "UPDATE users SET balance='$miqdor' WHERE id =$text");
            bot('SendMessage', [
                'chat_id' => $text,
                'text' => "📳 <a href='tg://user?id=$cid'>$cid</a><b> hisobingizga $res so'm o'tkazdi</b>",
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
            ]);
            sms($cid, "<b>✅</b> <a href='tg://user?id=$text'>Foydalanuvchiga</a><b> $res so'm o'tkazildi</b>", $ort);
            unlink("user/$cid.step");
        } else {
            bot('SendMessage', [
                'chat_id' => $cid,
                'text' => "<b>🤷🏻‍♂ Hisobingizda mablag' yetarli emas!</b>\n\nQayta urinib ko'ring:",
                'parse_mode' => 'html',
            ]);
        }
    } else {
        bot('SendMessage', [
            'chat_id' => $cid,
            'text' => "<b>🤷🏻‍♂ Foydalanuvchi topilmadi</b>\n\nQayta urinib ko'ring:",
            'parse_mode' => 'html',
        ]);
    }
}

if ((stripos($data, "menu=") !== false and joinchat($chat_id) == 1)) {
    $res = explode("=", $data)[1];
    if (empty($setting['payme_id']) or $setting['payme_id'] == "null" or $setting['payme_id'] == "NULL" or $setting['payme_id'] == "") {
    } else {
        $paymee = "💳 PAYME";
    }
    if ($res == "tolov") {
        $ops = get("set/payments.txt");
        $s = explode("\n", $ops);
        $soni = substr_count($ops, "\n");
        for ($i = 1; $i <= $soni; $i++) {
            $k[] = ['text' => $s[$i], 'callback_data' => "payBot=" . $s[$i]];
        }
        $keyboard2 = array_chunk($k, 2);
        //$keyboard2[]=[['text'=>"🅿️  PAYME AVTO",'callback_data'=>"payme11"]];
        $keyboard2[] = [['text' => "☎️ Admin yordamida", 'url' => "tg://user?id=$admin"]];
        $kb = json_encode([
            'inline_keyboard' => $keyboard2,
        ]);
        edit($chat_id, $message_id, "<b>🔰 Quyidagi to'lov tizimlardan birini tanlang:</b>", $kb);
    } elseif ($res == "back") {
        $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $chat_id"));
        del();
    } elseif ($res == "PAYME") {
        if (empty($setting['66ebeb594dc54a8ccfdcd597']) or $setting['66ebeb594dc54a8ccfdcd597'] == "null" or $setting['66ebeb594dc54a8ccfdcd597'] == "NULL") {
            bot('answerCallbackQuery', [
                'callback_query_id' => $cqid,
                'text' => "⚠️ Ushbu tolov tizimidagi kerakli malumotlar yetishmaydi",
                'show_alert' => true,
            ]);
        } else {
            del();
            sms($chat_id, "
💵 To‘lov miqdorini kiriting:

⬇️ Minimal 10000 so‘m
⬆️ Maksimal 12000000 so‘m", $ort);
            put("user/$chat_id.step", "payme");
        }
    }
}

if ((stripos($data, "payBot=") !== false)) {
    $h = explode("=", $data)[1];
    $card = get("set/pay/$h/wallet.txt");
    $info = get("set/pay/$h/addition.txt");
    edit($cid2, $mid2, "
🧾 <b>To'lov tizimi: $h

💳 Hamyon: <pre>$card</pre>
📑 Izoh: $cid2

🔹 Minimal:</b> 1,000 so'm

<b>🤝 Hisobingizni muvaffaqiyatli to'ldirish uchun quyidagi harakatlarni amalga oshiring: 

1) Pul miqdorini tepadagi Hamyonga tashlang. 
2) «✅ To'lov qildim» tugmasini bosing; 
4) Qancha pul miqdoni yuborganingizni kiritin;
3) Toʻlov haqidagi suratni botga yuboring;
3) <a href = 'https://t.me/SFXSMMHelp' > Operator</a> tomonidan to'lov tasdiqlanishini kuting!</b> 
", json_encode([
        'inline_keyboard' => [
            [['text' => "💵 Hisob to'ldirish qo'llanmasi", 'callback_data' => "hisob"]],
            [['text' => "✅ To‘lov qildim", 'callback_data' => "payed=$h"]],
            [['text' => "↔️ Orqaga", 'callback_data' => "menu=tolov"]],
        ]
    ]));
}

if (mb_stripos($data, "payed=") !== false) {
    $h = explode("=", $data)[1];
    sms($chat_id, "🧾 <b>To’lov miqdorini kiriting:</b>

<b>🔹 Minimal: 1,000 so'm</b>", $ort);
    put("user/$chat_id.step", "tolovqldm=$h");
}


if (mb_stripos($step, "tolovqldm=") !== false) {
    $h = explode("=", $step)[1];
    if (is_numeric($text) == true) {
        if (($text >= 1000)) {
            sms($cid, "
📑 <b>Toʻlov uchun chek rasmini sifatli va aniq yuboring.</b>", $ort);
            file_put_contents("user/$cid.step", "payed=$h=$text");
        } else {
            sms($cid, "
⛔ <b>Qaytadan kiriting:

🔹 Minimal: 1,000 so'm</b>", $ort);
        }
    } else {
        sms($cid, "<b>
⛔ Faqat raqamlardan foydalaning:

🔹 Minimal: 1,000 so'm", $ort);
    }
}

if (mb_stripos($step, "payed=") !== false) {
    $name = bot('getchat', ['chat_id' => $cid])->result->first_name;
    unlink("user/$cid.step");
    $ex = explode("=", $step);
    $h = $ex[1];
    $miqdor = $ex[2];

    // Foydalanuvchi hisobini olish
    $user = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = '$cid'"));
    $orders = mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `myorder` WHERE `user_id` = '$cid'"));
    $balance = $user['balance']; // Foydalanuvchi hisobidagi mablag'
    $odamt = $user['refnum'];
    $outing = $user['outing'];
    $user_id = $user['user_id'];

    if ($message->photo) {
        sms($cid, "<b>✅ Toʻlovingiz muvaffaqiyatli amalga oshirildi va administratorga yuborildi. Tasdiqlash jarayoni ortacha 10-15 daqiqada tastiqlanadi. Iltimos, kuting!</b>", $m);
        $ax = bot('CopyMessage', [
            'chat_id' => $paychannel,
            'message_id' => $mid,
            'from_chat_id' => $cid,
        ])->result->message_id;
        bot('sendMessage', [
            'chat_id' => $paychannel,
            'reply_to_message_id' => $ax,
            'parse_mode' => 'html',
            'text' => "<b>
📑 #chek | To'lov uchun chek

<blockquote>💳 To'lov tizimi: $h
🔢 To'lov miqdori: $miqdor so'm
✍️ Tartib raqami: $user_id
🆔 Foydalanuvchi ID: $cid
💰 Foydalanuvchi hisobi: $balance so'm
👥 Taklif qilganlarini soni: $odamt ta
📊 Barcha buyurtmalari: $orders ta
💵 To'lovlari umumiy: $outing so'm
⏰ Tolov qilingan aniq vaqt: $sana</blockquote>
</b>",
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "✅ Tasdiqlash", 'callback_data' => "pdone=$cid=$h=$miqdor"], ['text' => "⛔ Bekor qilish", 'callback_data' => "notpay=$cid=$miqdor"]],
                    [['text' => "$name", 'url' => "tg://user?id=$cid"]],
                ]
            ]),
        ]);
    } else {
        sms($cid, "<b>⛔ Faqat rasm (screenshot) qabul qilinadi!</b>", $m);
    }
}

$uid = $message->from->id;
if (mb_stripos($data, "notpay=") !== false and $Tc != "private") {
    if ($callfrid == $admin) {
        $ex = explode("=", $data);
        $use = $ex[1];
        $miqdor = $ex[2];
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'parse_mode' => 'html',
            'message_id' => $message_id,
            'text' => "⛔ <b>Foydalanuvchi ($use) hisobini $miqdor so'mga to'ldirish uchun so'rovi bekor qilindi! || #canceled</b>",
        ]);
        sms($use, "<b>⛔ Hisobingizni $miqdor soʻmga toʻldirish soʻrovi bekor qilindi! Sababi: soxta chek yuborgan boʻlishingiz mumkin. Iltimos, maʼlumotlaringizni tekshirib, qayta yuboring.</b>", null);
    } else {
        bot('answerCallbackQuery', [
            'callback_query_id' => $cqid,
            'text' => "⚠️ Siz administrator emassiz!",
            'show_alert' => false,
        ]);
    }
}

if (mb_stripos($data, "pdone=") !== false and $Tc != "private") {
    if ($callfrid == $admin) {
        $ex = explode("=", $data);
        $id = $ex[1];
        $tizim = $ex[2];
        $miqdor = $ex[3];
        sms($id, "✅ To’lovingiz tasdiqlandi

<blockquote>💰Hisobingizga <b>$miqdor</b> so'm qo'shildi</blockquote>", null);
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'parse_mode' => 'html',
            'message_id' => $message_id,
            'text' => "💵 <b>Foydalanuvchi ($id) hisobi $miqdor so'mga to'ldirildi. || #done</b>",
        ]);
        $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $id"));
        $put = $miqdor + $rew['balance'];
        $p2 = $miqdor + $rew['outing'];
        mysqli_query($connect, "UPDATE users SET balance=$put, outing=$p2 WHERE id = $id");
    } else {
        bot('answerCallbackQuery', [
            'callback_query_id' => $cqid,
            'text' => "⚠️ Siz administrator emassiz!",
            'show_alert' => false,
        ]);
    }
}

if ($text == "📢 Kanallar" and $cid == $admin) {
    sms($cid, "<b>$text bo'limi:</b>", json_encode([
        'inline_keyboard' => [
            [['text' => "➕ Qo‘shish", 'callback_data' => "kanal=add"]],
            [['text' => "*️⃣ Ro‘yxat", 'callback_data' => "kanal=list"], ['text' => "🗑️ O'chirish", 'callback_data' => "kanal=dl"]],
        ]
    ]));
}

if ((stripos($data, "kanal=") !== false)) {
    $rp = explode("=", $data)[1];
    if ($rp == "list") {
        $ops = get("set/channel");
        if (empty($ops)) {
            sms($chat_id, "🤷‍♂️ Xechqanday kanal topilmadi.", null);
        } else {
            $s = explode("\n", $ops);
            $soni = substr_count($ops, "\n");
            for ($i = 0; $i <= count($s) - 1; $i++) {
                $k[] = ['text' => $s[$i], 'url' => "t.me/" . str_replace("@", "", $s[$i])];
            }
            $keyboard2 = array_chunk($k, 2);
            $keyboard = json_encode([
                'inline_keyboard' => $keyboard2,
            ]);
            sms($chat_id, "🌐 <b>Barcha kanallar:</b>", $keyboard);
        }
    } elseif ($rp == "dl") {
        $ops = get("set/channel");
        if (empty($ops)) {
            sms($chat_id, "🤷‍♂️ Xechqanday kanal topilmadi.", null);
        } else {
            $s = explode("\n", $ops);
            $soni = substr_count($ops, "\n");
            for ($i = 0; $i <= count($s) - 1; $i++) {
                $k[] = ['text' => $s[$i], 'callback_data' => "kanal=del" . $s[$i]];
            }
            $keyboard2 = array_chunk($k, 2);
            $keyboard = json_encode([
                'inline_keyboard' => $keyboard2,
            ]);
            sms($chat_id, "🗑️ <b>O‘chiriladigan kanalni tanlang</b>:", $keyboard);
        }
    } elseif (mb_stripos($rp, "del@") !== false) {
        $d = explode("@", $rp)[1];
        $ops = get("set/channel");
        $soni = explode("\n", $ops);
        if (count($soni) == 1) {
            unlink("set/channel");
        } else {
            $ss = "@" . $d;
            $ops = str_replace("\n" . $ss . "", "", $ops);
            put("set/channel", $ops);
        }
        del();
        sms($chat_id, "✅ O‘chirildi", null);
    } elseif ($rp == "add") {
        del();
        sms($chat_id, "
♻️ Kanal userini kiriting

Namuna: @username", $aort);
        put("user/$chat_id.step", "kanal_add");
    }
}

if ($step == "kanal_add") {
    if (mb_stripos($text, "@") !== false) {
        $kanal = get("set/channel");
        sms($cid, "✅ Saqlandi!", $panel);
        if ($kanal == null) {
            file_put_contents("set/channel", $text);
        } else {
            file_put_contents("set/channel", "$kanal\n$text");
        }
        unlink("user/$chat_id.step");
    }
}

if ($data == "qoshimcha") {
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>📢 Kerakli kanalni manzilini yuboring:</b>

Namuna: @SFXSMM",
        'parse_mode' => "html",
        'reply_markup' => json_encode([
            'resize_keyboard' => true,
            'keyboard' => [
                [['text' => "🗄 Boshqarish"]],
            ]
        ])
    ]);
    file_put_contents("step/$cid2.step", "qoshimcha");
}
if ($step == "qoshimcha") {
    if ($tx == "🗄 Boshqaridh") {
        unlink("step/$cid.step");
    } else {
        if (stripos($text, "@") !== false) {
            $get = bot('getChat', ['chat_id' => $text]);
            $types = $get->result->type;
            $ch_name = $get->result->title;
            $ch_user = $get->result->username;
            file_put_contents("admin/qoshimcha.txt", "$ch_user");
            bot('SendMessage', [
                'chat_id' => $cid,
                'text' => "<b>Muvaffaqiyatli saqlandi!</b>",
                'parse_mode' => 'html',
                'reply_markup' => $panel,
            ]);
            unlink("step/$cid.step");
        } else {
            bot('SendMessage', [
                'chat_id' => $cid,
                'text' => "<b>⚠️ Kanal manzili kiritishda xatolik:</b>

Masalan: @SFXSMM",
                'parse_mode' => 'html',
            ]);
        }
    }
}

if (($text == "🗂 Xizmatlarga buyurtma berish") and joinchat($cid) == "true") { {
        if ($cid == $admin) {
            $n = "➕";
            $a = mysqli_query($connect, "SELECT * FROM `categorys`");
        } else {
            $a = mysqli_query($connect, "SELECT * FROM `categorys` WHERE category_status = 'ON'");
        }
    }
    $c = mysqli_num_rows($a);
    while ($s = mysqli_fetch_assoc($a)) {
        $k[] = ['text' => "" . enc("decode", $s['category_name']), 'callback_data' => "tanla1=" . $s['category_id']];
    }
    $keyboard2 = array_chunk($k, 2);
    $keyboard2[] = [['text' => "$n", 'callback_data' => "newFol"]];
    $keyboard2[] = [['text' => "🔍 Xizmatni qidirish (ID orqali) ", 'callback_data' => "order"]];
    $kb = json_encode([
        'inline_keyboard' => $keyboard2,
    ]);
    if ($c) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>✅️ Bizning xizmatlar eng arzon va tezkor!

👇 Quyidagi Ijtimoiy tarmoqlardan birini tanlang:</b>",
            'parse_mode' => 'html',
            'reply_markup' => $kb,
        ]);
        exit();
    } else {
        if ($cid == $admin) {
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "<b>✅️ Bizning xizmatlar eng arzon va tezkor!

👇 Quyidagi Ijtimoiy tarmoqlardan birini tanlang:</b>",
                'parse_mode' => 'html',
                'reply_markup' => $kb,
            ]);
        } else {
            sms($cid, "⚠️ Bu bo'lim qayta tiklanmoqda biroz kuting.", null);
        }
        exit;
    }
}



if ($data == "absd" and joinchat($chat_id) == 1) { {
        if ($chat_id == $admin) {
            $n = "➕";
            $a = mysqli_query($connect, "SELECT * FROM `categorys`");
        } else {
            $a = mysqli_query($connect, "SELECT * FROM `categorys` WHERE category_status = 'ON'");
        }
    }
    $c = mysqli_num_rows($a);
    while ($s = mysqli_fetch_assoc($a)) {
        $k[] = ['text' => enc("decode", $s['category_name']), 'callback_data' => "tanla1=" . $s['category_id']];
    }
    if (!$c) {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "⚠️ Tarmoqlar topilmadi!",
            'show_alert' => true,
        ]);
    } else {
        $keyboard2 = array_chunk($k, 2);
        $keyboard2[] = [['text' => "$n", 'callback_data' => "newFol"]];
        $keyboard2[] = [['text' => "🔍 Xizmatni qidirish (ID orqali)", 'callback_data' => "order"]];
        $kb = json_encode([
            'inline_keyboard' => $keyboard2,
        ]);
        edit($chat_id, $mid2, "<b>✅️ Bizning xizmatlar eng arzon va tezkor!

👇 Quyidagi Ijtimoiy tarmoqlardan birini tanlang:</b>", $kb);
        exit;
    }
}


if ($data == "newFol") {
    bot('deleteMessage', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
    ]);
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "<b>Yangi bo'lim uchun nom yuboring:</b>",
        'parse_mode' => 'html',
        'reply_markup' => $ort
    ]);
    file_put_contents("user/$chat_id.step", 'newFol');
}

if ($step == "newFol") {
    $res = mysqli_query($connect, "SELECT * FROM `categorys`");
    $n = mysqli_fetch_assoc($res);
    bot('SendMessage', [
        'chat_id' => $cid,
        'text' => "<b>$text</b> bo'limi qo'shildi!",
        'parse_mode' => 'html',
        'reply_markup' => $m
    ]);
    $text = enc("encode", $text);
    mysqli_query($connect, "INSERT INTO categorys(category_name,category_status) VALUES('$text','ON');");
    unlink("user/$cid.step");
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "<b>Yana bo'lim qo'shish uchun ''➕'' tugmasini bosing!</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "➕", 'callback_data' => "newFol"]],
            ]
        ])
    ]);
}



if ((mb_stripos($data, "tanla1=") !== false and joinchat($chat_id) == 1)) {
    $n = explode("=", $data)[1];
    $aa = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM categorys WHERE category_id = $n")); {
        if ($chat_id == $admin) {
            $n1 = "📝";
            $n2 = "➕";
            $n3 = "🗑"; {
                if ($aa['category_status'] == 'ON') {
                    $na = "🔒 O'chirish";
                    $d = "OFF";
                } else {
                    $na = "🔓 Yoqish";
                    $d = "ON";
                }
            }
        } else {
        }
    }
    $adds = json_decode(get("set/sub.json"), 1);
    $adds['cate_id'] = $n;
    put("set/sub.json", json_encode($adds));
    $new_arr = [];
    $k = [];
    $a = mysqli_query($connect, "SELECT * FROM cates WHERE category_id = $n");
    $c = mysqli_num_rows($a);
    while ($s = mysqli_fetch_assoc($a)) {
        if (!in_array(enc("decode", $s['name']), $new_arr)) {
            $new_arr[] = enc("decode", $s['name']);
            $k[] = ['text' => "" . enc("decode", $s['name']), 'callback_data' => "tanla2=" . $s['cate_id']];
        }
    }
    $keyboard2 = array_chunk($k, 1);
    $keyboard2[] = [['text' => "$n1", 'callback_data' => "editFolss=$n"], ['text' => "$n2", 'callback_data' => "adFol=$n"], ['text' => "$n3", 'callback_data' => "delFol=$n"]];
    $keyboard2[] = [['text' => "⏪ Orqaga", 'callback_data' => "absd"]];
    $kb = json_encode([
        'inline_keyboard' => $keyboard2,
    ]);
    if (!$c) {
        if ($chat_id == $admin) {
            edit($chat_id, $message_id, "<b>«" . enc("decode", $aa['category_name']) . "» - tarmoq bo'limlaridan birini tanlang.</b>", $kb);
        } else {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => "⚠️ Ushbu tarmq uchun xizmat turlari topilmadi!",
                'show_alert' => true,
            ]);
        }
    } else {
        edit($chat_id, $message_id, "<b>«" . enc("decode", $aa['category_name']) . "» - tarmoq bo'limlaridan birini tanlang.</b>", $kb);
        exit;
    }
}



if (mb_stripos($data, "editFolss=") !== false) {
    $ex = explode("=", $data)[1];
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>Yangi nom kiriting:</b>",
        'parse_mode' => 'html',
        'reply_markup' => $ort
    ]);
    file_put_contents("user/$cid2.step", "editFol=$ex");
}

if ((mb_stripos($step, "editFol=") !== false)) {
    $ex = explode("=", $step)[1];
    if (isset($text)) {
        $text = enc("encode", $text);
        mysqli_query($connect, "UPDATE categorys SET category_name = '$text' WHERE category_id = $ex");
        bot('SendMessage', [
            'chat_id' => $cid,
            'text' => "<b>Muvaffaqiyatli o'zgartirildi.</b>",
            'parse_mode' => 'html',
            'reply_markup' => $m
        ]);
        unlink("user/$cid.step");
    }
}

if (mb_stripos($data, "adFol=") !== false) {
    $ex = explode("=", $data)[1];
    file_put_contents("set/c.txt", $ex);
    bot('deleteMessage', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
    ]);
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "<b>Yangi ichki bo'lim uchun nom yuboring:</b>",
        'parse_mode' => 'html',
        'reply_markup' => $aort
    ]);
    file_put_contents("user/$chat_id.step", 'newFold');
}


if ($step == "newFold") {
    if (isset($text)) {
        $ci = get("set/c.txt");
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>$text</b> - nomli ichki bo'lim qo'shildi!",
            'parse_mode' => 'html',
            'reply_markup' => $m
        ]);
        $to = enc("encode", $text);
        mysqli_query($connect, "INSERT INTO cates(`name`,`category_id`) VALUES ('$to','$ci')");
        unlink("user/$cid.step");
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>Yana ichki bo'lim qo'shish uchun ''➕'' tugmasini bosing!</b>",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "➕", 'callback_data' => "adFol=$ci"]],
                ]
            ])
        ]);
    }
}


if (mb_stripos($data, "delFol=") !== false) {
    $ex = explode("=", $data)[1];
    $c = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM categorys WHERE category_id = $ex"));
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>" . enc("decode", $c['category_name']) . "</b> - bo'limni o'chirishga rizimisiz ?
   
<i>Bo'lim o'chirilsa qayta tiklash imkoni bo'lmaydi, rozi bo'lsangiz ''🗑 O'chirish'' tugmasini bosing!</i>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🗑 O'chirish", 'callback_data' => "delFols=$ex"]],
                [['text' => "⏪ Orqaga", 'callback_data' => "tanla1=$ex"]],
            ]
        ])
    ]);
}

if (mb_stripos($data, "delFols=") !== false) {
    $ex = explode("=", $data)[1];
    $cc = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM categorys WHERE category_id = $ex"));
    $sd = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM categorys WHERE category_id  = $ex"));
    $cd = $sd['category_id'];
    $d = enc("decode", $sd['category_name']);
    $qd = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM cates WHERE category_id  = $ex"));
    $sa = $qd['cate_id'];
    mysqli_query($connect, "DELETE FROM services WHERE category_id=$sa");
    mysqli_query($connect, "DELETE FROM cates WHERE category_id = $cd");
    mysqli_query($connect, "DELETE FROM categorys WHERE category_id='$ex'");
    bot('deleteMessage', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
    ]);
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "<b>" . enc("decode", $cc['category_name']) . "</b> - bo'limi o'chirildi!",
        'parse_mode' => 'html',
        'reply_markup' => $m
    ]);
}

if (mb_stripos($data, "tanla2=") !== false and joinchat($chat_id) == 1) {
    $n = explode("=", $data)[1];
    $as = 0;
    $caid = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM cates WHERE cate_id  = $n")); {
        if ($chat_id == $admin) {
            $nn = "Xizmatlarni yuklab olish";
            $n1 = "📝";
            $n2 = "➕";
            $n3 = "🗑";
            $a = mysqli_query($connect, "SELECT * FROM services WHERE category_id = '$n'");
        } else {
            $a = mysqli_query($connect, "SELECT * FROM services WHERE category_id = '$n' AND service_status = 'on'");
        }
    }
    $c = mysqli_num_rows($a);
    while ($s = mysqli_fetch_assoc($a)) {
        $as++;
        $narx = $s['service_price'];
        $k[] = ['text' => "" . base64_decode($s['service_name']) . " - $narx so‘m", 'callback_data' => "ordered=" . $s['service_id'] . "=" . $n];
    }
    $keyboard2 = array_chunk($k, 1);
    $adds = json_decode(get("set/sub.json"), 1);
    $keyboard2[] = [['text' => "$nn", 'callback_data' => "uplads-$n"]];
    $keyboard2[] = [['text' => "$n1", 'callback_data' => "editFoldm=$n"], ['text' => "$n2", 'callback_data' => "adds-$n"], ['text' => "$n3", 'callback_data' => "delFolm=$n"]];
    $keyboard2[] = [['text' => "⏪ Orqaga", 'callback_data' => "tanla1=" . $adds['cate_id']]];
    $kb = json_encode([
        'inline_keyboard' => $keyboard2,
    ]);
    if (!$c) {
        if ($chat_id == $admin) {
            edit($chat_id, $message_id, "<b>«" . enc("decode", $caid['name']) . "» - bo'lim xizmatlaridan birini tanlang.</b>

<b><i>💴 Narxlar 1000 tasi uchun berilgan:</i></b>", $kb);
        } else {
            bot('answerCallbackQuery', [
                'callback_query_id' => $qid,
                'text' => "⚠️ Ushbu bo'lim uchun xizmatlar topilmadi!",
                'show_alert' => true,
            ]);
        }
    } else {
        edit($chat_id, $message_id, "<b>«" . enc("decode", $caid['name']) . "» - bo'lim xizmatlaridan birini tanlang.</b>

<b><i>💴 Narxlar 1000 tasi uchun berilgan:</i></b>", $kb);
        exit;
    }
}


if (mb_stripos($data, "editFoldm=") !== false) {
    $ex = explode("=", $data)[1];
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>Ichki bo'lim uchun yangi nom kiriting:</b>",
        'parse_mode' => 'html',
        'reply_markup' => $ort
    ]);
    file_put_contents("user/$cid2.step", "editFoldms-$ex");
}

if (mb_stripos($step, "editFoldms-") !== false) {
    $ex = explode("-", $step)[1];
    if (isset($text)) {
        $text = enc("encode", $text);
        mysqli_query($connect, "UPDATE cates SET name = '$text' WHERE cate_id = $ex");
        bot('SendMessage', [
            'chat_id' => $cid,
            'text' => "<b>Muvaffaqiyatli o'zgartirildi.</b>",
            'parse_mode' => 'html',
            'reply_markup' => $m
        ]);
        unlink("user/$cid.step");
    }
}


if (mb_stripos($data, "delFolm=") !== false) {
    $ex = explode("=", $data)[1];
    $c = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM cates WHERE cate_id  = $ex"));
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>" . enc("decode", $c['name']) . "</b> - nomli ichki bo'limni o'chirishga rozimisiz ?
   
<i>Ichki bo'lim o'chirilsa qayta tiklash imkoni bo'lmaydi, rozi bo'lsangiz ''🗑 O'chirish'' tugmasini bosing!</i>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🗑 O'chirish", 'callback_data' => "delFoll=$ex"]],
                [['text' => "⏪ Orqaga", 'callback_data' => "tanla2=$ex"]],
            ]
        ])
    ]);
}





if (mb_stripos($data, "delFoll=") !== false) {
    $ex = explode("=", $data)[1];

    $qd = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM cates WHERE cate_id  = $ex"));
    $sa = $qd['cate_id'];
    $d = enc("decode", $qd['name']);
    mysqli_query($connect, "DELETE FROM services WHERE category_id=$sa");
    mysqli_query($connect, "DELETE FROM cates WHERE cate_id=$ex");
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>$d</b> - nomli ichki bo'lim o'chirildi!",
        'parse_mode' => 'html',
        'reply_markup' => $m
    ]);
}

if (stripos($data, "uplads-") !== false) {
    $n = explode("-", $data)[1];
    $upx = json_decode(get("set/upladd.json"), 1);
    $upx['cate_id'] = $n;
    file_put_contents("set/upladd.json", json_encode($upx, JSON_PRETTY_PRINT));
    $pr = 0;
    $prs = "";
    $a = mysqli_query($connect, "SELECT * FROM providers");
    $c = mysqli_num_rows($a);
    while ($s = mysqli_fetch_assoc($a)) {
        $pr++;
        $prtxt = str_replace(["/api/adapter/default/index", "/api/v1", "/api/v2", "https://"], ["", "", "", ""], $s['api_url']);
        $prs .= "<b>" . $pr . "</b>: $prtxt\n";
        $k[] = ['text' => $pr, 'callback_data' => "uplprv-" . $s['id']];
    }
    $keyboard2 = array_chunk($k, 3);
    $kb = json_encode([
        'inline_keyboard' => $keyboard2,
    ]);
    if (!$c) {

        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "⚠️ Provayderlar topilmadi!",
            'show_alert' => true,
        ]);
    } else {
        del();
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "Provayderni tanlang:
 
$prs",
            'parse_mode' => "HTML",
            'reply_markup' => $kb,
        ]);
    }
}

if (stripos($data, "uplprv-") !== false) {
    $n = explode("-", $data)[1];
    $upx = json_decode(get("set/upladd.json"), 1);
    $upx['provider'] = $n;
    file_put_contents("set/upladd.json", json_encode($upx, JSON_PRETTY_PRINT));
    edit($chat_id, $message_id, "Provayderning API valyutasini tanlang:", json_encode([
        'inline_keyboard' => [
            [['text' => "UZS", 'callback_data' => "uplval-UZS-" . $upx['provider']]],
            [['text' => "USD", 'callback_data' => "uplval-USD-" . $upx['provider']]],
            [['text' => "RUB", 'callback_data' => "uplval-RUB-" . $upx['provider']]],
            [['text' => "INR", 'callback_data' => "uplval-INR-" . $upx['provider']]],
            [['text' => "TRY", 'callback_data' => "uplval-TRY-" . $upx['provider']]],
        ]
    ]));
}


if (stripos($data, "uplval-") !== false) {
    $n = explode("-", $data)[1];
    $prv = explode("-", $data)[2];
    $upx = json_decode(get("set/upladd.json"), 1);
    $upx['currency'] = $n;
    file_put_contents("set/upladd.json", json_encode($upx, JSON_PRETTY_PRINT));
    $h = json_decode(arr($prv));
    $ko = 1;
    if ($h->error) {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "⚠️ Serverda nosozlik

Qaytadan urining",
            'show_alert' => true,
        ]);
    } else {
        for ($i = 0; $i <= 22; $i++) {
            if ($h->results[$i]->name) {
                $arr3[] = ['text' => "" . $h->results[$i]->name . "", 'callback_data' => "apload=$i=$prv"];
            }
        }
    }
    $arr = array_chunk($arr3, 1);
    $arr[] = [['text' => "Orqaga", 'callback_data' => "xizmat"], ['text' => "▶️ Keyingi", 'callback_data' => "nexti=next=$prv=$ko=$i"]];
    $kb = json_encode([
        'inline_keyboard' => $arr,
    ]);

    edit($chat_id, $message_id, "Kerakli xizmat turini tanlang", $kb);
}

if ((stripos($data, "nexti=") !== false)) {
    $res = explode("=", $data)[1];
    $prv = explode("=", $data)[2];
    $ko = explode("=", $data)[3];
    $kl = explode("=", $data)[4];
    $h = json_decode(arr($prv));
    $ko = $kl;
    if ($h->error) {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "⚠️ Serverda nosozlik

Qaytadan urining",
            'show_alert' => true,
        ]);
    } else {
        if ($res == "next") {
            $ma = $kl * 2;
            for ($i = $kl; $i <= $ma; $i++) {
                $d = $h->results[$i]->name ? $h->results[$i]->name : "";
                if ($h->results[$i]->name) {
                    $arr3[] = ['text' => $d, 'callback_data' => "apload=$i=$prv"];
                }
            }
        }

        $arr = array_chunk($arr3, 1);

        $arr[] = [['text' => "Orqaga", 'callback_data' => "xizmat"], ['text' => "▶️ Keyingi", 'callback_data' => "nexti=next=$prv=$ko=$i"]];
        $kb = json_encode([
            'inline_keyboard' => $arr,
        ]);
        edit($chat_id, $message_id, "Kerakli xizmat turini tanlang:", $kb);
        exit();
    }
}

if ((stripos($data, "apload=") !== false)) {
    $qa = explode("=", $data)[1];
    $qa = $qa + 1;
    $prv = explode("=", $data)[2];
    $h = json_decode(arr($prv), 1);
    if ($h['error']) {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "⚠️ Serverda nosozlik
	
Qaytadan urining",
            'show_alert' => true,
        ]);
    }
    foreach ($h['results'] as $vs) {
        if ($vs['id'] == $qa) {
            $nq = $vs['name'] ? $nq = $vs['name'] : "";
        }
    }
    bot('answerCallbackQuery', [
        'callback_query_id' => $qid,
        'text' => "$nq - uchun xizmatlar qidirilmoqda

Iltimos kuting...",
        'show_alert' => true,
    ]);
    $upx = json_decode(get("set/upladd.json"), 1);
    $upx['category'] = $nq;
    file_put_contents("set/upladd.json", json_encode($upx, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $s = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `providers` WHERE id = $prv"));
    $j = json_decode(file_get_contents($s['api_url'] . "?key=" . $s['api_key'] . "&action=services"), 1);
    $service_count = 0;
    $serviceid = 0;
    foreach ($j as $el) {
        if ($el['category'] == $nq) {

            $service_count++;
            $serviceid++;
            $name = $el["name"];
            $txe = $el['service'];
            $min = $el["min"];
            $max = $el["max"];
            $type = $el['type'];
            $service_ide = $el['service'];
            $cancel = $el['cancel'] ? 'true' : 'false';
            $dripfeed = $el['dripfeed'] ? 'true' : 'false';
            $refill = $el['refill'] ? 'true' : 'false';
            $k[] = ['text' => ($name), 'callback_data' => "couple=" . $txe];
        }
    }
    $ko = array_chunk($k, 1);
    if (empty($service_count)) {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "⚠️ Serverda nosozlik
	
Qaytadan urining",
            'show_alert' => true,
        ]);
    } else {
        $ko[] = [['text' => "✅ Barchasini yuklab olish", 'callback_data' => "allapl=$prv"]];
    }
    $ko[] = [['text' => "Orqaga", 'callback_data' => "xizmat"]];
    $kb = json_encode([
        'inline_keyboard' => $ko
    ]);
    edit($chat_id, $message_id, "
$nq

🔢 Xizmatlar soni: $service_count - ta", $kb);
}


if ((stripos($data, "allapl=") !== false)) {
    del();
    $prv = explode("=", $data)[1];
    $mas = bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "📂 Yuklab olish boshlandi!..

🔔 Iltimos kuting.",
    ])->result->message_id;

    $upx = json_decode(get("set/upladd.json"), 1);

    $s = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `providers` WHERE id = $prv"));

    $j = json_decode(file_get_contents($s['api_url'] . "?key=" . $s['api_key'] . "&action=services"), 1);
    if (empty($j)) {
        edit($cid2, $mas, "⚠️ Serverda nosozlik

Qaytadan urining", null);
    } else {
        $service_id = mysqli_num_rows(mysqli_query($connect, "SELECT * FROM `services`"));
        foreach ($j as $el) {
            if ($el['category'] == $upx['category']) {
                $service_id++;
                $name = ($el["name"]);
                $tas = $el['service'];
                $min = $el["min"];
                $max = $el["max"];
                $rate = $el["rate"];
                $type = $el['type'];
                $cancel = $el['cancel'] ? 'true' : 'false';
                $dripfeed = $el['dripfeed'] ? 'true' : 'false';
                $refill = $el['refill'] ? 'true' : 'false';

                if ($upx['currency'] == "USD") {
                    $fr = get("set/usd");
                } elseif ($upx['currency'] == "RUB") {
                    $fr = get("set/rub");
                } elseif ($upx['currency'] == "INR") {
                    $fr = get("set/inr");
                } elseif ($upx['currency'] == "TRY") {
                    $fr = get("set/try");
                } elseif ($upx['currency'] == "UZS") {
                    $fr = 1;
                }

                $foiz = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM percent WHERE id = 1"))['percent'];
                $rate = $rate * $fr;
                $rp = $rate / 100;
                $rp = $rp * $foiz + $rate;


                $service_price = $rp;
                $category_id = $upx['cate_id'];
                $api_service = $prv;
                $api_currency = $upx['currency'];
                $service_name = base64_encode(mb_convert_encoding(trans($name), "UTF-8", "UTF-8"));
                $service_desc = null;
                $service_edit = "true";
                $sq = mysqli_query($connect, "INSERT INTO 
services(`service_status`,`service_edit`,`service_price`,`category_id`,`service_api`,`api_service`,`api_currency`,`service_type`,`api_detail`,`service_name`,`service_desc`,`service_min`,`service_max`) VALUES ('on','$service_edit','$service_price','$category_id','$tas','$api_service','$api_currency','$type','{\"name\":\"$name\",\"min\":\"$min\",\"max\":\"$max\",\"type\":\"$type\",\"cancel\":\"$cancel\",\"refill\":\"$refill\",\"dripfeed\":\"$dripfeed\"}','$service_name','$service_desc','$min','$max');");
            }
        }

        edit($chat_id, $mas, "✅ Yuklab olish jarayoni tugallandi.", null);
        unlink("user/$cid2.step");
    }
}


if (mb_stripos($data, "adds-") !== false) {
    $pw = explode("-", $data)[1];
    file_put_contents("user/$chat_id.cate_id", $pw);
    $addss['cate_id'] = $pw;
    $adds = json_decode(get("set/adds.json"), 1);
    $adds['cate_id'] = $pw;
    $a = mysqli_query($connect, "SELECT * FROM providers");
    $c = mysqli_num_rows($a);
    if (!$c) {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "⚠️ Provayderlar topilmadi!",
            'show_alert' => true,
        ]);
    } else {
        $adds['category_id'] = file_get_contents("set/c.txt");
        put("set/adds.json", json_encode($adds, JSON_UNESCAPED_UNICODE));
        bot('deleteMessage', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
        ]);
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "<b>Yangi xizmat nomini yuboring:</b>",
            'parse_mode' => 'html',
            'reply_markup' => $ort
        ]);
        file_put_contents("user/$chat_id.step", 'servisw');
    }
}
if ($step == "servisw") {
    $pr = 0;
    $prs = "";
    $a = mysqli_query($connect, "SELECT * FROM providers");
    $c = mysqli_num_rows($a);
    while ($s = mysqli_fetch_assoc($a)) {
        $pr++;
        $prtxt = str_replace(["/api/v1", "/api/v2", "https://"], ["", "", ""], $s['api_url']);
        $prs .= "<b>" . $pr . "</b>: $prtxt\n";
        $k[] = ['text' => $pr, 'callback_data' => "checkC-" . $s['id']];
    }
    $keyboard2 = array_chunk($k, 3);
    $kb = json_encode([
        'inline_keyboard' => $keyboard2,
    ]);
    if (!$c) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "⚠️ Provayderlar topilmadi!",
        ]);
    } else {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "Provayderni tanlang:
 
$prs",
            'parse_mode' => "HTML",
            'reply_markup' => $kb,
        ]);

        put("set/adds.json.name", $text);
        file_put_contents("user/$cid.step", "servis0");
    }
}

if ((stripos($data, "checkC-") !== false and $stepc == "servis0" and $chat_id == $admin)) {
    $pw = explode("-", $data)[1];
    sms($chat_id, "Provayderning API xizmatlari bolimida korsatilgan valyutani tanlang:", json_encode([
        'inline_keyboard' => [
            [['text' => "UZS ", 'callback_data' => "checkP-UZS"]],
            [['text' => "USD ", 'callback_data' => "checkP-USD"]],
            [['text' => "RUB ", 'callback_data' => "checkP-RUB"]],
            [['text' => "INR ", 'callback_data' => "checkP-INR"]],
            [['text' => "TRY ", 'callback_data' => "checkP-TRY"]],
        ]
    ]));
    $adds = json_decode(get("set/adds.json"), 1);
    $adds['api_service'] = $pw;
    put("set/adds.json", json_encode($adds, JSON_UNESCAPED_UNICODE));
    file_put_contents("user/$chat_id.step", 'servis1');
}

if ((stripos($data, "checkP-") !== false and  $stepc == "servis1" and $chat_id == $admin)) {
    $pw = explode("-", $data)[1];
    if (isset($data)) {
        sms($chat_id, "📝 Xizmat xaqida malumotlar kiriting:

⚠️ Ma'lumot kiritish ni xoxlamasangiz <b>Kiritilmagan</b> tugmasini bosing", json_encode([
            'resize_keyboard' => true,
            'keyboard' => [
                [['text' => "Kiritilmagan"]],
                [['text' => "⏪ Orqaga"]],
            ]
        ]));
        $adds = json_decode(get("set/adds.json"), 1);
        $adds['api_currency'] = $pw;
        put("set/adds.json", json_encode($adds, JSON_UNESCAPED_UNICODE));
        file_put_contents("user/$chat_id.step", 'servis2');
    }
}
if (($step == "servis2" and $cid == $admin)) {
    if (isset($text)) {
        sms($cid, "💵 Buyurtma narxini yuboring (1000 ta) uchun", $ort);
        if ($text == "Kiritilmagan") {
            put("set/adds.json.desc", "");
        } else {
            put("set/adds.json.desc", $text);
        }
        file_put_contents("user/$cid.step", 'servis3');
    }
}


if (($step == "servis3" and $cid == $admin)) {
    if (is_numeric($text)) {
        sms($cid, "🆔 Xizmat IDsini yuboring:", $ort);
        $adds = json_decode(get("set/adds.json"), 1);
        $adds['service_price'] = $text;
        put("set/adds.json", json_encode($adds, JSON_UNESCAPED_UNICODE));
        file_put_contents("user/$cid.step", 'servisID');
    }
}


if ($step == "servisID") {
    if (is_numeric($text)) {
        $pw = json_decode(get("set/adds.json"));
        $cure = $pw->api_service;
        $ap = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = $cure"));
        $surl = $ap['api_url'];
        $skey = $ap['api_key'];
        $j = json_decode(get($surl . "?key=" . $skey . "&action=services"), true);
        foreach ($j as $el) {
            if ($el['service'] == "$text") {
                $name = $el["name"];
                $min = $el["min"];
                $max = $el["max"];
                $rate = $el["rate"];
                $rate = $el["rate"];
                $type = $el['type'];
                $tas = $el['service'];
                $cancel = $el['cancel'] ? 'true' : 'false';
                $dripfeed = $el['dripfeed'] ? 'true' : 'false';
                $refill = $el['refill'] ? 'true' : 'false';
                break;
            }
        }


        if (empty($min) and empty($max)) {
            sms($cid, "
🔕 Noma'lum xatolik yuz berdi.

Qaytadan xizmat IDsini yuboring:", null);
        } else {
            $category_id = $pw->cate_id;
            $service_price = $pw->service_price;
            $api_service = $pw->api_service;
            $api_currency = $pw->api_currency;
            $service_name = base64_encode(mb_convert_encoding(get("set/adds.json.name"), "UTF-8", "UTF-8"));
            $service_desc = base64_encode(get("set/adds.json.desc"));
            $cate_id = file_get_contents("user/$cid.cate_id");
            $service_edit = "true";
            mysqli_query($connect, "INSERT INTO services(`service_status`,`service_price`,`service_edit`,`category_id`,`service_api`,`api_service`,`api_currency`,`service_type`,`api_detail`,`service_name`,`service_desc`,`service_min`,`service_max`) VALUES ('on','$service_price','$service_edit','$category_id','$text','$api_service','$api_currency','$type','{\"name\":\"$name\",\"min\":\"$min\",\"max\":\"$max\",\"type\":\"$type\",\"cancel\":\"$cancel\",\"refill\":\"$refill\",\"dripfeed\":\"$dripfeed\"}','$service_name','$service_desc','$min','$max');");

            sms($cid, "✅ Yangi xizmat qo'shildi.", $m);
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "<b>Yana xizmat qo'shish uchun ''➕'' tugmasini bosing!</b>",
                'parse_mode' => 'html',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => "➕", 'callback_data' => "adds-$cate_id"]],
                    ]
                ])
            ]);
            unlink("user/$cid.cate_id");
        }
    }
}



if ($data == "order") {
    del();
    sms($cid2, "<b>🆔 O'zingizga kerak bo'lgan xizmat id raqamini yuboring:</b>", $ort);
    put("user/$cid2.step", "ordered");
}

if ($step == "ordered") {
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM services WHERE service_id = $text"));
    if ($rew) {
        bot('deleteMessage', [
            'chat_id' => $cid,
            'message_id' => $del,
        ]);

        $del = bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "<b>Yuklanmoqda...</b>",
            'parse_mode' => 'html',
            'reply_markup' => json_encode(['remove_keyboard' => true])
        ])->result->message_id;

        $a = mysqli_query($connect, "SELECT * FROM services WHERE service_id= '$text'");
        while ($s = mysqli_fetch_assoc($a)) {
            $nam = base64_decode($s['service_name']);
            $sid = $s['service_id'];
            $narx = $s['service_price'];
            $curr = $s['api_currency'];
            $ab = $s['service_desc'] ? $ab = $s['service_desc'] : null;
            $api = $s['api_service'];
            $type = $s['service_type'];
            $spi = $s['service_api'];
            $min = $s["service_min"];
            $max = $s["service_max"];
            $average_time = $s['average_time']; // O'rtacha vaqtni o'qish
        }

        if ($curr == "USD") {
            $fr = get("set/usd");
        } elseif ($curr == "RUB") {
            $fr = get("set/rub");
        } elseif ($curr == "INR") {
            $fr = get("set/inr");
        } elseif ($curr == "TRY") {
            $fr = get("set/try");
        }
        $ab ? $abs = "" . base64_decode($ab) . "" : null;

        if ($type == "Default" || $type == "default") {
            $ab = "<b>⏬ Minimal</b> - $min ta
<b>⏫ Maksimal</b> - $max ta

$abs";
        } elseif ($type == "Package") {
            $ab = "$abs";
        }

        if (empty($min) || empty($max)) {
            bot('answerCallbackQuery', [
                'callback_query_id' => $update->callback_query->id,
                'text' => "⚠️ Nimadur xato ketdi qaytadan urining.",
                'show_alert' => true,
            ]);
        } else {
            bot('deleteMessage', [
                'chat_id' => $cid,
                'message_id' => $del,
            ]);
            bot('sendMessage', [
                'chat_id' => $cid,
                'text' => "<b>🚀 Xizmat nomi:</b> " . ($nam) . "

<blockquote><b>🔑 Xizmat IDsi:</b> <code>$sid</code>
<b>💰 Xizmat narxi (1000x):</b> $narx so‘m
<b>♻️ Qayta tiklash: Mavjud emas !</b>
<b>🚫 Bekor qilish: Mavjud emas !</b></blockquote>

$ab<blockquote><b>⚠️ Buyurtma vaqtida havolani o‘zgartirish mumkin emas. Aks holda buyurtmangiz tugallangan holatga oʻzgaradi, bu holda biz toʻlovni qaytarmaymiz!</b>

<b>☝️ Ta‘rif bilan tanishib chiqib «✅ Buyurtma berish» tugmasini bosing.</b></blockquote>
",
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => "✅ Buyurtma berish", 'callback_data' => "aosdrder=$spi=$min=$max=" . $narx . "=$type=" . $api . "=$sid"]],
                    ]
                ])
            ]);
            sms($cid, "🖥️ Asosiy menyudasiz", $m);
            unlink("user/$cid.step");
        }
    } else {
        bot('SendMessage', [
            'chat_id' => $cid,
            'text' => "<b>Siz kiritgan ID bo'yicha hech qanday xizmat topilmadi!</b>

Qayta urinib ko'ring:",
            'parse_mode' => 'html',
        ]);
    }
}


if ((stripos($data, "ordered=") !== false)) {
    $n = explode("=", $data)[1];
    $n2 = explode("=", $data)[2];
    $aa = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM services WHERE service_id = $n"));
    $a = mysqli_query($connect, "SELECT * FROM services WHERE service_id= '$n'");
    while ($s = mysqli_fetch_assoc($a)) {
        $nam = base64_decode($s['service_name']);
        $sid = $s['service_id'];
        $narx = $s['service_price'];
        $curr = $s['api_currency'];
        $ab = $s['service_desc'] ? $ab = $s['service_desc'] : null;
        $api = $s['api_service'];
        $type = $s['service_type'];
        $spi = $s['service_api'];
        $min = $s["service_min"];
        $max = $s["service_max"];
        $average_time = $s["average_time"]; // O'rtacha bajarish vaqti
    }

    $ap = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = $api"));
    $surl = $ap['api_url'];
    $skey = $ap['api_key'];
    $j = json_decode(get($surl . "?key=" . $skey . "&action=services"), true);
    foreach ($j as $el) {
        if ($el['service'] == $spi) {
            $amin = $el["min"];
            $amax = $el["max"];
            break;
        }
    }

    if ($curr == "USD") {
        $fr = get("set/usd");
    } elseif ($curr == "RUB") {
        $fr = get("set/rub");
    } elseif ($curr == "INR") {
        $fr = get("set/inr");
    } elseif ($curr == "TRY") {
        $fr = get("set/try");
    }
    $ab ? $abs = "" . base64_decode($ab) . "" : null;

    if ($type == "Default" or $type == "default") {
        $ab = "<b>⏬ Minimal</b> - $min ta
<b>⏫ Maksimal</b> - $max ta

$abs";
    } elseif ($type == "Package") {
        $ab = "$abs";
    }
    if (empty($min) or empty($max)) {
        bot('answerCallbackQuery', [
            'callback_query_id' => $update->callback_query->id,
            'text' => "⚠️ Nimadur xato ketdi qaytadan urining.",
            'show_alert' => true,
        ]);
    } else { {
            if ($chat_id == $admin) {
                $nnn = "📝";
                $nnnn = "🗑";
            } else {
            }
        }
        bot('editMessageText', [
            'chat_id' => $chat_id,
            'message_id' => $message_id,
            'text' => "<b>🚀 Xizmat nomi:</b> " . ($nam) . "

<blockquote><b>🔑 Xizmat IDsi:</b> <code>$sid</code>
<b>💰 Xizmat narxi (1000x):</b> $narx so‘m
<b>♻️ Qayta tiklash: Mavjud emas !</b>
<b>🚫 Bekor qilish: Mavjud emas !</b></blockquote>

$ab<blockquote><b>⚠️ Buyurtma vaqtida havolani o‘zgartirish mumkin emas. Aks holda buyurtmangiz tugallangan holatga oʻzgaradi, bu holda biz toʻlovni qaytarmaymiz!</b>

<b>☝️ Ta‘rif bilan tanishib chiqib «✅ Buyurtma berish» tugmasini bosing.</b></blockquote>
",
            'parse_mode' => 'html',
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "$nnn", 'callback_data' => "edits=$sid=$n2"], ['text' => "$nnnn", 'callback_data' => "delxiz=$sid"]],
                    [['text' => "✅ Buyurtma berish", 'callback_data' => "aosdrder=$spi=$min=$max=" . $narx . "=$type=" . $api . "=$sid"]],
                    [['text' => "⏪ Orqaga", 'callback_data' => "tanla2=$n2"]],
                ]
            ])
        ]);
        exit;
    }
}


if (mb_stripos($data, "edits=") !== false) {
    $service_id = explode("=", $data)[1];
    $category_id = explode("=", $data)[2];
    $c = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM services WHERE service_id = $service_id"));
    $p = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = $c[api_service]"));

    if ($c['service_desc'] == null) {
        $service_desc = "Kiritilmagan";
    } else {
        $service_desc = enc("decode", $c['service_desc']);
    }
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>1. Nom:</b> " . enc("decode", $c['service_name']) . "
   
<b>2. Narx:</b> " . $c['service_price'] . " so'm
<b>3. Minimal:</b> " . $c['service_min'] . " ta
<b>4. Maksimal:</b> " . $c['service_max'] . " ta
<b>5. Provider:</b> <code>" . $p['api_url'] . "</code>
<b>6. Tavsif:</b> $service_desc

<b>API dagi SERVIS ID:</b> <code>" . $c['service_api'] . " </code>",
        'parse_mode' => 'html',
        'disable_web_page_preview' => true,
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "📝  Nom", 'callback_data' => "editservice=service_name=$service_id=$category_id"], ['text' => "📝  Narxi", 'callback_data' => "editservice=service_price=$service_id=$category_id"]],
                [['text' => "📝  Minimal", 'callback_data' => "editservice=service_min=$service_id=$category_id"], ['text' => "📝  Maksimal", 'callback_data' => "editservice=service_max=$service_id=$category_id"]],
                [['text' => "📝  Tavsif", 'callback_data' => "editservice=service_desc=$service_id=$category_id"]],
                [['text' => "📝  Provider", 'callback_data' => "editservispro=$service_id=$category_id"]],
                [['text' => "📝  API dagi SERVIS ID", 'callback_data' => "editservice_api=$service_id=$category_id"]],
                [['text' => "⏪ Orqaga", 'callback_data' => "ordered=$service_id=$category_id"]],
            ]
        ])
    ]);
    unlink("user/$cid.step");
}


if (mb_stripos($data, "editservice_api=") !== false) {
    $s_id = explode("=", $data)[1];
    $c_id = explode("=", $data)[2];
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>Yangi qiymatni kiriting:</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⏪ Orqaga", 'callback_data' => "edits=$s_id=$c_id"]],
            ]
        ])
    ]);
    file_put_contents("user/$cid2.step", "editXizma_t_id-$s_id-$c_id");
}



if (mb_stripos($step, "editXizma_t_id-") !== false) {
    $xiz = explode("-", $step)[1];
    $caid = explode("-", $step)[2];
    if (is_numeric($text)) {
        bot('SendMessage', [
            'chat_id' => $cid,
            'text' => "<b>Muvaffaqiyatli o'zgartirildi.</b>",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "⏪ Orqaga", 'callback_data' => "edits=$xiz=$caid"]],
                ]
            ])
        ]);
        unlink("user/$cid.step");
        mysqli_query($connect, "UPDATE services SET service_api='$text' WHERE service_id = $xiz");
        $providers_id = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM services WHERE service_id = $xiz"))['api_service'];
        $ap = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = $providers_id"));
        $surl = $ap['api_url'];
        $skey = $ap['api_key'];
        $j = json_decode(get($surl . "?key=" . $skey . "&action=services"), true);
        foreach ($j as $el) {
            if ($el['service'] == "$text") {
                $name = $el["name"];
                $min = $el["min"];
                $max = $el["max"];
                $rate = $el["rate"];
                $rate = $el["rate"];
                $type = $el['type'];
                $tas = $el['service'];
                $cancel = $el['cancel'] ? 'true' : 'false';
                $dripfeed = $el['dripfeed'] ? 'true' : 'false';
                $refill = $el['refill'] ? 'true' : 'false';
                break;
            }
        }
        mysqli_query($connect, "UPDATE services SET api_detail='{\"name\":\"$name\",\"min\":\"$min\",\"max\":\"$max\",\"type\":\"$type\",\"cancel\":\"$cancel\",\"refill\":\"$refill\",\"dripfeed\":\"$dripfeed\"}' WHERE service_id = $xiz");
        mysqli_query($connect, "UPDATE services SET service_api='$text' WHERE service_id = $xiz");
    } else {
        bot('SendMessage', [
            'chat_id' => $cid,
            'text' => "<b>⚠️ ID raqam yuboring.</b>",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "⏪ Orqaga", 'callback_data' => "edits=$xiz=$caid"]],
                ]
            ])
        ]);
    }
}

//// mysqli_query($connect,"UPDATE services SET service_api='$vo' WHERE service_id = $xiz");

if (mb_stripos($data, "editservispro=") !== false) {
    $s_id = explode("=", $data)[1];
    $c_id = explode("=", $data)[2];
    $pr = 0;
    $prs = "";
    $a = mysqli_query($connect, "SELECT * FROM providers");
    $c = mysqli_num_rows($a);
    while ($s = mysqli_fetch_assoc($a)) {
        $pr++;
        $prtxt = str_replace(["/api/v1", "/api/v2", "https://"], ["", "", ""], $s['api_url']);
        $prs .= "<b>" . $pr . "</b>: $prtxt\n";
        $k[] = ['text' => $pr, 'callback_data' => "editprovider-$s_id-$c_id-" . $s['id']];
    }
    $keyboard2 = array_chunk($k, 4);
    $keyboard2[] = [['text' => "⏪ Orqaga", 'callback_data' => "edits=$s_id=$c_id"]];
    $kb = json_encode([
        'inline_keyboard' => $keyboard2,
    ]);
    if (!$c) {
        bot('answerCallbackQuery', [
            'callback_query_id' => $qid,
            'text' => "⚠️ Provayderlar topilmadi!",
            'show_alert' => true,
        ]);
    } else {
        del();
        bot('sendMessage', [
            'chat_id' => $cid2,
            'text' => "Provayderni tanlang:
 
$prs",
            'parse_mode' => "HTML",
            'reply_markup' => $kb,
        ]);
    }
}

if ((stripos($data, "editprovider-") !== false and $cid2 == $admin)) {
    $s_id = explode("-", $data)[1];
    $c_id = explode("-", $data)[2];
    $p_id = explode("-", $data)[3];
    mysqli_query($connect, "UPDATE services SET api_service='$p_id' WHERE service_id = $s_id");
    del();
    bot('SendMessage', [
        'chat_id' => $cid2,
        'text' => "<b> Muvaffaqiyatli o'zgartirildi.</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⏪ Orqaga", 'callback_data' => "edits=$s_id=$c_id"]],
            ]
        ])
    ]);
}


if (mb_stripos($data, "editservice=") !== false) {
    $s = explode("=", $data)[1];
    $s_id = explode("=", $data)[2];
    $c_id = explode("=", $data)[3];
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>Yangi qiymatni kiriting:</b>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "⏪ Orqaga", 'callback_data' => "edits=$s_id=$c_id"]],
            ]
        ])
    ]);
    file_put_contents("user/$cid2.step", "editXizmatid-$s_id-$s-$c_id");
}





if (mb_stripos($step, "editXizmatid-") !== false) {
    $xiz = explode("-", $step)[1];
    $ex = explode("-", $step)[2];
    $caid = explode("-", $step)[3];
    if ($cid == $admin and isset($text)) {
        bot('SendMessage', [
            'chat_id' => $cid,
            'text' => "<b> Muvaffaqiyatli o'zgartirildi.</b>",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "⏪ Orqaga", 'callback_data' => "edits=$xiz=$caid"]],
                ]
            ])
        ]);
        if ($ex == "service_desc") {
            $vo = base64_encode($text);
            mysqli_query($connect, "UPDATE services SET service_desc='$vo' WHERE service_id = $xiz");
        } elseif ($ex == "service_name") {
            $vo = base64_encode($text);
            mysqli_query($connect, "UPDATE services SET service_name='$vo' WHERE service_id = $xiz");
        } elseif ($ex == "service_price") {
            $vo = $text;
            mysqli_query($connect, "UPDATE services SET service_edit='false', service_price='$vo' WHERE service_id = $xiz");
        } elseif ($ex == "service_min") {
            $vo = $text;
            mysqli_query($connect, "UPDATE services SET service_edit='false', service_min='$vo' WHERE service_id = $xiz");
        } elseif ($ex == "service_max") {
            $vo = $text;
            mysqli_query($connect, "UPDATE services SET service_edit='false', service_max='$vo' WHERE service_id = $xiz");
        }
        unlink("user/$cid.step");
    }
}


if (mb_stripos($step, "editXizmatlar-") !== false) {
    $xiz = explode("-", $step)[1];
    $ex = explode("-", $step)[2];
    $caid = explode("-", $step)[3];
    if ($cid == $admin and isset($text)) {
        bot('SendMessage', [
            'chat_id' => $cid,
            'text' => "<b> Muvaffaqiyatli o'zgartirildi.</b>",
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => "⏪ Orqaga", 'callback_data' => "edits=$xiz=$caid"]],
                ]
            ])
        ]);
        if ($ex == "service_desc") {
            $vo = base64_encode($text);
            mysqli_query($connect, "UPDATE services SET service_desc='$vo' WHERE service_id = $xiz");
        } elseif ($ex == "service_name") {
            $vo = base64_encode($text);
            mysqli_query($connect, "UPDATE services SET service_name='$vo' WHERE service_id = $xiz");
        } elseif ($ex == "service_id") {
            $vo = $text;
            mysqli_query($connect, "UPDATE services SET service_api='$vo' WHERE service_id = $xiz");
        } elseif ($ex == "service_price") {
            $vo = $text;
            mysqli_query($connect, "UPDATE services SET service_edit='false', service_price='$vo' WHERE service_id = $xiz");
        } elseif ($ex == "service_min") {
            $vo = $text;
            mysqli_query($connect, "UPDATE services SET service_edit='false', service_min='$vo' WHERE service_id = $xiz");
        } elseif ($ex == "service_max") {
            $vo = $text;
            mysqli_query($connect, "UPDATE services SET service_edit='false', service_max='$vo' WHERE service_id = $xiz");
        }
        unlink("user/$cid.step");
    }
}


if (mb_stripos($data, "delxiz=") !== false) {
    $ex = explode("=", $data)[1];
    $ex2 = explode("=", $data)[1];
    $c = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM services WHERE service_id = $ex"));
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "<b>" . enc("decode", $c['service_name']) . "</b> - xizmatini o'chirishga rizimisiz ?
   
<i>Xizmat o'chirilsa qayta tiklash imkoni bo'lmaydi, rozi bo'lsangiz ''🗑 O'chirish'' tugmasini bosing!</i>",
        'parse_mode' => 'html',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🗑 O'chirish", 'callback_data' => "delmat-$ex"]],
                [['text' => "⏪ Orqaga", 'callback_data' => "ordered=$ex=$ex2"]],
            ]
        ])
    ]);
}


if (mb_stripos($data, "delmat-") !== false) {
    $ichki = explode("-", $data)[1];
    mysqli_query($connect, "DELETE FROM services WHERE service_id = $ichki");
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendMessage', [
        'chat_id' => $cid2,
        'text' => "Xizmat o‘chirildi!",
        'parse_mode' => 'html',
        'reply_markup' => $m
    ]);
}

if ((stripos($data, "aosdrder=") !== false)) {
    del();
    $oid = explode("=", $data)[1];
    $omin = explode("=", $data)[2];
    $omax = explode("=", $data)[3];
    $orate = explode("=", $data)[4];
    $otype = explode("=", $data)[5];
    $prov = explode("=", $data)[6];
    $serv = explode("=", $data)[7];
    if ($otype == "Default" or $otype == "default") {
        sms($chat_id, "<b>Kerakli buyurtma miqdorini kiriting:</b>

⏬ Minimal -  $omin
⏫ Maksimal - $omax", $ort);
        put("user/$chat_id.step", "order=default=sp1");
        put("user/$chat_id.params", "$oid=$omin=$omax=$orate=$prov=$serv");
        put("user/$chat_id.si", $oid);
        exit;
    } elseif ($otype == "Package") {
        sms($chat_id, "📎 Kerakli xavolani kiriting (https://):", $ort);
        put("user/$chat_id.step", "order=package=sp2=1=$orate");
        put("user/$chat_id.params", "$oid=$omin=$omax=$orate=$prov=$serv");
        put("user/$chat_id.si", $oid);
        exit;
    }
}

$s = explode("=", $step);
if ($s[0] == "order" and $s[1] == "default" and $s[2] == "sp1" and is_numeric($text) and joinchat($cid) == 1) {
    $p = explode("=", get("user/$cid.params"));
    $narxi = $p[3] / 1000 * $text;
    if ($text >= $p[1] and $text <= $p[2]) {
        $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $cid"));
        if (($rew['balance'] >= $narxi)) {
            sms($cid, "
✅ $text saqlandi!

📎 Kerakli xavolani kiriting (https://):", $ort);
            put("user/$cid.step", "order=$s[1]=sp2=$text=$narxi");
            put("user/$cid.qu", $text);
            exit;
        } else {
            sms($cid, "⛔️ Yetarli mablag‘ mavjud emas

💰 Narxi: $narxi so‘m
🔢 Buyurtma miqdori: $text ta

Boshqa miqdor kiritib koring:", null);
            exit;
        }
    } else {
        sms($cid, "
⚠️ Buyurtma miqdorini notog’ri kiritilmoqda
 
 ⏬ Minimal -  $p[1]
 ⏫ Maksimal - $p[2]
 
 Boshqa miqdor kiriting", null);
        exit;
    }
}



if (($s[0] == "order" and ($s[1] == "default" or $s[1] == "package") and $s[2] == "sp2" and joinchat($cid) == 1)) {
    // Buyurtma miqdorini ko'rsatish
    if ($s[1] == "default") {
        $pc = "📊 <b>Buyurtma miqdori:</b> $s[3] ta";
    }

    // Foydalanuvchi balansini tekshirish
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $cid"));
    if (($rew['balance'] >= $s[4])) { // Mablag‘ yetarlimi?

        // Platformaga mos havolalarni tekshirish
        if ((mb_stripos($text, "https://t.me/") === 0) || // Telegram kanali
            (mb_stripos($text, "https://www.instagram.com/") === 0) || // Instagram
            (mb_stripos($text, "https://vm.tiktok.com/") === 0) || // TikTok
            (mb_stripos($text, "https://www.youtube.com/") === 0 || mb_stripos($text, "https://youtu.be/") === 0)
        ) { // YouTube

            // Ma'lumotlarni tasdiqlash sahifasi
            $msid = sms(
                $cid,
                "<b>📑<u> Ma'lumotlaringizni tekshiring:</u>

↔️ <b>Buyurtma narxi:</b> $s[4] so‘m
📎 <b>Buyurtma manzili:</b> <pre>$text</pre>
$pc

📋 <i>Agar kiritilgan ma'lumotlar to‘g‘ri bo‘lsa, pastdagi (✅ Tasdiqlash) tugmasini bosing. Ushbu amaldan so‘ng hisobingizdan $s[4] so‘m yechib olinadi va buyurtma bajarish uchun yuboriladi.</i>

⚠️ <b>Diqqat:</b> Buyurtma tasdiqlangandan keyin uni bekor qilish imkoni mavjud emas!</b>",
                json_encode([
                    'inline_keyboard' => [
                        [['text' => "✅ Tasdiqlash", 'callback_data' => "checkorder=" . uniqid()]],
                        [['text' => "🚫 Bekor qilish", 'callback_data' => "main"]],
                    ]
                ])
            )->result->message_id;

            // Foydalanuvchi ma'lumotlarini saqlash
            put("user/$cid.step", "order=$s[1]=sp3=$s[3]=$s[4]=$text");
            put("user/$cid.ur", $text);
            exit;
        } else {
            // Noto‘g‘ri havola bo‘lsa
            sms($cid, "⚠️ <b>Noto‘g‘ri havola yuborildi.

Iltimos, quyidagi platformalar uchun to‘g‘ri havolani yuboring:
<blockquote>- Telegram kanali: <code>https://t.me/kanalnomi</code>
- Instagram profili: <code>https://www.instagram.com/profil</code>
- TikTok profili: <code>https://www.tiktok.com/@profil</code>
- YouTube video yoki kanal: <code>https://www.youtube.com/...</code></blockquote></b>

<b>Diqqat! @ bilan boshlanuvchi username qabul qilinmaydi. Qaytadan harakat qilib ko‘ring.</b>", null);
        }
    } else {
        // Foydalanuvchi balansida yetarli mablag‘ bo‘lmasa
        sms($cid, "❌ <b>Hisobingizda yetarli mablag‘ mavjud emas.</b>

Buyurtmani tasdiqlash uchun hisobingizni to‘ldiring va qayta urinib ko‘ring. Agar yordam kerak bo‘lsa, qo‘llab-quvvatlash xizmatiga murojaat qiling.", $m);
    }
}

$sc = explode("=", get("user/$chat_id.step"));
if ((stripos($data, "checkorder=") !== false and $sc[0] == "order" and ($sc[1] == "default" or $sc[1] == "package") and $sc[2] == "sp3" and joinchat($chat_id) == 1)) {
    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $chat_id"));
    if ($rew['balance'] >= $sc[4]) {
        $sc = explode("=", get("user/$chat_id.step"));
        $sp = explode("=", get("user/$chat_id.params"));
        $m = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM providers WHERE id = " . $sp[4] . ""));
        $surl = $m['api_url'];
        $skey = $m['api_key'];
        $j = json_decode(get($surl . "?key=" . $skey . "&action=add&service=" . get("user/$chat_id.si") . "&link=" . get("user/$chat_id.ur") . "&quantity=" . get("user/$chat_id.qu") . ""), 1);
        $jid = $j['order'];
        $jer = $j['error'];
        if (empty($jid)) {
            sms(5813831511, "
<b>⚠️ API saytda xatolik yuz berdi!
🌐 Sayt:</b> " . str_replace(["https://", "/api/v2", "/api/v1"], ["", "", ""], $surl) . "
<b>🔑 Kalit:</b> <code>$skey</code>
<b>‼️ Sabab:</b> " . trans(str_replace(".", " ", $jer)) . "", null);
            if ($jer == "neworder.error.link_duplicate") {
                $ns = "❌ Siz kiritgan havolaga buyurtma bajarilmoqda! Buyurtma bajarilganidan so'ng qaytadan urining.";
            } else {
                $ns = "⚠️ Xatolik yuz berdi!";
            }
            bot('answerCallbackQuery', [
                'callback_query_id' => $cqid,
                'text' => "$ns",
                'show_alert' => 1,
            ]);
            bot('deleteMessage', [
                'chat_id' => $chat_id,
                'message_id' => $message_id,
            ]);
            bot('SendMessage', [
                'chat_id' => $chat_id,
                'text' => "🖥️ <b>Asosiy menyudasiz</b>",
                'reply_markup' => $m,
                'parse_mode' => 'html',
            ]);
            unlink("user/$chat_id.step");
            unlink("user/$chat_id.params");
            unlink("user/$chat_id.ur");
            unlink("user/$chat_id.qu");
            unlink("user/$chat_id.si");
            exit();
        } else {
            $oe = mysqli_num_rows(mysqli_query($connect, "SELECT * FROM orders"));
            $or = $oe + 1;
            $sav = date("Y.m.d H:i:s");
            mysqli_query($connect, "INSERT INTO myorder(`order_id`,`user_id`,`retail`,`status`,`service`,`order_create`,`last_check`) VALUES ('$or','$chat_id','$sc[4]','Pending','$sp[5]','$sav','$sav');");
            mysqli_query($connect, "INSERT INTO orders(`api_order`,`order_id`,`provider`,`status`) VALUES ('$jid','$or','$sp[4]','Pending');");
            $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $cid"));
            $order = "<b>✅ Buyurtma qabul qilindi!</b>

<b>🆔 Buyurtma idsi: <code>$or</code></b>
<b>📯 Buyurtma holati: ⏳Bajarilmoqda</b>

<b>❗️Buyurtma to'liq bajarilganda sizga habar beriladi!</b>";
            sms($chat_id, $order, json_encode([
                'inline_keyboard' => [
                    [['text' => "🔎 | Ko‘rish", 'callback_data' => "checkorders=$or"], ['text' => "⏩ Orqaga", 'callback_data' => "absd"]],
                ]
            ]));
            $bdh = $sp[4];
            $bdj = $sp[5];
            $ur0 = get("user/$chat_id.ur");
            $qu0 = get("user/$chat_id.qu");
            $si0 = get("user/$chat_id.si");
            $retail = $sc[4];
            $getname = bot('getchat', ['chat_id' => $chat_id]);
            $name = $getname->result->first_name;
            $sename = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM services WHERE service_id = $bdj"))['service_name'];
            $xizmatn = base64_decode($sename);
            $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $chat_id"));
            $new = $rew['balance'] - $sc[4];
            sms(
                $orderschannel,
                "<b><blockquote>🆕 Yangi buyurtma (@$bot)

☎️ Qo'llab-Quvvatlash markazi: @$aduser</blockquote>

🛍️ Buyurtma ID raqami: <code>$or</code>
🆔 Xizmat ID raqami: <code>$bdj</code>
🔢 Buyurtma miqdori: </b>$qu0 ta
📊 <b>Buyurtma narxi:</b> $retail so'm 
📖 <b>Xizmat nomi:</b> $xizmatn
👤 <b>Buyurtmachi: <a href='tg://user?id=$chat_id'>$name</a>
🔗 Havola: <code>$ur0</code></b>
<b>💸 Foydalanuvchi balansi:</b> $new so'm

<b>🛍️Buyurtma berilgan vaqt: $sana</b>",
                json_encode(['inline_keyboard' => [[['text' => "🔎 Buyurtma xolati", 'callback_data' => "orderz=$or"]]]])
            );
            $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $chat_id"));
            $miqdor = $rew['balance'] - $sc[4];
            mysqli_query($connect, "UPDATE users SET balance=$miqdor WHERE id =$chat_id");
            unlink("user/$chat_id.step");
            del();
            exit;
        }
    }
}

if (mb_stripos($data, "orderz=") !== false) {
    $id = str_replace("orderz=", "", $data);
    $response = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM orders WHERE order_id = $id"))['status'];
    if ($response == "Completed") {
        $status = "bajarilgan";
    }
    if ($response == "In progress") {
        $status = "bajarilmoqda";
    }
    if ($response == "Partial") {
        $status = "qayta ishlanmoqda";
    }
    if ($response == "Pending") {
        $status = "bajarilmoqda";
    }
    if ($response == "Processing") {
        $status = "bajarilmoqda";
    }
    if ($response == "Canceled") {
        $status = "bekor qilingan";
    }
    bot('answerCallbackQuery', [
        'callback_query_id' => $qid,
        'text' => "
🔎 Buyurtma xolati: $status",
        'show_alert' => true,
    ]);
}

if ($_GET['update'] == "status") {
    echo json_encode(["status" => true, "cron" => "Orders status"]);

    $mysql = mysqli_query($connect, "SELECT * FROM `orders` WHERE status NOT IN ('Canceled', 'Completed')");
    while ($mys = mysqli_fetch_assoc($mysql)) {
        $prv = (int)$mys['provider']; // SQL Injection xavfini bartaraf etish
        $order = (int)$mys['api_order'];
        $uorder = (int)$mys['order_id'];

        $mysa = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `myorder` WHERE order_id = $uorder"));
        if (!$mysa) continue; // Agar buyurtma yo‘q bo‘lsa, keyingi iteratsiyaga o‘ting

        $adm = (int)$mysa['user_id'];
        $retail = (float)$mysa['retail'];

        $m = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM `providers` WHERE id = $prv"));
        if (!$m) continue;

        $surl = $m['api_url'];
        $skey = $m['api_key'];
        $sav = date("Y.m.d H:i:s");

        // APIdan buyurtma statusini olish
        $response = get($surl . "?key=" . $skey . "&action=status&order=" . $order);
        $j = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($j['status'])) {
            $status = $j['status'];

            // Faqat status o‘zgargan bo‘lsa, yangilash va xabar yuborish
            if ($status != $mys['status']) {
                mysqli_query($connect, "UPDATE orders SET status='$status' WHERE order_id=$uorder");
                mysqli_query($connect, "UPDATE myorder SET status='$status', last_check='$sav' WHERE order_id=$uorder");

                if ($status == "Completed") {
                    sms($adm, "✅ <b>Sizning $uorder raqamli buyurtmangiz to‘liq bajarildi.

🔥 Bizning xizmatlarimizdan foydalanganligingiz uchun raxmat.</b>", json_encode([
                        'inline_keyboard' => [
                            [['text' => "🔎 | Ko‘rish", 'callback_data' => "checkorders=$uorder"]]
                        ]
                    ]));
                } elseif ($status == "Canceled") {
                    sms($adm, "❌ <b>Sizning $uorder raqamli buyurtmangiz bekor qilindi.

✅ Hisobingizga $retail so‘m qaytarildi</b>", null);
                    $rew = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $adm"));
                    $miqdor = $retail + (float)$rew['balance'];
                    mysqli_query($connect, "UPDATE users SET balance=$miqdor WHERE id = $adm");
                }
            }
        } elseif (isset($j['error'])) {
            $oi = (int)$mys['order_id'];
            mysqli_query($connect, "DELETE FROM myorder WHERE order_id = $oi");
        }
    }
}



$res = mysqli_query($connect, "SELECT*FROM users WHERE id=$cid");
while ($a = mysqli_fetch_assoc($res)) {
    $flid = $a['id'];
}
if (mb_stripos($text, "/start SFX") !== false) {
    $id = str_replace("/start SFX", "", $text);
    $refid = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE user_id = $id"))['id'];

    if (strlen($refid) > 0 && $refid > 0) {
        if ($refid == $cid) {
            bot('SendMessage', [
                'chat_id' => $cid,
                'text' => "⚠️ <b>Siz o‘zingizga referal bo‘lishingiz mumkin emas</b>",
                'parse_mode' => 'html',
                'reply_markup' => $m,
            ]);
        } else {
            if (mb_stripos($flid, "$cid") !== false) {
                bot('SendMessage', [
                    'chat_id' => $cid,
                    'text' => "⚠️ <b>Siz bizning botimizda allaqachon mavjudsiz.</b>",
                    'parse_mode' => 'html',
                    'reply_markup' => $m
                ]);
            } else {
                $kanal = file_get_contents("set/channel");
                if (joinchat($cid) == 1) {
                    // Yangilangan qism: Referalning balansi va refnum ustuni yangilanadi
                    $pul = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM users WHERE id = $refid"))['balance'];
                    $refnum = mysqli_fetch_assoc(mysqli_query($connect, "SELECT refnum FROM users WHERE id = $refid"))['refnum'];

                    // Balans va referal sonini oshirish
                    $newBalance = $pul + enc("decode", $setting['referal']);
                    $newRefnum = $refnum + 1;

                    mysqli_query($connect, "UPDATE users SET balance = $newBalance, refnum = $newRefnum WHERE id = $refid");

                    $text = "🖇️ <b>Sizda yangi <a href='tg://user?id=$cid'>taklif</a> mavjud!</b>

Hisobingizga " . enc("decode", $setting['referal']) . " so‘m qo'shildi!";
                    $p = get("user/$refid.users");
                    put("user/$refid.users", $p + 1);
                } else {
                    file_put_contents("user/$cid.id", $refid);
                    $text = "🖇️ <b>Sizda yangi <a href='tg://user?id=$cid'>taklif</a> mavjud!</b>";
                }
                bot('sendMessage', [
                    'chat_id' => $cid,
                    'text' => "🖥 <b>Asosiy menyudasiz.</b>",
                    'parse_mode' => 'html',
                    'reply_markup' => $m,
                ]);
                bot('SendMessage', [
                    'chat_id' => $refid,
                    'text' => $text,
                    'parse_mode' => 'html',
                ]);
                adduser($cid);
            }
        }
    }
}

if ($message) {
    adduser($cid);
}

if (($data == "main") and (joinchat($cid2) == 1)) {
    bot('AnswerCallbackQuery', [
        'callback_query_id' => $qid,
        'text' => "✅ Asosiy menyudasiz! Xizmatni tanlang:",
        'show_alert' => false,
    ]);
    bot('deleteMessage', [
        'chat_id' => $cid2,
        'message_id' => $mid2,
    ]);
    bot('sendmessage', [
        'chat_id' => $cid2,
        'parse_mode' => "html",
        'reply_markup' => $m,
        'text' => "🖥️ <b>Asosiy menyuga qaytdingiz.</b>",
    ]);
    unlink("user/$cid2.step");
    unlink("user/$cid2.ur");
    unlink("user/$cid2.params");
    unlink("user/$cid2.qu");
    unlink("user/$cid2.si");
    exit();
}