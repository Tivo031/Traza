<?php

namespace Tests\Feature;

use App\Models\ColumnaTablero;
use App\Models\Proyecto;
use App\Models\Rol;
use App\Models\Tablero;
use App\Models\Tarea;
use App\Models\Usuario;
use App\Models\UsuarioTablero;
use App\Services\OrganizacionServicio;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Soporte\CasoTraza;

class OrganizacionTrazaTest extends CasoTraza
{
    // Hereda la barrera: solo APP_ENV=testing y MySQL/traza_pruebas; nunca traza_db.
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
            'nombre' => 'Persona de prueba', 'correo' => Str::lower(Str::random(12)).'@example.test',
            'id_rol' => Rol::where('codigo', $rol)->firstOrFail()->id_rol,
            'contrasena' => Hash::make('ClavePruebas123'), 'activo' => $activo,
        ])->save();
        return $usuario;
    }

    private function proyecto(Usuario $admin, string $nombre = 'Proyecto de prueba'): Proyecto
    {
        return Proyecto::create(['id_creador' => $admin->id_usuario, 'nombre' => $nombre]);
    }

    private function tablero(Usuario $admin, ?Proyecto $proyecto = null, string $nombre = 'Tablero de prueba'): Tablero
    {
        return app(OrganizacionServicio::class)->crearTablero($proyecto ?? $this->proyecto($admin), $admin, ['nombre' => $nombre]);
    }

    private function tarea(Tablero $tablero, Usuario $responsable, string $estado = 'EN_PROGRESO'): Tarea
    {
        $columna = $tablero->columnas()->whereHas('estado', fn ($q) => $q->where('codigo', $estado))->firstOrFail();
        $tarea = Tarea::create([
            'id_columna' => $columna->id_columna,
            'id_creador' => $tablero->id_creador, 'id_responsable' => $responsable->id_usuario,
            'id_prioridad' => DB::table('prioridades')->where('codigo', 'MEDIA')->value('id_prioridad'),
            'id_tipo_tarea' => DB::table('tipos_tarea')->where('codigo', 'TAREA')->value('id_tipo_tarea'),
            'id_categoria' => DB::table('categorias')->where('nombre', 'General')->value('id_categoria'),
            'titulo' => 'Dato aislado de prueba', 'descripcion' => 'Fixture del test, no evidencia real.',
            'criterio_aceptacion' => 'Conservar responsable y referencias.', 'posicion' => 0,
        ]);
        $tarea->actividades()->create(['id_actor' => $tablero->id_creador, 'codigo_accion' => 'CREACION_TAREA']);
        if ($estado === 'COMPLETADO') {
            $tarea->fecha_cierre = now();
            $tarea->save();
        }
        return $tarea;
    }

    public function test_visitante_no_consulta_proyectos(): void
    {
        $this->get('/proyectos')->assertRedirect('/login');
    }

    public function test_miembro_no_administra_organizacion_ni_usuarios(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $tablero = $this->tablero($admin);
        $miembro = $this->usuario();
        $this->actingAs($miembro);
        $this->get('/proyectos/crear')->assertForbidden();
        $this->post('/proyectos', ['nombre' => 'No autorizado'])->assertForbidden();
        $this->post('/proyectos/'.$tablero->id_proyecto.'/tableros', ['nombre' => 'No autorizado'])->assertForbidden();
        $this->get('/tableros/'.$tablero->id_tablero.'/miembros')->assertForbidden();
        $this->post('/tableros/'.$tablero->id_tablero.'/miembros', ['id_usuario' => $miembro->id_usuario])->assertForbidden();
        $this->patch('/tableros/'.$tablero->id_tablero.'/miembros/'.$miembro->id_usuario, ['activo' => 0])->assertForbidden();
        $this->put('/proyectos/'.$tablero->id_proyecto, ['nombre' => 'No autorizado'])->assertForbidden();
        $this->put('/tableros/'.$tablero->id_tablero, ['nombre' => 'No autorizado'])->assertForbidden();
        $this->get('/usuarios')->assertForbidden();
        $this->put('/usuarios/'.$admin->id_usuario, ['nombre' => 'No autorizado'])->assertForbidden();
        $this->patch('/usuarios/'.$admin->id_usuario.'/estado', ['activo' => 0])->assertForbidden();
    }

    public function test_admin_crea_proyecto_con_creador_de_la_sesion(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $this->actingAs($admin)->post('/proyectos', ['nombre' => 'Desarrollo Traza', 'descripcion' => 'Trabajo del equipo'])
            ->assertRedirect();
        $proyecto = Proyecto::where('nombre', 'Desarrollo Traza')->firstOrFail();
        $this->assertEquals($admin->id_usuario, $proyecto->id_creador);
        $this->get('/proyectos/'.$proyecto->id_proyecto)->assertOk()->assertSee('Desarrollo Traza');
    }

    public function test_proyecto_rechaza_nombre_vacio_excesivo_y_creador_falsificado(): void
    {
        $this->actingAs($this->usuario('ADMINISTRADOR'));
        $this->post('/proyectos', ['nombre' => '   '])->assertSessionHasErrors('nombre');
        $this->post('/proyectos', ['nombre' => str_repeat('a', 151)])->assertSessionHasErrors('nombre');
        $this->post('/proyectos', ['nombre' => 'Intento', 'id_creador' => 999])->assertSessionHasErrors('id_creador');
        $this->assertDatabaseMissing('proyectos', ['nombre' => 'Intento']);
    }

    public function test_proyectos_permiten_nombres_iguales_y_edicion_preserva_tableros(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $tablero = $this->tablero($admin);
        $this->actingAs($admin)->post('/proyectos', ['nombre' => $tablero->proyecto->nombre])->assertRedirect();
        $this->assertEquals(2, Proyecto::where('nombre', $tablero->proyecto->nombre)->count());
        $this->put('/proyectos/'.$tablero->id_proyecto, ['nombre' => 'Actualizado'])->assertRedirect();
        $this->assertDatabaseHas('tableros', ['id_tablero' => $tablero->id_tablero, 'id_proyecto' => $tablero->id_proyecto]);
        $this->assertEquals($admin->id_usuario, $tablero->proyecto->fresh()->id_creador);
    }

    public function test_crear_tablero_genera_cuatro_columnas_en_orden(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $proyecto = $this->proyecto($admin);
        $this->actingAs($admin)->post('/proyectos/'.$proyecto->id_proyecto.'/tableros', ['nombre' => 'Construccion'])
            ->assertRedirect();
        $tablero = $proyecto->tableros()->firstOrFail();
        $columnas = $tablero->columnas()->with('estado')->orderBy('posicion')->get();
        $this->assertSame([0, 1, 2, 3], $columnas->pluck('posicion')->all());
        $this->assertSame(OrganizacionServicio::ESTADOS, $columnas->map(fn ($c) => $c->estado->codigo)->all());
        $this->assertSame(0, $tablero->asignaciones()->count());
        $this->get('/tableros/'.$tablero->id_tablero)->assertOk()->assertSee('Sin tareas');
    }

    public function test_nombre_tablero_es_unico_dentro_del_proyecto(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $tablero = $this->tablero($admin);
        $this->actingAs($admin)->post('/proyectos/'.$tablero->id_proyecto.'/tableros', ['nombre' => $tablero->nombre])
            ->assertSessionHasErrors('nombre');
        $otro = $this->proyecto($admin, 'Otro proyecto');
        $this->post('/proyectos/'.$otro->id_proyecto.'/tableros', ['nombre' => $tablero->nombre])->assertRedirect();
        $this->assertSame(1, $tablero->proyecto->tableros()->count());
        $this->assertSame(1, $otro->tableros()->count());
    }

    public function test_editar_tablero_conserva_columnas_y_no_acepta_otro_proyecto(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $tablero = $this->tablero($admin);
        $ids = $tablero->columnas()->orderBy('id_columna')->pluck('id_columna')->all();
        $this->actingAs($admin)->put('/tableros/'.$tablero->id_tablero, ['nombre' => $tablero->nombre])->assertRedirect();
        $this->put('/tableros/'.$tablero->id_tablero, ['nombre' => 'Otro', 'id_proyecto' => 999])->assertSessionHasErrors('id_proyecto');
        $this->assertSame($ids, $tablero->columnas()->orderBy('id_columna')->pluck('id_columna')->all());
    }

    public function test_fallo_de_columna_revierte_tablero_y_columnas(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $proyecto = $this->proyecto($admin);
        ColumnaTablero::creating(function (ColumnaTablero $columna): void {
            if ($columna->posicion === 2) throw new \RuntimeException('Fallo controlado exclusivamente en el test.');
        });
        try {
            $this->actingAs($admin)->post('/proyectos/'.$proyecto->id_proyecto.'/tableros', ['nombre' => 'Prueba atomica'])
                ->assertStatus(500);
            $this->assertFalse($proyecto->tableros()->exists());
            $this->assertDatabaseCount('columnas_tablero', 0);
        } finally {
            ColumnaTablero::flushEventListeners();
        }
    }

    public function test_asignacion_repetida_no_duplica_y_reactivacion_conserva_id(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $tablero = $this->tablero($admin);
        $miembro = $this->usuario();
        $url = '/tableros/'.$tablero->id_tablero.'/miembros';
        $this->actingAs($admin)->post($url, ['id_usuario' => $miembro->id_usuario])->assertRedirect();
        $id = UsuarioTablero::where('id_tablero', $tablero->id_tablero)->value('id_usuario_tablero');
        $this->post($url, ['id_usuario' => $miembro->id_usuario])->assertRedirect();
        $this->patch($url.'/'.$miembro->id_usuario, ['activo' => 0])->assertRedirect();
        $this->patch($url.'/'.$miembro->id_usuario, ['activo' => 1])->assertRedirect();
        $this->assertSame(1, $tablero->asignaciones()->count());
        $this->assertDatabaseHas('usuarios_tableros', ['id_usuario_tablero' => $id, 'activo' => 1]);
    }

    public function test_cuenta_inactiva_no_recibe_asignacion(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $tablero = $this->tablero($admin);
        $inactivo = $this->usuario(activo: false);
        $this->actingAs($admin)->post('/tableros/'.$tablero->id_tablero.'/miembros', ['id_usuario' => $inactivo->id_usuario])
            ->assertSessionHasErrors('id_usuario');
        $this->assertFalse($tablero->asignaciones()->exists());
    }

    public function test_asignacion_inexistente_no_se_modifica(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $tablero = $this->tablero($admin);
        $miembro = $this->usuario();
        $this->actingAs($admin)->patch('/tableros/'.$tablero->id_tablero.'/miembros/'.$miembro->id_usuario, ['activo' => 1])
            ->assertNotFound();
        $this->assertFalse($tablero->asignaciones()->exists());
    }

    public function test_miembro_ve_solo_sus_tableros_y_no_proyectos_ajenos(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $miembro = $this->usuario();
        $proyecto = $this->proyecto($admin, 'Proyecto accesible');
        $permitido = $this->tablero($admin, $proyecto, 'Tablero permitido');
        $reservado = $this->tablero($admin, $proyecto, 'Tablero reservado');
        $otro = $this->tablero($admin, $this->proyecto($admin, 'Proyecto oculto'));
        app(OrganizacionServicio::class)->concederAcceso($permitido, $miembro);
        $this->actingAs($miembro)->get('/proyectos')->assertOk()->assertSee('Tablero permitido')
            ->assertDontSee('Tablero reservado')->assertDontSee('Proyecto oculto')->assertDontSee('Nuevo proyecto');
        $this->get('/proyectos/'.$proyecto->id_proyecto)->assertOk()->assertSee('Tablero permitido')->assertDontSee('Tablero reservado');
        $this->get('/tableros/'.$permitido->id_tablero)->assertOk();
        $this->get('/tableros/'.$reservado->id_tablero)->assertNotFound();
        $this->get('/proyectos/'.$otro->id_proyecto)->assertNotFound();
    }

    public function test_retirar_asignacion_quita_lectura_en_siguiente_peticion(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $miembro = $this->usuario();
        $tablero = $this->tablero($admin);
        app(OrganizacionServicio::class)->concederAcceso($tablero, $miembro);
        $this->actingAs($miembro)->get('/tableros/'.$tablero->id_tablero)->assertOk();
        app(OrganizacionServicio::class)->retirarAcceso($tablero, $miembro);
        $this->get('/tableros/'.$tablero->id_tablero)->assertNotFound();
    }

    public function test_tarea_abierta_impide_retirar_membresia_y_desactivar_cuenta(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $miembro = $this->usuario();
        $tablero = $this->tablero($admin);
        app(OrganizacionServicio::class)->concederAcceso($tablero, $miembro);
        $this->tarea($tablero, $miembro);
        $this->actingAs($admin)->patch('/tableros/'.$tablero->id_tablero.'/miembros/'.$miembro->id_usuario, ['activo' => 0])
            ->assertSessionHasErrors('asignacion');
        $this->patch('/usuarios/'.$miembro->id_usuario.'/estado', ['activo' => 0])->assertSessionHasErrors('activo');
        $this->assertTrue($miembro->fresh()->activo);
        $this->assertTrue($tablero->asignaciones()->firstOrFail()->activo);
    }

    public function test_tarea_cerrada_conserva_responsable_al_retirar_y_desactivar(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $miembro = $this->usuario();
        $tablero = $this->tablero($admin);
        app(OrganizacionServicio::class)->concederAcceso($tablero, $miembro);
        $tarea = $this->tarea($tablero, $miembro, 'COMPLETADO');
        $this->actingAs($admin)->patch('/tableros/'.$tablero->id_tablero.'/miembros/'.$miembro->id_usuario, ['activo' => 0])->assertRedirect();
        $this->patch('/usuarios/'.$miembro->id_usuario.'/estado', ['activo' => 0])->assertRedirect();
        $this->assertFalse($miembro->fresh()->activo);
        $this->assertEquals($miembro->id_usuario, $tarea->fresh()->id_responsable);
        $this->assertSame(1, $tarea->actividades()->count());
    }

    public function test_ultimo_administrador_no_puede_desactivarse(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $this->actingAs($admin)->patch('/usuarios/'.$admin->id_usuario.'/estado', ['activo' => 0])->assertSessionHasErrors('activo');
        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_autodesactivacion_con_otro_admin_cierra_la_sesion(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $this->usuario('ADMINISTRADOR');
        $this->actingAs($admin)->patch('/usuarios/'.$admin->id_usuario.'/estado', ['activo' => 0])->assertRedirect('/login');
        $this->assertGuest();
        $this->assertFalse($admin->fresh()->activo);
    }

    public function test_edicion_usuario_normaliza_y_conserva_rol_y_clave(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $miembro = $this->usuario();
        $hash = $miembro->contrasena;
        $this->actingAs($admin)->put('/usuarios/'.$miembro->id_usuario, ['nombre' => 'Nombre corregido', 'correo' => 'NUEVO@EXAMPLE.TEST'])->assertRedirect();
        $actual = $miembro->fresh();
        $this->assertSame('nuevo@example.test', $actual->correo);
        $this->assertSame($hash, $actual->contrasena);
        $this->assertSame('MIEMBRO', $actual->rol->codigo);
    }

    public function test_edicion_usuario_rechaza_correo_duplicado_y_rol_enviado(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $miembro = $this->usuario();
        $this->actingAs($admin)->put('/usuarios/'.$miembro->id_usuario, ['nombre' => 'Miembro', 'correo' => $admin->correo])
            ->assertSessionHasErrors('correo');
        $this->put('/usuarios/'.$miembro->id_usuario, ['nombre' => 'Miembro', 'correo' => $miembro->correo, 'id_rol' => $admin->id_rol])
            ->assertSessionHasErrors('id_rol');
        $this->assertSame('MIEMBRO', $miembro->fresh()->rol->codigo);
    }

    public function test_paginas_de_formulario_cargan_y_escapan_contenido(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $proyecto = $this->proyecto($admin, '<script>alert(1)</script>');
        $tablero = $this->tablero($admin, $proyecto);
        $this->actingAs($admin);
        foreach (['/proyectos/crear', '/proyectos/'.$proyecto->id_proyecto.'/editar',
            '/proyectos/'.$proyecto->id_proyecto.'/tableros/crear', '/tableros/'.$tablero->id_tablero.'/editar',
            '/tableros/'.$tablero->id_tablero.'/miembros', '/usuarios/'.$admin->id_usuario.'/editar'] as $ruta) {
            $this->get($ruta)->assertOk();
        }
        $this->get('/proyectos/'.$proyecto->id_proyecto)->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_verificador_consulta_sin_crear_registros(): void
    {
        $admin = $this->usuario('ADMINISTRADOR');
        $tablero = $this->tablero($admin);
        $conteos = [Proyecto::count(), Tablero::count(), ColumnaTablero::count(), UsuarioTablero::count()];
        $this->artisan('traza:verificar-organizacion')->assertExitCode(0);
        $this->assertSame($conteos, [Proyecto::count(), Tablero::count(), ColumnaTablero::count(), UsuarioTablero::count()]);
    }
}
