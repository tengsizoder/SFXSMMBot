# SFXSMM Bot — Qayta yozilgan (Refactored)

## 📁 Loyiha tuzilmasi

```
src/
├── index.php              — Asosiy webhook entry point
├── config.php             — Bot sozlamalari (token, admin, DB)
├── database.php           — Xavfsiz database class (prepared statements)
├── helpers.php            — Telegram API va yordamchi funksiyalar
├── middleware.php         — Foydalanuvchi tekshiruvlari
├── cron.php               — Cron vazifalari
└── handlers/
    ├── start.php          — /start va navigatsiya
    ├── admin.php          — Admin panel va statistika
    ├── payment.php        — To'lov tizimi
    ├── orders.php         — Buyurtma berish
    ├── account.php        — Foydalanuvchi hisobi
    ├── support.php        — Qo'llab-quvvatlash va FAQ
    ├── api_panel.php      — API boshqaruvi
    └── info.php           — Vertual xizmatlar haqida
```

## 🚀 O'rnatish

### 1. Webhook o'rnatish
```
https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://yourdomain.uz/src/index.php
```

### 2. Database sozlash
`config.php` da DB_HOST, DB_USER, DB_PASS, DB_NAME ni o'zgartiring.

### 3. Cron Job sozlash
Hostingda Cron Job ga qo'shing (har 1 daqiqada):
```
https://yourdomain.uz/src/index.php?update=send
https://yourdomain.uz/src/index.php?update=status
```

## ✅ Yaxshilangan jihatlar

| Oldingi muammo | Hozirgi yechim |
|---|---|
| 7800+ qator bitta faylda | 14 ta alohida modul |
| SQL Injection xavfi | Prepared Statements |
| O'zgaruvchilar takrorlanishi | Aniq funksiyalar |
| Global state (file_get_contents) | Database class |
| Kod o'qishda qiyinchilik | Har bir handler alohida fayl |
| Xatoliklarni boshqarish yo'q | Error logging |

## ⚙️ Muhim sozlamalar (`config.php`)

- `BOT_TOKEN` — Telegram bot token
- `ADMIN_ID` — Admin Telegram ID
- `DB_*` — Database ulanish
- `MIN_DEPOSIT` — Minimal to'lov miqdori
- `MIN_TRANSFER` — Minimal o'tkazma

## 📝 Izoh

Eski `SFXSMM.php` fayli saqlab qolindi (arxiv sifatida). Yangi kod `src/` papkasida.
