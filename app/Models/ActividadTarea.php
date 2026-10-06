<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActividadTarea extends ModeloBase
{
    protected $table = 'actividades_tarea';
    protected $primaryKey = 'id_actividad';
    protected $fillable = ['id_tarea', 'id_actor', 'codigo_accion', 'id_estado_anterior', 'id_estado_nuevo', 'observacion'];

    protected function casts(): array
    {
        return ['fecha_creacion' => 'datetime'];
    }

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'id_tarea', 'id_tarea');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_actor', 'id_usuario');
    }

    public function estadoAnterior(): BelongsTo
    {
        return $this->belongsTo(EstadoTarea::class, 'id_estado_anterior', 'id_estado');
    }

    public function estadoNuevo(): BelongsTo
    {
        return $this->belongsTo(EstadoTarea::class, 'id_estado_nuevo', 'id_estado');
    }

    protected static function booted(): void
    {
        // Defensa adicional a nivel Eloquent; SQL directo requiere permisos propios.
        static::updating(fn () => throw new \LogicException('La actividad no se edita.'));
        static::deleting(fn () => throw new \LogicException('La actividad no se elimina.'));
    }

}
