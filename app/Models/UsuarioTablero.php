<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioTablero extends ModeloBase
{
    protected $table = 'usuarios_tableros';
    protected $primaryKey = 'id_usuario_tablero';
    protected $fillable = ['id_tablero', 'id_usuario', 'activo'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['activo' => 'boolean']);
    }

    public function tablero(): BelongsTo
    {
        return $this->belongsTo(Tablero::class, 'id_tablero', 'id_tablero');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

}
