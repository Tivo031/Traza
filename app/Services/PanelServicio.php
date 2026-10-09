<?php

namespace App\Services;

use App\Models\{Proyecto, Usuario};
use App\Support\AccesoOrganizacion;
use Illuminate\Support\Facades\DB;

class PanelServicio
{
    public function consultar(Proyecto $proyecto, Usuario $usuario): array
    {
        AccesoOrganizacion::comprobarProyecto($usuario, $proyecto);
        $hoy = now('America/Guatemala')->toDateString();
        $tableros = AccesoOrganizacion::tablerosVisibles($usuario)->where('id_proyecto', $proyecto->id_proyecto)
            ->orderBy('nombre')->orderBy('id_tablero')->get();
        // Solo unimos relaciones muchos-a-uno: comentarios y subtareas no multiplican los conteos.
        $grupos = DB::table('tareas as t')
            ->join('columnas_tablero as c', 'c.id_columna', '=', 't.id_columna')
            ->join('estados_tarea as e', 'e.id_estado', '=', 'c.id_estado')
            ->whereIn('c.id_tablero', $tableros->modelKeys())
            ->select('c.id_tablero')->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN e.codigo = 'COMPLETADO' THEN 1 ELSE 0 END) AS completadas")
            ->selectRaw("SUM(CASE WHEN e.codigo = 'EN_PROGRESO' THEN 1 ELSE 0 END) AS en_progreso")
            ->selectRaw("SUM(CASE WHEN t.fecha_limite < ? AND e.codigo <> 'COMPLETADO' THEN 1 ELSE 0 END) AS vencidas", [$hoy])
            ->groupBy('c.id_tablero')->get()->keyBy('id_tablero');
        $resumen = ['total' => 0, 'completadas' => 0, 'en_progreso' => 0, 'vencidas' => 0];
        foreach (array_keys($resumen) as $campo) $resumen[$campo] = (int) $grupos->sum($campo);
        return compact('resumen', 'tableros', 'grupos', 'hoy');
    }
}
