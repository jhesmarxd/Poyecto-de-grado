<?php

namespace App\Http\Requests;

use App\Models\Cancha;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCanchaRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'precio_por_hora' => ['required', 'numeric', 'min:0', 'max:100000'],
            'estado' => ['required', Rule::in(array_keys(Cancha::ESTADOS))],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'color_malla' => ['nullable', 'string', 'max:255'],
            'altura_malla' => ['nullable', 'string', 'max:255'],
            'tipo_piso' => ['nullable', 'string', 'max:255'],
            'tipo_recinto' => ['required', Rule::in(['cerrada', 'abierta'])],
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
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe exceder 255 caracteres.',
            'precio_por_hora.required' => 'El precio por hora es obligatorio.',
            'precio_por_hora.min' => 'El precio no puede ser negativo.',
            'precio_por_hora.max' => 'El precio no debe exceder 100,000.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser activa o inactiva.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser jpeg, jpg, png o webp.',
            'imagen.max' => 'La imagen no debe exceder 4 MB.',
            'tipo_recinto.required' => 'El tipo de recinto es obligatorio.',
            'tipo_recinto.in' => 'El tipo de recinto debe ser cerrada o abierta.',
        ];
    }
}
