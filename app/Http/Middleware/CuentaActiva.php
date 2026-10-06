<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['correo' => 'Tu cuenta no tiene acceso activo.']);
        }
        $respuesta = $next($request);
        $respuesta->headers->set('Cache-Control', 'no-store, private');
        return $respuesta;
    }
}
