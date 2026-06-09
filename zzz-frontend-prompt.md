# Build Prompt: Wedding Guestbook — Guest App + Gallery Frontends

## Context

You are building **two small single-page web apps** that consume an existing Laravel JSON API (already built — do not modify it). The platform is a voice-and-photo wedding guestbook. Each wedding is one "event", identified in URLs by a **slug** (e.g. `sarah-ali-x7k2`).

1. **Guest App** — deployed at `https://vgb2.raikancinta.com`. Guests open `/{slug}`, then submit a **photo (optional)**, a **voice recording (required)**, and a written **message** for that wedding.
2. **Gallery** — deployed at `https://gallery-vgb.raikancinta.com`. Couples/guests open `/{slug}`, enter a **6-digit PIN**, and then browse **approved** submissions (photo + audio playback + message).

Both are separate-domain SPAs talking to the same API host. **There is no shared login** — the slug + (for the gallery) the PIN are the only access mechanisms.

## Tech stack (recommended — adjust if you have a strong reason)

- **React + Vite + TypeScript** (or Vue 3 — pick one and stay consistent).
- **Tailwind CSS** for styling. Mobile-first: guests are on phones at a wedding.
- Plain `fetch` (or `axios`) — no heavy data layer needed.
- Two separate Vite apps (or one repo with two entry points). Keep them independent; they share only the API types.
- Routing: each app is essentially one route, `/:slug`. Use the path segment as the slug.

## API base URL

Read from an env var, never hardcode:

```
VITE_API_BASE_URL=http://127.0.0.1:8000   # dev (Laravel `php artisan serve`)
# prod: the deployed API host
```

All endpoints below are under `${VITE_API_BASE_URL}/api`.

## Universal response envelope

**Every** response — success or failure — is JSON with this shape. Never assume an empty/HTML body.

```jsonc
// success
{ "success": true, ...payload }
// failure
{ "success": false, "message": "Human-readable message", "errors"?: { "field": ["msg", ...] } }
```

Status codes you must handle on every call: **200/201** (ok), **401** (bad PIN / bad-or-expired token), **404** (unknown slug), **422** (validation — has `errors`), **429** (rate-limited — show "please slow down"), **500** (server — show the `message`, offer retry). Always read `message` for user-facing error text.

---

## App 1 — Guest App

### Route: `/{slug}`

A single submission form. The slug comes from the URL path; you never ask the user for it, and you never send an `event_id` — the API resolves the event from the slug server-side.

### Endpoint

```
POST ${API}/api/events/{slug}/submissions     (multipart/form-data)
```

**Form fields:**

| Field | Required | Notes |
|-------|----------|-------|
| `guest_name` | yes | text, max 100 |
| `audio` | **yes** | file; mp3/wav/m4a/webm/ogg; max 20 MB |
| `photo` | no | image; jpg/png/webp/heic; max 10 MB |
| `guest_message` | no | text, max 2000 |

**Build the request like this:**

```ts
const fd = new FormData();
fd.append("guest_name", name);
fd.append("audio", audioBlob, "voice.webm");   // required
if (photoFile) fd.append("photo", photoFile);
if (message) fd.append("guest_message", message);

const res = await fetch(`${API}/api/events/${slug}/submissions`, {
  method: "POST",
  body: fd,                       // do NOT set Content-Type; the browser sets the multipart boundary
  headers: { "Accept": "application/json" },
});
const json = await res.json();
```

**Responses:**

```jsonc
// 201
{ "success": true, "message": "Submission received", "data": { "id": 123 } }
// 422
{ "success": false, "message": "Validation failed", "errors": { "audio": ["A voice recording is required."] } }
// 404  -> { "success": false, "message": "Event not found" }
// 429  -> too many submissions; ask them to wait a moment
// 500  -> { "success": false, "message": "Could not save your submission. Please try again." }
```

### Audio recording (the core feature — get this right)

- Record in-browser with **MediaRecorder** (`navigator.mediaDevices.getUserMedia({ audio: true })`).
- Output a `webm` or `ogg` Blob (whatever the browser produces) and send it as the `audio` field. The API accepts webm/ogg/mp3/wav/m4a.
- UI states: **idle → recording (show timer + a stop button) → recorded (playback preview + re-record)**. Don't allow submit until audio exists.
- Handle **mic permission denied** gracefully with a clear message; optionally allow an audio file upload as a fallback.
- Keep the recording reasonably short for size (well under 20 MB).

### Photo (optional)

- File input with `accept="image/*"` and `capture="environment"` so phones offer the camera.
- Show a thumbnail preview; allow clearing it.

### UX requirements

- **Mobile-first, large tap targets.** Guests use this once, quickly, at a party.
- Show a clear **success screen** after 201 ("Thank you! Your message has been sent for the couple to review."). Submissions are **pending moderation** — do not imply it's instantly public.
- **Inline field errors** from the 422 `errors` object, keyed by field name.
- Disable the submit button while uploading; show a progress/spinner. Uploads can be slow on venue wifi.
- On 404 (bad slug), show a friendly "This guestbook link isn't valid" page.
- Don't lose a recording on a failed submit — let them retry without re-recording.

---

## App 2 — Gallery

### Route: `/{slug}`

Two phases: **(A) PIN gate**, then **(B) approved-submissions feed**.

### Phase A — verify the PIN

```
POST ${API}/api/events/{slug}/verify-pin      (application/json)
body: { "pin": "483920" }
```

```jsonc
// 200  -> { "success": true, "token": "<opaque-token-string>" }
// 401  -> { "success": false, "message": "Incorrect PIN" }
// 429  -> too many attempts (limit is 5/min) — tell them to wait
```

```ts
const res = await fetch(`${API}/api/events/${slug}/verify-pin`, {
  method: "POST",
  headers: { "Content-Type": "application/json", "Accept": "application/json" },
  body: JSON.stringify({ pin }),
});
```

- The returned **`token`** is **scoped to this event** and **expires (~1 hour)**. Store it in memory or `sessionStorage` keyed by slug — NOT localStorage long-term (it expires server-side anyway).
- A token from one event will **not** work on another (you'll get 401) — that's expected.
- Build a simple PIN entry UI (6-digit). On 401 show "Incorrect PIN" and let them retry; after a few quick tries the API may 429 — surface that.

### Phase B — list approved submissions

Send the token as a **Bearer header** (preferred) or `?token=` query param.

```
GET ${API}/api/events/{slug}/submissions?page=1
headers: { "Authorization": "Bearer <token>", "Accept": "application/json" }
```

```jsonc
// 200
{
  "success": true,
  "event": { "couple_name": "Sarah & Ali", "wedding_date": "2026-08-12" },
  "data": [
    {
      "id": 123,
      "guest_name": "Auntie Mariam",
      "guest_message": "Wishing you both...",
      "photo_url": "https://.../events/5/photos/uuid.jpg",  // may be null
      "audio_url": "https://.../events/5/audio/uuid.webm",   // always present
      "created_at": "2026-08-12T14:03:00+00:00"
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "total": 47 }
}
// 401 -> token missing/expired/invalid -> send the user back to the PIN gate
```

- Only **approved** entries are returned — no pending/binned, no moderation UI here.
- **20 per page.** Implement pagination or infinite scroll using `meta.current_page` / `meta.last_page`; pass `?page=`.
- Render each entry as a card: photo (if `photo_url` not null), an **audio `<player>`** for `audio_url` (this is the emotional core — make playback prominent and pleasant), the message, the guest name, and a formatted date.
- Use `photo_url` / `audio_url` **as-is** — they're ready-to-use URLs (may be time-limited signed URLs on cloud storage). Don't construct file paths yourself. If `photo_url` is null, render an audio-only card.
- Show the couple's name + wedding date (from `event`) as a header.
- **On any 401 during browsing** (token expired mid-session), drop back to the PIN gate with a gentle "Please re-enter the PIN" message.

### UX requirements

- This is a keepsake — make it feel warm. A clean, scrollable feed; tasteful empty state if there are no approved entries yet ("No messages to show yet — check back soon.").
- Mobile-first, but it'll also be viewed on laptops/TVs — make it look good wider too.
- One audio playing at a time is a nice touch (pause others when one starts).

---

## CORS / connectivity notes

- The API already allows these origins via CORS: the two prod domains plus `http://localhost:5173` and `http://localhost:3000` for dev. If you run Vite on a different port, tell the backend owner to add it to `CORS_ALLOWED_ORIGINS` — don't try to work around CORS client-side.
- No cookies/credentials are used; do **not** set `credentials: "include"`. Auth is purely the bearer token for the gallery.

## Deliverables

1. Two SPAs (guest + gallery), each routed by `/:slug`, reading `VITE_API_BASE_URL` from env.
2. A small typed API client wrapping the three calls, with a single error-handling path that reads the `{ success, message, errors }` envelope and maps status codes to user-facing states (404 / 422 / 401 / 429 / 500).
3. Guest app: MediaRecorder-based audio capture (required) + optional photo + message, with mic-permission and upload-failure handling, and a clear pending-moderation success screen.
4. Gallery: PIN gate → token storage (sessionStorage, per-slug) → paginated approved feed with audio playback and photos; auto-return to PIN gate on 401.
5. Mobile-first Tailwind UI for both.

## Out of scope (the API does not support these — don't build UI for them)

- Editing or deleting submissions.
- Any moderation (approve/bin) — that's a separate internal tool.
- Account/login, social sharing, notifications.
- Listing events or discovering slugs — a user always arrives with a specific slug (typically via a QR code).

## Quick manual test against local API

```
# 1. start the API:  php artisan serve   (defaults to http://127.0.0.1:8000)
# 2. an event + PIN already exists from the seeder; ask the backend owner for a test slug + PIN,
#    or read it from the owner dashboard event detail page.
# 3. point VITE_API_BASE_URL at http://127.0.0.1:8000 and exercise both flows.
```
