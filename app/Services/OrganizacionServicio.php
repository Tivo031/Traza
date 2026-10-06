<?php

namespace App\Services;

use App\Models\EstadoTarea;
use App\Models\Proyecto;
use App\Models\Rol;
use App\Models\Tablero;
use App\Models\Tarea;
use App\Models\Usuario;
use App\Models\UsuarioTablero;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizacionServicio
{
    public const ESTADOS = ['POR_HACER', 'EN_PROGRESO', 'EN_REVISION', 'COMPLETADO'];

    public function crearTablero(Proyecto $proyecto, Usuario $creador, array $datos): Tablero
    {
        abort_unless($creador->esAdministrador(), 403);
        try {
            return DB::transaction(function () use ($proyecto, $creador, $datos): Tablero {
                $estados = EstadoTarea::whereIn('codigo', self::ESTADOS)->pluck('id_estado', 'codigo');
                if ($estados->count() !== count(self::ESTADOS)) {
                    throw ValidationException::withMessages([
                        'nombre' => 'Faltan estados del catálogo. Revisa la carga inicial antes de crear un tablero.',
                    ]);
                }
                $tablero = Tablero::create([
                    'id_proyecto' => $proyecto->id_proyecto,
                    'id_creador' => $creador->id_usuario,
                    'nombre' => $datos['nombre'],
                    'descripcion' => $datos['descripcion'] ?? null,
                ]);
                foreach (self::ESTADOS as $posicion => $codigo) {
                    $tablero->columnas()->create(['id_estado' => $estados[$codigo], 'posicion' => $posicion]);
                }
                // No se inventan tareas ni se asignan miembros sin que el administrador lo indique.
                return $tablero;
            }, 3);
        } catch (QueryException $error) {
            $this->traducirDuplicadoTablero($error);
            throw $error;
        }
    }

    public function editarTablero(Tablero $tablero, array $datos): void
    {
        try {
            $tablero->update(['nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion'] ?? null]);
        } catch (QueryException $error) {
            $this->traducirDuplicadoTablero($error);
            throw $error;
        }
    }

    private function traducirDuplicadoTablero(QueryException $error): void
    {
        if ((int) ($error->errorInfo[1] ?? 0) === 1062
            && str_contains($error->getMessage(), 'cu_tableros_proyecto_nombre')) {
            throw ValidationException::withMessages(['nombre' => 'Ya existe un tablero con ese nombre en este proyecto.']);
        }
    }

    public function concederAcceso(Tablero $tablero, Usuario $usuario): UsuarioTablero
    {
        return DB::transaction(function () use ($tablero, $usuario): UsuarioTablero {
            // Orden compartido: usuario -> tablero -> membresía -> tareas.
            $usuarioActual = Usuario::whereKey($usuario->id_usuario)->lockForUpdate()->firstOrFail();
            if (! $usuarioActual->activo) {
                throw ValidationException::withMessages(['id_usuario' => 'Activa la cuenta antes de asignarla a un tablero.']);
            }
            Tablero::whereKey($tablero->id_tablero)->lockForUpdate()->firstOrFail();
            $asignacion = UsuarioTablero::where('id_tablero', $tablero->id_tablero)
                ->where('id_usuario', $usuarioActual->id_usuario)->lockForUpdate()->first();
            if ($asignacion) {
                if (! $asignacion->activo) {
                    $asignacion->activo = true;
                    $asignacion->save();
                }
                return $asignacion;
            }
            return UsuarioTablero::create([
                'id_tablero' => $tablero->id_tablero, 'id_usuario' => $usuarioActual->id_usuario, 'activo' => 1,
            ]);
        }, 3);
    }

    public function retirarAcceso(Tablero $tablero, Usuario $usuario): void
    {
        DB::transaction(function () use ($tablero, $usuario): void {
            Usuario::whereKey($usuario->id_usuario)->lockForUpdate()->firstOrFail();
            Tablero::whereKey($tablero->id_tablero)->lockForUpdate()->firstOrFail();
            $asignacion = UsuarioTablero::where('id_tablero', $tablero->id_tablero)
                ->where('id_usuario', $usuario->id_usuario)->lockForUpdate()->firstOrFail();
            if ($this->tieneTareasAbiertas($usuario->id_usuario, $tablero->id_tablero)) {
                throw ValidationException::withMessages([
                    'asignacion' => 'Reasigna las tareas abiertas de esta persona antes de retirar su acceso al tablero.',
                ]);
            }
            $asignacion->activo = false;
            $asignacion->save(); // No eliminar la fila ni alterar responsables históricos.
        }, 3);
    }

    public function cambiarEstadoUsuario(Usuario $usuario, bool $activo): void
    {
        DB::transaction(function () use ($usuario, $activo): void {
            $idRolAdministrador = Rol::where('codigo', 'ADMINISTRADOR')->firstOrFail()->id_rol;
            // Bloqueo de todos los administradores: dos solicitudes no pueden desactivar al último a la vez.
            $cuentas = Usuario::where(function ($consulta) use ($usuario, $idRolAdministrador): void {
                $consulta->where('id_rol', $idRolAdministrador)->orWhere('id_usuario', $usuario->id_usuario);
            })->orderBy('id_usuario')->lockForUpdate()->get();
            $actual = $cuentas->firstWhere('id_usuario', $usuario->id_usuario);
            abort_unless($actual, 404);
            if (! $activo) {
                $otroAdministrador = $cuentas->contains(fn (Usuario $cuenta): bool =>
                    $cuenta->id_usuario !== $actual->id_usuario
                    && $cuenta->id_rol === $idRolAdministrador && $cuenta->activo);
                if ($actual->id_rol === $idRolAdministrador && ! $otroAdministrador) {
                    throw ValidationException::withMessages(['activo' => 'Debe permanecer al menos un Administrador activo.']);
                }
                if ($this->tieneTareasAbiertas($actual->id_usuario)) {
                    throw ValidationException::withMessages([
                        'activo' => 'Reasigna todas las tareas abiertas de esta persona antes de desactivar su cuenta.',
                    ]);
                }
            }
            $actual->activo = $activo;
            $actual->save();
        }, 3);
    }

    private function tieneTareasAbiertas(int $idUsuario, ?int $idTablero = null): bool
    {
        // El estado viene de la columna; no se confunde fecha_cierre con la fuente del estado.
        return Tarea::where('id_responsable', $idUsuario)
            ->whereHas('columna', function ($columnas) use ($idTablero): void {
                if ($idTablero !== null) {
                    $columnas->where('id_tablero', $idTablero);
                }
                $columnas->whereHas('estado', fn ($estados) => $estados->where('codigo', '!=', 'COMPLETADO'));
            })->select('id_tarea')->lockForUpdate()->first() !== null;
    }
}
