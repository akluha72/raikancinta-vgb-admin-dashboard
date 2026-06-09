<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

/**
 * Issues and verifies stateless, event-scoped access tokens for the gallery.
 *
 * The gallery is a separate-domain SPA, so we cannot rely on server session
 * state. Instead, after a correct PIN we hand back a self-contained token:
 *
 *     base64url(event_id . "." . expires) . "." . base64url(hmac_sha256(payload, APP_KEY))
 *
 * The signature is keyed by the application key, so it cannot be forged
 * client-side. The token embeds the event_id it is scoped to — a token
 * issued for one event can never unlock another (checked at verify time).
 */
class GalleryTokenService
{
    /**
     * Issue a token for the given event, valid for the configured TTL.
     */
    public function issue(int $eventId): string
    {
        $expires = time() + Config::get('guestbook.token_ttl', 3600);
        $payload = $eventId.'.'.$expires;

        return $this->b64($payload).'.'.$this->b64($this->sign($payload));
    }

    /**
     * Verify a token is well-formed, unexpired, correctly signed, AND scoped
     * to the given event. Returns true only if every check passes.
     */
    public function isValidFor(?string $token, int $eventId): bool
    {
        if (! is_string($token) || ! str_contains($token, '.')) {
            return false;
        }

        [$encodedPayload, $encodedSig] = array_pad(explode('.', $token, 2), 2, '');

        $payload = $this->unb64($encodedPayload);
        $providedSig = $this->unb64($encodedSig);

        if ($payload === '' || $providedSig === '') {
            return false;
        }

        // Constant-time signature comparison to resist timing attacks.
        if (! hash_equals($this->sign($payload), $providedSig)) {
            return false;
        }

        [$tokenEventId, $expires] = array_pad(explode('.', $payload, 2), 2, null);

        // Scope check: the token must belong to the event being accessed.
        if ((string) $tokenEventId !== (string) $eventId) {
            return false;
        }

        // Expiry check.
        return is_numeric($expires) && (int) $expires >= time();
    }

    private function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->key(), true);
    }

    /**
     * Use the raw application key (strip the "base64:" prefix Laravel stores).
     */
    private function key(): string
    {
        $key = (string) Config::get('app.key');

        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7));
        }

        return $key;
    }

    private function b64(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function unb64(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
