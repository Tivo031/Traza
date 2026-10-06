<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerificarBase extends Command
{
    protected $signature = 'traza:verificar-base';
    protected $description = 'Comprueba tablas, campos, claves y catalogos sin modificar datos.';

    public function handle(): int
    {
        $tablas = ['roles', 'estados_tarea', 'prioridades', 'tipos_tarea', 'categorias', 'usuarios',
            'proyectos', 'tableros', 'usuarios_tableros', 'columnas_tablero', 'tareas', 'subtareas',
            'elementos_verificacion', 'comentarios_tarea', 'actividades_tarea'];
        foreach ($tablas as $tabla) {
            if (! Schema::hasTable($tabla)) {
                $this->error('Falta la tabla '.$tabla.'. Revisa migrate:status.');
                return self::FAILURE;
            }
        }
        $base = DB::connection()->getDatabaseName();
        $campos = DB::table('information_schema.COLUMNS')->where('TABLE_SCHEMA', $base)
            ->whereIn('TABLE_NAME', $tablas)->count();
        $claves = DB::table('information_schema.TABLE_CONSTRAINTS')->where('CONSTRAINT_SCHEMA', $base)
            ->whereIn('TABLE_NAME', $tablas)->get()->groupBy('CONSTRAINT_TYPE');
        $filas = [
            ['Tablas de negocio', count($tablas), 15], ['Campos de negocio', $campos, 104],
            ['Claves primarias', $claves->get('PRIMARY KEY', collect())->count(), 15],
            ['Claves foraneas', $claves->get('FOREIGN KEY', collect())->count(), 22],
            ['Unicidades adicionales', $claves->get('UNIQUE', collect())->count(), 10],
            ['Restricciones CHECK', $claves->get('CHECK', collect())->count(), 6],
        ];
        $this->table(['Verificacion', 'Encontrado', 'Esperado'], $filas);
        foreach ($filas as $fila) {
            if ((int) $fila[1] !== $fila[2]) {
                $this->error('La estructura no coincide con la fase 1. No borres datos para corregirla.');
                return self::FAILURE;
            }
        }
        if (Schema::hasTable('users')) {
            $this->warn('Existe una tabla users ajena al esquema. Traza autentica solamente con usuarios.');
        }
        $this->table(['Catalogo', 'Registros'], collect(['roles', 'estados_tarea', 'prioridades', 'tipos_tarea', 'categorias'])
            ->map(fn ($tabla) => [$tabla, DB::table($tabla)->count()])->all());
        $this->info('Estructura revisada. Esto no sustituye las pruebas funcionales de Auth y de los modulos.');
        return self::SUCCESS;
    }
}
