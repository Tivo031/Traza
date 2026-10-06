<?php

use App\Http\Middleware\CuentaActiva;
use App\Http\Middleware\SoloAdministrador;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'cuenta.activa' => CuentaActiva::class,
            'administrador' => SoloAdministrador::class,
        ]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/proyectos');
        // La contrasena nunca se recorta: se comprueba exactamente como se escribio.
        $middleware->trimStrings(except: ['contrasena', 'confirmacion_contrasena']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // No conservar secretos en old() al producirse errores de validacion.
        $exceptions->dontFlash(['contrasena', 'confirmacion_contrasena', 'token']);
    })->create();
