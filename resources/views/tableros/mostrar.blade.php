@extends('layouts.aplicacion')
@section('titulo', 'Tablero')
@push('estilos')<link rel="stylesheet" href="{{ asset('traza/css/tareas.css') }}">@endpush
@section('contenido')
<a class="volver-tablero" href="{{ route('proyectos.show', $tablero->proyecto) }}"><span data-icono="volver"></span>{{ $tablero->proyecto->nombre }}</a>
<div class="cabecera-pagina">
    <div><span class="etiqueta-superior">TABLERO DEL EQUIPO</span><h1>{{ $tablero->nombre }}</h1><p>{{ $miembrosActivos }} miembros activos &middot; {{ $tablero->columnas->sum('tareas_count') }} tareas</p></div>
    <div class="grupo-en-linea">
        @if(auth()->user()->esAdministrador())<a class="btn btn-light" href="{{ route('tableros.edit', $tablero) }}">Editar tablero</a><a class="btn btn-light" href="{{ route('asignaciones.index', $tablero) }}"><span data-icono="usuarios"></span>Miembros</a>@endif
        <a class="btn btn-primary" href="{{ route('tareas.create', $tablero) }}"><span data-icono="mas"></span>Nueva tarea</a>
    </div>
</div>
@if($tablero->descripcion)<p class="descripcion-con-saltos texto-secundario">{{ $tablero->descripcion }}</p>@endif
<p class="texto-mini texto-secundario">Arrastra desde el asa de una tarjeta en escritorio o utiliza <strong>Abrir / Mover</strong>. Todo cambio se confirma en el servidor.</p>
<div class="kanban-desplazable" role="region" aria-label="Tablero Kanban" tabindex="0">
    <div class="kanban kanban-real" data-kanban>
    @foreach($tablero->columnas as $columna)
        <section class="columna-kanban estado-{{ $columna->posicion + 1 }}">
            <header class="titulo-columna"><span class="punto-estado"></span><h2>{{ $columna->estado->nombre }}</h2><span class="cantidad">{{ $columna->tareas_count }}</span></header>
            <div class="lista-tareas-real" data-lista-columna="{{ $columna->id_columna }}" data-estado="{{ $columna->estado->codigo }}" data-nombre="{{ $columna->estado->nombre }}">
                @forelse($columna->tareas as $tarea)@include('tareas.tarjeta')@empty<div class="columna-vacia">Sin tareas todavía.</div>@endforelse
            </div>
        </section>
    @endforeach
    </div>
</div>
<div class="modal fade" id="modal-mover-tarea" tabindex="-1" aria-labelledby="titulo-movimiento" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" id="form-mover-tarea" data-guardar-tarea>
        @csrf @method('PATCH')
        <input type="hidden" name="id_columna_esperada"><input type="hidden" name="revision_esperada">
        <input type="hidden" name="id_columna_destino"><input type="hidden" name="posicion_destino">
        <div class="modal-header"><h2 class="modal-title fs-5" id="titulo-movimiento">Confirmar movimiento</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancelar"></button></div>
        <div class="modal-body">
            <p id="resumen-movimiento" class="descripcion-con-saltos"></p>
            <div class="mb-3" id="grupo-responsable-movimiento"><label class="form-label" for="responsable-movimiento">Responsable *</label><select class="form-select" name="id_responsable" id="responsable-movimiento"><option value="">Seleccionar</option>@foreach($responsables as $persona)<option value="{{ $persona->id_usuario }}">{{ $persona->nombre }}</option>@endforeach</select></div>
            <div id="grupo-criterio-movimiento" class="mb-3"><strong>Criterio de aceptación</strong><p id="criterio-movimiento" class="descripcion-con-saltos mt-2"></p></div>
            <div id="grupo-observacion-movimiento"><label class="form-label" for="observacion-movimiento">Defecto observado *</label><textarea id="observacion-movimiento" class="form-control" name="observacion" maxlength="5000" rows="3"></textarea></div>
            <p class="texto-mini texto-secundario mb-0 mt-3">El sistema volverá a validar permisos, responsable, avance y estado vigente.</p>
        </div>
        <div class="modal-footer"><button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit" id="confirmar-movimiento">Confirmar</button></div>
    </form>
</div></div></div>
@endsection
@push('scripts')<script src="{{ asset('traza/js/tareas.js') }}"></script>@endpush
