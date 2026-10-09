<?php

namespace App\Http\Controllers;

use App\Http\Requests\{EstadoElementoRequest, GuardarElementoRequest};
use App\Models\{ElementoVerificacion, Subtarea, Tarea};
use App\Services\SeguimientoServicio;
use Illuminate\Http\RedirectResponse;

class ElementoVerificacionController extends Controller
{
    public function store(GuardarElementoRequest $request, Tarea $tarea, Subtarea $subtarea, SeguimientoServicio $servicio): RedirectResponse
    {
        $servicio->crearElemento($tarea, $subtarea, $request->user(), $request->validated());
        return redirect()->route('tareas.show', $tarea)->withFragment('subtareas')->with('status', 'Elemento agregado. Si estaba finalizada, la subtarea vuelve a pendiente.');
    }

    public function update(GuardarElementoRequest $request, Tarea $tarea, Subtarea $subtarea,
        ElementoVerificacion $elemento, SeguimientoServicio $servicio): RedirectResponse
    {
        $servicio->editarElemento($tarea, $subtarea, $elemento, $request->user(), $request->validated());
        return redirect()->route('tareas.show', $tarea)->withFragment('subtareas')->with('status', 'Elemento guardado.');
    }

    public function estado(EstadoElementoRequest $request, Tarea $tarea, Subtarea $subtarea,
        ElementoVerificacion $elemento, SeguimientoServicio $servicio): RedirectResponse
    {
        $servicio->estadoElemento($tarea, $subtarea, $elemento, $request->user(), $request->validated());
        return redirect()->route('tareas.show', $tarea)->withFragment('subtareas')->with('status', 'Verificación guardada. Revisa el avance de la subtarea.');
    }
}
