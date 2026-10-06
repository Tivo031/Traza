<?php

namespace App\Support;

use App\Models\Proyecto;
use App\Models\Tablero;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;

class AccesoOrganizacion
{
    // Devuelve una consulta, no una lista: cada pantalla agrega su orden o filtro.
    public static function tablerosVisibles(Usuario $usuario): Builder
    {
        $consulta = Tablero::query();
        if (! $usuario->activo) {
            return $consulta->whereRaw('1 = 0');
        }
        if (! $usuario->esAdministrador()) {
            $consulta->whereHas('asignaciones', function (Builder $asignaciones) use ($usuario): void {
                $asignaciones->where('id_usuario', $usuario->id_usuario)->where('activo', 1);
            });
        }
        return $consulta;
    }

    public static function comprobarProyecto(Usuario $usuario, Proyecto $proyecto): void
    {
        abort_unless($usuario->activo && ($usuario->esAdministrador()
            || self::tablerosVisibles($usuario)->where('id_proyecto', $proyecto->id_proyecto)->exists()), 404);
    }

    public static function comprobarTablero(Usuario $usuario, Tablero $tablero): void
    {
        abort_unless(self::tablerosVisibles($usuario)->whereKey($tablero->id_tablero)->exists(), 404);
    }
}
