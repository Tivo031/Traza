@php
    $idSub = $subtarea->id_subtarea;
    $formSub = 'subtarea-'.$idSub;
    $formNuevoElemento = 'elemento-nuevo-'.$idSub;
    $sinPendientes = ! $subtarea->elementos->contains(fn ($e) => ! $e->completado);
@endphp
<article class="subtarea-edicion" id="subtarea-{{ $idSub }}">
    <div class="cabecera-seguimiento">
        <h3>{{ $subtarea->titulo }}</h3>
        <span class="etiqueta {{ $subtarea->fecha_finalizacion ? 'estado-4' : 'categoria-1' }}">{{ $subtarea->fecha_finalizacion ? 'Completada' : 'Pendiente' }}</span>
    </div>
    @if($subtarea->descripcion)
        <p class="descripcion-con-saltos texto-mini mt-2">{{ $subtarea->descripcion }}</p>
    @endif
    <p class="texto-mini texto-secundario mb-2">{{ $subtarea->elementos->where('completado', true)->count() }} de {{ $subtarea->elementos->count() }} elementos marcados</p>
    <div class="elementos-seguimiento">
        @forelse($subtarea->elementos as $elemento)
            @include('seguimiento.elemento')
        @empty
            <p class="texto-mini texto-secundario">Sin elementos. Esta subtarea se puede finalizar manualmente.</p>
        @endforelse
    </div>
    @if($editable)
        <details class="editor-seguimiento compacto" @if(old('formulario') === $formNuevoElemento) open @endif>
            <summary class="enlace-editor">Agregar elemento de verificación</summary>
            <form method="POST" action="{{ route('elementos.store', [$tarea, $subtarea]) }}" class="mt-3" data-guardar-tarea>
                @include('seguimiento.version', ['formulario' => $formNuevoElemento])
                <label class="form-label" for="elemento-nuevo-{{ $idSub }}">Acción a verificar *</label>
                <input class="form-control mb-2" name="titulo" id="elemento-nuevo-{{ $idSub }}" maxlength="150" required value="{{ old('formulario') === $formNuevoElemento ? old('titulo') : '' }}">
                <button type="submit" class="btn btn-light btn-sm">Guardar elemento</button>
            </form>
        </details>
        <div class="acciones-subtarea">
            <form method="POST" action="{{ route('subtareas.estado', [$tarea, $subtarea]) }}" data-guardar-tarea>
                @include('seguimiento.version', ['formulario' => 'estado-subtarea-'.$idSub])
                @method('PATCH')
                <input type="hidden" name="completada" value="{{ $subtarea->fecha_finalizacion ? '0' : '1' }}">
                <button class="btn {{ $subtarea->fecha_finalizacion ? 'btn-light' : 'btn-primary' }} btn-sm" type="submit" @disabled(! $subtarea->fecha_finalizacion && ! $sinPendientes)>
                    {{ $subtarea->fecha_finalizacion ? 'Reabrir subtarea' : 'Finalizar subtarea' }}
                </button>
            </form>
            @if(! $sinPendientes)
                <span class="texto-mini texto-secundario">Faltan elementos por marcar.</span>
            @endif
        </div>
        <details class="editor-seguimiento compacto" @if(old('formulario') === $formSub) open @endif>
            <summary class="enlace-editor">Editar subtarea y orden</summary>
            <form method="POST" action="{{ route('subtareas.update', [$tarea, $subtarea]) }}" class="mt-3" data-guardar-tarea>
                @include('seguimiento.version', ['formulario' => $formSub])
                @method('PUT')
                <label class="form-label" for="titulo-sub-{{ $idSub }}">Título *</label>
                <input id="titulo-sub-{{ $idSub }}" class="form-control mb-2" name="titulo" maxlength="150" required value="{{ old('formulario') === $formSub ? old('titulo') : $subtarea->titulo }}">
                <label class="form-label" for="descripcion-sub-{{ $idSub }}">Descripción</label>
                <textarea id="descripcion-sub-{{ $idSub }}" class="form-control mb-2" name="descripcion" rows="2" maxlength="10000">{{ old('formulario') === $formSub ? old('descripcion') : $subtarea->descripcion }}</textarea>
                <label class="form-label" for="posicion-sub-{{ $idSub }}">Orden (0 es el primero)</label>
                <input id="posicion-sub-{{ $idSub }}" class="form-control campo-orden mb-2" name="posicion" type="number" min="0" max="{{ max(0, $tarea->subtareas_count - 1) }}" required value="{{ old('formulario') === $formSub ? old('posicion') : $subtarea->posicion }}">
                <button type="submit" class="btn btn-light btn-sm">Guardar cambios</button>
            </form>
        </details>
    @endif
</article>
