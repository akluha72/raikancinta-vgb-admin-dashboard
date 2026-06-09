<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestbookEntry extends Model
{
    /**
     * The `guestbook_entries` table has both created_at and updated_at,
     * so default timestamp handling is correct.
     */
    protected $fillable = [
        'event_id',
        'guest_name',
        'event_date',
        'photo',
        'audio',
        'guest_message',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'event_date' => 'date',
    ];

    /**
     * The event this submission belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
