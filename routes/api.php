<?php

use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\GuestSubmissionController;
use Illuminate\Support\Facades\Route;

/*
| Public APIs for the guest submission app and the gallery.
|
| The {event} parameter is resolved server-side from the slug (binding defined
| in AppServiceProvider) — the slug only ever resolves to a valid event or a
| 404. The client can never inject or override event_id; all scoping derives
| from this lookup.
*/

// Guest submission — throttled to absorb live-event bursts without abuse.
Route::post('/events/{event}/submissions', [GuestSubmissionController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.submissions.store');

// Gallery PIN check — tighter throttle to resist brute force on a 6-digit PIN.
Route::post('/events/{event}/verify-pin', [GalleryController::class, 'verifyPin'])
    ->middleware('throttle:5,1')
    ->name('api.gallery.verify-pin');

// Approved entries — token-gated (event-scoped token from verify-pin).
Route::get('/events/{event}/submissions', [GalleryController::class, 'index'])
    ->middleware('gallery.token')
    ->name('api.gallery.index');
