<?php

namespace App\Support;

use App\Models\Tarea;

final class VistaTarea
{
    public static function codigo(Tarea $tarea): string
    {
        return $tarea->columna->estado->codigo;
    }

    public static function editable(Tarea $tarea): bool
    {
        return in_array(self::codigo($tarea), ReglasTarea::EDITABLES, true);
    }

    public static function vencida(Tarea $tarea): bool
    {
        return $tarea->fecha_limite !== null && self::codigo($tarea) !== 'COMPLETADO'
            && $tarea->fecha_limite->format('Y-m-d') < now('America/Guatemala')->toDateString();
    }

    public static function porcentaje(Tarea $tarea): ?int
    {
        $total = (int) ($tarea->subtareas_count ?? $tarea->subtareas()->count());
        $terminadas = (int) ($tarea->subtareas_completadas_count
            ?? $tarea->subtareas()->whereNotNull('fecha_finalizacion')->count());
        return $total === 0 ? null : (int) round($terminadas / $total * 100);
    }

    public static function revision(Tarea $tarea): int
    {
        // La ultima actividad es un token de version, no un campo nuevo del esquema.
        return (int) ($tarea->actividades_max_id_actividad ?? $tarea->actividades()->max('id_actividad') ?? 0);
    }

    public static function prioridad(string $codigo): string
    {
        return ['ALTA' => 'prioridad-1', 'MEDIA' => 'prioridad-2', 'BAJA' => 'prioridad-3'][$codigo] ?? 'categoria-1';
    }
}
