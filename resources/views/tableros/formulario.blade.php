@extends('layouts.aplicacion')
@section('titulo', $tablero->exists ? 'Editar tablero' : 'Nuevo tablero')
@section('contenido')
<a class="volver-tablero" href="{{ route('proyectos.show', $proyecto) }}"><span data-icono="volver"></span>Volver al proyecto</a>
<div class="cabecera-pagina"><div><span class="etiqueta-superior">{{ $proyecto->nombre }}</span><h1>{{ $tablero->exists ? 'Editar tablero' : 'Nuevo tablero' }}</h1><p>El tablero pertenece a este proyecto; no se traslada a otro al editarlo.</p></div></div>
<form class="superficie ancho-formulario" method="POST" action="{{ $tablero->exists ? route('tableros.update', $tablero) : route('tableros.store', $proyecto) }}">
    @csrf
    @if($tablero->exists) @method('PUT') @endif
    @include('proyectos.campos', ['registro' => $tablero])
    @unless($tablero->exists)
        <div class="aviso-linea mt-4"><span data-icono="tablero"></span><p>Se crearán las columnas: Por hacer, En progreso, En revisión / QA y Completado.</p></div>
    @endunless
    <div class="grupo-en-linea mt-4">
        <button type="submit" class="btn btn-primary">{{ $tablero->exists ? 'Guardar cambios' : 'Crear tablero' }}</button>
        <a class="btn btn-light" href="{{ route('proyectos.show', $proyecto) }}">Cancelar</a>
    </div>
</form>
@endsection
