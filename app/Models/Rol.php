<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends ModeloBase
{
    protected $table = 'roles';
    protected $primaryKey = 'id_rol';
    protected $fillable = ['codigo', 'nombre'];

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'id_rol', 'id_rol');
    }

}
