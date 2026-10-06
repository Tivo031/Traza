<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ValidacionAcceso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:6,1')->only('sendResetLinkEmail');
    }

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        ValidacionAcceso::normalizarCorreo($request);
        $datos = $request->validate(['correo' => ['required', 'string', 'email', 'max:254']]);
        Password::broker('usuarios')->sendResetLink(['correo' => $datos['correo'], 'activo' => 1]);
        // Misma respuesta para cuenta inexistente, inactiva o solicitud repetida.
        return back()->with('status', 'Si el correo corresponde a una cuenta activa, recibirás un enlace para restablecer la contraseña.');
    }
}
