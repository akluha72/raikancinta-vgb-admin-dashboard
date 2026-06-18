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
     * Return this event's submissions, paginated. Reached only after the
     * gallery.token middleware has validated an event-scoped token, so the
     * caller is proven to hold the PIN for exactly this event.
     *
     * Used by the PIN-protected gallery app (vgb-gallery.raikancinta.com).
     */
    public function index(Request $request, Event $event): JsonResponse
    {
        return $this->paginatedSubmissions($event);
    }

    /**
     * Public live-wall feed: the same submissions as index(), but with NO PIN
     * gate. Anyone holding the event slug (it's in the guest-app QR) can read
     * it, so the guest app at vgb2.raikancinta.com can show every submission as
     * it arrives. Throttled at the route level to absorb live-event polling.
     *
     * This intentionally exposes all of an event's submissions publicly; the
     * slug is the only thing protecting them. Keep the PIN-gated index() for
     * the separate gallery app.
     */
    public function feed(Request $request, Event $event): JsonResponse
    {
        return $this->paginatedSubmissions($event);
    }

    /**
     * Shared listing used by both the PIN-gated gallery and the public feed.
     *
     * Moderation is disabled for now — all submissions are returned. When the
     * approval workflow is re-enabled, add ->where('status', 'approved') here
     * (submissions are currently stored as 'approved' on arrival).
     */
    private function paginatedSubmissions(Event $event): JsonResponse
    {
        $entries = $event->entries()
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
