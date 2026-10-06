<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tablero extends ModeloBase
{
    protected $table = 'tableros';
    protected $primaryKey = 'id_tablero';
    protected $fillable = ['id_proyecto', 'id_creador', 'nombre', 'descripcion'];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id_proyecto');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_creador', 'id_usuario');
    }

    public function columnas(): HasMany
    {
        return $this->hasMany(ColumnaTablero::class, 'id_tablero', 'id_tablero');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(UsuarioTablero::class, 'id_tablero', 'id_tablero');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'usuarios_tableros', 'id_tablero', 'id_usuario', 'id_tablero', 'id_usuario')
            ->withPivot(['id_usuario_tablero', 'activo']);
    }

}
