<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoTarea extends ModeloBase
{
    protected $table = 'estados_tarea';
    protected $primaryKey = 'id_estado';
    protected $fillable = ['codigo', 'nombre'];

    public function columnas(): HasMany
    {
        return $this->hasMany(ColumnaTablero::class, 'id_estado', 'id_estado');
    }

    public function actividadesOrigen(): HasMany
    {
        return $this->hasMany(ActividadTarea::class, 'id_estado_anterior', 'id_estado');
    }

    public function actividadesDestino(): HasMany
    {
        return $this->hasMany(ActividadTarea::class, 'id_estado_nuevo', 'id_estado');
    }

}
