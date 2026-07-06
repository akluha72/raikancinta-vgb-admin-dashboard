<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    /** @use HasFactory<\Database\Factories\LeadFactory> */
    use HasFactory;

    /**
     * `source` and `status` are constrained to config/crm.php values in the
     * request layer, not the DB, so they stay mass-assignable here.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'source',
        'status',
        'notes',
        'last_contacted_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'last_contacted_at' => 'datetime',
    ];

    /**
     * Deals/opportunities belonging to this lead.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Sum of every project's value (pipeline + closed). Uses the already
     * loaded `projects` relation when present to avoid an extra query.
     */
    protected function totalValue(): Attribute
    {
        return Attribute::get(fn (): float => (float) $this->projects->sum('value'));
    }

    /**
     * Sum of value across this lead's WON projects (recognised revenue).
     */
    protected function wonValue(): Attribute
    {
        return Attribute::get(fn (): float => (float) $this->projects
            ->where('stage', 'won')
            ->sum('value'));
    }

    /**
     * Count of projects that are still open (not in a terminal stage).
     */
    protected function openProjectsCount(): Attribute
    {
        return Attribute::get(fn (): int => $this->projects
            ->reject(fn (Project $p) => $p->isClosed())
            ->count());
    }
}
