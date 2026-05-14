<?php

namespace App\Modules\Betting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualEventResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'sport_event_id' => ['required', 'integer', 'exists:sport_events,id'],
            'home_score' => ['nullable', 'integer', 'min:0'],
            'away_score' => ['nullable', 'integer', 'min:0'],
            'result_type' => ['required', 'string', 'in:home,away,draw,cancelled'],
            'winner_name' => ['nullable', 'string', 'max:255'],
            'observation' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'sport_event_id.required' => 'Debe seleccionar el evento deportivo.',
            'sport_event_id.exists' => 'El evento deportivo seleccionado no existe.',
            'result_type.required' => 'Debe seleccionar el resultado del evento.',
            'result_type.in' => 'El resultado debe ser home, away, draw o cancelled.',
        ];
    }
}
