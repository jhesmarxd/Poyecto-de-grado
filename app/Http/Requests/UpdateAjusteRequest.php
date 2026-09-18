<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAjusteRequest extends FormRequest
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
            'color_primario' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_acento' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_oscuro' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'horario_atencion_dia_inicio' => ['required', 'integer', 'between:1,7'],
            'horario_atencion_dia_fin' => ['required', 'integer', 'between:1,7', 'gte:horario_atencion_dia_inicio'],
            'horario_atencion_hora_inicio' => ['required', 'date_format:H:i'],
            'horario_atencion_hora_fin' => ['required', 'date_format:H:i', 'after:horario_atencion_hora_inicio'],
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
            'color_primario.required' => 'El color primario es obligatorio.',
            'color_primario.regex' => 'El color primario debe ser un código hexadecimal válido (ej: #198754).',
            'color_acento.required' => 'El color de acento es obligatorio.',
            'color_acento.regex' => 'El color de acento debe ser un código hexadecimal válido.',
            'color_oscuro.required' => 'El color oscuro es obligatorio.',
            'color_oscuro.regex' => 'El color oscuro debe ser un código hexadecimal válido.',
            'horario_atencion_dia_inicio.required' => 'El día inicial es obligatorio.',
            'horario_atencion_dia_inicio.between' => 'El día inicial no es válido.',
            'horario_atencion_dia_fin.required' => 'El día final es obligatorio.',
            'horario_atencion_dia_fin.between' => 'El día final no es válido.',
            'horario_atencion_dia_fin.gte' => 'El día final debe ser igual o posterior al día inicial.',
            'horario_atencion_hora_inicio.required' => 'La hora de apertura es obligatoria.',
            'horario_atencion_hora_inicio.date_format' => 'La hora de apertura debe tener formato HH:mm.',
            'horario_atencion_hora_fin.required' => 'La hora de cierre es obligatoria.',
            'horario_atencion_hora_fin.date_format' => 'La hora de cierre debe tener formato HH:mm.',
            'horario_atencion_hora_fin.after' => 'La hora de cierre debe ser posterior a la hora de apertura.',
        ];
    }
}
