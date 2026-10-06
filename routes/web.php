<?php

use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\TableroController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Se conserva el Auth de la fase 1, sin regenerar sus controladores.
Auth::routes(['verify' => false, 'confirm' => false]);
Route::redirect('/', '/proyectos');
Route::redirect('/home', '/proyectos')->name('home');

Route::middleware(['auth', 'cuenta.activa', 'auth.session'])->group(function (): void {
    Route::get('/proyectos', [ProyectoController::class, 'index'])->name('proyectos.index');

    Route::middleware('administrador')->group(function (): void {
        Route::get('/proyectos/crear', [ProyectoController::class, 'create'])->name('proyectos.create');
        Route::post('/proyectos', [ProyectoController::class, 'store'])->name('proyectos.store');
        Route::get('/proyectos/{proyecto}/editar', [ProyectoController::class, 'edit'])
            ->whereNumber('proyecto')->name('proyectos.edit');
        Route::put('/proyectos/{proyecto}', [ProyectoController::class, 'update'])
            ->whereNumber('proyecto')->name('proyectos.update');

        Route::get('/proyectos/{proyecto}/tableros/crear', [TableroController::class, 'create'])
            ->whereNumber('proyecto')->name('tableros.create');
        Route::post('/proyectos/{proyecto}/tableros', [TableroController::class, 'store'])
            ->whereNumber('proyecto')->name('tableros.store');
        Route::get('/tableros/{tablero}/editar', [TableroController::class, 'edit'])
            ->whereNumber('tablero')->name('tableros.edit');
        Route::put('/tableros/{tablero}', [TableroController::class, 'update'])
            ->whereNumber('tablero')->name('tableros.update');

        Route::get('/tableros/{tablero}/miembros', [AsignacionController::class, 'index'])
            ->whereNumber('tablero')->name('asignaciones.index');
        Route::post('/tableros/{tablero}/miembros', [AsignacionController::class, 'store'])
            ->whereNumber('tablero')->name('asignaciones.store');
        Route::patch('/tableros/{tablero}/miembros/{usuario}', [AsignacionController::class, 'update'])
            ->whereNumber(['tablero', 'usuario'])->name('asignaciones.update');

        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/{usuario}/editar', [UsuarioController::class, 'edit'])
            ->whereNumber('usuario')->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])
            ->whereNumber('usuario')->name('usuarios.update');
        Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'estado'])
            ->whereNumber('usuario')->name('usuarios.estado');
    });

    // Las consultas de Miembro comprueban la pertenencia en el servidor.
    Route::get('/proyectos/{proyecto}', [ProyectoController::class, 'show'])
        ->whereNumber('proyecto')->name('proyectos.show');
    Route::get('/tableros/{tablero}', [TableroController::class, 'show'])
        ->whereNumber('tablero')->name('tableros.show');
});
