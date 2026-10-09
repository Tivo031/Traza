<?php

namespace App\Http\Requests;

class GuardarElementoRequest extends CambioSeguimientoRequest
{
    public function rules(): array
    {
        return array_merge($this->comunes(), [
            'titulo' => ['required', 'string', 'max:150'],
            'posicion' => [$this->route('elemento') ? 'required' : 'prohibited', 'integer', 'min:0', 'max:1000000'],
            'completado' => ['prohibited'],
        ]);
    }
}
