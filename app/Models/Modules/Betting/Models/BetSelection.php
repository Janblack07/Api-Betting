<?php

namespace App\Modules\Betting\Models;

use App\Modules\Odds\Models\OddsSnapshot;
use App\Modules\Odds\Models\SportEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetSelection extends Model
{
    protected $fillable = [
        'bet_id',
        'sport_event_id',
        'snapshot_id',
        'external_event_id',
        'sport_key',
        'market_key',
        'bookmaker_key',
        'selection_name',
        'odds_price',
        'point',
        'status',
        'result',
    ];

    protected $casts = [
        'odds_price' => 'decimal:4',
        'point' => 'decimal:2',
    ];

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }

    public function sportEvent(): BelongsTo
    {
        return $this->belongsTo(SportEvent::class);
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(OddsSnapshot::class, 'snapshot_id');
    }
}
