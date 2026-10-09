<?php

namespace App\Http\Requests;

class GuardarSubtareaRequest extends CambioSeguimientoRequest
{
    public function rules(): array
    {
        return array_merge($this->comunes(), [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:10000'],
            'posicion' => [$this->route('subtarea') ? 'required' : 'prohibited', 'integer', 'min:0', 'max:1000000'],
            'completada' => ['prohibited'], 'completado' => ['prohibited'],
        ]);
    }
}
