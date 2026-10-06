<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElementoVerificacion extends ModeloBase
{
    protected $table = 'elementos_verificacion';
    protected $primaryKey = 'id_elemento';
    protected $fillable = ['id_subtarea', 'titulo', 'completado', 'posicion'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), ['completado' => 'boolean', 'posicion' => 'integer']);
    }

    public function subtarea(): BelongsTo
    {
        return $this->belongsTo(Subtarea::class, 'id_subtarea', 'id_subtarea');
    }

}
