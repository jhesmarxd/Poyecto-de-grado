<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReservaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>, string>
     */
    public function rules(): array
    {
        return [
            'cancha_id' => ['required', 'integer', 'exists:canchas,id'],
            'horario_id' => ['required', 'integer', 'exists:horarios,id'],
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'balon_id' => ['nullable', 'integer', 'exists:balones,id'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cancha_id.required' => 'Debe seleccionar una cancha.',
            'cancha_id.exists' => 'La cancha seleccionada no existe.',
            'horario_id.required' => 'Debe seleccionar un horario.',
            'horario_id.exists' => 'El horario seleccionado no existe.',
            'fecha.required' => 'Debe seleccionar una fecha.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'fecha.after_or_equal' => 'La fecha no puede ser en el pasado.',
            'balon_id.exists' => 'El balón seleccionado no existe.',
        ];
    }
}
