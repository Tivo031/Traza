<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ColumnaTablero extends ModeloBase
{
    protected $table = 'columnas_tablero';
    protected $primaryKey = 'id_columna';
    protected $fillable = ['id_tablero', 'id_estado', 'posicion'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['posicion' => 'integer']);
    }

    public function tablero(): BelongsTo
    {
        return $this->belongsTo(Tablero::class, 'id_tablero', 'id_tablero');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoTarea::class, 'id_estado', 'id_estado');
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'id_columna', 'id_columna');
    }

}
