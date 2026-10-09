<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Services\PanelServicio;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function show(Request $request, Proyecto $proyecto, PanelServicio $servicio): View
    {
        return view('panel.mostrar', array_merge(['proyecto' => $proyecto], $servicio->consultar($proyecto, $request->user())));
    }
}
