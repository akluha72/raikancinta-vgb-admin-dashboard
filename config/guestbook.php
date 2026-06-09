<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Guest App + Gallery Base URLs
    |--------------------------------------------------------------------------
    |
    | Base URLs for the per-event guest submission app and the post-wedding
    | gallery. The owner dashboard composes per-event links from these, so the
    | domains are never hardcoded in code or views.
    |
    */

    'guest_app_base_url' => env('GUEST_APP_BASE_URL', 'https://vgb2.raikancinta.com'),

    'gallery_base_url' => env('GALLERY_BASE_URL', 'https://gallery-vgb.raikancinta.com'),

    /*
    |--------------------------------------------------------------------------
    | Submission Upload Disk
    |--------------------------------------------------------------------------
    |
    | Filesystem disk where guest photos + audio are stored, namespaced by
    | event. "public" for local dev (served via storage:link); "s3" in prod
    | (S3 / Cloudflare R2 — configured via AWS_* env). Never hardcoded.
    |
    */

    'disk' => env('GUESTBOOK_DISK', env('FILESYSTEM_DISK', 'public')),

    /*
    |--------------------------------------------------------------------------
    | Gallery Access Token
    |--------------------------------------------------------------------------
    |
    | After a correct PIN, the gallery receives a stateless, event-scoped HMAC
    | token (no server session — the gallery is a separate-domain SPA). TTL is
    | in seconds. Read-time URL expiry for signed file URLs reuses this value.
    |
    */

    'token_ttl' => (int) env('GALLERY_TOKEN_TTL', 3600),

];
