<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use App\Notifications\RecuperacionContrasena;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\Soporte\CasoTraza;

class AutenticacionTrazaTest extends CasoTraza
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
    }

    private function usuario(string $rol = 'MIEMBRO', bool $activo = true): Usuario
    {
        $usuario = new Usuario();
        $usuario->forceFill([
            'nombre' => 'Persona de prueba', 'correo' => Str::lower(Str::random(14)).'@example.test',
            'contrasena' => Hash::make('ClavePruebas123'),
            'id_rol' => Rol::where('codigo', $rol)->firstOrFail()->id_rol, 'activo' => $activo,
        ])->save();
        return $usuario;
    }

    public function test_catalogos_repetibles(): void
    {
        $this->seed(CatalogosSeeder::class);
        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseCount('estados_tarea', 4);
        $this->assertDatabaseCount('prioridades', 3);
        $this->assertDatabaseCount('tipos_tarea', 2);
        $this->assertDatabaseCount('categorias', 1);
    }

    public function test_registro_crea_miembro_y_normaliza_correo(): void
    {
        $this->post('/register', [
            'nombre' => 'Persona registrada', 'correo' => 'REGISTRO@EXAMPLE.TEST',
            'contrasena' => 'ClavePruebas123', 'confirmacion_contrasena' => 'ClavePruebas123',
        ])->assertRedirect('/login');
        $usuario = Usuario::where('correo', 'registro@example.test')->firstOrFail();
        $this->assertSame('MIEMBRO', $usuario->rol->codigo);
        $this->assertTrue(Hash::check('ClavePruebas123', $usuario->contrasena));
        $this->assertFalse($usuario->asignaciones()->exists());
        $this->assertGuest();
    }

    public function test_registro_rechaza_rol_inyectado(): void
    {
        $this->post('/register', [
            'nombre' => 'Prueba', 'correo' => 'rol@example.test', 'id_rol' => Rol::where('codigo','ADMINISTRADOR')->first()->id_rol,
            'contrasena' => 'ClavePruebas123', 'confirmacion_contrasena' => 'ClavePruebas123',
        ])->assertSessionHasErrors('id_rol');
        $this->assertDatabaseMissing('usuarios', ['correo' => 'rol@example.test']);
    }

    public function test_error_no_guarda_contrasena_en_la_sesion(): void
    {
        $this->from('/register')->post('/register', [
            'nombre' => 'Prueba', 'correo' => 'invalido',
            'contrasena' => 'ClavePruebas123', 'confirmacion_contrasena' => 'Distinta123456',
        ])->assertSessionHasErrors(['correo', 'confirmacion_contrasena'])
            ->assertSessionMissing('_old_input.contrasena')->assertSessionMissing('_old_input.confirmacion_contrasena');
    }

    public function test_contrasena_de_11_caracteres_se_rechaza(): void
    {
        $this->post('/register', ['nombre' => 'Prueba', 'correo' => 'corta@example.test',
            'contrasena' => 'Abcdefghi12', 'confirmacion_contrasena' => 'Abcdefghi12',
        ])->assertSessionHasErrors('contrasena');
    }

    public function test_correo_duplicado_se_rechaza(): void
    {
        $usuario = $this->usuario();
        $this->post('/register', ['nombre' => 'Otra persona', 'correo' => strtoupper($usuario->correo),
            'contrasena' => 'ClavePruebas123', 'confirmacion_contrasena' => 'ClavePruebas123',
        ])->assertSessionHasErrors('correo');
    }

    public function test_ingresa_usuario_activo_y_cierra_sesion(): void
    {
        $usuario = $this->usuario();
        $this->post('/login', ['correo' => strtoupper($usuario->correo), 'contrasena' => 'ClavePruebas123'])
            ->assertRedirect('/proyectos');
        $this->assertAuthenticatedAs($usuario);
        $this->get('/proyectos')->assertOk();
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/proyectos')->assertRedirect('/login');
    }

    public function test_cuenta_inactiva_no_ingresa(): void
    {
        $usuario = $this->usuario(activo: false);
        $this->post('/login', ['correo' => $usuario->correo, 'contrasena' => 'ClavePruebas123'])
            ->assertSessionHasErrors('correo');
        $this->assertGuest();
    }

    public function test_miembro_no_accede_a_usuarios(): void
    {
        $this->actingAs($this->usuario())->get('/usuarios')->assertForbidden();
    }

    public function test_administrador_consulta_usuarios(): void
    {
        $this->actingAs($this->usuario('ADMINISTRADOR'))->get('/usuarios')->assertOk();
    }

    public function test_se_bloquea_el_sexto_intento_fallido(): void
    {
        $usuario = $this->usuario();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['correo' => $usuario->correo, 'contrasena' => 'NoEsLaClave']);
        }
        $respuesta = $this->post('/login', ['correo' => $usuario->correo, 'contrasena' => 'ClavePruebas123']);
        $respuesta->assertSessionHasErrors('correo');
        $this->assertGuest();
    }

    public function test_recuperacion_usa_notificacion_y_respuesta_generica(): void
    {
        Notification::fake();
        $usuario = $this->usuario();
        $this->post('/password/email', ['correo' => $usuario->correo])->assertSessionHas('status');
        $mensaje = session('status');
        Notification::assertSentTo($usuario, RecuperacionContrasena::class);
        $this->post('/password/email', ['correo' => 'desconocido@example.test'])->assertSessionHas('status', $mensaje);
    }

    public function test_restaurar_clave_invalida_el_token_usado(): void
    {
        $usuario = $this->usuario();
        $token = Password::broker('usuarios')->createToken($usuario);
        $datos = ['correo' => $usuario->correo, 'token' => $token,
            'contrasena' => 'NuevaClave12345', 'confirmacion_contrasena' => 'NuevaClave12345'];
        $this->post('/password/reset', $datos)->assertRedirect('/login');
        $this->assertTrue(Hash::check('NuevaClave12345', $usuario->fresh()->contrasena));
        $this->assertFalse(Hash::check('ClavePruebas123', $usuario->fresh()->contrasena));
        $this->post('/password/reset', $datos)->assertSessionHasErrors('correo');
    }

    public function test_token_vencido_no_cambia_la_clave(): void
    {
        $usuario = $this->usuario();
        $token = Password::broker('usuarios')->createToken($usuario);
        DB::table('password_reset_tokens')->where('email', $usuario->correo)->update(['created_at' => now()->subMinutes(61)]);
        $this->post('/password/reset', ['correo' => $usuario->correo, 'token' => $token,
            'contrasena' => 'NuevaClave12345', 'confirmacion_contrasena' => 'NuevaClave12345',
        ])->assertSessionHasErrors('correo');
        $this->assertTrue(Hash::check('ClavePruebas123', $usuario->fresh()->contrasena));
    }

    public function test_paginas_publicas_cargan_sin_sesion(): void
    {
        $this->get('/login')->assertOk()->assertSee('Correo');
        $this->get('/register')->assertOk()->assertSee('Crear cuenta');
        $this->get('/password/reset')->assertOk()->assertSee('Solicitar enlace');
    }

    public function test_miembro_solo_ve_tableros_asignados_al_cargar_proyectos(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $miembro = $this->usuario();
        $proyecto = DB::table('proyectos')->insertGetId([
            'id_creador' => $admin->id_usuario, 'nombre' => 'Proyecto prueba de acceso',
        ], 'id_proyecto');
        foreach (['Tablero permitido', 'Tablero reservado'] as $nombre) {
            $tablero = DB::table('tableros')->insertGetId([
                'id_creador' => $admin->id_usuario, 'id_proyecto' => $proyecto, 'nombre' => $nombre,
            ], 'id_tablero');
            foreach (['POR_HACER', 'EN_PROGRESO', 'EN_REVISION', 'COMPLETADO'] as $posicion => $codigo) {
                DB::table('columnas_tablero')->insert([
                    'id_tablero' => $tablero, 'posicion' => $posicion,
                    'id_estado' => DB::table('estados_tarea')->where('codigo', $codigo)->value('id_estado'),
                ]);
            }
            if ($nombre === 'Tablero permitido') {
                DB::table('usuarios_tableros')->insert([
                    'id_tablero' => $tablero, 'id_usuario' => $miembro->id_usuario, 'activo' => 1,
                ]);
            }
        }
        $this->actingAs($miembro)->get('/proyectos')->assertOk()
            ->assertSee('Tablero permitido')->assertDontSee('Tablero reservado');
    }

    public function test_cuenta_desactivada_pierde_acceso_en_siguiente_peticion(): void
    {
        $usuario = $this->usuario(activo: false);
        $this->actingAs($usuario)->get('/proyectos')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_no_se_trunca_una_contrasena_mayor_de_72_bytes(): void
    {
        $clave = str_repeat('a', 72).'1';
        $this->post('/register', [
            'nombre' => 'Prueba longitud', 'correo' => 'longitud@example.test',
            'contrasena' => $clave, 'confirmacion_contrasena' => $clave,
        ])->assertSessionHasErrors('contrasena');
        $this->assertDatabaseMissing('usuarios', ['correo' => 'longitud@example.test']);
    }

    public function test_seeder_administrador_pregunta_datos_y_no_cambia_una_cuenta_existente(): void
    {
        $this->artisan('db:seed', ['--class' => \Database\Seeders\AdministradorSeeder::class])
            ->expectsQuestion('Nombre del Administrador', 'Administrador de prueba')
            ->expectsQuestion('Correo del Administrador', 'admin.prueba@example.test')
            ->expectsQuestion('Contrasena: minimo 12 caracteres, letra y numero', 'ClavePruebas123')
            ->expectsQuestion('Confirma la contrasena', 'ClavePruebas123')
            ->assertExitCode(0);
        $admin = Usuario::where('correo', 'admin.prueba@example.test')->firstOrFail();
        $hash = $admin->contrasena;
        $this->assertSame('ADMINISTRADOR', $admin->rol->codigo);
        $this->artisan('db:seed', ['--class' => \Database\Seeders\AdministradorSeeder::class])
            ->assertExitCode(0);
        $this->assertSame($hash, $admin->fresh()->contrasena);
        $this->assertDatabaseCount('usuarios', 1);
    }
}
