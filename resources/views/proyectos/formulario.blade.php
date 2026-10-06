@extends('layouts.aplicacion')
@section('titulo', $proyecto->exists ? 'Editar proyecto' : 'Nuevo proyecto')
@section('contenido')
<a class="volver-tablero" href="{{ $proyecto->exists ? route('proyectos.show', $proyecto) : route('proyectos.index') }}"><span data-icono="volver"></span>Volver a proyectos</a>
<div class="cabecera-pagina"><div><span class="etiqueta-superior">ORGANIZACIÓN</span><h1>{{ $proyecto->exists ? 'Editar proyecto' : 'Nuevo proyecto' }}</h1><p>Define el nombre y el propósito del trabajo.</p></div></div>
<form class="superficie ancho-formulario" method="POST" action="{{ $proyecto->exists ? route('proyectos.update', $proyecto) : route('proyectos.store') }}">
    @csrf
    @if($proyecto->exists) @method('PUT') @endif
    @include('proyectos.campos', ['registro' => $proyecto])
    <div class="grupo-en-linea mt-4">
        <button type="submit" class="btn btn-primary">{{ $proyecto->exists ? 'Guardar cambios' : 'Crear proyecto' }}</button>
        <a class="btn btn-light" href="{{ $proyecto->exists ? route('proyectos.show', $proyecto) : route('proyectos.index') }}">Cancelar</a>
    </div>
</form>
@endsection
