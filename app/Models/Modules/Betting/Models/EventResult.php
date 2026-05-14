<?php

namespace App\Modules\Betting\Models;

use App\Modules\Odds\Models\SportEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventResult extends Model
{
    protected $fillable = [
        'sport_event_id',
        'external_event_id',
        'sport_key',
        'home_score',
        'away_score',
        'winner_name',
        'result_type',
        'status',
        'source',
        'raw_payload',
        'resulted_at',
    ];

    protected $casts = [
        'home_score' => 'integer',
        'away_score' => 'integer',
        'raw_payload' => 'array',
        'resulted_at' => 'datetime',
    ];

    public function sportEvent(): BelongsTo
    {
        return $this->belongsTo(SportEvent::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || $this->result_type === 'cancelled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isDraw(): bool
    {
        return $this->result_type === 'draw';
    }
}
