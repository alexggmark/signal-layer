<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GA4Connection extends Model
{
    /** @use HasFactory<\Database\Factories\GA4ConnectionFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ga4_connections';

    /**
     * Rate limit in hours between report generations.
     */
    public const RATE_LIMIT_HOURS = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'property_id',
        'property_name',
        'refresh_token',
        'last_report_generated_at',
        'connected_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'refresh_token' => 'encrypted',
            'last_report_generated_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns this GA4 connection.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if a report can be generated (respects 5-hour rate limit).
     */
    public function canGenerateReport(): bool
    {
        if (is_null($this->last_report_generated_at)) {
            return true;
        }

        return $this->last_report_generated_at->addHours(self::RATE_LIMIT_HOURS)->isPast();
    }

    /**
     * Get the time remaining until the next report can be generated.
     *
     * @return array{hours: int, minutes: int}|null
     */
    public function timeUntilNextReport(): ?array
    {
        if ($this->canGenerateReport()) {
            return null;
        }

        $nextAvailable = $this->last_report_generated_at->addHours(self::RATE_LIMIT_HOURS);
        $diff = Carbon::now()->diff($nextAvailable);

        return [
            'hours' => $diff->h,
            'minutes' => $diff->i,
        ];
    }

    /**
     * Mark a report as having been generated.
     */
    public function markReportGenerated(): void
    {
        $this->update(['last_report_generated_at' => Carbon::now()]);
    }

    /**
     * Get all snapshots for this connection.
     */
    public function snapshots(): HasMany
    {
        return $this->hasMany(GA4Snapshot::class, 'connection_id');
    }

    /**
     * Get the latest snapshot for this connection.
     */
    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(GA4Snapshot::class, 'connection_id')
            ->latestOfMany('generated_at');
    }
}
