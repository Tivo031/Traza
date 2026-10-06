<?php

namespace App\Services;

use App\Models\{ActividadTarea, ColumnaTablero, ElementoVerificacion, Tablero, Tarea, Usuario, UsuarioTablero};
use App\Support\{AccesoOrganizacion, ReglasTarea};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TareaServicio
{
    private const CAMPOS = ['titulo', 'descripcion', 'criterio_aceptacion', 'id_prioridad',
        'id_tipo_tarea', 'id_categoria', 'fecha_inicio', 'fecha_limite'];

    public function crear(Tablero $tablero, Usuario $actor, array $datos): Tarea
    {
        return DB::transaction(function () use ($tablero, $actor, $datos): Tarea {
            $idResponsable = ! empty($datos['id_responsable']) ? (int) $datos['id_responsable'] : null;
            [$actual, $personas, $tableroActual] = $this->bloquearContexto($tablero, $actor, [$idResponsable]);
            if ($idResponsable !== null) $this->exigirResponsable($personas, $tableroActual, $idResponsable);
            $columna = $this->columnaEstado($tableroActual, 'POR_HACER');
            $tarea = Tarea::create(array_merge($this->contenido($datos), [
                'id_columna' => $columna->id_columna, 'id_creador' => $actual->id_usuario,
                'id_responsable' => null, 'posicion' => $this->siguientePosicion($columna),
            ]));
            $this->actividad($tarea, $actual, 'CREACION_TAREA');
            if ($idResponsable !== null) {
                $this->asignar($tarea, $actual, $idResponsable);
                $destino = $this->columnaEstado($tableroActual, 'EN_PROGRESO');
                $tarea->id_columna = $destino->id_columna;
                $tarea->posicion = $this->siguientePosicion($destino);
                $tarea->save();
                $this->actividad($tarea, $actual, 'CAMBIO_ESTADO', null, $columna->id_estado, $destino->id_estado);
            }
            return $tarea->fresh();
        }, 3);
    }

    public function editar(Tarea $referencia, Usuario $actor, array $datos): Tarea
    {
        return DB::transaction(function () use ($referencia, $actor, $datos): Tarea {
            $idResponsable = ! empty($datos['id_responsable']) ? (int) $datos['id_responsable'] : null;
            [$actual, $personas, $tablero] = $this->bloquearContexto(
                $referencia->columna->tablero, $actor, [$referencia->id_responsable, $idResponsable]);
            $tarea = $this->bloquearTarea($referencia, $datos, $tablero);
            $origen = $tarea->columna;
            if (! in_array($origen->estado->codigo, ReglasTarea::EDITABLES, true)) {
                $this->error('La tarea solo se edita en Por hacer o En progreso.');
            }
            if ($origen->estado->codigo === 'EN_PROGRESO' && $idResponsable === null) {
                $this->error('En progreso no puede quedar sin responsable.', 'id_responsable');
            }
            if ($idResponsable !== null) $this->exigirResponsable($personas, $tablero, $idResponsable);
            $tarea->fill($this->contenido($datos));
            $cambiados = array_keys($tarea->getDirty());
            if ($cambiados !== []) {
                $tarea->save();
                $this->actividad($tarea, $actual, 'EDICION_TAREA', 'Campos actualizados: '.implode(', ', $cambiados).'.');
            }
            if ($idResponsable !== null && (int) $tarea->id_responsable !== $idResponsable) {
                $this->asignar($tarea, $actual, $idResponsable);
            }
            if ($origen->estado->codigo === 'POR_HACER' && $idResponsable !== null) {
                $destino = $this->columnaEstado($tablero, 'EN_PROGRESO');
                $tarea->id_columna = $destino->id_columna;
                $tarea->posicion = $this->siguientePosicion($destino);
                $tarea->save();
                $this->actividad($tarea, $actual, 'CAMBIO_ESTADO', null, $origen->id_estado, $destino->id_estado);
            }
            return $tarea->fresh();
        }, 3);
    }

    public function mover(Tarea $referencia, Usuario $actor, array $datos): Tarea
    {
        return DB::transaction(function () use ($referencia, $actor, $datos): Tarea {
            $elegido = ! empty($datos['id_responsable']) ? (int) $datos['id_responsable'] : null;
            [$actual, $personas, $tablero] = $this->bloquearContexto(
                $referencia->columna->tablero, $actor, [$referencia->id_responsable, $elegido]);
            $tarea = $this->bloquearTarea($referencia, $datos, $tablero);
            $origen = $tarea->columna;
            // Consultar exclusivamente dentro del tablero evita traslados no autorizados.
            $destino = $tablero->columnas()->with('estado')->whereKey($datos['id_columna_destino'])->first();
            if (! $destino) $this->error('El destino no pertenece al mismo tablero.', 'id_columna_destino');
            $iniciar = $origen->estado->codigo === 'POR_HACER' && $destino->estado->codigo === 'EN_PROGRESO';
            if ($elegido !== null && ! $iniciar) {
                $this->error('El responsable solo se elige al iniciar; para sustituirlo usa Editar tarea.', 'id_responsable');
            }
            $idResponsable = $iniciar ? $elegido : ($tarea->id_responsable ? (int) $tarea->id_responsable : null);
            $valido = $idResponsable !== null && $this->responsableValido($personas, $tablero, $idResponsable);
            // Las mutaciones futuras de subtareas deberan bloquear primero la tarea, igual que este servicio.
            $subtareas = $tarea->subtareas()->orderBy('id_subtarea')->lockForUpdate()->get();
            $pendientes = $subtareas->contains(fn ($s) => $s->fecha_finalizacion === null)
                || ElementoVerificacion::whereIn('id_subtarea', $subtareas->modelKeys())->where('completado', 0)
                    ->orderBy('id_elemento')->lockForUpdate()->first() !== null;
            $observacion = trim((string) ($datos['observacion'] ?? ''));
            $error = ReglasTarea::errorMovimiento($origen->estado->codigo, $destino->estado->codigo,
                (int) $tarea->id_responsable === (int) $actual->id_usuario, $actual->esAdministrador(),
                $valido, trim($tarea->criterio_aceptacion) !== '', $pendientes, $observacion);
            if ($error !== null) $this->error($error);
            $mismaColumna = (int) $origen->id_columna === (int) $destino->id_columna;
            if ($iniciar) $this->asignar($tarea, $actual, $idResponsable);
            // En QA y Completado se agregan al final; sus tarjetas no se reordenan manualmente.
            $posicion = in_array($destino->estado->codigo, ReglasTarea::EDITABLES, true)
                && isset($datos['posicion_destino']) ? (int) $datos['posicion_destino'] : null;
            $tarea->id_columna = $destino->id_columna;
            $tarea->save();
            if ($destino->estado->codigo === 'COMPLETADO') {
                // Actualizacion por consulta: evita convertir una expresion SQL con el cast datetime.
                Tarea::whereKey($tarea->id_tarea)->update(['fecha_cierre' => DB::raw('CURRENT_TIMESTAMP')]);
            }
            $this->ordenar($tarea, $destino, $posicion);
            if (! $mismaColumna) $this->normalizar($origen);
            $this->actividad($tarea, $actual, $mismaColumna ? 'REORDENAMIENTO_TAREA' : 'CAMBIO_ESTADO',
                $mismaColumna ? 'Orden de tarjeta actualizado.' : ($observacion !== '' ? $observacion : null),
                $mismaColumna ? null : $origen->id_estado, $mismaColumna ? null : $destino->id_estado);
            return $tarea->fresh();
        }, 3);
    }

    private function bloquearContexto(Tablero $tablero, Usuario $actor, array $otros): array
    {
        // Orden compatible con OrganizacionServicio: usuarios por ID -> tablero -> membresias -> tarea.
        // Serializar escrituras por tablero simplifica el orden para un equipo pequeno.
        $ids = array_values(array_unique(array_filter(array_merge([$actor->id_usuario], $otros))));
        $personas = Usuario::whereKey($ids)->orderBy('id_usuario')->lockForUpdate()->get()->keyBy('id_usuario');
        $actual = $personas->get($actor->id_usuario);
        abort_unless($actual && $actual->activo, 403);
        $actual->load('rol');
        $tableroActual = Tablero::whereKey($tablero->id_tablero)->lockForUpdate()->firstOrFail();
        UsuarioTablero::where('id_tablero', $tableroActual->id_tablero)->whereIn('id_usuario', $ids)
            ->orderBy('id_usuario')->lockForUpdate()->get();
        AccesoOrganizacion::comprobarTablero($actual, $tableroActual);
        return [$actual, $personas, $tableroActual];
    }

    private function bloquearTarea(Tarea $referencia, array $datos, Tablero $tablero): Tarea
    {
        $tarea = Tarea::whereKey($referencia->id_tarea)->lockForUpdate()->firstOrFail();
        $tarea->load('columna.estado');
        abort_unless((int) $tarea->columna->id_tablero === (int) $tablero->id_tablero, 409,
            'La ubicación de la tarea cambió. Recarga el tablero.');
        // Leer la actividad con bloqueo realiza una lectura actual, no una instantanea antigua de MySQL.
        $ultima = $tarea->actividades()->orderByDesc('id_actividad')->lockForUpdate()->first();
        abort_if((int) $datos['id_columna_esperada'] !== (int) $tarea->id_columna
            || (int) $datos['revision_esperada'] !== (int) ($ultima?->id_actividad ?? 0), 409,
            'La tarea cambió desde que la abriste. Recarga y revisa los datos antes de volver a guardar.');
        return $tarea;
    }

    private function responsableValido(Collection $personas, Tablero $tablero, int $id): bool
    {
        return (bool) $personas->get($id)?->activo && UsuarioTablero::where('id_tablero', $tablero->id_tablero)
            ->where('id_usuario', $id)->where('activo', 1)->exists();
    }

    private function exigirResponsable(Collection $personas, Tablero $tablero, int $id): void
    {
        if (! $this->responsableValido($personas, $tablero, $id)) {
            $this->error('Elige un responsable activo y asignado a este tablero.', 'id_responsable');
        }
    }

    private function asignar(Tarea $tarea, Usuario $actor, int $id): void
    {
        $anterior = $tarea->id_responsable;
        $tarea->id_responsable = $id;
        $tarea->save();
        $this->actividad($tarea, $actor, 'ASIGNACION_RESPONSABLE',
            'Responsable: '.($anterior ? '#'.$anterior : 'sin asignar').' -> #'.$id.'.');
    }

    private function columnaEstado(Tablero $tablero, string $codigo): ColumnaTablero
    {
        $columna = $tablero->columnas()->with('estado')->whereHas('estado', fn ($q) => $q->where('codigo', $codigo))->first();
        if (! $columna) $this->error('Falta una columna del flujo. Ejecuta el verificador de organización.');
        return $columna;
    }

    private function contenido(array $datos): array
    {
        $salida = array_intersect_key($datos, array_flip(self::CAMPOS));
        $salida['fecha_inicio'] = $datos['fecha_inicio'] ?? null;
        $salida['fecha_limite'] = $datos['fecha_limite'] ?? null;
        return $salida;
    }

    private function siguientePosicion(ColumnaTablero $columna): int
    {
        $ultima = $columna->tareas()->orderByDesc('posicion')->orderByDesc('id_tarea')->lockForUpdate()->first();
        return $ultima === null ? 0 : (int) $ultima->posicion + 1;
    }

    private function ordenar(Tarea $tarea, ColumnaTablero $columna, ?int $posicion): void
    {
        $ids = $columna->tareas()->where('id_tarea', '!=', $tarea->id_tarea)
            ->orderBy('posicion')->orderBy('id_tarea')->lockForUpdate()->pluck('id_tarea')->all();
        array_splice($ids, $posicion === null ? count($ids) : min($posicion, count($ids)), 0, [$tarea->id_tarea]);
        $this->guardarOrden($ids);
    }

    private function normalizar(ColumnaTablero $columna): void
    {
        $this->guardarOrden($columna->tareas()->orderBy('posicion')->orderBy('id_tarea')->lockForUpdate()->pluck('id_tarea')->all());
    }

    private function guardarOrden(array $ids): void
    {
        foreach ($ids as $posicion => $id) {
            Tarea::whereKey($id)->where('posicion', '!=', $posicion)->update(['posicion' => $posicion]);
        }
    }

    private function actividad(Tarea $tarea, Usuario $actor, string $codigo, ?string $nota = null,
        ?int $anterior = null, ?int $nuevo = null): void
    {
        ActividadTarea::create(['id_tarea' => $tarea->id_tarea, 'id_actor' => $actor->id_usuario,
            'codigo_accion' => $codigo, 'id_estado_anterior' => $anterior,
            'id_estado_nuevo' => $nuevo, 'observacion' => $nota]);
    }

    private function error(string $mensaje, string $campo = 'tarea'): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
