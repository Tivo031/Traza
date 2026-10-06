<?php

namespace App\Http\Controllers;

use App\Http\Requests\{GuardarTareaRequest, MoverTareaRequest};
use App\Models\{Categoria, Prioridad, Tablero, Tarea, TipoTarea};
use App\Services\TareaServicio;
use App\Support\{AccesoOrganizacion, VistaTarea};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class TareaController extends Controller
{
    public function create(Request $request, Tablero $tablero): View
    {
        AccesoOrganizacion::comprobarTablero($request->user(), $tablero);
        return view('tareas.formulario', array_merge($this->opciones($tablero), ['tablero' => $tablero,
            'tarea' => new Tarea(['id_prioridad' => Prioridad::where('codigo', 'MEDIA')->value('id_prioridad'),
                'id_tipo_tarea' => TipoTarea::where('codigo', 'TAREA')->value('id_tipo_tarea'),
                'id_categoria' => Categoria::where('nombre', 'General')->value('id_categoria')])]));
    }

    public function store(GuardarTareaRequest $request, Tablero $tablero, TareaServicio $servicio): RedirectResponse
    {
        $tarea = $servicio->crear($tablero, $request->user(), $request->validated());
        return redirect()->route('tareas.show', $tarea)->with('status', 'Tarea creada y registrada en el historial.');
    }

    public function edit(Request $request, Tarea $tarea): View|RedirectResponse
    {
        $tablero = $tarea->columna->tablero;
        AccesoOrganizacion::comprobarTablero($request->user(), $tablero);
        if (! VistaTarea::editable($tarea)) {
            return redirect()->route('tareas.show', $tarea)->withErrors(['tarea' => 'La tarea no se puede editar en su estado actual.']);
        }
        $tarea->loadMax('actividades', 'id_actividad');
        return view('tareas.formulario', array_merge($this->opciones($tablero), compact('tarea', 'tablero')));
    }

    public function update(GuardarTareaRequest $request, Tarea $tarea, TareaServicio $servicio): RedirectResponse
    {
        $servicio->editar($tarea, $request->user(), $request->validated());
        return redirect()->route('tareas.show', $tarea)->with('status', 'Tarea guardada.');
    }

    public function show(Request $request, Tarea $tarea): View
    {
        $tablero = $tarea->columna->tablero;
        AccesoOrganizacion::comprobarTablero($request->user(), $tablero);
        $tarea->load(['columna.estado', 'prioridad', 'tipo', 'categoria', 'creador', 'responsable',
            'subtareas' => fn ($q) => $q->with('elementos')->orderBy('posicion')->orderBy('id_subtarea')]);
        $tarea->loadCount(['subtareas', 'subtareas as subtareas_completadas_count' => fn ($q) => $q->whereNotNull('fecha_finalizacion')]);
        $tarea->loadMax('actividades', 'id_actividad');
        $actividades = $tarea->actividades()->with(['actor', 'estadoAnterior', 'estadoNuevo'])
            ->orderByDesc('fecha_creacion')->orderByDesc('id_actividad')->paginate(20, ['*'], 'historial');
        $comentarios = $tarea->comentarios()->with('autor')->orderByDesc('fecha_creacion')
            ->orderByDesc('id_comentario')->paginate(10, ['*'], 'comentarios');
        $tablero->load(['proyecto', 'columnas.estado']);
        return view('tareas.mostrar', array_merge($this->opciones($tablero),
            compact('tarea', 'tablero', 'actividades', 'comentarios')));
    }

    public function mover(MoverTareaRequest $request, Tarea $tarea, TareaServicio $servicio): RedirectResponse
    {
        $servicio->mover($tarea, $request->user(), $request->validated());
        // Destino interno conocido: no se aceptan URLs de retorno arbitrarias.
        return redirect()->route('tableros.show', $tarea->columna->id_tablero)
            ->with('status', 'Movimiento guardado y registrado en el historial.');
    }

    private function opciones(Tablero $tablero): array
    {
        return ['prioridades' => Prioridad::orderBy('id_prioridad')->get(),
            'tipos' => TipoTarea::orderBy('id_tipo_tarea')->get(), 'categorias' => Categoria::orderBy('nombre')->get(),
            'responsables' => $tablero->usuarios()->where('usuarios.activo', 1)->wherePivot('activo', 1)
                ->orderBy('nombre')->get()];
    }
}
