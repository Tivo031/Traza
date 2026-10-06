<?php

namespace App\Console\Commands;

use App\Models\Proyecto;
use App\Models\Tablero;
use App\Models\UsuarioTablero;
use App\Services\OrganizacionServicio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class VerificarOrganizacion extends Command
{
    protected $signature = 'traza:verificar-organizacion';
    protected $description = 'Consulta rutas y columnas de la fase 2. No crea ni modifica registros.';

    public function handle(): int
    {
        foreach (['proyectos', 'tableros', 'columnas_tablero', 'estados_tarea', 'usuarios_tableros'] as $tabla) {
            if (! Schema::hasTable($tabla)) {
                $this->error('Falta la tabla '.$tabla.'. Revisa la instalacion de la fase 1.');
                return self::FAILURE;
            }
        }
        $rutas = ['proyectos.store', 'proyectos.update', 'proyectos.show', 'tableros.store', 'tableros.show',
            'tableros.update', 'asignaciones.index', 'asignaciones.store', 'asignaciones.update', 'usuarios.update', 'usuarios.estado'];
        $faltantes = array_filter($rutas, fn ($nombre) => ! Route::has($nombre));
        if ($faltantes) {
            $this->error('Faltan rutas: '.implode(', ', $faltantes).'. Revisa la copia y limpia route:clear.');
            return self::FAILURE;
        }
        $incorrectos = [];
        Tablero::with(['columnas' => fn ($columnas) => $columnas->with('estado')->orderBy('posicion')])
            ->chunkById(100, function ($tableros) use (&$incorrectos): void {
                foreach ($tableros as $tablero) {
                    $codigos = $tablero->columnas->map(fn ($columna) => $columna->estado?->codigo)->all();
                    $posiciones = $tablero->columnas->pluck('posicion')->all();
                    if ($codigos !== OrganizacionServicio::ESTADOS || $posiciones !== [0, 1, 2, 3]) {
                        $incorrectos[] = [$tablero->id_tablero, $tablero->nombre];
                    }
                }
            }, 'id_tablero');
        $totalTableros = Tablero::count();
        $this->table(['Comprobacion', 'Valor'], [
            ['Base consultada', config('database.connections.'.config('database.default').'.database')],
            ['Proyectos', Proyecto::count()], ['Tableros', $totalTableros],
            ['Asignaciones activas', UsuarioTablero::where('activo', 1)->count()],
            ['Tableros con columnas incorrectas', count($incorrectos)], ['Rutas de organizacion', 'Disponibles'],
        ]);
        if ($incorrectos) {
            $this->table(['ID tablero', 'Nombre'], $incorrectos);
            $this->error('Hay tableros incompletos. No se hicieron correcciones automaticas.');
            return self::FAILURE;
        }
        if ($totalTableros === 0) {
            $this->warn('Crea el primer proyecto y tablero desde la aplicacion para comprobar sus cuatro columnas.');
        } else {
            $this->info('Cada tablero tiene los cuatro estados esperados, en posiciones 0 a 3.');
        }
        $this->line('Consulta terminada. No acredita las pruebas funcionales ni modifica datos.');
        return self::SUCCESS;
    }
}
