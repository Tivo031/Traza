<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarComentarioRequest;
use App\Models\Tarea;
use App\Services\SeguimientoServicio;
use Illuminate\Http\RedirectResponse;

class ComentarioController extends Controller
{
    public function store(GuardarComentarioRequest $request, Tarea $tarea, SeguimientoServicio $servicio): RedirectResponse
    {
        $servicio->comentar($tarea, $request->user(), $request->validated());
        return redirect()->route('tareas.show', $tarea)->withFragment('comentarios')->with('status', 'Comentario publicado.');
    }
}
