<?php

namespace App\Console\Commands;

use App\Models\{ComentarioTarea, ElementoVerificacion, Subtarea};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Route, Schema};

class VerificarSeguimiento extends Command
{
    protected $signature = 'traza:verificar-seguimiento';
    protected $description = 'Consulta subtareas, elementos y comentarios; no modifica registros.';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('Selecciona la conexion mysql.'); return self::FAILURE;
        }
        foreach (['tareas', 'subtareas', 'elementos_verificacion', 'comentarios_tarea', 'actividades_tarea'] as $tabla) {
            if (! Schema::hasTable($tabla)) { $this->error('Falta la tabla '.$tabla.'.'); return self::FAILURE; }
        }
        $rutas = collect(['subtareas.store', 'subtareas.update', 'subtareas.estado',
            'elementos.store', 'elementos.update', 'elementos.estado', 'comentarios.store', 'panel.show'])
            ->every(fn ($nombre) => Route::has($nombre));
        $incidencias = [
            'Subtareas finalizadas con elementos pendientes' => Subtarea::whereNotNull('fecha_finalizacion')
                ->whereHas('elementos', fn ($q) => $q->where('completado', 0))->count(),
            'Finalizacion anterior a creacion' => Subtarea::whereNotNull('fecha_finalizacion')
                ->whereColumn('fecha_finalizacion', '<', 'fecha_creacion')->count(),
            'Elementos fuera de 0/1' => ElementoVerificacion::whereNotIn('completado', [0, 1])->count(),
            'Comentarios vacios' => ComentarioTarea::whereRaw('TRIM(contenido) = ?', [''])->count(),
        ];
        $this->table(['Comprobacion', 'Valor'], [
            ['Base consultada', DB::connection()->getDatabaseName()],
            ['Subtareas', Subtarea::count()],
            ['Subtareas completadas', Subtarea::whereNotNull('fecha_finalizacion')->count()],
            ['Elementos de verificacion', ElementoVerificacion::count()],
            ['Elementos marcados', ElementoVerificacion::where('completado', 1)->count()],
            ['Comentarios', ComentarioTarea::count()],
            ['Rutas de seguimiento y panel', $rutas ? 'Disponibles' : 'Incompletas'],
        ]);
        $this->table(['Incidencia', 'Cantidad'], collect($incidencias)->map(fn ($n, $k) => [$k, $n])->values()->all());
        if (Subtarea::count() === 0) $this->warn('Sin subtareas: todavia falta probar su creacion y finalizacion.');
        $this->line('Consulta sin escrituras. No acredita permisos, vistas, concurrencia ni pruebas funcionales completas.');
        return $rutas && array_sum($incidencias) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
