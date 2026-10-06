<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Support\ValidacionAcceso;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:6,1')->only('reset');
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'correo' => is_string($request->query('correo')) ? $request->query('correo') : '',
        ]);
    }

    public function reset(Request $request)
    {
        ValidacionAcceso::normalizarCorreo($request);
        $datos = $request->validate([
            'token' => ['required', 'string'],
            'correo' => ['required', 'string', 'email', 'max:254'],
            'contrasena' => ValidacionAcceso::reglasContrasena(),
            'confirmacion_contrasena' => ['required', 'string', 'same:contrasena'],
        ]);
        $estado = Password::broker('usuarios')->reset([
            'correo' => $datos['correo'], 'activo' => 1, 'token' => $datos['token'],
            'password' => $datos['contrasena'],
        ], function (Usuario $usuario, string $contrasena): void {
            $usuario->forceFill([
                'contrasena' => Hash::make($contrasena),
                'token_recordatorio' => Str::random(60),
            ])->save();
            event(new PasswordReset($usuario));
        });
        if ($estado !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'correo' => 'El enlace no es válido, ya fue usado o venció. Solicita uno nuevo.',
            ]);
        }
        return redirect()->route('login')->with('status', 'Contraseña actualizada. Inicia sesión con tu nueva clave.');
    }
}
