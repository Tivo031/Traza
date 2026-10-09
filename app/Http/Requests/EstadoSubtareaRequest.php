<?php

namespace App\Http\Requests;

class EstadoSubtareaRequest extends CambioSeguimientoRequest
{
    public function rules(): array
    {
        return array_merge($this->comunes(), ['completada' => ['required', 'boolean']]);
    }
}
