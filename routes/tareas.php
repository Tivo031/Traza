<?php

use App\Http\Controllers\TareaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa', 'auth.session'])->group(function (): void {
    Route::get('/tableros/{tablero}/tareas/crear', [TareaController::class, 'create'])
        ->whereNumber('tablero')->name('tareas.create');
    Route::post('/tableros/{tablero}/tareas', [TareaController::class, 'store'])
        ->whereNumber('tablero')->name('tareas.store');
    Route::get('/tareas/{tarea}', [TareaController::class, 'show'])->whereNumber('tarea')->name('tareas.show');
    Route::get('/tareas/{tarea}/editar', [TareaController::class, 'edit'])->whereNumber('tarea')->name('tareas.edit');
    Route::put('/tareas/{tarea}', [TareaController::class, 'update'])->whereNumber('tarea')->name('tareas.update');
    Route::patch('/tareas/{tarea}/mover', [TareaController::class, 'mover'])->whereNumber('tarea')->name('tareas.mover');
});
