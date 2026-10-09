<?php

use App\Http\Controllers\{ComentarioController, ElementoVerificacionController, PanelController, SubtareaController};
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'cuenta.activa', 'auth.session'])->group(function (): void {
    Route::post('/tareas/{tarea}/subtareas', [SubtareaController::class, 'store'])
        ->whereNumber('tarea')->name('subtareas.store');
    Route::put('/tareas/{tarea}/subtareas/{subtarea}', [SubtareaController::class, 'update'])
        ->whereNumber(['tarea', 'subtarea'])->name('subtareas.update');
    Route::patch('/tareas/{tarea}/subtareas/{subtarea}/estado', [SubtareaController::class, 'estado'])
        ->whereNumber(['tarea', 'subtarea'])->name('subtareas.estado');
    Route::post('/tareas/{tarea}/subtareas/{subtarea}/elementos', [ElementoVerificacionController::class, 'store'])
        ->whereNumber(['tarea', 'subtarea'])->name('elementos.store');
    Route::put('/tareas/{tarea}/subtareas/{subtarea}/elementos/{elemento}', [ElementoVerificacionController::class, 'update'])
        ->whereNumber(['tarea', 'subtarea', 'elemento'])->name('elementos.update');
    Route::patch('/tareas/{tarea}/subtareas/{subtarea}/elementos/{elemento}/estado', [ElementoVerificacionController::class, 'estado'])
        ->whereNumber(['tarea', 'subtarea', 'elemento'])->name('elementos.estado');
    Route::post('/tareas/{tarea}/comentarios', [ComentarioController::class, 'store'])
        ->whereNumber('tarea')->name('comentarios.store');
    Route::get('/proyectos/{proyecto}/panel', [PanelController::class, 'show'])
        ->whereNumber('proyecto')->name('panel.show');
});
