<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Services\GalleryTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards gallery read endpoints. The route must already have an {event}
 * bound by slug (so event_id is derived server-side, never client-supplied).
 * The request must carry a gallery token scoped to that exact event.
 */
class VerifyGalleryToken
{
    public function __construct(
        private readonly GalleryTokenService $tokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Event|null $event */
        $event = $request->route('event');

        // The slug bind failing would normally 404 before reaching here; this
        // is a defensive guard in case the middleware is misconfigured.
        if (! $event instanceof Event) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        }

        $token = $request->bearerToken() ?: $request->query('token');

        if (! $this->tokens->isValidFor($token, $event->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired access token. Please re-enter the gallery PIN.',
            ], 401);
        }

        return $next($request);
    }
}
