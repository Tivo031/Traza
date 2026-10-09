<?php

namespace App\Http\Requests;

class EstadoElementoRequest extends CambioSeguimientoRequest
{
    public function rules(): array
    {
        return array_merge($this->comunes(), ['completado' => ['required', 'boolean']]);
    }
}
