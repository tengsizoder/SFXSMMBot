<?php
/**
 * SFXSMM Bot — Cron vazifalar
 * 
 * Bu fayl Cron Job orqali chaqiriladi.
 * ?update=send — Ommaviy xabar yuborish
 * ?update=status — Buyurtma statuslarini yangilash
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

Database::connect();

$action = $_GET['update'] ?? '';

// ==========================================
// OMMAVIY XABAR YUBORISH
// ==========================================
if ($action === 'send') {
    $row = Database::fetchOne("SELECT * FROM send LIMIT 1");

    if (!$row) {
        die("Navbat bo'sh");
    }

    $startId  = intval($row['start_id']);
    $stopId   = intval($row['stop_id']);
    $adminId  = $row['admin_id'];
    $msgId    = $row['message_id'];

    // Keyingi partiya foydalanuvchilarni olish
    $users = Database::fetchAll(
        "SELECT id FROM users LIMIT ?, ?",
        [$startId, CRON_SEND_LIMIT]
    );

    if (empty($users)) {
        // Tugadi
        bot('sendMessage', [
            'chat_id'    => $adminId,
            'text'       => "✓ <b>Xabar barcha foydalanuvchilarga yuborildi!</b>\n▪ Jami: $startId ta",
            'parse_mode' => 'html',
        ]);
        Database::execute("DELETE FROM send");
        die("Tugadi: $startId");
    }

    $sent = 0;
    foreach ($users as $user) {
        bot('forwardMessage', [
            'chat_id'      => $user['id'],
            'from_chat_id' => $adminId,
            'message_id'   => $msgId,
        ]);
        $sent++;
    }

    $newStart = $startId + $sent;
    Database::execute("UPDATE send SET start_id = ? WHERE id = ?", [$newStart, $row['id'] ?? 1]);

    if ($newStart >= $stopId) {
        bot('sendMessage', [
            'chat_id'    => $adminId,
            'text'       => "✓ <b>Xabar yuborildi!</b> Jami: $newStart ta",
            'parse_mode' => 'html',
        ]);
        Database::execute("DELETE FROM send");
    }

    die("Yuborildi: $newStart / $stopId");
}

// ==========================================
// BUYURTMA STATUSINI YANGILASH
// ==========================================
if ($action === 'status') {
    $orders = Database::fetchAll(
        "SELECT o.*, s.api_service, s.service_api_id FROM orders o 
         LEFT JOIN services s ON s.service_id = o.service_id 
         WHERE o.status IN ('Pending', 'In progress', 'Processing')
         LIMIT 50"
    );

    $updated = 0;

    foreach ($orders as $order) {
        $providerId = $order['api_service'] ?? null;
        if (!$providerId) continue;

        $provider = Database::fetchOne("SELECT * FROM providers WHERE id = ?", [$providerId]);
        if (!$provider) continue;

        // Provider API dan status so'rash
        $ctx = stream_context_create(['http' => ['timeout' => 10]]);
        $response = @file_get_contents(
            $provider['api_url'] . '?key=' . $provider['api_key'] .
            '&action=status&order=' . $order['order_id'],
            false, $ctx
        );

        $data = json_decode($response, true);

        if (!$data || !isset($data['status'])) continue;

        $newStatus = $data['status'];
        $remains = $data['remains'] ?? 0;

        // Statusni yangilash
        if ($newStatus !== $order['status']) {
            Database::execute(
                "UPDATE orders SET status = ? WHERE order_id = ?",
                [$newStatus, $order['order_id']]
            );
            Database::execute(
                "UPDATE myorder SET status = ?, last_check = NOW() WHERE order_id = ?",
                [$newStatus, $order['order_id']]
            );

            // Partial yoki Canceled bo'lsa — qaytarish
            if (in_array($newStatus, ['Partial', 'Canceled'])) {
                $myOrder = Database::fetchOne(
                    "SELECT * FROM myorder WHERE order_id = ?",
                    [$order['order_id']]
                );

                if ($myOrder && $remains > 0) {
                    $pricePerUnit = floatval($myOrder['retail']) / intval($myOrder['quantity']);
                    $refund = $pricePerUnit * $remains;

                    Database::execute(
                        "UPDATE users SET balance = balance + ? WHERE id = ?",
                        [$refund, $myOrder['user_id']]
                    );
                }
            }

            // Bajarilganda xabar
            if ($newStatus === 'Completed') {
                $myOrder = Database::fetchOne(
                    "SELECT user_id FROM myorder WHERE order_id = ?",
                    [$order['order_id']]
                );
                if ($myOrder) {
                    bot('sendMessage', [
                        'chat_id'    => $myOrder['user_id'],
                        'text'       => "✓ <b>{$order['order_id']} raqamli buyurtmangiz bajarildi!</b>\n\n🔥 Xizmatlarimizdan foydalanganingiz uchun rahmat.",
                        'parse_mode' => 'html',
                    ]);
                }
            }

            $updated++;
        }
    }

    die("Status updated: $updated orders");
}

die("Unknown action: $action");
