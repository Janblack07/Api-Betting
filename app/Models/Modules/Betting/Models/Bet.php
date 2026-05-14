<?php

namespace App\Modules\Betting\Models;

use App\Models\User;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bet extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'type',
        'total_amount',
        'total_odds',
        'potential_win',
        'status',
        'rejection_reason',
        'placed_at',
        'settled_at',
        'cancelled_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'total_odds' => 'decimal:4',
        'potential_win' => 'decimal:2',
        'placed_at' => 'datetime',
        'settled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function selections(): HasMany
    {
        return $this->hasMany(BetSelection::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'reference_id')
            ->where('reference_type', 'bet');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isSettled(): bool
    {
        return in_array($this->status, ['won', 'lost', 'refunded', 'cancelled', 'rejected'], true);
    }
}
