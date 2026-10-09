<?php

namespace App\Http\Requests;

use App\Support\AccesoOrganizacion;
use Illuminate\Foundation\Http\FormRequest;

abstract class CambioSeguimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        AccesoOrganizacion::comprobarTablero($this->user(), $this->route('tarea')->columna->tablero);
        return true;
    }

    protected function comunes(): array
    {
        return [
            'id_columna_esperada' => ['required', 'integer', 'min:1'],
            'revision_esperada' => ['required', 'integer', 'min:0'],
            'formulario' => ['nullable', 'string', 'max:100'],
            'id_actor' => ['prohibited'], 'id_usuario' => ['prohibited'], 'id_creador' => ['prohibited'],
            'id_tarea' => ['prohibited'], 'id_subtarea' => ['prohibited'], 'id_elemento' => ['prohibited'],
            'id_columna' => ['prohibited'], 'id_tablero' => ['prohibited'], 'id_proyecto' => ['prohibited'],
            'fecha_creacion' => ['prohibited'], 'fecha_actualizacion' => ['prohibited'],
            'fecha_cierre' => ['prohibited'], 'fecha_finalizacion' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return ['titulo' => 'título', 'descripcion' => 'descripción', 'posicion' => 'posición',
            'contenido' => 'comentario', 'completado' => 'estado del elemento',
            'completada' => 'estado de la subtarea', 'revision_esperada' => 'versión de la tarea',
            'id_columna_esperada' => 'columna de origen'];
    }
}
