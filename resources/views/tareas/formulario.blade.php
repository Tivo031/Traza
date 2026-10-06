@extends('layouts.aplicacion')
@section('titulo', $tarea->exists ? 'Editar tarea' : 'Nueva tarea')
@push('estilos')<link rel="stylesheet" href="{{ asset('traza/css/tareas.css') }}">@endpush
@section('contenido')
<a class="volver-tablero" href="{{ route('tableros.show', $tablero) }}"><span data-icono="volver"></span>{{ $tablero->nombre }}</a>
<div class="cabecera-pagina"><div><span class="etiqueta-superior">TRABAJO DEL EQUIPO</span><h1>{{ $tarea->exists ? 'Editar tarea' : 'Nueva tarea' }}</h1><p>Define el trabajo y la condición para darlo por aprobado.</p></div></div>
<form class="superficie formulario-tarea" method="POST" action="{{ $tarea->exists ? route('tareas.update', $tarea) : route('tareas.store', $tablero) }}" data-guardar-tarea>
    @csrf
    @if($tarea->exists)
        @method('PUT')
        <input type="hidden" name="id_columna_esperada" value="{{ old('id_columna_esperada', $tarea->id_columna) }}">
        <input type="hidden" name="revision_esperada" value="{{ old('revision_esperada', \App\Support\VistaTarea::revision($tarea)) }}">
    @endif
    <div class="row g-3">
        <div class="col-12"><label class="form-label" for="titulo">Título *</label><input class="form-control" id="titulo" name="titulo" maxlength="150" required value="{{ old('titulo', $tarea->titulo) }}"></div>
        <div class="col-12"><label class="form-label" for="descripcion">Descripción *</label><textarea class="form-control" id="descripcion" name="descripcion" rows="4" maxlength="10000" required>{{ old('descripcion', $tarea->descripcion) }}</textarea><div class="form-text">En un defecto, indica pasos para reproducirlo, resultado esperado y resultado observado.</div></div>
        <div class="col-12"><label class="form-label" for="criterio">Criterio de aceptación *</label><textarea class="form-control" id="criterio" name="criterio_aceptacion" rows="3" maxlength="2000" required>{{ old('criterio_aceptacion', $tarea->criterio_aceptacion) }}</textarea><div class="form-text">Describe cómo comprobar que el trabajo está correcto.</div></div>
        <div class="col-md-4"><label class="form-label" for="prioridad">Prioridad *</label><select class="form-select" id="prioridad" name="id_prioridad" required>@foreach($prioridades as $opcion)<option value="{{ $opcion->id_prioridad }}" @selected(old('id_prioridad', $tarea->id_prioridad) == $opcion->id_prioridad)>{{ $opcion->nombre }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="tipo">Tipo *</label><select class="form-select" id="tipo" name="id_tipo_tarea" required>@foreach($tipos as $opcion)<option value="{{ $opcion->id_tipo_tarea }}" @selected(old('id_tipo_tarea', $tarea->id_tipo_tarea) == $opcion->id_tipo_tarea)>{{ $opcion->nombre }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="categoria">Categoría *</label><select class="form-select" id="categoria" name="id_categoria" required>@foreach($categorias as $opcion)<option value="{{ $opcion->id_categoria }}" @selected(old('id_categoria', $tarea->id_categoria) == $opcion->id_categoria)>{{ $opcion->nombre }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label" for="responsable">Responsable</label><select class="form-select" id="responsable" name="id_responsable"><option value="">Sin asignar (solo en Por hacer)</option>@foreach($responsables as $opcion)<option value="{{ $opcion->id_usuario }}" @selected(old('id_responsable', $tarea->id_responsable) == $opcion->id_usuario)>{{ $opcion->nombre }}</option>@endforeach</select><div class="form-text">Asignar responsable en Por hacer inicia la tarea. Solo aparecen miembros activos del tablero.</div></div>
        <div class="col-md-3"><label class="form-label" for="inicio">Inicio planificado</label><input type="date" class="form-control" id="inicio" name="fecha_inicio" value="{{ old('fecha_inicio', $tarea->fecha_inicio?->format('Y-m-d')) }}"></div>
        <div class="col-md-3"><label class="form-label" for="limite">Fecha límite</label><input type="date" class="form-control" id="limite" name="fecha_limite" value="{{ old('fecha_limite', $tarea->fecha_limite?->format('Y-m-d')) }}"></div>
    </div>
    @if($responsables->isEmpty())<p class="alert alert-warning mt-3">Todavía no hay responsables elegibles. El Administrador debe asignar usuarios activos a este tablero.</p>@endif
    <div class="grupo-en-linea mt-4"><button class="btn btn-primary" type="submit">{{ $tarea->exists ? 'Guardar cambios' : 'Crear tarea' }}</button><a class="btn btn-light" href="{{ $tarea->exists ? route('tareas.show', $tarea) : route('tableros.show', $tablero) }}">Cancelar</a></div>
</form>
@endsection
@push('scripts')<script src="{{ asset('traza/js/tareas.js') }}"></script>@endpush
