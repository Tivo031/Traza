<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends ModeloBase
{
    protected $table = 'proyectos';
    protected $primaryKey = 'id_proyecto';
    protected $fillable = ['id_creador', 'nombre', 'descripcion'];

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_creador', 'id_usuario');
    }

    public function tableros(): HasMany
    {
        return $this->hasMany(Tablero::class, 'id_proyecto', 'id_proyecto');
    }

}
