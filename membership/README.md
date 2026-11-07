# Ruakura Club membership

PHP 8.5 + MariaDB 10.11. No build step.

## Deploying on Plesk

1. Upload this folder (without `vendor/` and `config.php`).
2. In Plesk, set the site's **document root** to the `public` folder. Everything else (`src/`, `templates/`, `sql/`, `vendor/`, `config.php`) must not be web-accessible.
3. Run Composer install from Plesk (it finds `composer.json`).
4. Create a database and user in Plesk, then in phpMyAdmin run `sql/schema.sql` and then `sql/seed.sql`.
   A database created before companies were added needs `sql/upgrade-2026-11-companies.sql` run once instead.
5. Copy `config.example.php` to `config.php` and fill in the database details.
6. Make sure the site is HTTPS-only (Plesk: SSL/TLS → redirect HTTP to HTTPS).
7. Visit `/setup.php` straight away and create the first login. The page switches itself off once an account exists.
8. Log in. On first login you'll be asked to scan a QR code with an authenticator app.

## Login

- Password, then a 6-digit authenticator code (TOTP) every time.
- 5 failed attempts (password or code) locks the account for 15 minutes. The login page doesn't say so: every failure gets the same "Incorrect username or password", and attempts while locked are ignored (they don't extend the lockout).
- Every login, failure, lockout and account change is recorded with the date, time and IP address under **Management → Login activity** (kept 12 months).
- Sessions end after 30 minutes without activity.
- Add and deactivate logins under **Management → Users**. Everyone has the same access. You can't deactivate yourself.
- Change your password or move to a new authenticator app under **Profile → My profile**.
- Someone else forgot their password, lost their phone, or got locked out? Another user fixes it under **Management → Users → Manage**.
- If the only user is locked out of their authenticator, the hosting admin can run this in phpMyAdmin, then the user scans a new QR code at next login:
  `UPDATE users SET totp_secret = NULL, totp_last_timeslice = NULL WHERE username = '…';`
  This is why it's worth having at least two users.
