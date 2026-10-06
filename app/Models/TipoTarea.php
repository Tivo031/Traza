<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoTarea extends ModeloBase
{
    protected $table = 'tipos_tarea';
    protected $primaryKey = 'id_tipo_tarea';
    protected $fillable = ['codigo', 'nombre'];

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'id_tipo_tarea', 'id_tipo_tarea');
    }

}
