<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use App\Support\ValidacionAcceso;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdministradorSeeder extends Seeder
{
    public function run(): void
    {
        $rol = Rol::where('codigo', 'ADMINISTRADOR')->firstOrFail();
        if (Usuario::where('id_rol', $rol->id_rol)->exists()) {
            $this->command?->warn('Ya existe un Administrador. No se crea otro ni se cambia su clave.');
            return;
        }
        if (! $this->command || $this->command->option('no-interaction')) {
            throw new \RuntimeException('Ejecuta AdministradorSeeder en una terminal interactiva.');
        }
        $datos = [
            'nombre' => trim((string) $this->command->ask('Nombre del Administrador')),
            'correo' => mb_strtolower(trim((string) $this->command->ask('Correo del Administrador'))),
            'contrasena' => (string) $this->command->secret('Contrasena: minimo 12 caracteres, letra y numero', false),
            'confirmacion_contrasena' => (string) $this->command->secret('Confirma la contrasena', false),
        ];
        $validador = Validator::make($datos, [
            'nombre' => ['required', 'string', 'max:150'],
            'correo' => ['required', 'email', 'max:254', 'unique:usuarios,correo'],
            'contrasena' => ValidacionAcceso::reglasContrasena(),
            'confirmacion_contrasena' => ['required', 'same:contrasena'],
        ]);
        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $error) {
                $this->command->error($error);
            }
            throw new \RuntimeException('No se creo el Administrador. Corrige los datos y ejecuta de nuevo el seeder.');
        }
        $usuario = new Usuario();
        $usuario->forceFill([
            'id_rol' => $rol->id_rol, 'nombre' => $datos['nombre'], 'correo' => $datos['correo'],
            'contrasena' => Hash::make($datos['contrasena']), 'activo' => 1,
        ])->save();
        $this->command->info('Administrador creado. Inicia sesion con tus datos; la clave no se mostrara.');
    }
}
