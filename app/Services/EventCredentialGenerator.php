<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Support\Str;

/**
 * Generates the server-side credentials for an event: a unique, unguessable
 * slug and a gallery PIN. Kept out of the controller so the collision logic
 * is isolated and unit-testable.
 */
class EventCredentialGenerator
{
    /** Length of the random token appended to the slug. */
    private const TOKEN_LENGTH = 4;

    /**
     * Build a unique slug from the couple name.
     *
     * Format: "{slugified-name}-{token}", e.g. "Sarah & Ali" -> "sarah-ali-x7k2".
     * The random token makes the slug both unique and unguessable. We loop,
     * regenerating the token, until the slug is not already present in `events`.
     */
    public function uniqueSlug(string $coupleName): string
    {
        $base = Str::slug($coupleName);

        // Defensive: if the name slugifies to nothing (e.g. all symbols),
        // fall back to a neutral base so we never produce a leading hyphen.
        if ($base === '') {
            $base = 'event';
        }

        // Regenerate the token until we land on a slug no event uses yet.
        do {
            $slug = $base.'-'.$this->token();
        } while (Event::where('slug', $slug)->exists());

        return $slug;
    }

    /**
     * A 6-digit numeric gallery PIN, e.g. "483920".
     * Zero-padded so it always renders as 6 characters.
     */
    public function galleryPin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * A short, lowercase alphanumeric token (no ambiguous casing).
     */
    private function token(): string
    {
        return Str::lower(Str::random(self::TOKEN_LENGTH));
    }
}
