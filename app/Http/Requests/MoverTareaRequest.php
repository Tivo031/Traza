<?php

namespace App\Http\Requests;

use App\Support\AccesoOrganizacion;
use Illuminate\Foundation\Http\FormRequest;

class MoverTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        AccesoOrganizacion::comprobarTablero($this->user(), $this->route('tarea')->columna->tablero);
        return true;
    }

    public function rules(): array
    {
        return [
            'id_columna_esperada' => ['required', 'integer', 'min:1'],
            'revision_esperada' => ['required', 'integer', 'min:0'],
            'id_columna_destino' => ['required', 'integer', 'min:1'],
            'posicion_destino' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'id_responsable' => ['nullable', 'integer', 'min:1'],
            'observacion' => ['nullable', 'string', 'max:5000'],
            'id_estado' => ['prohibited'], 'id_creador' => ['prohibited'], 'id_actor' => ['prohibited'],
            'id_columna' => ['prohibited'], 'fecha_cierre' => ['prohibited'],
            'fecha_creacion' => ['prohibited'], 'fecha_actualizacion' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return ['*.integer' => 'El valor de :attribute debe ser un entero.',
            '*.min' => 'El valor de :attribute está fuera del intervalo permitido.',
            '*.max' => 'El valor de :attribute supera el límite permitido.'];
    }

    public function attributes(): array
    {
        return ['observacion' => 'observación', 'id_responsable' => 'responsable',
            'revision_esperada' => 'versión de la tarea', 'id_columna_esperada' => 'columna de origen',
            'id_columna_destino' => 'columna de destino', 'posicion_destino' => 'posición de destino'];
    }
}
