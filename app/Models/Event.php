<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
