<?php

namespace App\Http\Controllers;

use App\Models\Tablero;
use App\Models\Usuario;
use App\Services\OrganizacionServicio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AsignacionController extends Controller
{
    public function index(Tablero $tablero): View
    {
        $tablero->load('proyecto');
        $asignaciones = $tablero->asignaciones()->with('usuario.rol')->orderByDesc('activo')
            ->orderBy('id_usuario_tablero')->get();
        $usuariosDisponibles = Usuario::where('activo', 1)
            ->whereDoesntHave('asignaciones', fn ($asignacion) =>
                $asignacion->where('id_tablero', $tablero->id_tablero)->where('activo', 1))
            ->orderBy('nombre')->get();
        return view('asignaciones.index', compact('tablero', 'asignaciones', 'usuariosDisponibles'));
    }

    public function store(Request $request, Tablero $tablero, OrganizacionServicio $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'id_usuario' => ['required', 'integer', Rule::exists('usuarios', 'id_usuario')->where('activo', 1)],
            'id_tablero' => ['prohibited'], 'activo' => ['prohibited'],
        ], [
            'id_usuario.required' => 'Selecciona un usuario.',
            'id_usuario.integer' => 'Selecciona un usuario válido.',
            'id_usuario.exists' => 'La cuenta seleccionada no existe o está inactiva.',
        ]);
        $servicio->concederAcceso($tablero, Usuario::findOrFail($datos['id_usuario']));
        return redirect()->route('asignaciones.index', $tablero)->with('status', 'Asignación activa, sin duplicar registros.');
    }

    public function update(Request $request, Tablero $tablero, Usuario $usuario, OrganizacionServicio $servicio): RedirectResponse
    {
        $datos = $request->validate(['activo' => ['required', 'boolean']], [
            'activo.required' => 'Indica la acción de la asignación.',
            'activo.boolean' => 'El estado de la asignación debe ser 0 o 1.',
        ]);
        abort_unless($tablero->asignaciones()->where('id_usuario', $usuario->id_usuario)->exists(), 404);
        if ((bool) $datos['activo']) {
            $servicio->concederAcceso($tablero, $usuario);
            $mensaje = 'Acceso al tablero reactivado.';
        } else {
            $servicio->retirarAcceso($tablero, $usuario);
            $mensaje = 'Acceso retirado. La asignación y el historial se conservan.';
        }
        return redirect()->route('asignaciones.index', $tablero)->with('status', $mensaje);
    }
}
