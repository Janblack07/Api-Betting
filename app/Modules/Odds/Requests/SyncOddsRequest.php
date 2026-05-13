<?php

namespace App\Modules\Odds\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncOddsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'sport_key' => ['nullable', 'string', 'max:100', 'exists:sports,sport_key'],
            'event_id' => ['nullable', 'integer', 'exists:sport_events,id'],
            'regions' => ['nullable', 'string', 'max:100'],
            'markets' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
