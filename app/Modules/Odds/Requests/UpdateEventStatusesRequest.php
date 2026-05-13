<?php

namespace App\Modules\Odds\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventStatusesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'sport_key' => ['nullable', 'string', 'max:100', 'exists:sports,sport_key'],
        ];
    }
}
