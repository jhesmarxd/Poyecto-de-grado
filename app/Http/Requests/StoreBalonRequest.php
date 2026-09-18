<?php

namespace App\Http\Requests;

use App\Models\Balon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBalonRequest extends FormRequest
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
            'tipo' => ['required', Rule::in(array_keys(Balon::TIPOS))],
            'marca' => ['nullable', 'string', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
            'calidad' => ['nullable', 'string', 'max:255'],
            'precio_por_hora' => ['required', 'numeric', 'min:0', 'max:100000'],
            'descripcion' => ['nullable', 'string'],
            'destacado' => ['sometimes', 'boolean'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
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
            'tipo.required' => 'El tipo de balón es obligatorio.',
            'tipo.in' => 'El tipo debe ser economy o premium.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no debe exceder 255 caracteres.',
            'precio_por_hora.required' => 'El precio por hora es obligatorio.',
            'precio_por_hora.min' => 'El precio no puede ser negativo.',
            'precio_por_hora.max' => 'El precio no debe exceder 100,000.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser jpeg, jpg, png o webp.',
            'imagen.max' => 'La imagen no debe exceder 4MB.',
        ];
    }
}
