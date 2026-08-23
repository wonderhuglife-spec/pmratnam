# Phase C tests — admin shell + pages CRUD

Requires Phase B install (`.env`, schema, first admin). PHP 8.1+ and MySQL/MariaDB.

## Checklist

| # | Test | How | Expected |
|---|---|---|---|
| 1 | Auth gate | Hit `/admin/index.php` logged out | Redirect to `/admin/login.php` |
| 2 | Dashboard counts | Sign in, open dashboard | Counts match `SELECT COUNT(*)` on `pages`, `posts`, `categories`, `media`, `leads` |
| 3 | Sidebar | Open any admin screen | Links: Dashboard, Pages, Posts, Categories, Media, Menus, SEO, Forms, Users, Settings, Tools, View Website, Logout |
| 4 | Create page | `/admin/pages/edit.php` save draft with title, slug, content, SEO fields | New `pages` row; flash “Page saved.” |
| 5 | Edit page | Change title/content, save | Same `id`; updated columns in DB |
| 6 | Publish | List → Publish, or editor status published | `status = published`, `published_at` set |
| 7 | Search / filter / pagination | Search title; filter draft | List matches SQL filters; pager when >20 rows |
| 8 | Duplicate | Duplicate from list | New draft, title ends with `(copy)`, unique slug |
| 9 | Delete | Delete from list | Row gone from `pages` |
| 10 | Other nav | Posts, Categories, Media, etc. | Live `SELECT` lists (empty is OK); no fake numbers |

## Results (this session)

Filled in after the HTTP + SQL run against local MariaDB.
