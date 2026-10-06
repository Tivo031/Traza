<?php

namespace App\Support;

/** Reglas del flujo, sin consultas: facilitan probar las decisiones por separado. */
final class ReglasTarea
{
    public const EDITABLES = ['POR_HACER', 'EN_PROGRESO'];

    public static function errorMovimiento(
        string $origen, string $destino, bool $esResponsable, bool $esAdministrador,
        bool $responsableValido, bool $tieneCriterio, bool $hayPendientes, string $observacion
    ): ?string {
        if ($origen === $destino) {
            return in_array($origen, self::EDITABLES, true) ? null
                : 'Solo se pueden ordenar tarjetas en Por hacer o En progreso.';
        }
        if ($origen === 'POR_HACER' && $destino === 'EN_PROGRESO') {
            return $responsableValido ? null : 'Selecciona un responsable activo y asignado al tablero.';
        }
        if ($origen === 'EN_PROGRESO' && $destino === 'EN_REVISION') {
            if (! $esResponsable) return 'Solo el responsable de la tarea puede enviarla a revisión.';
            if (! $responsableValido) return 'El responsable debe conservar su cuenta y asignación activas.';
            if (! $tieneCriterio) return 'Define el criterio de aceptación antes de enviar a revisión.';
            return $hayPendientes ? 'Completa las subtareas y sus elementos antes de enviar a revisión.' : null;
        }
        if ($origen === 'EN_REVISION' && in_array($destino, ['COMPLETADO', 'EN_PROGRESO'], true)) {
            if (! $esAdministrador) return 'Solo un Administrador puede aprobar o rechazar la revisión.';
            if (! $responsableValido) return 'El responsable debe conservar su cuenta y asignación activas.';
            if ($destino === 'EN_PROGRESO') {
                return trim($observacion) !== '' ? null : 'Explica el defecto observado para devolver la tarea a En progreso.';
            }
            if (! $tieneCriterio) return 'No se puede aprobar una tarea sin criterio de aceptación.';
            return $hayPendientes ? 'No se puede aprobar mientras existan subtareas o elementos pendientes.' : null;
        }
        return 'Transición no permitida. Completado es final y no se pueden saltar estados.';
    }
}
