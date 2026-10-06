<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends ModeloBase
{
    protected $table = 'categorias';
    protected $primaryKey = 'id_categoria';
    protected $fillable = ['nombre'];

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'id_categoria', 'id_categoria');
    }

}
