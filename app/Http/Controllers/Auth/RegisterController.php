<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Usuario;
use App\Support\ValidacionAcceso;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:6,1')->only('register');
    }

    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        ValidacionAcceso::normalizarCorreo($request);
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'correo' => ['required', 'string', 'email', 'max:254', 'unique:usuarios,correo'],
            'contrasena' => ValidacionAcceso::reglasContrasena(),
            'confirmacion_contrasena' => ['required', 'string', 'same:contrasena'],
            'id_rol' => ['prohibited'], 'activo' => ['prohibited'],
            'fecha_creacion' => ['prohibited'], 'fecha_actualizacion' => ['prohibited'],
        ]);
        $rol = Rol::where('codigo', 'MIEMBRO')->first();
        if (! $rol) {
            throw ValidationException::withMessages(['correo' => 'Falta cargar los catálogos. Ejecuta los seeders antes de registrar usuarios.']);
        }
        $usuario = new Usuario();
        $usuario->forceFill([
            'id_rol' => $rol->id_rol, 'nombre' => $datos['nombre'],
            'correo' => $datos['correo'], 'contrasena' => Hash::make($datos['contrasena']),
            'activo' => 1,
        ]);
        try {
            $usuario->save();
        } catch (UniqueConstraintViolationException $error) {
            throw ValidationException::withMessages(['correo' => 'Este correo ya está registrado.']);
        }
        event(new Registered($usuario));
        // El registro no asigna tableros ni privilegios y vuelve al acceso.
        return redirect()->route('login')->with('status', 'Cuenta creada. Ya puedes iniciar sesión.');
    }
}
