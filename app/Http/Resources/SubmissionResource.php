<?php

namespace App\Http\Resources;

use App\Models\GuestbookEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

/**
 * Shapes a single approved guestbook entry for the gallery. File URLs are
 * resolved at read time from the stored relative paths — raw storage paths
 * are never exposed. On cloud disks we mint short-lived signed URLs.
 *
 * @mixin GuestbookEntry
 */
class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'guest_name' => $this->guest_name,
            'guest_message' => $this->guest_message,
            'photo_url' => $this->resolveUrl($this->photo),
            'audio_url' => $this->resolveUrl($this->audio),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }

    /**
     * Turn a stored relative path into a usable URL. Returns null when there
     * is no file (e.g. optional photo). Uses temporary signed URLs on disks
     * that support them (S3/R2), falling back to a permanent public URL.
     */
    private function resolveUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $disk = Storage::disk(Config::get('guestbook.disk', 'public'));

        try {
            // Cloud disks: time-limited signed URL scoped to the gallery TTL.
            return $disk->temporaryUrl(
                $path,
                now()->addSeconds(Config::get('guestbook.token_ttl', 3600))
            );
        } catch (\Throwable) {
            // Local/public disk doesn't support temporaryUrl — use public URL.
            return $disk->url($path);
        }
    }
}
