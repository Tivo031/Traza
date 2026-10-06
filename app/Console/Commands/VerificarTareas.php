<?php

namespace App\Console\Commands;

use App\Models\{Tarea, UsuarioTablero};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Route, Schema};

class VerificarTareas extends Command
{
    protected $signature = 'traza:verificar-tareas';
    protected $description = 'Consulta tareas y coherencia del flujo, sin modificar registros.';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('Selecciona la conexion mysql.'); return self::FAILURE;
        }
        foreach (['tareas', 'actividades_tarea', 'columnas_tablero', 'usuarios_tableros'] as $tabla) {
            if (! Schema::hasTable($tabla)) { $this->error('Falta la tabla '.$tabla.'. Revisa la fase 1.'); return self::FAILURE; }
        }
        $conteos = ['POR_HACER' => 0, 'EN_PROGRESO' => 0, 'EN_REVISION' => 0, 'COMPLETADO' => 0];
        $fallos = ['Sin evento de creacion' => 0, 'Responsabilidad incoherente' => 0,
            'Responsable abierto sin acceso activo' => 0, 'Cierre incompatible con estado' => 0,
            'Revision/cierre con subtareas pendientes' => 0];
        Tarea::with(['columna.estado', 'responsable'])
            ->withCount(['actividades as creaciones_count' => fn ($q) => $q->where('codigo_accion', 'CREACION_TAREA')])
            ->chunkById(200, function ($tareas) use (&$conteos, &$fallos): void {
                foreach ($tareas as $tarea) {
                    $codigo = $tarea->columna->estado->codigo;
                    $conteos[$codigo] = ($conteos[$codigo] ?? 0) + 1;
                    if ((int) $tarea->creaciones_count !== 1) $fallos['Sin evento de creacion']++;
                    if (($codigo !== 'POR_HACER' && $tarea->id_responsable === null)
                        || ($codigo === 'POR_HACER' && $tarea->id_responsable !== null)) $fallos['Responsabilidad incoherente']++;
                    if ($codigo !== 'COMPLETADO' && $tarea->id_responsable !== null) {
                        $asignado = UsuarioTablero::where('id_tablero', $tarea->columna->id_tablero)
                            ->where('id_usuario', $tarea->id_responsable)->where('activo', 1)->exists();
                        if (! $tarea->responsable?->activo || ! $asignado) $fallos['Responsable abierto sin acceso activo']++;
                    }
                    if (($codigo === 'COMPLETADO') !== ($tarea->fecha_cierre !== null)) $fallos['Cierre incompatible con estado']++;
                    if (in_array($codigo, ['EN_REVISION', 'COMPLETADO'], true) && $tarea->subtareas()->where(function ($q): void {
                        $q->whereNull('fecha_finalizacion')->orWhereHas('elementos', fn ($e) => $e->where('completado', 0));
                    })->exists()) $fallos['Revision/cierre con subtareas pendientes']++;
                }
            }, 'id_tarea');
        $fallos['Actividad con estados incoherentes'] = DB::table('actividades_tarea')->where(function ($q): void {
            $q->where(function ($q): void {
                $q->where('codigo_accion', 'CAMBIO_ESTADO')->where(function ($q): void {
                    $q->whereNull('id_estado_anterior')->orWhereNull('id_estado_nuevo')
                        ->orWhereColumn('id_estado_anterior', 'id_estado_nuevo');
                });
            })->orWhere(function ($q): void {
                $q->where('codigo_accion', '!=', 'CAMBIO_ESTADO')->where(function ($q): void {
                    $q->whereNotNull('id_estado_anterior')->orWhereNotNull('id_estado_nuevo');
                });
            });
        })->count();
        $rutas = collect(['tareas.create', 'tareas.store', 'tareas.show', 'tareas.edit', 'tareas.update', 'tareas.mover'])
            ->every(fn ($nombre) => Route::has($nombre));
        $this->table(['Comprobacion', 'Valor'], [
            ['Base consultada', DB::connection()->getDatabaseName()], ['Tareas', array_sum($conteos)],
            ['Actividades', DB::table('actividades_tarea')->count()], ['Rutas de tareas', $rutas ? 'Disponibles' : 'Incompletas'],
        ]);
        $this->table(['Estado', 'Tareas'], collect($conteos)->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->table(['Incidencia', 'Cantidad'], collect($fallos)->map(fn ($n, $k) => [$k, $n])->values()->all());
        if (array_sum($conteos) === 0) $this->warn('Todavia no hay tareas: crea una y prueba su recorrido antes de dar por validado el modulo.');
        $this->line('Consulta sin escrituras. No acredita permisos, arrastre, concurrencia ni pruebas funcionales completas.');
        return $rutas && array_sum($fallos) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
