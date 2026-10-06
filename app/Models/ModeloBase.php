<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ModeloBase extends Model
{
    // MySQL administra las dos fechas comunes del documento.
    public $timestamps = false;

    protected function casts(): array
    {
        return ['fecha_creacion' => 'datetime', 'fecha_actualizacion' => 'datetime'];
    }
}
