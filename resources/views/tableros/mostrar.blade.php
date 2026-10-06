@extends('layouts.aplicacion')
@section('titulo', 'Tablero')
@section('contenido')
<a class="volver-tablero" href="{{ route('proyectos.show', $tablero->proyecto) }}"><span data-icono="volver"></span>{{ $tablero->proyecto->nombre }}</a>
<div class="cabecera-pagina">
    <div><span class="etiqueta-superior">TABLERO DEL EQUIPO</span><h1>{{ $tablero->nombre }}</h1><p>{{ $miembrosActivos }} miembros activos</p></div>
    @if(auth()->user()->esAdministrador())
    <div class="grupo-en-linea"><a class="btn btn-light" href="{{ route('tableros.edit', $tablero) }}"><span data-icono="editar"></span>Editar tablero</a><a class="btn btn-primary" href="{{ route('asignaciones.index', $tablero) }}"><span data-icono="usuarios"></span>Miembros</a></div>
    @endif
</div>
@if($tablero->descripcion)<p class="descripcion-con-saltos texto-secundario">{{ $tablero->descripcion }}</p>@endif
<div class="aviso-linea mb-4"><span data-icono="aviso"></span><p>Proyecto, tablero y miembros ya se guardan en la base. La creación de tareas, el detalle y los movimientos se conectarán en el siguiente bloque.</p></div>
<div class="kanban-desplazable" role="region" aria-label="Columnas del tablero" tabindex="0">
    <div class="kanban kanban-organizacion">
    @forelse($tablero->columnas as $columna)
        <section class="columna-kanban estado-{{ $columna->posicion + 1 }}">
            <header class="titulo-columna"><span class="punto-estado"></span><h2>{{ $columna->estado->nombre }}</h2><span class="cantidad">{{ $columna->tareas_count }}</span></header>
            <div class="columna-vacia">{{ $columna->tareas_count ? 'Hay tareas registradas; su consulta detallada se integrará en el siguiente bloque.' : 'Sin tareas todavía.' }}</div>
        </section>
    @empty
        <p role="alert">El tablero no tiene columnas. Revisa la estructura antes de continuar.</p>
    @endforelse
    </div>
</div>
@endsection
