<?php

namespace App\Http\Requests;

use App\Rules\SinSuperposicionHorario;
use Illuminate\Foundation\Http\FormRequest;

class StoreHorarioRequest extends FormRequest
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
        $cancha = $this->route('cancha');

        return [
            'dia_semana' => ['required', 'integer', 'min:1', 'max:7'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => [
                'required',
                'date_format:H:i',
                'after:hora_inicio',
                new SinSuperposicionHorario(
                    $cancha->id,
                    $this->integer('dia_semana'),
                    $this->input('hora_inicio'),
                    $this->input('hora_fin'),
                ),
            ],
            'disponible' => ['sometimes', 'boolean'],
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
            'dia_semana.required' => 'El día de la semana es obligatorio.',
            'dia_semana.min' => 'El día debe estar entre 1 (lunes) y 7 (domingo).',
            'dia_semana.max' => 'El día debe estar entre 1 (lunes) y 7 (domingo).',
            'hora_inicio.required' => 'La hora de inicio es obligatoria.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener formato HH:mm.',
            'hora_fin.required' => 'La hora de fin es obligatoria.',
            'hora_fin.date_format' => 'La hora de fin debe tener formato HH:mm.',
            'hora_fin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
