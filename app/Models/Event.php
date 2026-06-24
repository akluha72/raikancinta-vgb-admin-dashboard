<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    /**
     * The `events` table already exists and uses only `created_at`
     * (no `updated_at`), so disable Eloquent's automatic `updated_at`.
     */
    const UPDATED_AT = null;

    /**
     * Mass-assignable attributes. `slug` and `gallery_pin` are generated
     * server-side (see EventCredentialGenerator), never user-supplied.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'couple_name',
        'wedding_date',
        'gallery_pin',
        'plan_tier',
        'couple_photo',
        'greeting_audio',
    ];

    /**
     * Append the resolved public media URLs to array/JSON output so the guest
     * app (vgb2) receives couple_photo_url / greeting_audio_url directly.
     *
     * @var list<string>
     */
    protected $appends = [
        'couple_photo_url',
        'greeting_audio_url',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'wedding_date' => 'date',
    ];

    /**
     * Submissions belonging to this event.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(GuestbookEntry::class);
    }

    /**
     * Public URL for the couple photo, resolved at read time from the stored
     * relative path. Null when no photo is set.
     */
    protected function couplePhotoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->resolveMediaUrl($this->couple_photo));
    }

    /**
     * Public URL for the greeting audio, resolved at read time. Null when unset.
     */
    protected function greetingAudioUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->resolveMediaUrl($this->greeting_audio));
    }

    /**
     * Turn a stored relative path into a usable URL. Mirrors
     * SubmissionResource: a temporary signed URL on cloud disks (S3/R2),
     * falling back to a permanent public URL on the local/public disk.
     */
    private function resolveMediaUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        $disk = Storage::disk(Config::get('guestbook.disk', 'public'));

        try {
            return $disk->temporaryUrl(
                $path,
                now()->addSeconds((int) Config::get('guestbook.token_ttl', 3600))
            );
        } catch (\Throwable) {
            return $disk->url($path);
        }
    }

    /**
     * Derived status label based on wedding_date. Not stored in the DB.
     *
     *  - Draft    -> wedding_date is null
     *  - Upcoming -> wedding_date is in the future
     *  - Live     -> wedding_date is today
     *  - Past     -> wedding_date is in the past
     */
    protected function status(): Attribute
    {
        return Attribute::get(function (): string {
            if (is_null($this->wedding_date)) {
                return 'Draft';
            }

            // Compare by calendar day only (ignore time-of-day).
            $today = now()->startOfDay();
            $wedding = $this->wedding_date->copy()->startOfDay();

            return match (true) {
                $wedding->greaterThan($today) => 'Upcoming',
                $wedding->lessThan($today) => 'Past',
                default => 'Live',
            };
        });
    }
}
