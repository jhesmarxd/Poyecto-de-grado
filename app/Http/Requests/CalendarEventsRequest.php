<?php

namespace App\Http\Requests;

use App\Models\Reserva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CalendarEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cancha_id' => $this->input('cancha_id') ?: null,
            'estado' => $this->input('estado') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
            'cancha_id' => ['nullable', 'integer', 'exists:canchas,id'],
            'estado' => ['nullable', Rule::in(array_keys(Reserva::ESTADOS))],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['start', 'end'])) {
                    return;
                }

                $timezone = (string) config('app.timezone', 'America/La_Paz');
                $start = Carbon::parse((string) $this->input('start'), $timezone)->startOfDay();
                $end = Carbon::parse((string) $this->input('end'), $timezone)->startOfDay();

                if ($start->diffInDays($end) > 370) {
                    $validator->errors()->add('end', 'El calendario permite consultar como máximo 370 días por solicitud.');
                }
            },
        ];
    }
}
