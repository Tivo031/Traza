<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComentarioTarea extends ModeloBase
{
    protected $table = 'comentarios_tarea';
    protected $primaryKey = 'id_comentario';
    protected $fillable = ['id_tarea', 'id_usuario', 'contenido'];

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class, 'id_tarea', 'id_tarea');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

}
