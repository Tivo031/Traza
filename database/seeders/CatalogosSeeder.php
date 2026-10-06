<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $catalogos = [
            'roles' => ['ADMINISTRADOR' => 'Administrador', 'MIEMBRO' => 'Miembro'],
            'estados_tarea' => ['POR_HACER' => 'Por hacer', 'EN_PROGRESO' => 'En progreso',
                'EN_REVISION' => 'En revisión / QA', 'COMPLETADO' => 'Completado'],
            'prioridades' => ['ALTA' => 'Alta', 'MEDIA' => 'Media', 'BAJA' => 'Baja'],
            'tipos_tarea' => ['TAREA' => 'Tarea', 'DEFECTO' => 'Defecto (bug)'],
        ];
        DB::transaction(function () use ($catalogos): void {
            foreach ($catalogos as $tabla => $valores) {
                foreach ($valores as $codigo => $nombre) {
                    DB::table($tabla)->updateOrInsert(['codigo' => $codigo], ['nombre' => $nombre]);
                }
            }
            DB::table('categorias')->updateOrInsert(['nombre' => 'General'], ['nombre' => 'General']);
        });
    }
}
