<?php

namespace App\Http\Requests;

class GuardarComentarioRequest extends CambioSeguimientoRequest
{
    public function rules(): array
    {
        return array_merge($this->comunes(), ['contenido' => ['required', 'string', 'max:5000']]);
    }
}
