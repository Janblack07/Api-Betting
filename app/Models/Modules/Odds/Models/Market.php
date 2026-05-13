<?php

namespace App\Modules\Odds\Models;

use App\Modules\Odds\Models\OddsSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    protected $fillable = [
        'market_key',
        'name',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function oddsSnapshots(): HasMany
    {
        return $this->hasMany(OddsSnapshot::class);
    }
}
