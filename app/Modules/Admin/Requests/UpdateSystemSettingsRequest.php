<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'odds.default_region' => ['nullable', 'string', 'in:us,uk,eu,au'],
            'odds.default_market' => ['nullable', 'string', 'in:h2h,spreads,totals,outrights'],
            'odds.sync_interval_seconds' => ['nullable', 'integer', 'min:15', 'max:3600'],
        ];
    }

    public function messages(): array
    {
        return [
            'odds.default_region.in' => 'La región debe ser us, uk, eu o au.',
            'odds.default_market.in' => 'El mercado debe ser h2h, spreads, totals u outrights.',
            'odds.sync_interval_seconds.min' => 'El intervalo mínimo permitido es de 15 segundos.',
            'odds.sync_interval_seconds.max' => 'El intervalo máximo permitido es de 3600 segundos.',
        ];
    }
}
