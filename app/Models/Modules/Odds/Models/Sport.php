<?php

namespace App\Modules\Odds\Models;

use App\Modules\Odds\Models\SportEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sport extends Model
{
    protected $fillable = [
        'sport_key',
        'group',
        'title',
        'description',
        'active',
        'has_outrights',
    ];

    protected $casts = [
        'active' => 'boolean',
        'has_outrights' => 'boolean',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(SportEvent::class);
    }
}
