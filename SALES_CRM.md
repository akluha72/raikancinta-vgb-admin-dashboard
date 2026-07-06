# Sales CRM (MVP)

A Salesforce-style sales layer added on top of the wedding voice-guestbook
dashboard. It captures leads from **raikancinta.com** automatically, tracks each
lead's deals ("projects") through a pipeline, and reports profit/loss and other
sales analytics.

> **Status:** MVP. Built on the existing Laravel 12 + Breeze (Blade + Alpine +
> Tailwind) stack. No new framework introduced.

---

## Table of contents

- [What's new](#whats-new)
- [How lead capture works](#how-lead-capture-works)
- [Embedding the capture form on raikancinta.com](#embedding-the-capture-form-on-raikancintacom)
- [Data model](#data-model)
- [Pages & navigation](#pages--navigation)
- [The analytics dashboard](#the-analytics-dashboard)
- [Configuration](#configuration)
- [Setup & running locally](#setup--running-locally)
- [Testing](#testing)
- [Migration note (important)](#migration-note-important)
- [Files added / changed](#files-added--changed)

---

## What's new

1. **Automatic lead capture.** When a visitor on raikancinta.com clicks "contact
   on WhatsApp", a small popup form collects their name, phone, and email and
   saves them into this dashboard **before** the WhatsApp conversation starts —
   no more keying leads in by hand.
2. **Lead + project pipeline.** Every lead can have multiple projects (deals).
   Each project moves through a configurable pipeline (New → Contacted → Quote
   Sent → Negotiation → Won / Lost) and shows a progress bar.
3. **Sales analytics dashboard.** The new landing page shows headline KPIs plus
   four charts: pipeline funnel, revenue & profit over time, win/loss, and lead
   source & volume.

---

## How lead capture works

```
Visitor on raikancinta.com
        │  clicks "Chat on WhatsApp"
        ▼
  Popup form (name, phone, email)          ← public/embed/lead-form.js
        │  POST /api/leads   (CORS-locked, throttled)
        ▼
  Dashboard saves the Lead (source = website)
        │  responds { success, whatsapp_url }
        ▼
  Browser redirects to https://wa.me/<number>?text=…
```

- The endpoint is **`POST /api/leads`** (`api.leads.store`). It's public but
  locked to allowed origins via CORS and rate-limited (`throttle:10,1`).
- **De-duplication:** a repeat submission from the same phone within
  `crm.dedup_window_minutes` (default 60) updates the existing lead instead of
  creating a duplicate — this absorbs double-taps and retries.
- The phone number is normalised to digits (with an optional leading `+`) so the
  same person always maps to one lead.
- The returned `whatsapp_url` is built from `config('crm.whatsapp_number')` plus
  a prefilled message (`{name}` is replaced with the lead's name).

---

## Embedding the capture form on raikancinta.com

The widget is a single dependency-free script — no build step needed on the
marketing site (works with WordPress, static HTML, anything).

```html
<!-- 1. A trigger (any element with this class opens the form) -->
<a href="#" class="js-raikan-lead">Chat on WhatsApp</a>

<!-- 2. The widget script (point it at your dashboard domain) -->
<script
  src="https://YOUR-DASHBOARD-DOMAIN/embed/lead-form.js"
  data-api="https://YOUR-DASHBOARD-DOMAIN/api/leads"
  data-trigger=".js-raikan-lead"
  defer></script>
```

You can also open it programmatically: `window.RaikanLead.open()`.

**Before it works in production:**

1. Add the marketing origin(s) to `CORS_ALLOWED_ORIGINS` in the dashboard's
   `.env` (comma-separated, never `*`):
   ```
   CORS_ALLOWED_ORIGINS="…existing…,https://raikancinta.com,https://www.raikancinta.com"
   ```
2. Set your WhatsApp number (international format, no `+` or spaces —
   e.g. Malaysian `011-1234 5678` → `601112345678`):
   ```
   WHATSAPP_NUMBER=601112345678
   WHATSAPP_MESSAGE="Hi, saya {name}. Saya berminat dengan Raikan Cinta."
   ```

A live demo + copy-paste snippet is served at `/embed/example.html`.

---

## Data model

```
Lead (contact)  1 ──────< *  Project (deal)  * >────── 0..1  Event (wedding guestbook)
```

### `leads`
| Column | Notes |
|---|---|
| `name`, `phone` (indexed), `email` | phone is normalised digits |
| `source` | `website` / `whatsapp` / `referral` / `manual` |
| `status` | `new` / `contacted` / `qualified` / `won` / `lost` |
| `notes`, `last_contacted_at` | |

### `projects`
| Column | Notes |
|---|---|
| `lead_id` | FK → leads (cascade delete) |
| `event_id` | FK → events, **nullable** (linked once the guestbook is provisioned) |
| `title` | |
| `stage` | pipeline stage key (see `config/crm.php`) |
| `value`, `cost` | decimals in MYR; **profit = value − cost** (computed) |
| `expected_close_date`, `closed_at` | `closed_at` is stamped when a deal enters Won/Lost |

Model accessors of note:
- `Project::profit` — value minus cost.
- `Project::progress` — 0–100% based on the stage's position in the pipeline
  (Won = 100, Lost = 0). Drives the progress bar.
- `Lead::total_value`, `Lead::won_value`, `Lead::open_projects_count`.

---

## Pages & navigation

The top nav now has **Dashboard · Leads · Events**.

| Route | Page |
|---|---|
| `GET /dashboard` | Sales analytics dashboard (landing page) |
| `GET /leads` | Lead list with search + status/source filters |
| `GET /leads/create`, `GET /leads/{lead}/edit` | Add / edit a lead |
| `GET /leads/{lead}` | Lead detail — info, WhatsApp button, projects with progress bars, quick stage change |
| `GET /projects/create?lead={id}` | Add a project to a lead |
| `GET /projects/{project}/edit` | Edit a project (incl. linking a guestbook event) |
| `PATCH /projects/{project}/stage` | Quick stage move from the lead page |

All CRM pages are behind `auth`. The dashboard also requires a `verified` email.

---

## The analytics dashboard

**KPI tiles:** Total Leads · Open Projects · Pipeline Value · Revenue (Won) ·
Profit · Win Rate.

**Charts (Chart.js):**
1. **Revenue & Profit (last 6 months)** — monthly revenue + cost bars with a
   profit line.
2. **Pipeline Funnel** — project count per stage (value shown in the tooltip).
3. **Win / Loss** — won vs lost doughnut, with won/lost value beneath.
4. **Lead Volume (last 6 months)** — new leads per month.
5. **Lead Source** — breakdown by source.

> All time-series are grouped **in PHP** (not with SQL date functions) so the
> code runs identically on SQLite (dev) and MySQL (prod). Chart colours are
> dark-theme-tuned (the app renders dark-mode-only).

---

## Configuration

Everything tunable lives in [`config/crm.php`](config/crm.php):

- `whatsapp_number`, `whatsapp_message`
- `currency` (`MYR`) and `currency_symbol` (`RM`)
- `pipeline` — the ordered stages, each with a label, badge colour, and chart
  hex. Editing this changes the funnel, badges, and progress bars everywhere.
- `lead_sources`, `lead_statuses`
- `dedup_window_minutes`

Environment variables (see `.env.example`): `WHATSAPP_NUMBER`,
`WHATSAPP_MESSAGE`, `CRM_CURRENCY`, `CRM_CURRENCY_SYMBOL`,
`CRM_DEDUP_WINDOW_MINUTES`, and the extended `CORS_ALLOWED_ORIGINS`.

---

## Setup & running locally

```bash
composer install
npm install
cp .env.example .env        # then set APP_KEY, WHATSAPP_NUMBER, etc.
php artisan key:generate

php artisan migrate
php artisan db:seed --class=CrmDemoSeeder   # optional: demo leads/projects so charts have data

npm run build       # or: npm run dev
php artisan serve
```

Log in, and `/dashboard` becomes your landing page.

To test the capture endpoint directly:

```bash
curl -X POST http://127.0.0.1:8000/api/leads \
  -H "Content-Type: application/json" \
  -d '{"name":"Aisyah","phone":"011-2345 6789","email":"a@example.com"}'
# → 201 { "success": true, "whatsapp_url": "https://wa.me/…" }
```

---

## Testing

```bash
php artisan test
```

CRM behaviour is covered by [`tests/Feature/CrmTest.php`](tests/Feature/CrmTest.php):
lead capture (create, dedup, validation), authenticated page rendering, adding a
project, and the Won→`closed_at` transition.

---

## Migration note (important)

The `events` and `guestbook_entries` tables previously existed **only** in the
committed MySQL schema dump (`database/schema/mysql-schema.sql`), with no Laravel
`create` migrations. That meant a fresh **SQLite** setup (local dev and the test
suite) had no such tables and every migration failed.

This update adds guarded create-migrations
(`2026_06_23_000000_create_events_table.php`,
`2026_06_23_000001_create_guestbook_entries_table.php`). They are wrapped in
`Schema::hasTable(...)` checks, so they:

- **create** the tables on a fresh SQLite / CI database, and
- **no-op** on any environment where the tables already exist (e.g. production
  built from the schema dump).

Net effect: `php artisan migrate` and `php artisan test` now work on a clean
checkout, and production is unaffected.

---

## Files added / changed

**New**
- Models: `app/Models/Lead.php`, `app/Models/Project.php`
- Controllers: `app/Http/Controllers/{LeadController,ProjectController,SalesDashboardController}.php`,
  `app/Http/Controllers/Api/LeadCaptureController.php`
- Requests: `app/Http/Requests/{StoreLeadRequest,StoreLeadOwnerRequest,StoreProjectRequest,UpdateProjectRequest}.php`
- Config: `config/crm.php`
- Migrations: `database/migrations/2026_06_23_*` (events/guestbook backfill),
  `database/migrations/2026_07_06_*` (leads/projects)
- Factories/seeder: `database/factories/{LeadFactory,ProjectFactory}.php`,
  `database/seeders/CrmDemoSeeder.php`
- Views: `resources/views/{sales,leads,projects}/**`,
  `resources/views/components/{stage-badge,lead-status-badge,money}.blade.php`
- Embed widget: `public/embed/lead-form.js`, `public/embed/example.html`
- Tests: `tests/Feature/CrmTest.php`

**Changed**
- `app/Models/Event.php` (added the `project()` relation)
- `routes/web.php`, `routes/api.php`
- `resources/views/layouts/navigation.blade.php`
- `resources/js/app.js` (registers Chart.js), `package.json`
- `.env.example`, `tests/Feature/ExampleTest.php`
