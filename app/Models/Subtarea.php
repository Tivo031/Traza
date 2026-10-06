<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subtarea extends ModeloBase
{
    protected $table = 'subtareas';
    protected $primaryKey = 'id_subtarea';
    protected $fillable = ['id_tarea', 'titulo', 'descripcion', 'posicion', 'fecha_finalizacion'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['posicion' => 'integer', 'fecha_finalizacion' => 'datetime']);
    }

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'id_tarea', 'id_tarea');
    }

    public function elementos(): HasMany
    {
        return $this->hasMany(ElementoVerificacion::class, 'id_subtarea', 'id_subtarea');
    }

}
