<?php

namespace App\Http\Controllers;

use App\Http\Requests\{EstadoSubtareaRequest, GuardarSubtareaRequest};
use App\Models\{Subtarea, Tarea};
use App\Services\SeguimientoServicio;
use Illuminate\Http\RedirectResponse;

class SubtareaController extends Controller
{
    public function store(GuardarSubtareaRequest $request, Tarea $tarea, SeguimientoServicio $servicio): RedirectResponse
    {
        $servicio->crearSubtarea($tarea, $request->user(), $request->validated());
        return redirect()->route('tareas.show', $tarea)->withFragment('subtareas')->with('status', 'Subtarea creada.');
    }

    public function update(GuardarSubtareaRequest $request, Tarea $tarea, Subtarea $subtarea, SeguimientoServicio $servicio): RedirectResponse
    {
        $servicio->editarSubtarea($tarea, $subtarea, $request->user(), $request->validated());
        return redirect()->route('tareas.show', $tarea)->withFragment('subtareas')->with('status', 'Subtarea guardada.');
    }

    public function estado(EstadoSubtareaRequest $request, Tarea $tarea, Subtarea $subtarea, SeguimientoServicio $servicio): RedirectResponse
    {
        $servicio->estadoSubtarea($tarea, $subtarea, $request->user(), $request->validated());
        return redirect()->route('tareas.show', $tarea)->withFragment('subtareas')->with('status', 'Estado de subtarea guardado.');
    }
}
