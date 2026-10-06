@extends('layouts.aplicacion')
@section('titulo', 'Tableros del proyecto')
@section('contenido')
<a class="volver-tablero" href="{{ route('proyectos.index') }}"><span data-icono="volver"></span>Todos los proyectos</a>
<div class="cabecera-pagina">
    <div><span class="etiqueta-superior">PROYECTO</span><h1>{{ $proyecto->nombre }}</h1></div>
    @if(auth()->user()->esAdministrador())
    <div class="grupo-en-linea">
        <a class="btn btn-light" href="{{ route('proyectos.edit', $proyecto) }}"><span data-icono="editar"></span>Editar proyecto</a>
        <a class="btn btn-primary" href="{{ route('tableros.create', $proyecto) }}"><span data-icono="mas"></span>Nuevo tablero</a>
    </div>
    @endif
</div>
@if($proyecto->descripcion)<p class="descripcion-con-saltos texto-secundario">{{ $proyecto->descripcion }}</p>@endif
<h2 class="mb-3">Tableros disponibles</h2>
@if($tableros->isEmpty())
    <section class="superficie vacio"><span data-icono="tablero"></span><h3>Aún no hay tableros</h3><p>Crea el primero para organizar el trabajo de este proyecto.</p></section>
@else
<div class="row g-3">
@foreach($tableros as $tablero)
    <div class="col-md-6 col-xl-4"><article class="superficie h-100 tarjeta-tablero">
        <div class="icono-proyecto"><span data-icono="tablero"></span></div>
        <h3>{{ $tablero->nombre }}</h3><p class="texto-secundario texto-mini">{{ \Illuminate\Support\Str::limit($tablero->descripcion ?: 'Sin descripción.', 150) }}</p>
        <div class="grupo-en-linea mb-3"><span class="etiqueta categoria-1">{{ $tablero->columnas_count }} columnas</span><span class="etiqueta categoria-2">{{ $tablero->miembros_activos_count }} miembros activos</span></div>
        <div class="grupo-en-linea">
            <a class="btn btn-primary btn-sm" href="{{ route('tableros.show', $tablero) }}">Abrir tablero</a>
            @if(auth()->user()->esAdministrador())
            <a class="btn btn-light btn-sm" href="{{ route('asignaciones.index', $tablero) }}">Miembros</a>
            <a class="btn btn-light btn-sm" href="{{ route('tableros.edit', $tablero) }}">Editar</a>
            @endif
        </div>
    </article></div>
@endforeach
</div>
@endif
@endsection
