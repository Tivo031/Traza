<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\OrganizacionServicio;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        return view('usuarios.index', ['usuarios' => Usuario::with('rol')->orderBy('nombre')->paginate(15)]);
    }

    public function edit(Usuario $usuario): View
    {
        $usuario->load('rol');
        return view('usuarios.formulario', compact('usuario'));
    }

    public function update(Request $request, Usuario $usuario): RedirectResponse
    {
        if (is_string($request->input('correo'))) {
            $request->merge(['correo' => mb_strtolower(trim($request->input('correo')))]);
        }
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'correo' => ['required', 'string', 'email:rfc', 'max:254', Rule::unique('usuarios', 'correo')->ignore($usuario)],
            'id_usuario' => ['prohibited'], 'id_rol' => ['prohibited'], 'activo' => ['prohibited'],
            'contrasena' => ['prohibited'], 'token_recordatorio' => ['prohibited'],
            'fecha_creacion' => ['prohibited'], 'fecha_actualizacion' => ['prohibited'],
            'fecha_verificacion_correo' => ['prohibited'],
        ]);
        try {
            $usuario->nombre = $datos['nombre'];
            if ($usuario->correo !== $datos['correo']) {
                $usuario->fecha_verificacion_correo = null;
            }
            $usuario->correo = $datos['correo'];
            $usuario->save();
        } catch (QueryException $error) {
            if ((int) ($error->errorInfo[1] ?? 0) === 1062
                && str_contains($error->getMessage(), 'cu_usuarios_correo')) {
                throw ValidationException::withMessages(['correo' => 'Ese correo ya pertenece a otra cuenta.']);
            }
            throw $error;
        }
        return redirect()->route('usuarios.index')->with('status', 'Datos del usuario actualizados.');
    }

    public function estado(Request $request, Usuario $usuario, OrganizacionServicio $servicio): RedirectResponse
    {
        $datos = $request->validate(['activo' => ['required', 'boolean']], [
            'activo.required' => 'Indica el estado de la cuenta.', 'activo.boolean' => 'El estado debe ser 0 o 1.',
        ]);
        $activo = (bool) $datos['activo'];
        $servicio->cambiarEstadoUsuario($usuario, $activo);
        if (! $activo && $request->user()->id_usuario === $usuario->id_usuario) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('status', 'Tu cuenta fue desactivada y la sesión se cerró.');
        }
        return redirect()->route('usuarios.index')->with('status', $activo ? 'Cuenta activada.' : 'Cuenta desactivada; historial conservado.');
    }
}
