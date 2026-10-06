@extends('layouts.aplicacion')
@section('titulo', 'Proyectos')
@section('contenido')
<div class="cabecera-pagina">
    <div><span class="etiqueta-superior">ESPACIO DE TRABAJO</span><h1>Proyectos y tableros</h1><p>Tu punto de partida para organizar el trabajo del equipo.</p></div>
    <span class="etiqueta categoria-2">{{ auth()->user()->rol->nombre }}</span>
</div>
<div class="superficie mb-4">
    <div class="subtitulo-seccion"><h2>Bienvenido, {{ auth()->user()->nombre }}.</h2><span data-icono="check"></span></div>
    <p class="texto-secundario mb-0">Tu sesión está activa. La base ya puede guardar usuarios con la nomenclatura definida para Traza.</p>
</div>
@if($proyectos->isEmpty())
    <div class="superficie vacio">
        <span data-icono="proyectos"></span>
        <h2>{{ auth()->user()->esAdministrador() ? 'Aún no hay proyectos' : 'Aún no tienes tableros asignados' }}</h2>
        <p>No se cargaron proyectos ni tareas ficticias en esta entrega.</p>
        <p class="texto-mini">Siguiente etapa: crear proyectos y tableros con sus cuatro columnas.</p>
    </div>
@else
    <div class="row g-3">
    @foreach($proyectos as $proyecto)
        <div class="col-md-6"><section class="superficie h-100"><h2>{{ $proyecto->nombre }}</h2><p class="texto-secundario">{{ $proyecto->descripcion }}</p>
        @forelse($proyecto->tableros as $tablero)
            <p class="mb-2"><span data-icono="tablero"></span> {{ $tablero->nombre }}</p>
        @empty
            <p class="texto-mini texto-secundario">Sin tableros disponibles.</p>
        @endforelse
        </section></div>
    @endforeach
    </div>
@endif
<div class="alert alert-light border mt-4 texto-mini">En esta fase funcionan el acceso, el registro, la recuperación y la consulta inicial. Kanban, edición de usuarios, tareas y panel se conectarán en las siguientes etapas.</div>
@endsection
