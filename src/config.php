<?php
/**
 * SFXSMM Bot — Konfiguratsiya fayli
 * 
 * Bu faylda botning barcha asosiy sozlamalari saqlanadi.
 * Muhim: Bu faylni GitHub ga yuklamasdan oldin, token va parollarni
 * .env faylga ko'chirish tavsiya etiladi.
 */

// Xatolarni ko'rsatish (ishlab chiqish uchun)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Productionda 0, developmentda 1

// Vaqt zonasi
date_default_timezone_set('Asia/Tashkent');

// ==========================================
// BOT SOZLAMALARI
// ==========================================
define('BOT_TOKEN', '7576059347:AAFDDOX4oXq2fIo7hiymerxKt6Rjiq8Ivy8');
define('BOT_API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// ==========================================
// ADMIN SOZLAMALARI
// ==========================================
define('ADMIN_ID', '6929970231');
define('ADMIN_USERNAME', 'SFXSMMHelp');

// ==========================================
// KANALLAR
// ==========================================
define('ORDERS_CHANNEL', '@SFXSMMBaza');
define('PAYMENT_CHANNEL', '@SFXSMMTolov');

// ==========================================
// DATABASE SOZLAMALARI
// ==========================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sfxsmm_bot');

// ==========================================
// FAYL YO'LLARI
// ==========================================
define('BOT_DIR', __DIR__ . '/../bot/');
define('USER_DIR', BOT_DIR . 'user/');
define('STEP_DIR', BOT_DIR . 'step/');
define('SET_DIR', BOT_DIR . 'set/');

// ==========================================
// TO'LOV SOZLAMALARI
// ==========================================
define('MIN_DEPOSIT', 1000);        // Minimal to'lov miqdori (so'm)
define('MIN_TRANSFER', 1000);       // Minimal o'tkazma miqdori (so'm)

// ==========================================
// LIMITLAR
// ==========================================
define('CRON_SEND_LIMIT', 150);     // Cron har bir ishga tushganda yuboradigan xabarlar soni
define('TOP_LIST_LIMIT', 20);       // TOP ro'yxatlar limiti
define('TOP_BALANCE_LIMIT', 100);   // TOP balans limiti

// ==========================================
// PAPKALARNI YARATISH
// ==========================================
$required_dirs = [USER_DIR, STEP_DIR, SET_DIR, USER_DIR . 'mt/'];
foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}
