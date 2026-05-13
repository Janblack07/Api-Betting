<?php

namespace App\Modules\Odds\Models;

use App\Modules\Odds\Models\OddsSnapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bookmaker extends Model
{
    protected $fillable = [
        'bookmaker_key',
        'title',
        'region',
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
