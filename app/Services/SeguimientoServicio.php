<?php

namespace App\Services;

use App\Models\{ActividadTarea, ComentarioTarea, ElementoVerificacion, Subtarea, Tablero, Tarea, Usuario, UsuarioTablero};
use App\Support\{AccesoOrganizacion, ReglasTarea};
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeguimientoServicio
{
    public function crearSubtarea(Tarea $referencia, Usuario $actor, array $datos): void
    {
        $this->ejecutar($referencia, $actor, $datos, function (Tarea $tarea, Usuario $actual) use ($datos): void {
            $subtarea = $tarea->subtareas()->create([
                'titulo' => $datos['titulo'], 'descripcion' => $datos['descripcion'] ?? null,
                'posicion' => $this->siguientePosicion($tarea->subtareas(), 'id_subtarea'),
                'fecha_finalizacion' => null,
            ]);
            $this->actividad($tarea, $actual, 'CREACION_SUBTAREA', 'Subtarea #'.$subtarea->id_subtarea.': '.$subtarea->titulo);
        });
    }

    public function editarSubtarea(Tarea $referencia, Subtarea $subreferencia, Usuario $actor, array $datos): void
    {
        $this->ejecutar($referencia, $actor, $datos, function (Tarea $tarea, Usuario $actual) use ($subreferencia, $datos): void {
            $subtarea = $this->subtarea($tarea, $subreferencia);
            $subtarea->fill(['titulo' => $datos['titulo'], 'descripcion' => $datos['descripcion'] ?? null]);
            $cambio = $subtarea->isDirty();
            $subtarea->save();
            $orden = $this->ordenar($tarea->subtareas(), $subtarea->id_subtarea, (int) $datos['posicion'], 'id_subtarea');
            if ($cambio || $orden) {
                $this->actividad($tarea, $actual, 'EDICION_SUBTAREA', 'Texto u orden de subtarea #'.$subtarea->id_subtarea.' actualizado.');
            }
        });
    }

    public function estadoSubtarea(Tarea $referencia, Subtarea $subreferencia, Usuario $actor, array $datos): void
    {
        $this->ejecutar($referencia, $actor, $datos, function (Tarea $tarea, Usuario $actual) use ($subreferencia, $datos): void {
            $subtarea = $this->subtarea($tarea, $subreferencia);
            $completar = (bool) $datos['completada'];
            $elementos = $subtarea->elementos()->orderBy('id_elemento')->lockForUpdate()->get();
            if ($completar && $elementos->contains(fn ($e) => ! $e->completado)) {
                $this->error('Completa todos los elementos antes de finalizar la subtarea.');
            }
            if ($completar === ($subtarea->fecha_finalizacion !== null)) return;
            // MySQL genera la hora, igual que en el cierre de tareas. Evita aplicar un cast a una expresion SQL.
            Subtarea::whereKey($subtarea->id_subtarea)->update([
                'fecha_finalizacion' => $completar ? DB::raw('CURRENT_TIMESTAMP') : null,
            ]);
            $this->actividad($tarea, $actual, $completar ? 'FINALIZACION_SUBTAREA' : 'REAPERTURA_SUBTAREA',
                'Subtarea #'.$subtarea->id_subtarea.($completar ? ' finalizada.' : ' devuelta a pendiente.'));
        });
    }

    public function crearElemento(Tarea $referencia, Subtarea $subreferencia, Usuario $actor, array $datos): void
    {
        $this->ejecutar($referencia, $actor, $datos, function (Tarea $tarea, Usuario $actual) use ($subreferencia, $datos): void {
            $subtarea = $this->subtarea($tarea, $subreferencia);
            $elemento = $subtarea->elementos()->create(['titulo' => $datos['titulo'], 'completado' => 0,
                'posicion' => $this->siguientePosicion($subtarea->elementos(), 'id_elemento')]);
            $this->actividad($tarea, $actual, 'CREACION_ELEMENTO',
                'Elemento #'.$elemento->id_elemento.' en subtarea #'.$subtarea->id_subtarea.': '.$elemento->titulo);
            $this->reabrirSiCorresponde($tarea, $subtarea, $actual, $elemento->id_elemento);
        });
    }

    public function editarElemento(Tarea $referencia, Subtarea $subreferencia,
        ElementoVerificacion $elementoreferencia, Usuario $actor, array $datos): void
    {
        $this->ejecutar($referencia, $actor, $datos, function (Tarea $tarea, Usuario $actual)
            use ($subreferencia, $elementoreferencia, $datos): void {
            $subtarea = $this->subtarea($tarea, $subreferencia);
            $elemento = $this->elemento($subtarea, $elementoreferencia);
            $elemento->titulo = $datos['titulo'];
            $cambio = $elemento->isDirty();
            $elemento->save();
            $orden = $this->ordenar($subtarea->elementos(), $elemento->id_elemento, (int) $datos['posicion'], 'id_elemento');
            if ($cambio || $orden) {
                $this->actividad($tarea, $actual, 'EDICION_ELEMENTO',
                    'Texto u orden de elemento #'.$elemento->id_elemento.' en subtarea #'.$subtarea->id_subtarea.' actualizado.');
            }
        });
    }

    public function estadoElemento(Tarea $referencia, Subtarea $subreferencia,
        ElementoVerificacion $elementoreferencia, Usuario $actor, array $datos): void
    {
        $this->ejecutar($referencia, $actor, $datos, function (Tarea $tarea, Usuario $actual)
            use ($subreferencia, $elementoreferencia, $datos): void {
            $subtarea = $this->subtarea($tarea, $subreferencia);
            $elemento = $this->elemento($subtarea, $elementoreferencia);
            $completar = (bool) $datos['completado'];
            if ($elemento->completado !== $completar) {
                $elemento->completado = $completar;
                $elemento->save();
                $this->actividad($tarea, $actual, $completar ? 'MARCADO_ELEMENTO' : 'DESMARCADO_ELEMENTO',
                    'Elemento #'.$elemento->id_elemento.' en subtarea #'.$subtarea->id_subtarea.($completar ? ' completado.' : ' pendiente.'));
            }
            if (! $completar) $this->reabrirSiCorresponde($tarea, $subtarea, $actual, $elemento->id_elemento);
        });
    }

    public function comentar(Tarea $referencia, Usuario $actor, array $datos): void
    {
        $this->ejecutar($referencia, $actor, $datos, function (Tarea $tarea, Usuario $actual) use ($datos): void {
            $comentario = ComentarioTarea::create(['id_tarea' => $tarea->id_tarea,
                'id_usuario' => $actual->id_usuario, 'contenido' => $datos['contenido']]);
            $this->actividad($tarea, $actual, 'COMENTARIO_AGREGADO', 'Comentario #'.$comentario->id_comentario.' publicado.');
        }, true);
    }

    private function ejecutar(Tarea $referencia, Usuario $actor, array $datos, Closure $trabajo, bool $permiteRevision = false): void
    {
        DB::transaction(function () use ($referencia, $actor, $datos, $trabajo, $permiteRevision): void {
            // Mismo orden de bloqueo de las fases anteriores: usuario -> tablero -> membresia -> tarea -> subtarea -> elemento.
            $actual = Usuario::whereKey($actor->id_usuario)->lockForUpdate()->firstOrFail();
            abort_unless($actual->activo, 403);
            $actual->load('rol');
            $tablero = Tablero::whereKey($referencia->columna->id_tablero)->lockForUpdate()->firstOrFail();
            UsuarioTablero::where('id_tablero', $tablero->id_tablero)->where('id_usuario', $actual->id_usuario)
                ->lockForUpdate()->get();
            AccesoOrganizacion::comprobarTablero($actual, $tablero);
            $tarea = Tarea::whereKey($referencia->id_tarea)->lockForUpdate()->firstOrFail();
            $tarea->load('columna.estado');
            abort_unless((int) $tarea->columna->id_tablero === (int) $tablero->id_tablero, 409);
            $ultima = $tarea->actividades()->orderByDesc('id_actividad')->lockForUpdate()->first();
            abort_if((int) $datos['id_columna_esperada'] !== (int) $tarea->id_columna
                || (int) $datos['revision_esperada'] !== (int) ($ultima?->id_actividad ?? 0), 409,
                'La tarea cambió. Recarga su detalle antes de volver a guardar.');
            $codigo = $tarea->columna->estado->codigo;
            if ($codigo === 'COMPLETADO') $this->error('La tarea completada es de solo lectura.');
            if (! in_array($codigo, ReglasTarea::EDITABLES, true)
                && ! ($permiteRevision && $codigo === 'EN_REVISION')) {
                $this->error('Para modificar el trabajo, la tarea debe estar en Por hacer o En progreso.');
            }
            $trabajo($tarea, $actual);
        }, 3);
    }

    private function subtarea(Tarea $tarea, Subtarea $referencia): Subtarea
    {
        // La referencia anidada se comprueba en el servidor: un ID ajeno no basta para editar.
        return $tarea->subtareas()->whereKey($referencia->id_subtarea)->lockForUpdate()->firstOrFail();
    }

    private function elemento(Subtarea $subtarea, ElementoVerificacion $referencia): ElementoVerificacion
    {
        return $subtarea->elementos()->whereKey($referencia->id_elemento)->lockForUpdate()->firstOrFail();
    }

    private function reabrirSiCorresponde(Tarea $tarea, Subtarea $subtarea, Usuario $actual, int $idElemento): void
    {
        if ($subtarea->fecha_finalizacion === null) return;
        $subtarea->fecha_finalizacion = null;
        $subtarea->save();
        $this->actividad($tarea, $actual, 'REAPERTURA_SUBTAREA',
            'Subtarea #'.$subtarea->id_subtarea.' reabierta por elemento pendiente #'.$idElemento.'.');
    }

    private function siguientePosicion($relacion, string $clave): int
    {
        $ultimo = $relacion->orderByDesc('posicion')->orderByDesc($clave)->lockForUpdate()->first();
        return $ultimo === null ? 0 : (int) $ultimo->posicion + 1;
    }

    private function ordenar($relacion, int $id, int $posicion, string $clave): bool
    {
        $filas = $relacion->orderBy('posicion')->orderBy($clave)->lockForUpdate()->get();
        $ids = $filas->modelKeys();
        $otros = array_values(array_filter($ids, fn ($valor) => (int) $valor !== $id));
        array_splice($otros, min($posicion, count($otros)), 0, [$id]);
        if ($ids === $otros) return false;
        foreach ($otros as $orden => $identificador) {
            $filas->firstWhere($clave, $identificador)->update(['posicion' => $orden]);
        }
        return true;
    }

    private function actividad(Tarea $tarea, Usuario $actor, string $codigo, string $observacion): void
    {
        ActividadTarea::create(['id_tarea' => $tarea->id_tarea, 'id_actor' => $actor->id_usuario,
            'codigo_accion' => $codigo, 'id_estado_anterior' => null, 'id_estado_nuevo' => null,
            'observacion' => $observacion]);
    }

    private function error(string $mensaje): never
    {
        throw ValidationException::withMessages(['seguimiento' => $mensaje]);
    }
}
