# CivicTrack v3 — Upgrade Guide
### Phone OTP Auth · Multi-Language · Public Toilet · Production UI

---

## What's New in v3

| Feature | Details |
|---|---|
| 🔐 **Citizen Auth** | Register/login with phone number + OTP (no password) |
| 📱 **OTP Verification** | 6-digit OTP with auto-advance inputs, paste support, demo mode |
| 🌐 **3 Languages** | English, Hindi (हिन्दी), Telugu (తెలుగు) — switch from any page |
| 🚻 **Public Toilet Category** | Separate toilet issue sub-types (cleanliness, water, fixtures, etc.) |
| 📲 **SMS-Ready** | Fast2SMS / MSG91 / Twilio gateway stubs — plug in API key to go live |
| 🎨 **Upgraded UI** | Glassmorphism auth, animated background, mobile hamburger nav |
| 📋 **My Complaints** | Citizen dashboard showing their personal complaint history |
| 🔒 **CSRF Protection** | All forms protected; no ward-based filtering |

---

## Files in This ZIP

```
civictrack-v3-upgrade/
│
├── database-upgrade.sql          ← Run this FIRST in phpMyAdmin/MySQL
│
├── lang/
│   ├── en.php                    ← English strings
│   ├── hi.php                    ← Hindi strings
│   └── te.php                    ← Telugu strings
│
├── includes/
│   ├── config.php                ← REPLACE existing (adds OTP/SMS settings)
│   ├── auth.php                  ← REPLACE existing (adds citizen sessions)
│   ├── functions.php             ← REPLACE existing (no ward, toilet category)
│   ├── header.php                ← REPLACE existing (lang switcher, citizen nav)
│   ├── footer.php                ← REPLACE existing
│   ├── lang.php                  ← NEW — language helper
│   └── otp.php                   ← NEW — OTP generate/verify/send
│
├── citizen/
│   ├── login.php                 ← NEW — phone login page
│   ├── register.php              ← NEW — name + phone registration
│   ├── verify-otp.php            ← NEW — OTP entry page
│   ├── dashboard.php             ← NEW — citizen's complaint history
│   └── logout.php                ← NEW — citizen sign-out
│
├── index.php                     ← REPLACE existing (translated, no ward)
├── submit.php                    ← REPLACE existing (toilet category, citizen pre-fill)
├── track.php                     ← REPLACE existing (translated, no ward filter)
├── view.php                      ← REPLACE existing (translated)
├── style.css                     ← REPLACE existing (all new styles)
│
└── README-upgrade.md             ← This file
```

---

## Step-by-Step Installation

### Step 1 — Run the Database Migration

Open **phpMyAdmin** → select `civictrack` database → click **SQL** tab → paste and run:

```
database-upgrade.sql
```

Or via terminal:
```bash
mysql -u root -p civictrack < database-upgrade.sql
```

This adds:
- `citizens` table (phone + name)
- `otp_codes` table (OTP storage)
- `citizen_id` column to `complaints`
- `toilet_sub` column to `complaints`

---

### Step 2 — Create the `lang/` folder

Inside your `civictrack/` project root, create a folder called `lang/`:

```
civictrack/
└── lang/        ← create this folder
    ├── en.php
    ├── hi.php
    └── te.php
```

Copy the three `lang/*.php` files from this ZIP into it.

---

### Step 3 — Create the `citizen/` folder

Inside your `civictrack/` project root, create a folder called `citizen/`:

```
civictrack/
└── citizen/      ← create this folder
    ├── login.php
    ├── register.php
    ├── verify-otp.php
    ├── dashboard.php
    └── logout.php
```

Copy all files from `citizen/` in this ZIP into it.

---

### Step 4 — Replace `includes/` files

Copy these files from this ZIP into your `civictrack/includes/` folder,
**overwriting** the existing files:

- `config.php`
- `auth.php`
- `functions.php`
- `header.php`
- `footer.php`

Also copy the **new** files into `includes/`:
- `lang.php`   ← NEW
- `otp.php`    ← NEW

---

### Step 5 — Replace root PHP + CSS files

Copy these into your `civictrack/` root folder (**overwrite** existing):

- `index.php`
- `submit.php`
- `track.php`
- `view.php`
- `style.css`

---

### Step 6 — Update `APP_URL` in config.php

Open `civictrack/includes/config.php` and confirm:

```php
define('APP_URL', 'http://localhost/civictrack');  // match your XAMPP path
```

---

### Step 7 — Test the Setup

1. Visit `http://localhost/civictrack/` — home page should load with language switcher
2. Visit `http://localhost/civictrack/citizen/register.php` — register with your phone
3. An OTP box (yellow) will appear on screen (demo mode) — click it to auto-fill
4. You should be logged in and redirected to your dashboard

---

## OTP Demo Mode (XAMPP / localhost)

By default `OTP_DEMO_MODE = true` in `config.php`. This means:

- The OTP is **shown on screen** in a yellow box
- **No real SMS is sent**
- Click the OTP code to auto-fill it into the input boxes
- Perfect for testing on XAMPP

**To switch to real SMS** when you go live:

```php
// in includes/config.php
define('OTP_DEMO_MODE',  false);
define('SMS_PROVIDER',   'fast2sms');  // or 'msg91' or 'twilio'
define('SMS_API_KEY',    'YOUR_API_KEY_HERE');
```

### Fast2SMS (recommended for India — free tier available)
1. Sign up at [fast2sms.com](https://fast2sms.com)
2. Get your API key from Dashboard → Dev API
3. Paste it into `SMS_API_KEY` in `config.php`

---

## Language Switcher

The language switcher appears in the top navigation bar.
Click **English / हिन्दी / తెలుగు** to switch.

The selected language is stored in the session and persists across pages.

To add a new language:
1. Copy `lang/en.php` → `lang/mr.php` (for example)
2. Translate all values
3. Add `'mr' => 'मराठी'` to the `SUPPORTED_LANGS` array in `includes/lang.php`

---

## Admin Portal (unchanged)

The admin portal works exactly as before:
- URL: `http://localhost/civictrack/admin/login.php`
- Run `setup.php` if you have login issues

New in admin view:
- Complaints show citizen phone number
- Toilet sub-category is visible in complaint details

---

## Cron Job (auto-escalation — unchanged)

```bash
0 0 * * * php /path/to/civictrack/cron/escalate.php >> /var/log/civictrack.log 2>&1
```

---

## Production Checklist

- [ ] Set `OTP_DEMO_MODE = false` in `config.php`
- [ ] Set real `SMS_API_KEY` and `SMS_PROVIDER`
- [ ] Set `DEBUG_MODE = false` in `config.php`
- [ ] Update `APP_URL` to your real domain
- [ ] Set HTTPS and `'secure' => true` in session params (in `auth.php`)
- [ ] Add `.htaccess` with `Deny from all` in `includes/`, `lang/`, `cron/`
- [ ] Delete `setup.php` from server
