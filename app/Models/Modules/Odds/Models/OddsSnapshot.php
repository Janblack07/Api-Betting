<?php

namespace App\Modules\Odds\Models;

use App\Modules\Betting\Models\BetSelection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OddsSnapshot extends Model
{
    protected $fillable = [
        'sport_event_id',
        'external_event_id',
        'sport_key',
        'bookmaker_id',
        'bookmaker_key',
        'bookmaker_title',
        'market_id',
        'market_key',
        'selection_name',
        'selection_description',
        'price',
        'point',
        'commence_time',
        'snapshot_at',
        'hash',
        'is_active',
        'raw_payload',
    ];

    protected $casts = [
        'price' => 'decimal:4',
        'point' => 'decimal:2',
        'commence_time' => 'datetime',
        'snapshot_at' => 'datetime',
        'is_active' => 'boolean',
        'raw_payload' => 'array',
    ];

    public function sportEvent(): BelongsTo
    {
        return $this->belongsTo(SportEvent::class);
    }

    public function bookmaker(): BelongsTo
    {
        return $this->belongsTo(Bookmaker::class);
    }

    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    public function betSelections(): HasMany
    {
        return $this->hasMany(BetSelection::class, 'snapshot_id');
    }

    public function isAvailable(): bool
    {
        return $this->is_active === true;
    }
}
