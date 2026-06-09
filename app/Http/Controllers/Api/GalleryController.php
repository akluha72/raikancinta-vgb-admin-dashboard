<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubmissionResource;
use App\Models\Event;
use App\Services\GalleryTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function __construct(
        private readonly GalleryTokenService $tokens,
    ) {}

    /**
     * Verify the gallery PIN for an event and, on success, issue a stateless
     * event-scoped access token. The slug resolves the event server-side; the
     * PIN is compared against that event's stored gallery_pin only.
     */
    public function verifyPin(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'pin' => ['required', 'string'],
        ]);

        // Constant-time compare; reject if the event has no PIN set.
        $expected = (string) ($event->gallery_pin ?? '');

        if ($expected === '' || ! hash_equals($expected, (string) $validated['pin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect PIN',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'token' => $this->tokens->issue($event->id),
        ]);
    }

    /**
     * Return this event's approved submissions, paginated. Reached only after
     * the gallery.token middleware has validated an event-scoped token, so the
     * caller is proven to hold the PIN for exactly this event.
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        $entries = $event->entries()
            ->where('status', 'approved')
            ->latest('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'event' => [
                'couple_name' => $event->couple_name,
                'wedding_date' => $event->wedding_date?->toDateString(),
            ],
            'data' => SubmissionResource::collection($entries)->resolve(),
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'total' => $entries->total(),
            ],
        ]);
    }
}
