@extends('layouts.aplicacion')
@section('titulo', 'Tarea actualizada')
@section('contenido')
<section class="superficie ancho-formulario"><h1>La tarea cambió</h1><p>Otra acción actualizó los datos desde que abriste la pantalla. Tu solicitud se canceló sin sobrescribir esa actualización.</p><p>Abre nuevamente el tablero, revisa el estado vigente y repite la operación.</p><a href="{{ route('proyectos.index') }}" class="btn btn-primary">Volver a proyectos</a></section>
@endsection
