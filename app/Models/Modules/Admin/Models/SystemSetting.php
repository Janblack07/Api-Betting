<?php

namespace App\Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_value',
        'type',
        'description',
    ];

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->setting_value,
            'decimal' => (float) $this->setting_value,
            'boolean' => filter_var($this->setting_value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }
}
