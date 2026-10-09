@extends('layouts.aplicacion')
@section('titulo', 'Detalle de tarea')
@push('estilos')
<link rel="stylesheet" href="{{ asset('traza/css/tareas.css') }}">
<link rel="stylesheet" href="{{ asset('traza/css/seguimiento.css') }}">
@endpush
@section('contenido')
@php
    $vista = \App\Support\VistaTarea::class;
    $codigo = $vista::codigo($tarea);
    $editable = $vista::editable($tarea);
    $avance = $vista::porcentaje($tarea);
    $permitidos = match ($codigo) {
        'POR_HACER' => ['EN_PROGRESO'],
        'EN_PROGRESO' => (int) $tarea->id_responsable === (int) auth()->id() ? ['EN_REVISION'] : [],
        'EN_REVISION' => auth()->user()->esAdministrador() ? ['COMPLETADO', 'EN_PROGRESO'] : [],
        default => [],
    };
@endphp
<a class="volver-tablero" href="{{ route('tableros.show', $tablero) }}"><span data-icono="volver"></span>{{ $tablero->nombre }}</a>
<div class="cabecera-pagina"><div><span class="etiqueta-superior">TAREA #{{ $tarea->id_tarea }}</span><h1>{{ $tarea->titulo }}</h1><div class="grupo-en-linea"><span class="etiqueta estado-{{ $tarea->columna->posicion + 1 }}">{{ $tarea->columna->estado->nombre }}</span><span class="etiqueta {{ $vista::prioridad($tarea->prioridad->codigo) }}">{{ $tarea->prioridad->nombre }}</span><span class="etiqueta {{ $tarea->tipo->codigo === 'DEFECTO' ? 'defecto' : 'categoria-1' }}">{{ $tarea->tipo->nombre }}</span>@if($vista::vencida($tarea))<span class="etiqueta defecto">Vencida</span>@endif</div></div>
@if($editable)<a class="btn btn-primary" href="{{ route('tareas.edit', $tarea) }}"><span data-icono="editar"></span>Editar tarea</a>@endif</div>
<div class="rejilla-detalle"><div>
    <section class="superficie mb-4"><h2>Descripción</h2><p class="descripcion-con-saltos">{{ $tarea->descripcion }}</p><h2 class="mt-4">Criterio de aceptación</h2><p class="descripcion-con-saltos mb-0">{{ $tarea->criterio_aceptacion }}</p></section>
    @include('seguimiento.subtareas')
    @include('seguimiento.comentarios')
    <section class="superficie mb-4" id="historial"><h2>Historial de actividad</h2><p class="texto-mini texto-secundario">Más reciente primero &middot; hora de Guatemala</p>
        @forelse($actividades as $actividad)
        <article class="evento-tarea"><strong>{{ $actividad->actor->nombre }}</strong><span class="texto-mini texto-secundario"> &middot; {{ $actividad->fecha_creacion->copy()->setTimezone('America/Guatemala')->format('d/m/Y H:i:s') }}</span>
            <p class="mb-1">{{ \Illuminate\Support\Str::headline($actividad->codigo_accion) }}</p>
            @if($actividad->codigo_accion === 'CAMBIO_ESTADO')<p class="texto-mini">{{ $actividad->estadoAnterior?->nombre }} &rarr; {{ $actividad->estadoNuevo?->nombre }}</p>@endif
            @if($actividad->observacion)<p class="descripcion-con-saltos texto-mini mb-0">{{ $actividad->observacion }}</p>@endif
        </article>
        @empty<p>Sin actividad registrada.</p>@endforelse
        <div class="mt-3">{{ $actividades->withQueryString()->links() }}</div>
    </section>
</div><aside>
    <section class="superficie mb-4"><h2>Información</h2><dl class="datos-tarea">
        <dt>Responsable</dt><dd>{{ $tarea->responsable?->nombre ?? 'Sin asignar' }}</dd>
        <dt>Creada por</dt><dd>{{ $tarea->creador->nombre }}</dd>
        <dt>Categoría</dt><dd>{{ $tarea->categoria->nombre }}</dd>
        <dt>Inicio planificado</dt><dd>{{ $tarea->fecha_inicio?->format('d/m/Y') ?? 'Sin fecha' }}</dd>
        <dt>Fecha límite</dt><dd>{{ $tarea->fecha_limite?->format('d/m/Y') ?? 'Sin fecha' }}</dd>
        <dt>Creación</dt><dd>{{ $tarea->fecha_creacion->copy()->setTimezone('America/Guatemala')->format('d/m/Y H:i') }}</dd>
        <dt>Cierre</dt><dd>{{ $tarea->fecha_cierre ? $tarea->fecha_cierre->copy()->setTimezone('America/Guatemala')->format('d/m/Y H:i') : 'Sin cerrar' }}</dd>
    </dl></section>
    <section class="superficie" id="mover"><h2>{{ $codigo === 'EN_REVISION' ? 'Revisión de calidad' : 'Cambiar estado' }}</h2>
    @if($permitidos !== [])
    <form method="POST" action="{{ route('tareas.mover', $tarea) }}" data-guardar-tarea>
        @csrf @method('PATCH')
        <input type="hidden" name="id_columna_esperada" value="{{ $tarea->id_columna }}"><input type="hidden" name="revision_esperada" value="{{ $vista::revision($tarea) }}">
        <label class="form-label" for="destino">Acción</label><select id="destino" name="id_columna_destino" class="form-select mb-3" data-destino-detalle required>
            @foreach($tablero->columnas as $columna)@if(in_array($columna->estado->codigo, $permitidos, true))<option value="{{ $columna->id_columna }}" data-rechazo="{{ $codigo === 'EN_REVISION' && $columna->estado->codigo === 'EN_PROGRESO' ? '1' : '0' }}">{{ ['EN_PROGRESO' => $codigo === 'EN_REVISION' ? 'Rechazar y devolver' : 'Asignar e iniciar', 'EN_REVISION' => 'Enviar a revisión', 'COMPLETADO' => 'Aprobar y completar'][$columna->estado->codigo] }}</option>@endif
            @endforeach
        </select>
        @if($codigo === 'POR_HACER')<label for="responsable-mover" class="form-label">Responsable *</label><select id="responsable-mover" name="id_responsable" class="form-select mb-3" required><option value="">Seleccionar</option>@foreach($responsables as $persona)<option value="{{ $persona->id_usuario }}">{{ $persona->nombre }}</option>@endforeach</select>@endif
        @if($codigo === 'EN_REVISION')<label class="form-label" for="motivo">Observación de revisión</label><textarea id="motivo" class="form-control" name="observacion" rows="3" maxlength="5000" data-motivo-detalle>{{ old('observacion') }}</textarea><p class="form-text">Obligatoria al rechazar. Antes de aprobar, comprueba el criterio de aceptación.</p>@endif
        @if($codigo === 'EN_PROGRESO')<p class="form-text">Al confirmar declaras el avance completo. Las subtareas existentes deben estar terminadas.</p>@endif
        <button class="btn btn-primary w-100 mt-2" type="submit">Confirmar acción</button>
    </form>
    @elseif($codigo === 'COMPLETADO')<p class="texto-secundario mb-0">Tarea cerrada. El trabajo y el historial son de solo lectura.</p>
    @elseif($codigo === 'EN_REVISION')<p class="texto-secundario mb-0">La tarea espera la decisión de un Administrador.</p>
    @else<p class="texto-secundario mb-0">Solo el responsable puede enviar esta tarea a revisión.</p>@endif
    </section>
</aside></div>
@endsection
@push('scripts')<script src="{{ asset('traza/js/tareas.js') }}"></script>@endpush
