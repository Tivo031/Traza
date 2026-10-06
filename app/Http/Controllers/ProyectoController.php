<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Support\AccesoOrganizacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProyectoController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $consulta = Proyecto::query()->orderByDesc('id_proyecto');
        if ($usuario->esAdministrador()) {
            $consulta->with(['tableros' => fn ($tableros) => $tableros->orderBy('nombre')]);
        } else {
            $permitidos = function ($tableros) use ($usuario): void {
                $tableros->whereHas('asignaciones', fn ($asignaciones) =>
                    $asignaciones->where('id_usuario', $usuario->id_usuario)->where('activo', 1));
            };
            $consulta->whereHas('tableros', $permitidos)->with(['tableros' => $permitidos]);
        }
        return view('proyectos.index', ['proyectos' => $consulta->paginate(12)]);
    }

    public function create(): View
    {
        return view('proyectos.formulario', ['proyecto' => new Proyecto()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $proyecto = Proyecto::create([
            'nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion'] ?? null,
            'id_creador' => $request->user()->id_usuario,
        ]);
        return redirect()->route('proyectos.show', $proyecto)->with('status', 'Proyecto creado correctamente.');
    }

    public function show(Request $request, Proyecto $proyecto): View
    {
        AccesoOrganizacion::comprobarProyecto($request->user(), $proyecto);
        $tableros = AccesoOrganizacion::tablerosVisibles($request->user())
            ->where('id_proyecto', $proyecto->id_proyecto)
            ->withCount(['columnas', 'asignaciones as miembros_activos_count' => fn ($asignaciones) =>
                $asignaciones->where('activo', 1)->whereHas('usuario', fn ($usuarios) => $usuarios->where('activo', 1))])
            ->orderBy('nombre')->get();
        return view('proyectos.mostrar', compact('proyecto', 'tableros'));
    }

    public function edit(Proyecto $proyecto): View
    {
        return view('proyectos.formulario', compact('proyecto'));
    }

    public function update(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $datos = $this->validar($request);
        $proyecto->update(['nombre' => $datos['nombre'], 'descripcion' => $datos['descripcion'] ?? null]);
        return redirect()->route('proyectos.show', $proyecto)->with('status', 'Proyecto actualizado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:10000'],
            'id_creador' => ['prohibited'], 'id_proyecto' => ['prohibited'],
            'fecha_creacion' => ['prohibited'], 'fecha_actualizacion' => ['prohibited'],
        ], [], ['descripcion' => 'descripción']);
    }
}
