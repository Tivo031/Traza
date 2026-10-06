<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Prioridad extends ModeloBase
{
    protected $table = 'prioridades';
    protected $primaryKey = 'id_prioridad';
    protected $fillable = ['codigo', 'nombre'];

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'id_prioridad', 'id_prioridad');
    }

}
