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

## Results (this session, local PHP 8.3 + MariaDB `pmratnam`)

HTTP via PHP built-in server `127.0.0.1:8080`. Admin session: Wonder / `wonder@pmratnam.test`.

| # | Result |
|---|---|
| 1 | **Pass** — logged-out `/admin/index.php` → 302 `/admin/login.php` |
| 2 | **Pass** — empty DB showed all zeros; after two pages existed, dashboard Pages = 2 matching `COUNT(*)`. Posts/categories/media/leads stayed 0. |
| 3 | **Pass** — all 13 labels present in the signed-in layout |
| 4 | **Pass** — POST create → `pages.id = 1`, slug `phase-c-test-page`, status `draft`, content and meta/canonical stored |
| 5 | **Pass** — same id; title `Phase C Test Page (edited)`, template `service`, excerpt/content/meta updated |
| 6 | **Pass** — list Publish toggle set `status = published`, `published_at = 2026-08-23 17:02:40` |
| 7 | **Pass** — `?q=phase-c&status=published` returned the row; `status=draft` showed no matches after publish |
| 8 | **Pass** — duplicate created id 2, title `… (copy)`, slug `phase-c-test-page-2`, status `draft` |
| 9 | **Pass** — delete id 2; remaining row is published id 1 only |
| 10 | **Pass** — posts, categories, media, menus, SEO, forms, users, settings, tools all HTTP 200 with live empty/real lists |

### SQL row after create → edit → publish (id 1)

```
id=1
title=Phase C Test Page (edited)
slug=phase-c-test-page
status=published
template=service
published_at=2026-08-23 17:02:40
meta_title=Phase C meta title edited
excerpt=Updated excerpt.
content=<h2>Hello edited</h2><p>Updated <em>body</em>.</p>
canonical_url=https://pmratnam.com/phase-c-test-page
```

That published row was left in the local database for inspection. Pagination with >20 rows was not exercised (only one remaining page).
