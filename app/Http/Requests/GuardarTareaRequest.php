<?php

namespace App\Http\Requests;

use App\Support\AccesoOrganizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tablero = $this->route('tablero') ?? $this->route('tarea')->columna->tablero;
        AccesoOrganizacion::comprobarTablero($this->user(), $tablero);
        return true;
    }

    public function rules(): array
    {
        $edicion = $this->route('tarea') !== null;
        return [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:10000'],
            'criterio_aceptacion' => ['required', 'string', 'max:2000'],
            'id_prioridad' => ['required', 'integer', Rule::exists('prioridades', 'id_prioridad')],
            'id_tipo_tarea' => ['required', 'integer', Rule::exists('tipos_tarea', 'id_tipo_tarea')],
            'id_categoria' => ['required', 'integer', Rule::exists('categorias', 'id_categoria')],
            'id_responsable' => ['nullable', 'integer', 'min:1'], // Membresia y cuenta se revisan bloqueadas en el servicio.
            'fecha_inicio' => ['nullable', 'date_format:Y-m-d'],
            'fecha_limite' => array_merge(['nullable', 'date_format:Y-m-d'],
                $this->filled('fecha_inicio') ? ['after_or_equal:fecha_inicio'] : []),
            'id_columna_esperada' => [$edicion ? 'required' : 'prohibited', 'integer', 'min:1'],
            'revision_esperada' => [$edicion ? 'required' : 'prohibited', 'integer', 'min:0'],
            'id_tarea' => ['prohibited'], 'id_creador' => ['prohibited'], 'id_actor' => ['prohibited'],
            'id_tablero' => ['prohibited'], 'id_proyecto' => ['prohibited'], 'id_estado' => ['prohibited'],
            'id_columna' => ['prohibited'], 'posicion' => ['prohibited'],
            'fecha_creacion' => ['prohibited'], 'fecha_actualizacion' => ['prohibited'], 'fecha_cierre' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.integer' => 'El valor de :attribute debe ser un entero.',
            '*.exists' => 'Selecciona un valor existente para :attribute.',
            '*.min' => 'El valor de :attribute no es válido.',
            '*.date_format' => 'Escribe :attribute con formato de fecha válido.',
            'fecha_limite.after_or_equal' => 'La fecha límite no puede ser anterior al inicio planificado.',
        ];
    }

    public function attributes(): array
    {
        return ['titulo' => 'título', 'descripcion' => 'descripción',
            'criterio_aceptacion' => 'criterio de aceptación', 'id_prioridad' => 'prioridad',
            'id_tipo_tarea' => 'tipo', 'id_categoria' => 'categoría', 'id_responsable' => 'responsable',
            'fecha_inicio' => 'fecha de inicio', 'fecha_limite' => 'fecha límite',
            'revision_esperada' => 'versión de la tarea', 'id_columna_esperada' => 'columna de origen'];
    }
}
