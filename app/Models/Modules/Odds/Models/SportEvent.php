<?php

namespace App\Modules\Odds\Models;

use App\Modules\Betting\Models\BetSelection;
use App\Modules\Betting\Models\EventResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SportEvent extends Model
{
    protected $fillable = [
        'external_event_id',
        'sport_id',
        'sport_key',
        'home_team',
        'away_team',
        'commence_time',
        'status',
        'is_live',
        'is_active',
        'raw_payload',
    ];

    protected $casts = [
        'commence_time' => 'datetime',
        'is_live' => 'boolean',
        'is_active' => 'boolean',
        'raw_payload' => 'array',
    ];

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function oddsSnapshots(): HasMany
    {
        return $this->hasMany(OddsSnapshot::class);
    }

    public function betSelections(): HasMany
    {
        return $this->hasMany(BetSelection::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(EventResult::class);
    }

    public function isAvailableForBetting(): bool
    {
        return $this->is_active === true
            && in_array($this->status, ['scheduled', 'live'], true);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [
            'completed',
            'cancelled',
            'suspended',
            'unavailable',
        ], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'scheduled' => 'Programado',
            'live' => 'En vivo',
            'completed' => 'Finalizado',
            'cancelled' => 'Cancelado',
            'suspended' => 'Suspendido',
            'unavailable' => 'No disponible',
            default => 'Desconocido',
        };
    }
}
