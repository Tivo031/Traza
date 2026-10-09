<?php

namespace Tests\Feature;

use App\Models\{ActividadTarea, Categoria, ElementoVerificacion, Prioridad, Proyecto, Rol, Subtarea, Tablero, Tarea, TipoTarea, Usuario};
use App\Services\{OrganizacionServicio, PanelServicio, SeguimientoServicio, TareaServicio};
use App\Support\VistaTarea;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Soporte\CasoTraza;

class SeguimientoTrazaTest extends CasoTraza
{
    use DatabaseTransactions; // CasoTraza bloquea el acceso a cualquier base distinta de traza_pruebas.

    private Usuario $admin;
    private Usuario $miembro;
    private Tablero $tablero;
    private Proyecto $proyecto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogosSeeder::class);
        $this->admin = $this->persona('ADMINISTRADOR');
        $this->miembro = $this->persona();
        $this->proyecto = Proyecto::create(['id_creador' => $this->admin->id_usuario, 'nombre' => 'Fixture fase 4']);
        $org = app(OrganizacionServicio::class);
        $this->tablero = $org->crearTablero($this->proyecto, $this->admin, ['nombre' => 'Tablero fixture']);
        $org->concederAcceso($this->tablero, $this->admin);
        $org->concederAcceso($this->tablero, $this->miembro);
    }

    private function persona(string $rol = 'MIEMBRO'): Usuario
    {
        $u = new Usuario();
        $u->forceFill(['nombre' => 'Persona de prueba', 'correo' => Str::lower(Str::random(16)).'@example.test',
            'id_rol' => Rol::where('codigo', $rol)->value('id_rol'),
            'contrasena' => Hash::make('ClaveDePrueba123'), 'activo' => 1])->save();
        return $u;
    }

    private function tarea(?Tablero $tablero = null, ?string $limite = null): Tarea
    {
        return app(TareaServicio::class)->crear($tablero ?? $this->tablero, $this->admin, [
            'titulo' => 'Tarea de fixture', 'descripcion' => 'Datos de prueba, no evidencia real del curso.',
            'criterio_aceptacion' => 'Verificar los registros.',
            'id_prioridad' => Prioridad::where('codigo', 'MEDIA')->value('id_prioridad'),
            'id_tipo_tarea' => TipoTarea::where('codigo', 'TAREA')->value('id_tipo_tarea'),
            'id_categoria' => Categoria::where('nombre', 'General')->value('id_categoria'),
            'id_responsable' => $tablero ? null : $this->miembro->id_usuario, 'fecha_limite' => $limite,
        ]);
    }

    private function datos(Tarea $tarea, array $extra = []): array
    {
        $t = $tarea->fresh();
        return array_merge(['id_columna_esperada' => $t->id_columna,
            'revision_esperada' => VistaTarea::revision($t)], $extra);
    }

    private function subtarea(Tarea $tarea): Subtarea
    {
        app(SeguimientoServicio::class)->crearSubtarea($tarea->fresh(), $this->miembro, $this->datos($tarea, ['titulo' => 'Subtarea fixture']));
        return $tarea->subtareas()->orderByDesc('id_subtarea')->firstOrFail();
    }

    private function elemento(Tarea $tarea, Subtarea $subtarea): ElementoVerificacion
    {
        app(SeguimientoServicio::class)->crearElemento($tarea->fresh(), $subtarea, $this->miembro, $this->datos($tarea, ['titulo' => 'Elemento fixture']));
        return $subtarea->elementos()->orderByDesc('id_elemento')->firstOrFail();
    }

    private function ir(Tarea $tarea, string $codigo, Usuario $actor): Tarea
    {
        return app(TareaServicio::class)->mover($tarea->fresh(), $actor, $this->datos($tarea, [
            'id_columna_destino' => $this->tablero->columnas()->whereHas('estado', fn ($q) => $q->where('codigo', $codigo))->value('id_columna'),
        ]));
    }

    public function test_detalle_y_panel_se_renderizan_con_datos(): void
    {
        $t = $this->tarea(); $s = $this->subtarea($t); $this->elemento($t, $s);
        $this->actingAs($this->miembro)->get(route('tareas.show', $t))->assertOk()
            ->assertSee('Agregar subtarea')->assertSee('Publicar comentario')->assertSee('Historial de actividad');
        $this->get(route('panel.show', $this->proyecto))->assertOk()->assertViewHas('resumen', fn ($r) => $r['total'] === 1);
    }

    public function test_visitante_no_puede_publicar_ni_ver_panel(): void
    {
        $t = $this->tarea();
        $this->post(route('subtareas.store', $t), $this->datos($t, ['titulo' => 'X']))->assertRedirect('/login');
        $this->get(route('panel.show', $this->proyecto))->assertRedirect('/login');
    }

    public function test_crear_subtarea_registra_padre_fecha_nula_y_actividad(): void
    {
        $t = $this->tarea();
        $this->actingAs($this->miembro)->post(route('subtareas.store', $t), $this->datos($t, ['titulo' => 'Revisar acceso']))->assertRedirect();
        $s = $t->subtareas()->firstOrFail();
        $this->assertNull($s->fecha_finalizacion);
        $this->assertEquals($t->id_tarea, $s->id_tarea);
        $this->assertSame(1, $t->actividades()->where('codigo_accion', 'CREACION_SUBTAREA')->count());
    }

    public function test_ajeno_no_crea_ni_comenta_y_no_ve_panel(): void
    {
        $t = $this->tarea(); $ajeno = $this->persona();
        $this->actingAs($ajeno)->postJson(route('subtareas.store', $t), $this->datos($t, ['titulo' => 'X']))->assertNotFound();
        $this->postJson(route('comentarios.store', $t), $this->datos($t, ['contenido' => 'X']))->assertNotFound();
        $this->get(route('panel.show', $this->proyecto))->assertNotFound();
    }

    public function test_ids_anidados_de_otras_tareas_y_subtareas_no_se_aceptan(): void
    {
        $t = $this->tarea(); $otra = $this->tarea(); $s = $this->subtarea($t); $ajena = $this->subtarea($otra);
        $e = $this->elemento($otra, $ajena);
        $this->actingAs($this->miembro)->putJson(route('subtareas.update', [$t, $ajena]), $this->datos($t, ['titulo' => 'Intruso', 'posicion' => 0]))->assertNotFound();
        $this->patchJson(route('elementos.estado', [$t, $s, $e]), $this->datos($t, ['completado' => 1]))->assertNotFound();
        $this->assertFalse($e->fresh()->completado);
    }

    public function test_valida_titulos_campos_reservados_y_booleanos(): void
    {
        $t = $this->tarea(); $s = $this->subtarea($t); $e = $this->elemento($t, $s);
        $this->actingAs($this->miembro);
        foreach ([['titulo' => ' '], ['titulo' => str_repeat('a', 151)], ['titulo' => 'X', 'id_actor' => $this->admin->id_usuario],
            ['titulo' => 'X', 'fecha_finalizacion' => '2030-01-01']] as $extra) {
            $this->postJson(route('subtareas.store', $t), $this->datos($t, $extra))->assertUnprocessable();
        }
        $this->patchJson(route('elementos.estado', [$t, $s, $e]), $this->datos($t, ['completado' => 2]))->assertUnprocessable();
        $this->post(route('subtareas.store', $t), $this->datos($t, ['titulo' => str_repeat('a', 150)]))->assertRedirect();
    }

    public function test_elemento_pendiente_impide_finalizar_y_no_crea_actividad(): void
    {
        $t = $this->tarea(); $s = $this->subtarea($t); $this->elemento($t, $s);
        $n = $t->actividades()->count();
        $this->actingAs($this->miembro)->patchJson(route('subtareas.estado', [$t, $s]), $this->datos($t, ['completada' => 1]))->assertUnprocessable();
        $this->assertNull($s->fresh()->fecha_finalizacion); $this->assertSame($n, $t->actividades()->count());
    }

    public function test_marcar_todo_no_finaliza_automaticamente_ni_cierra_tarea(): void
    {
        $t = $this->tarea(); $s = $this->subtarea($t); $e = $this->elemento($t, $s);
        $this->actingAs($this->miembro)->patch(route('elementos.estado', [$t, $s, $e]), $this->datos($t, ['completado' => 1]))->assertRedirect();
        $this->assertNull($s->fresh()->fecha_finalizacion);
        $this->patch(route('subtareas.estado', [$t, $s]), $this->datos($t, ['completada' => 1]))->assertRedirect();
        $this->assertNotNull($s->fresh()->fecha_finalizacion);
        $this->assertSame('EN_PROGRESO', VistaTarea::codigo($t->fresh()));
    }

    public function test_sin_elementos_se_finaliza_manualmente_y_avance_es_por_subtareas(): void
    {
        $t = $this->tarea();
        $this->assertNull(VistaTarea::porcentaje($t->fresh()));
        $s1 = $this->subtarea($t); $s2 = $this->subtarea($t);
        $this->assertSame(0, VistaTarea::porcentaje($t->fresh()));
        $this->actingAs($this->miembro)->patch(route('subtareas.estado', [$t, $s1]), $this->datos($t, ['completada' => 1]))->assertRedirect();
        $this->assertSame(50, VistaTarea::porcentaje($t->fresh()));
        $this->patch(route('subtareas.estado', [$t, $s2]), $this->datos($t, ['completada' => 1]))->assertRedirect();
        $this->assertSame(100, VistaTarea::porcentaje($t->fresh()));
        $this->patch(route('subtareas.estado', [$t, $s1]), $this->datos($t, ['completada' => 0]))->assertRedirect();
        $this->assertSame(50, VistaTarea::porcentaje($t->fresh()));
    }

    public function test_desmarcar_y_agregar_pendiente_reabren_subtarea(): void
    {
        $t = $this->tarea(); $s = $this->subtarea($t); $e = $this->elemento($t, $s);
        $this->actingAs($this->miembro)->patch(route('elementos.estado', [$t, $s, $e]), $this->datos($t, ['completado' => 1]))->assertRedirect();
        $this->patch(route('subtareas.estado', [$t, $s]), $this->datos($t, ['completada' => 1]))->assertRedirect();
        $this->patch(route('elementos.estado', [$t, $s, $e]), $this->datos($t, ['completado' => 0]))->assertRedirect();
        $this->assertNull($s->fresh()->fecha_finalizacion);
        $this->patch(route('elementos.estado', [$t, $s, $e]), $this->datos($t, ['completado' => 1]))->assertRedirect();
        $this->patch(route('subtareas.estado', [$t, $s]), $this->datos($t, ['completada' => 1]))->assertRedirect();
        $this->post(route('elementos.store', [$t, $s]), $this->datos($t, ['titulo' => 'Nuevo pendiente']))->assertRedirect();
        $this->assertNull($s->fresh()->fecha_finalizacion);
        $this->assertSame(2, $t->actividades()->where('codigo_accion', 'REAPERTURA_SUBTAREA')->count());
    }

    public function test_editar_y_reordenar_conserva_padre_y_verificacion(): void
    {
        $t = $this->tarea(); $a = $this->subtarea($t); $b = $this->subtarea($t);
        $e1 = $this->elemento($t, $b); $e2 = $this->elemento($t, $b);
        $this->actingAs($this->miembro)->put(route('subtareas.update', [$t, $b]), $this->datos($t, ['titulo' => 'Primera', 'posicion' => 0]))->assertRedirect();
        $this->assertSame(0, $b->fresh()->posicion); $this->assertSame(1, $a->fresh()->posicion);
        $this->put(route('elementos.update', [$t, $b, $e2]), $this->datos($t, ['titulo' => 'Primer elemento', 'posicion' => 0]))->assertRedirect();
        $this->assertSame(0, $e2->fresh()->posicion); $this->assertSame(1, $e1->fresh()->posicion);
        $this->assertFalse($e2->fresh()->completado);
    }

    public function test_revision_bloquea_trabajo_pero_permite_comentar(): void
    {
        $t = $this->ir($this->tarea(), 'EN_REVISION', $this->miembro);
        $this->actingAs($this->miembro)->postJson(route('subtareas.store', $t), $this->datos($t, ['titulo' => 'Fuera de estado']))->assertUnprocessable();
        $this->post(route('comentarios.store', $t), $this->datos($t, ['contenido' => 'Listo para validar']))->assertRedirect();
        $this->assertSame(1, $t->comentarios()->count());
    }

    public function test_completado_no_admite_comentarios_ni_subtareas(): void
    {
        $t = $this->ir($this->ir($this->tarea(), 'EN_REVISION', $this->miembro), 'COMPLETADO', $this->admin);
        $this->actingAs($this->admin)->postJson(route('comentarios.store', $t), $this->datos($t, ['contenido' => 'No guardar']))->assertUnprocessable();
        $this->postJson(route('subtareas.store', $t), $this->datos($t, ['titulo' => 'No guardar']))->assertUnprocessable();
        $this->get(route('tareas.show', $t))->assertOk()->assertDontSee('Publicar comentario')->assertDontSee('Agregar subtarea');
    }

    public function test_revision_obsoleta_no_duplica_una_operacion(): void
    {
        $t = $this->tarea(); $datos = $this->datos($t, ['titulo' => 'Una vez']);
        $this->actingAs($this->miembro)->postJson(route('subtareas.store', $t), $datos)->assertRedirect();
        $this->postJson(route('subtareas.store', $t), $datos)->assertStatus(409);
        $this->assertSame(1, $t->subtareas()->count());
    }

    public function test_comentario_autor_validacion_y_salida_escapada(): void
    {
        $t = $this->tarea(); $this->actingAs($this->miembro);
        foreach ([' ', str_repeat('x', 5001)] as $texto) {
            $this->postJson(route('comentarios.store', $t), $this->datos($t, ['contenido' => $texto]))->assertUnprocessable();
        }
        $this->postJson(route('comentarios.store', $t), $this->datos($t, ['contenido' => 'X', 'id_usuario' => $this->admin->id_usuario]))->assertUnprocessable();
        $payload = '<script>alert("x")</script>';
        $this->post(route('comentarios.store', $t), $this->datos($t, ['contenido' => $payload]))->assertRedirect();
        $this->assertEquals($this->miembro->id_usuario, $t->comentarios()->firstOrFail()->id_usuario);
        $this->get(route('tareas.show', $t))->assertOk()->assertSee(e($payload), false)->assertDontSee($payload, false);
        $this->assertSame(1, $t->actividades()->where('codigo_accion', 'COMENTARIO_AGREGADO')->count());
    }

    public function test_panel_filtra_tableros_ajenos_y_no_multiplica_conteos(): void
    {
        $t = $this->tarea(); $s = $this->subtarea($t); $this->elemento($t, $s); $this->subtarea($t);
        app(SeguimientoServicio::class)->comentar($t->fresh(), $this->miembro, $this->datos($t, ['contenido' => 'Comentario de prueba']));
        $otro = app(OrganizacionServicio::class)->crearTablero($this->proyecto, $this->admin, ['nombre' => 'Privado']);
        $this->tarea($otro);
        $panel = app(PanelServicio::class);
        $this->assertSame(1, $panel->consultar($this->proyecto, $this->miembro)['resumen']['total']);
        $this->assertSame(2, $panel->consultar($this->proyecto, $this->admin)['resumen']['total']);
        $this->actingAs($this->miembro)->get(route('panel.show', $this->proyecto))->assertOk()->assertDontSee('Privado');
    }

    public function test_panel_fechas_ayer_hoy_nula_y_cerrada(): void
    {
        $ayer = now('America/Guatemala')->subDay()->toDateString(); $hoy = now('America/Guatemala')->toDateString();
        $this->tarea(null, $ayer); $this->tarea(null, $hoy); $this->tarea();
        $cerrada = $this->tarea(null, $ayer);
        $this->ir($this->ir($cerrada, 'EN_REVISION', $this->miembro), 'COMPLETADO', $this->admin);
        $r = app(PanelServicio::class)->consultar($this->proyecto, $this->admin)['resumen'];
        $this->assertSame(['total' => 4, 'completadas' => 1, 'en_progreso' => 3, 'vencidas' => 1], $r);
    }

    public function test_panel_proyecto_vacio_muestra_ceros(): void
    {
        $vacio = Proyecto::create(['nombre' => 'Vacio', 'id_creador' => $this->admin->id_usuario]);
        $this->actingAs($this->admin)->get(route('panel.show', $vacio))->assertOk()
            ->assertViewHas('resumen', fn ($r) => $r === ['total' => 0, 'completadas' => 0, 'en_progreso' => 0, 'vencidas' => 0]);
    }

    public function test_fallo_de_actividad_revierte_subtarea(): void
    {
        $t = $this->tarea(); $n = $t->actividades()->count();
        ActividadTarea::creating(function (): void { throw new \RuntimeException('Fallo controlado de prueba'); });
        try {
            try {
                app(SeguimientoServicio::class)->crearSubtarea($t, $this->miembro, $this->datos($t, ['titulo' => 'Se revierte']));
                $this->fail('La operacion debio fallar.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Fallo controlado de prueba', $e->getMessage());
            }
        } finally {
            ActividadTarea::flushEventListeners();
        }
        $this->assertSame(0, $t->subtareas()->count()); $this->assertSame($n, $t->actividades()->count());
    }
}
