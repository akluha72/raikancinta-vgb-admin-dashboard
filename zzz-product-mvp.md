# Build Prompt: Wedding Guestbook — Owner Dashboard MVP (Laravel)

## Context

I run a voice-and-photo wedding guestbook platform. Guests submit a photo, a voice recording, and a written message via a guest app; couples view approved submissions in a post-wedding gallery. The system is multi-tenant: one deployment serves many weddings, isolated by `event_id`.

I need an **owner dashboard** — an internal admin panel for me (the business owner) to onboard new customers (events) and monitor submissions, so I no longer create events by running SQL manually.

This is separate from the per-event moderation tool (the approve/bin tool the couple uses). Do NOT build moderation here.

## Tech stack

- **Laravel 11** (latest stable)
- **MySQL** (existing database — see schema below)
- **Blade + Tailwind CSS** for views (keep it simple; no SPA)
- **Laravel Breeze** for owner authentication (single admin user is fine for MVP)
- Standard Laravel conventions (Eloquent, migrations, form requests, resource controllers)

## Existing database

Two tables already exist. Do NOT recreate them — generate Eloquent models that map to them.

```sql
-- events (parent table)
events (
  id            INT(11) PK AUTO_INCREMENT,
  slug          VARCHAR(120) UNIQUE NOT NULL,
  couple_name   VARCHAR(150) NOT NULL,
  wedding_date  DATE NULL,
  gallery_pin   VARCHAR(10) NULL,
  plan_tier     VARCHAR(50) DEFAULT 'basic',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)

-- guestbook_entries (submissions, scoped by event_id)
guestbook_entries (
  id            INT(11) PK AUTO_INCREMENT,
  event_id      INT(11) NOT NULL,           -- FK -> events.id
  guest_name    VARCHAR(100) NOT NULL,
  event_date    DATE NOT NULL,
  photo         VARCHAR(255) NULL,
  audio         VARCHAR(255) NOT NULL,
  guest_message TEXT NULL,
  status        ENUM('pending','approved','binned') DEFAULT 'pending',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
)
```

Note: `events` uses only `created_at` (no `updated_at`), so disable Eloquent's automatic `updated_at` on the Event model (`const UPDATED_AT = null;`) or set `$timestamps` appropriately. `guestbook_entries` has both.

Define the relationships:
- `Event hasMany GuestbookEntry`
- `GuestbookEntry belongsTo Event`

## Configuration (env)

Add these to `.env` / config so links are generated correctly. Do NOT hardcode domains in code:

```
GUEST_APP_BASE_URL=https://vgb2.raikancinta.com
GALLERY_BASE_URL=https://gallery-vgb.raikancinta.com
```

## Features to build (MVP — exactly these four, nothing more)

### 1. Create Event

A form (`GET /events/create`, `POST /events`) with:
- **Couple name** (required, string)
- **Wedding date** (optional, date)
- **Plan tier** (optional, select: basic / premium — default basic)

On submit, the controller must auto-generate:

**Slug** — from the couple name, with collision protection:
- Slugify the couple name (lowercase, hyphenated): e.g. "Sarah & Ali" → `sarah-ali`
- Append a short random token to guarantee uniqueness and make it unguessable: `sarah-ali-x7k2` (4-char lowercase alphanumeric)
- Before insert, verify the slug does not already exist in `events`; regenerate the token if it collides (loop until unique)

**Gallery PIN** — a random 6-digit numeric code (e.g. `483920`), stored in `gallery_pin`.

Validate input with a Form Request. On success, redirect to the new event's detail page with a flash message.

### 2. List All Events

`GET /events` — a Blade table showing all events:

| Couple | Wedding Date | Slug | Submissions | Status | |
|--------|-------------|------|-------------|--------|---|
| Sarah & Ali | 2026-08-12 | sarah-ali-x7k2 | 47 | Live | View → |

- **Submissions** = total count of `guestbook_entries` for that event (use `withCount`)
- **Status** = a derived label (see "Status logic" below)
- Most recent events first
- A simple search box filtering by couple name (optional but nice)
- A prominent "Create Event" button

### 3. Event Detail View

`GET /events/{event}` — for one event, show:

**Links section** (each with a copy-to-clipboard button):
- Guest submission URL: `{GUEST_APP_BASE_URL}/{slug}`
- Gallery URL: `{GALLERY_BASE_URL}/{slug}`

**Submission summary** (the counts, queried with a single grouped query):
- Total
- Pending
- Approved
- Binned

Display as simple stat cards — no charts.

**Event info:**
- Couple name, wedding date, plan tier, created date
- Gallery PIN displayed, with a **"Reset PIN"** button (`POST /events/{event}/reset-pin`) that regenerates a new 6-digit PIN and saves it

### 4. Submission Counts

Implemented as part of the detail view and list view above. Use efficient aggregate queries, not loops:

```php
// counts grouped by status for one event
GuestbookEntry::where('event_id', $event->id)
    ->selectRaw('status, COUNT(*) as total')
    ->groupBy('status')
    ->pluck('total', 'status');
```

For the list view, use `Event::withCount('entries')`.

## Status logic (derived, not stored)

Compute event status in the model as an accessor — do not add a DB column:
- **Upcoming** — `wedding_date` is in the future
- **Live** — `wedding_date` is today
- **Past** — `wedding_date` is in the past
- **Draft** — `wedding_date` is null

## Routes summary

```
GET    /events                      events.index    (list)
GET    /events/create               events.create   (form)
POST   /events                      events.store     (create)
GET    /events/{event}              events.show      (detail)
POST   /events/{event}/reset-pin    events.reset-pin (regenerate PIN)
```

All routes behind `auth` middleware (Breeze).

## Explicitly OUT of scope (do not build)

- Submission moderation / approve / bin (that's a separate per-event tool)
- Charts or time-series metrics (plain numbers only)
- Billing or payment tracking
- Plan-tier limit enforcement / quotas
- Album export / delivery
- Guest notifications
- Per-event branding / theming
- Editing or deleting events (read + create only for MVP)
- Multi-admin roles / permissions (single owner login)

## Deliverables

1. Migration-free Eloquent models (`Event`, `GuestbookEntry`) mapped to existing tables, with relationships and the status accessor.
2. `EventController` (resource: index, create, store, show) + a `reset-pin` action.
3. A `StoreEventRequest` form request with validation.
4. A service or helper class for slug + token + PIN generation with collision checking (keep generation logic out of the controller).
5. Blade views: `events/index`, `events/create`, `events/show`, styled with Tailwind, clean and minimal.
6. Copy-to-clipboard JS for the link fields (vanilla JS, no framework).
7. Breeze auth scaffolding for the owner login.
8. Seed one test event so I can see the UI populated.

## Code quality

- Keep generation logic (slug/token/PIN) in a dedicated, testable class.
- Use Eloquent and query builder idiomatically; no raw loops for counting.
- Validate and sanitize all input.
- Comment the non-obvious parts (collision loop, status accessor).