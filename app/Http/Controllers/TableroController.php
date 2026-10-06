<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Tablero;
use App\Services\OrganizacionServicio;
use App\Support\AccesoOrganizacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TableroController extends Controller
{
    public function create(Proyecto $proyecto): View
    {
        return view('tableros.formulario', ['proyecto' => $proyecto, 'tablero' => new Tablero()]);
    }

    public function store(Request $request, Proyecto $proyecto, OrganizacionServicio $servicio): RedirectResponse
    {
        $datos = $this->validar($request, $proyecto);
        $tablero = $servicio->crearTablero($proyecto, $request->user(), $datos);
        return redirect()->route('tableros.show', $tablero)
            ->with('status', 'Tablero creado con sus cuatro columnas. Ya puedes asignar miembros.');
    }

    public function show(Request $request, Tablero $tablero): View
    {
        AccesoOrganizacion::comprobarTablero($request->user(), $tablero);
        $tablero->load(['proyecto', 'columnas' => fn ($columnas) =>
            $columnas->with(['estado', 'tareas' => fn ($q) => $q
                ->with(['prioridad', 'tipo', 'categoria', 'responsable', 'columna.estado'])
                ->withCount(['subtareas', 'subtareas as subtareas_completadas_count' => fn ($s) => $s->whereNotNull('fecha_finalizacion')])
                ->withMax('actividades', 'id_actividad')->orderBy('posicion')->orderBy('id_tarea')])
                ->withCount('tareas')->orderBy('posicion')]);
        $miembrosActivos = $tablero->asignaciones()->where('activo', 1)
            ->whereHas('usuario', fn ($usuarios) => $usuarios->where('activo', 1))->count();
        $responsables = $tablero->usuarios()->where('usuarios.activo', 1)->wherePivot('activo', 1)->orderBy('nombre')->get();
        return view('tableros.mostrar', compact('tablero', 'miembrosActivos', 'responsables'));
    }

    public function edit(Tablero $tablero): View
    {
        return view('tableros.formulario', ['tablero' => $tablero, 'proyecto' => $tablero->proyecto]);
    }

    public function update(Request $request, Tablero $tablero, OrganizacionServicio $servicio): RedirectResponse
    {
        $datos = $this->validar($request, $tablero->proyecto, $tablero);
        $servicio->editarTablero($tablero, $datos);
        return redirect()->route('tableros.show', $tablero)->with('status', 'Tablero actualizado.');
    }

    private function validar(Request $request, Proyecto $proyecto, ?Tablero $tablero = null): array
    {
        $unico = Rule::unique('tableros', 'nombre')->where('id_proyecto', $proyecto->id_proyecto);
        if ($tablero) {
            $unico->ignore($tablero); // Modelo obtenido de la ruta, no un ID enviado en el formulario.
        }
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150', $unico],
            'descripcion' => ['nullable', 'string', 'max:10000'],
            'id_proyecto' => ['prohibited'], 'id_tablero' => ['prohibited'], 'id_creador' => ['prohibited'],
            'columnas' => ['prohibited'], 'fecha_creacion' => ['prohibited'], 'fecha_actualizacion' => ['prohibited'],
        ], ['nombre.unique' => 'Ya existe un tablero con ese nombre en este proyecto.'], ['descripcion' => 'descripción']);
    }
}
