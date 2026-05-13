<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class ApiUsageLog extends Model
{
    protected $fillable = [
        'provider',
        'endpoint',
        'sport_key',
        'regions',
        'markets',
        'credits_used',
        'requests_used',
        'requests_remaining',
        'response_status',
        'requested_at',
    ];

    protected $casts = [
        'credits_used' => 'integer',
        'requests_used' => 'integer',
        'requests_remaining' => 'integer',
        'response_status' => 'integer',
        'requested_at' => 'datetime',
    ];
}
