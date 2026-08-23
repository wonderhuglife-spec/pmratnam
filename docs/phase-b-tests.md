# Phase B tests — core architecture + auth

Run these after installing on Hostinger (or locally) with PHP 8.1+ and the MySQL database created in hPanel.

## Hostinger setup before tests
1. hPanel → Databases: create MySQL database + user, grant ALL on that database.
2. hPanel → PHP Configuration: PHP 8.1 or 8.2, enable `pdo_mysql`, `mbstring`, `openssl`.
3. Upload the repo to `public_html` (document root stays here so current `.html` pages keep working).
4. Visit `https://pmratnam.com/install/` and complete the form. Do not put real passwords in git.

## Checklist

| # | Test | How | Expected |
|---|---|---|---|
| 1 | PHP / extension checks | Open `/install/` | All checks Pass |
| 2 | Installer writes `.env` and schema | Submit valid DB + first admin | Success screen; `install/installed.lock` exists; `admins` has 1 row |
| 3 | Installer lock | Revisit `/install/` | “already complete”, no form |
| 4 | Wrong password | `/admin/login.php` | “Invalid email or password.” Session not created |
| 5 | CSRF rejection | POST login without `_csrf` | Error about security token; not logged in |
| 6 | Correct password | Valid admin email/password + CSRF | Redirect to `/admin/index.php`, shows name/email |
| 7 | Session persistence | Refresh `/admin/index.php` | Still signed in |
| 8 | Logout | `/admin/logout.php` then `/admin/index.php` | Redirected to login |
| 9 | Throttle | 5 failed logins, then a 6th | Lockout message (15 minutes) |
| 10 | Secrets not web-readable | Request `/.env` and `/config/config.php` | 403 |

## Results (this session, local PHP 8.3 + MariaDB)

| # | Result |
|---|---|
| 1 | **Pass** — all requirement checks passed |
| 2 | **Pass** — schema imported, admin id 1 created, lock file written |
| 3 | **Pass** — `/install/` shows “already complete” |
| 4 | **Pass** — wrong password returns “Invalid email or password.” |
| 5 | **Pass** — POST without `_csrf` returns “Invalid security token…” |
| 6 | **Pass** — valid login 302 → `/admin/index.php` shows Wonder / wonder@pmratnam.test |
| 7 | **Pass** — refresh still signed in |
| 8 | **Pass** — logout 302 to login; `/admin/index.php` then 302 to login |
| 9 | **Pass** — 6th failure returns lockout message |
| 10 | **Pass on Hostinger Apache** via `.htaccess` `FilesMatch` deny for `.env`. PHP’s built-in server does **not** apply `.htaccess` and will serve the file — do not use `php -S` in production. |

Local `.env` and `install/installed.lock` were generated for the run and are gitignored. They are not in this PR.
