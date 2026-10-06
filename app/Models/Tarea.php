<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarea extends ModeloBase
{
    protected $table = 'tareas';
    protected $primaryKey = 'id_tarea';
    protected $fillable = ['id_columna', 'id_prioridad', 'id_tipo_tarea', 'id_categoria', 'id_creador', 'id_responsable', 'titulo', 'descripcion', 'criterio_aceptacion', 'posicion', 'fecha_inicio', 'fecha_limite', 'fecha_cierre'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['posicion' => 'integer', 'fecha_inicio' => 'date', 'fecha_limite' => 'date', 'fecha_cierre' => 'datetime']);
    }

    public function columna(): BelongsTo
    {
        return $this->belongsTo(ColumnaTablero::class, 'id_columna', 'id_columna');
    }

    public function prioridad(): BelongsTo
    {
        return $this->belongsTo(Prioridad::class, 'id_prioridad', 'id_prioridad');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoTarea::class, 'id_tipo_tarea', 'id_tipo_tarea');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_creador', 'id_usuario');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_responsable', 'id_usuario');
    }

    public function subtareas(): HasMany
    {
        return $this->hasMany(Subtarea::class, 'id_tarea', 'id_tarea');
    }

    public function comentarios(): HasMany
    {
        return $this->hasMany(ComentarioTarea::class, 'id_tarea', 'id_tarea');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(ActividadTarea::class, 'id_tarea', 'id_tarea');
    }

}
