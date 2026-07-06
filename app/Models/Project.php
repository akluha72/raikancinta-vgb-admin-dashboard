<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'event_id',
        'title',
        'stage',
        'value',
        'cost',
        'expected_close_date',
        'closed_at',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:2',
        'cost' => 'decimal:2',
        'expected_close_date' => 'date',
        'closed_at' => 'datetime',
    ];

    /**
     * The lead (contact) this deal belongs to.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * The provisioned wedding event, once the deal is delivered. Nullable.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Gross profit for the deal: contract value minus expenses.
     */
    protected function profit(): Attribute
    {
        return Attribute::get(fn (): float => (float) $this->value - (float) $this->cost);
    }

    /**
     * Progress through the pipeline as a 0–100 percentage, derived from the
     * current stage's position among the OPEN stages (won = 100, lost = 0).
     * Drives the progress bar on the lead detail page.
     */
    protected function progress(): Attribute
    {
        return Attribute::get(function (): int {
            if ($this->stage === 'won') {
                return 100;
            }
            if ($this->stage === 'lost') {
                return 0;
            }

            $open = array_values(array_filter(
                array_keys(self::pipeline()),
                fn (string $key) => empty(self::pipeline()[$key]['closed'])
            ));

            $index = array_search($this->stage, $open, true);
            if ($index === false || count($open) <= 1) {
                return 0;
            }

            // Distribute open stages evenly below 100 (100 is reserved for won).
            return (int) round((($index + 1) / (count($open) + 1)) * 100);
        });
    }

    /**
     * Whether the deal is in a terminal (closed) stage — won or lost.
     */
    public function isClosed(): bool
    {
        return ! empty(self::pipeline()[$this->stage]['closed']);
    }

    /**
     * Human label for the current stage, from config.
     */
    protected function stageLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::pipeline()[$this->stage]['label'] ?? ucfirst($this->stage));
    }

    /**
     * Projects that have been won (recognised revenue).
     */
    public function scopeWon(Builder $query): Builder
    {
        return $query->where('stage', 'won');
    }

    /**
     * Projects that were lost.
     */
    public function scopeLost(Builder $query): Builder
    {
        return $query->where('stage', 'lost');
    }

    /**
     * Projects still in play (not in any terminal stage).
     */
    public function scopeOpen(Builder $query): Builder
    {
        $closed = array_keys(array_filter(
            self::pipeline(),
            fn (array $s) => ! empty($s['closed'])
        ));

        return $query->whereNotIn('stage', $closed);
    }

    /**
     * The configured pipeline map (stage key => meta).
     *
     * @return array<string, array<string, mixed>>
     */
    public static function pipeline(): array
    {
        return Config::get('crm.pipeline', []);
    }
}
