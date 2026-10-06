<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InicioController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();
        $consulta = Proyecto::query()->orderByDesc('id_proyecto');
        if ($usuario->esAdministrador()) {
            $consulta->with('tableros');
        } else {
            // Se reutiliza en whereHas (Builder) y with (relacion HasMany).
            $autorizados = function ($tableros) use ($usuario): void {
                $tableros->whereHas('asignaciones', function (Builder $asignaciones) use ($usuario): void {
                    $asignaciones->where('id_usuario', $usuario->id_usuario)->where('activo', 1);
                });
            };
            $consulta->whereHas('tableros', $autorizados)->with(['tableros' => $autorizados]);
        }
        return view('inicio', ['proyectos' => $consulta->get()]);
    }
}
