<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CatalogosSeeder::class);
        // El administrador se crea aparte, con preguntas privadas, nunca con clave fija.
    }
}
