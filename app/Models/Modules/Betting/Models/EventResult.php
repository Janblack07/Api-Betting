<?php

namespace App\Modules\Betting\Models;

use App\Modules\Odds\Models\SportEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventResult extends Model
{
    protected $fillable = [
        'sport_event_id',
        'home_score',
        'away_score',
        'winner_name',
        'status',
        'source',
        'raw_payload',
    ];

    protected $casts = [
        'home_score' => 'integer',
        'away_score' => 'integer',
        'raw_payload' => 'array',
    ];

    public function sportEvent(): BelongsTo
    {
        return $this->belongsTo(SportEvent::class);
    }
}
