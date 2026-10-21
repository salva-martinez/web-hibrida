<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de creación y edición de planes.
 *
 * Se centralizan aquí porque antes la creación y la edición usaban reglas
 * distintas para los mismos campos: el mismo plan era válido al editarlo e
 * inválido al crearlo (repeticiones con rangos, intensidad 0, kg 0). Con un
 * único FormRequest las dos rutas comparten exactamente el mismo contrato.
 */
class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isFisio() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'paciente_id' => [
                'required',
                Rule::exists('users', 'id')->where('role', 'paciente'),
            ],
            'titulo' => 'required|string|max:255',
            'fecha' => 'required|date',
            'activo' => 'boolean',

            'dias' => 'required|array|min:1',
            'dias.*.notas_fisio' => 'nullable|string|max:2000',
            'dias.*.ejercicios' => 'required|array|min:1',
            'dias.*.ejercicios.*.ejercicio_id' => [
                'required',
                Rule::exists('ejercicios', 'id'),
            ],
            'dias.*.ejercicios.*.series' => 'required|integer|min:1|max:350',
            // "8-10" es un rango válido: la columna es de tipo string a propósito.
            'dias.*.ejercicios.*.repeticiones' => 'required|string|max:20',
            'dias.*.ejercicios.*.intensidad' => 'required|integer|min:0|max:10',
            'dias.*.ejercicios.*.kg' => 'nullable|numeric|min:0|max:350',
            'dias.*.ejercicios.*.descanso' => 'nullable|string|max:20',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paciente_id.exists' => 'El paciente seleccionado no existe.',
            'dias.*.ejercicios.*.ejercicio_id.exists' => 'Uno de los ejercicios no existe.',
            'dias.*.ejercicios.*.repeticiones.required' => 'Indica las repeticiones de cada ejercicio.',
            'dias.*.ejercicios.*.intensidad.min' => 'La intensidad mínima es 0.',
            'dias.*.ejercicios.*.intensidad.max' => 'La intensidad máxima es 10.',
            'dias.*.ejercicios.*.descanso.max' => 'El descanso no puede superar los 20 caracteres.',
        ];
    }

    /**
     * El plan solo puede asignarse a un paciente de esta cartera.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('paciente_id')) {
                return;
            }

            $paciente = User::find($this->input('paciente_id'));

            if (! $paciente || ! $paciente->isPaciente() || ! $paciente->perteneceAlFisioAutenticado()) {
                $validator->errors()->add('paciente_id', 'Ese paciente no pertenece a tu cartera.');
            }
        });
    }
}
