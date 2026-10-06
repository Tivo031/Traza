<?php

namespace App\Http\Controllers;

use App\Models\Usuario;

class UsuariosConsultaController extends Controller
{
    public function index()
    {
        return view('usuarios.index', [
            'usuarios' => Usuario::with('rol')->orderBy('nombre')->paginate(15),
        ]);
    }
}
