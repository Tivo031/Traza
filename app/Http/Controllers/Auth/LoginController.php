<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ValidacionAcceso;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/proyectos';
    protected $maxAttempts = 5;
    protected $decayMinutes = 1;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function username(): string
    {
        return 'correo';
    }

    protected function validateLogin(Request $request): void
    {
        ValidacionAcceso::normalizarCorreo($request);
        $request->validate([
            'correo' => ['required', 'string', 'email', 'max:254'],
            'contrasena' => ['required', 'string', 'max:72'],
        ]);
    }

    protected function credentials(Request $request): array
    {
        // password es una clave interna de Auth, no una columna de nuestra base.
        return ['correo' => $request->input('correo'),
            'password' => $request->input('contrasena'), 'activo' => 1];
    }

    protected function attemptLogin(Request $request): bool
    {
        // Evita que una clave mas larga coincida por el limite de bcrypt.
        if (strlen($request->input('contrasena')) > 72) {
            return false;
        }
        return $this->guard()->attempt($this->credentials($request), $request->boolean('recordarme'));
    }
}
