# Ruakura Club membership

PHP 8.5 + MariaDB 10.11. No build step.

## Deploying on Plesk

1. Upload this folder (without `vendor/` and `config.php`).
2. In Plesk, set the site's **document root** to the `public` folder. Everything else (`src/`, `templates/`, `sql/`, `vendor/`, `config.php`) must not be web-accessible.
3. Run Composer install from Plesk (it finds `composer.json`).
4. Create a database and user in Plesk, then in phpMyAdmin run `sql/schema.sql` and then `sql/seed.sql`.
5. Copy `config.example.php` to `config.php` and fill in the database details.
6. Make sure the site is HTTPS-only (Plesk: SSL/TLS → redirect HTTP to HTTPS).
7. Visit `/setup.php` straight away and create the first login. The page switches itself off once an account exists.
8. Log in. On first login you'll be asked to scan a QR code with an authenticator app.

## Login

- Password, then a 6-digit authenticator code (TOTP) every time.
- 5 failed attempts (password or code) locks the account for 15 minutes. The login page doesn't say so: every failure gets the same "Incorrect username or password", and attempts while locked are ignored (they don't extend the lockout).
- Every login, failure, lockout and account change is recorded with the date, time and IP address under **Management → Users → Login activity** (kept 12 months).
- Sessions end after 30 minutes without activity.
- Add and deactivate logins under **Management → Users**. Everyone has the same access. You can't deactivate yourself.
- Change your password or move to a new authenticator app under **your name (top right) → My profile**.
- Someone else forgot their password, lost their phone, or got locked out? Another user fixes it under **Management → Users → Manage**.
- If the only user is locked out of their authenticator, the hosting admin can run this in phpMyAdmin, then the user scans a new QR code at next login:
  `UPDATE users SET totp_secret = NULL, totp_last_timeslice = NULL WHERE username = '…';`
  This is why it's worth having at least two users.

## Brevo

1. In Brevo, create an API key (SMTP & API → API keys) and put it in `config.php` under `brevo` → `api_key`.
2. Create the lists you want in Brevo, then choose them under **Management → Brevo lists**: a main list for current members, a lapsed list, and optionally a list per interest.
3. **Exports → Update Brevo mailing list** shows who will be added and removed. **Send to Brevo** applies it. Run it after renewals or member changes.

Only members who agreed to the newsletter are put on lists. Withdrawing consent, resigning or dying takes them off. Lapsed members move to the lapsed list. People on those lists who aren't members, and lists not chosen there, are never touched. For the first real run, try it against test lists.

## Emailing members

**Email → Email members** sends one of your Brevo templates (edit them in Brevo; there's a link on the page) to the members you tick: current, lapsed or both, optionally only those expiring by a date.

- **One email each** (default): each address gets its own personalised email. Templates can use `{{ params.FIRSTNAME }}`, `{{ params.LASTNAME }}`, `{{ params.FULLNAME }}`, `{{ params.MEMBERSHIP_NUMBER }}` and `{{ params.EXPIRY }}`.
- **BCC**: one email addressed to the template's sender, with members in BCC (98 per email). Placeholders are blank.
- Members sharing an email address get one email. CC is never used.
- The page previews the email for each ticked member (|< < > >| to flick through), with their details filled in. Send a test to yourself first to see the exact version. Every send is logged under **Email → Sent emails** and on each member's page.
- These are transactional emails, so they reach members who unsubscribed from the newsletter. Use them only for membership business (renewals, notices), not promotion.
- The page stops a send that would go over the daily limit in `config.php` (`daily_limit`, 300 on Brevo's free plan). Raise it if you upgrade.
