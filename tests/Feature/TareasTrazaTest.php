<?php

namespace Tests\Feature;

use App\Models\{ActividadTarea, Categoria, Prioridad, Proyecto, Rol, Subtarea, Tablero, Tarea, TipoTarea, Usuario};
use App\Services\{OrganizacionServicio, TareaServicio};
use App\Support\VistaTarea;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Soporte\CasoTraza;

class TareasTrazaTest extends CasoTraza
{
    use DatabaseTransactions; // Solo traza_pruebas: barrera heredada de CasoTraza.
    private Usuario $admin;
    private Usuario $miembro;
    private Tablero $tablero;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        $this->admin = $this->persona('ADMINISTRADOR');
        $this->miembro = $this->persona();
        $proyecto = Proyecto::create(['id_creador' => $this->admin->id_usuario, 'nombre' => 'Fixture de pruebas']);
        $org = app(OrganizacionServicio::class);
        $this->tablero = $org->crearTablero($proyecto, $this->admin, ['nombre' => 'Fixture del tablero']);
        $org->concederAcceso($this->tablero, $this->admin);
        $org->concederAcceso($this->tablero, $this->miembro);
    }

    private function persona(string $rol = 'MIEMBRO'): Usuario
    {
        $u = new Usuario();
        $u->forceFill(['nombre' => 'Persona de prueba', 'correo' => Str::lower(Str::random(14)).'@example.test',
            'id_rol' => Rol::where('codigo', $rol)->value('id_rol'),
            'contrasena' => Hash::make('ClaveDePrueba123'), 'activo' => 1])->save();
        return $u;
    }

    private function datos(array $extra = []): array
    {
        return array_replace(['titulo' => 'Tarea de prueba', 'descripcion' => 'Fixture, no evidencia del curso.',
            'criterio_aceptacion' => 'Conservar los datos al recargar.',
            'id_prioridad' => Prioridad::where('codigo', 'MEDIA')->value('id_prioridad'),
            'id_tipo_tarea' => TipoTarea::where('codigo', 'TAREA')->value('id_tipo_tarea'),
            'id_categoria' => Categoria::where('nombre', 'General')->value('id_categoria'),
            'id_responsable' => null, 'fecha_inicio' => null, 'fecha_limite' => null], $extra);
    }

    private function crear(?Usuario $responsable = null, array $extra = []): Tarea
    {
        return app(TareaServicio::class)->crear($this->tablero, $this->admin,
            $this->datos(array_replace(['id_responsable' => $responsable?->id_usuario], $extra)));
    }

    private function movimiento(Tarea $tarea, string $codigo, array $extra = []): array
    {
        $tarea = $tarea->fresh();
        return array_replace(['id_columna_esperada' => $tarea->id_columna,
            'revision_esperada' => VistaTarea::revision($tarea),
            'id_columna_destino' => $this->tablero->columnas()->whereHas('estado', fn ($q) => $q->where('codigo', $codigo))->value('id_columna')], $extra);
    }

    private function ir(Tarea $tarea, string $codigo, Usuario $actor, array $extra = []): Tarea
    {
        return app(TareaServicio::class)->mover($tarea->fresh(), $actor, $this->movimiento($tarea, $codigo, $extra));
    }

    public function test_visitante_no_abre_formulario(): void
    {
        $this->get(route('tareas.create', $this->tablero))->assertRedirect('/login');
    }

    public function test_miembro_ajeno_no_lee_ni_crea_ni_mueve(): void
    {
        $tarea = $this->crear(); $ajeno = $this->persona();
        $this->actingAs($ajeno)->get(route('tareas.show', $tarea))->assertNotFound();
        $this->postJson(route('tareas.store', $this->tablero), $this->datos())->assertNotFound();
        $this->patchJson(route('tareas.mover', $tarea), $this->movimiento($tarea, 'EN_PROGRESO',
            ['id_responsable' => $this->miembro->id_usuario]))->assertNotFound();
    }

    public function test_formulario_tablero_y_detalle_se_renderizan(): void
    {
        $this->actingAs($this->admin)->get(route('tareas.create', $this->tablero))->assertOk()->assertSee('Nueva tarea');
        $tarea = $this->crear();
        $this->get(route('tableros.show', $this->tablero))->assertOk()->assertSee('Tarea de prueba');
        $this->get(route('tareas.show', $tarea))->assertOk()->assertSee('Historial de actividad');
        $this->get(route('tareas.edit', $tarea))->assertOk()->assertSee('Editar tarea');
    }

    public function test_crear_sin_responsable_deja_backlog_y_actividad(): void
    {
        $this->actingAs($this->miembro)->post(route('tareas.store', $this->tablero), $this->datos())->assertRedirect();
        $t = Tarea::firstOrFail();
        $this->assertSame('POR_HACER', VistaTarea::codigo($t));
        $this->assertNull($t->id_responsable);
        $this->assertEquals($this->miembro->id_usuario, $t->id_creador);
        $this->assertSame(1, $t->actividades()->where('codigo_accion', 'CREACION_TAREA')->count());
    }

    public function test_responsable_inicial_crea_tres_eventos_e_inicia(): void
    {
        $t = $this->crear($this->miembro);
        $this->assertSame('EN_PROGRESO', VistaTarea::codigo($t));
        $this->assertSame(['CREACION_TAREA', 'ASIGNACION_RESPONSABLE', 'CAMBIO_ESTADO'],
            $t->actividades()->orderBy('id_actividad')->pluck('codigo_accion')->all());
    }

    public function test_rechaza_datos_invalidos_y_campos_reservados(): void
    {
        $this->actingAs($this->admin);
        foreach ([['titulo' => '   '], ['titulo' => str_repeat('a', 151)], ['criterio_aceptacion' => ''],
            ['id_prioridad' => 999999], ['id_responsable' => 0], ['id_creador' => 100],
            ['fecha_cierre' => '2030-01-01'], ['fecha_inicio' => '2030-05-02', 'fecha_limite' => '2030-05-01']] as $extra) {
            $this->postJson(route('tareas.store', $this->tablero), $this->datos($extra))->assertUnprocessable();
        }
        $this->assertSame(0, Tarea::count());
    }

    public function test_titulos_repetidos_y_150_caracteres_son_validos(): void
    {
        $this->actingAs($this->admin);
        foreach ([1, 2] as $n) $this->post(route('tareas.store', $this->tablero), $this->datos(['titulo' => str_repeat('a', 150)]))->assertRedirect();
        $this->assertSame(2, Tarea::count());
    }

    public function test_rechaza_responsable_ajeno_inactivo_y_admin_sin_membresia(): void
    {
        $inactivo = $this->persona(); $org = app(OrganizacionServicio::class);
        $org->concederAcceso($this->tablero, $inactivo); $inactivo->activo = false; $inactivo->save();
        $this->actingAs($this->admin);
        foreach ([$this->persona(), $inactivo, $this->persona('ADMINISTRADOR')] as $u) {
            $this->postJson(route('tareas.store', $this->tablero), $this->datos(['id_responsable' => $u->id_usuario]))->assertUnprocessable();
        }
        $this->assertSame(0, Tarea::count());
    }

    public function test_ningun_salto_directo_a_completado(): void
    {
        $t = $this->crear(); $eventos = $t->actividades()->count();
        $this->actingAs($this->admin)->patchJson(route('tareas.mover', $t), $this->movimiento($t, 'COMPLETADO'))->assertUnprocessable();
        $this->assertSame('POR_HACER', VistaTarea::codigo($t->fresh()));
        $this->assertSame($eventos, $t->actividades()->count());
    }

    public function test_enviar_a_revision_solo_responsable(): void
    {
        $t = $this->crear($this->miembro);
        $this->actingAs($this->admin)->patchJson(route('tareas.mover', $t), $this->movimiento($t, 'EN_REVISION'))->assertUnprocessable();
        $this->actingAs($this->miembro)->patch(route('tareas.mover', $t), $this->movimiento($t, 'EN_REVISION'))->assertRedirect();
        $this->assertSame('EN_REVISION', VistaTarea::codigo($t->fresh()));
    }

    public function test_subtareas_pendientes_bloquean_revision(): void
    {
        $t = $this->crear($this->miembro);
        Subtarea::create(['id_tarea' => $t->id_tarea, 'titulo' => 'Pendiente', 'posicion' => 0]);
        $this->actingAs($this->miembro)->patchJson(route('tareas.mover', $t), $this->movimiento($t, 'EN_REVISION'))->assertUnprocessable();
    }

    public function test_rechazo_exige_motivo_y_conserva_responsable(): void
    {
        $t = $this->ir($this->crear($this->miembro), 'EN_REVISION', $this->miembro);
        $this->actingAs($this->admin)->patchJson(route('tareas.mover', $t), $this->movimiento($t, 'EN_PROGRESO'))->assertUnprocessable();
        $this->patch(route('tareas.mover', $t), $this->movimiento($t, 'EN_PROGRESO', ['observacion' => 'Falta validacion']))->assertRedirect();
        $t = $t->fresh(); $this->assertSame('EN_PROGRESO', VistaTarea::codigo($t));
        $this->assertEquals($this->miembro->id_usuario, $t->id_responsable); $this->assertNull($t->fecha_cierre);
    }

    public function test_miembro_no_aprueba_y_admin_registra_cierre(): void
    {
        $t = $this->ir($this->crear($this->miembro), 'EN_REVISION', $this->miembro);
        $this->actingAs($this->miembro)->patchJson(route('tareas.mover', $t), $this->movimiento($t, 'COMPLETADO'))->assertUnprocessable();
        $this->actingAs($this->admin)->patch(route('tareas.mover', $t), $this->movimiento($t, 'COMPLETADO'))->assertRedirect();
        $t = $t->fresh(); $this->assertSame('COMPLETADO', VistaTarea::codigo($t)); $this->assertNotNull($t->fecha_cierre);
        $this->assertFalse(VistaTarea::editable($t));
    }

    public function test_no_reabre_ni_edita_completado(): void
    {
        $t = $this->ir($this->ir($this->crear($this->miembro), 'EN_REVISION', $this->miembro), 'COMPLETADO', $this->admin);
        $this->actingAs($this->admin)->patchJson(route('tareas.mover', $t), $this->movimiento($t, 'EN_PROGRESO'))->assertUnprocessable();
        $this->putJson(route('tareas.update', $t), $this->datos(['id_responsable' => $this->miembro->id_usuario,
            'id_columna_esperada' => $t->id_columna, 'revision_esperada' => VistaTarea::revision($t)]))->assertUnprocessable();
    }

    public function test_otro_tablero_se_rechaza(): void
    {
        $t = $this->crear();
        $otro = app(OrganizacionServicio::class)->crearTablero($this->tablero->proyecto, $this->admin, ['nombre' => 'Otro']);
        $this->actingAs($this->admin)->patchJson(route('tareas.mover', $t), $this->movimiento($t, 'EN_PROGRESO',
            ['id_columna_destino' => $otro->columnas()->first()->id_columna, 'id_responsable' => $this->miembro->id_usuario]))->assertUnprocessable();
    }

    public function test_conflicto_no_pisa_edicion_ni_se_omite_con_estado_igual(): void
    {
        $t = $this->crear(); $viejos = $this->movimiento($t, 'EN_PROGRESO', ['id_responsable' => $this->miembro->id_usuario]);
        $this->actingAs($this->admin)->put(route('tareas.update', $t), $this->datos(['titulo' => 'Nuevo titulo',
            'id_columna_esperada' => $t->id_columna, 'revision_esperada' => VistaTarea::revision($t)]))->assertRedirect();
        $this->patchJson(route('tareas.mover', $t), $viejos)->assertStatus(409);
        $this->assertSame('Nuevo titulo', $t->fresh()->titulo);
        $this->assertSame('POR_HACER', VistaTarea::codigo($t->fresh()));
    }

    public function test_reordenar_conserva_estado_y_guarda_actividad_distinta(): void
    {
        $a = $this->crear(); $b = $this->crear();
        $this->actingAs($this->admin)->patch(route('tareas.mover', $b), $this->movimiento($b, 'POR_HACER', ['posicion_destino' => 0]))->assertRedirect();
        $this->assertSame(0, $b->fresh()->posicion); $this->assertSame(1, $a->fresh()->posicion);
        $e = $b->actividades()->orderByDesc('id_actividad')->first();
        $this->assertSame('REORDENAMIENTO_TAREA', $e->codigo_accion); $this->assertNull($e->id_estado_nuevo);
    }

    public function test_vencimiento_ayer_hoy_sin_limite_y_cerrada(): void
    {
        $ayer = now('America/Guatemala')->subDay()->toDateString(); $hoy = now('America/Guatemala')->toDateString();
        $this->assertTrue(VistaTarea::vencida($this->crear(extra: ['fecha_limite' => $ayer])));
        $this->assertFalse(VistaTarea::vencida($this->crear(extra: ['fecha_limite' => $hoy])));
        $this->assertFalse(VistaTarea::vencida($this->crear()));
        $t = $this->crear($this->miembro, ['fecha_limite' => $ayer]);
        $t = $this->ir($this->ir($t, 'EN_REVISION', $this->miembro), 'COMPLETADO', $this->admin);
        $this->assertFalse(VistaTarea::vencida($t));
    }

    public function test_fallo_de_actividad_revierte_tarea(): void
    {
        $dispatcher = ActividadTarea::getEventDispatcher();
        ActividadTarea::setEventDispatcher(clone $dispatcher);
        ActividadTarea::creating(fn () => throw new \RuntimeException('Fallo controlado de prueba'));
        try {
            $this->actingAs($this->admin)->post(route('tareas.store', $this->tablero), $this->datos())->assertStatus(500);
            $this->assertSame(0, Tarea::count());
        } finally { ActividadTarea::setEventDispatcher($dispatcher); }
    }

    public function test_texto_se_escapa_en_detalle(): void
    {
        $t = $this->crear(extra: ['descripcion' => '<script>alert(1)</script>']);
        $this->actingAs($this->admin)->get(route('tareas.show', $t))->assertOk()->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
