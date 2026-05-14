<?php

namespace App\Modules\Betting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetSettlementLog extends Model
{
    protected $fillable = [
        'bet_id',
        'admin_id',
        'settlement_type',
        'source',
        'previous_status',
        'new_status',
        'observation',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
