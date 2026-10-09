@php
    $idEle = $elemento->id_elemento;
    $formEle = 'elemento-'.$idEle;
@endphp
<div class="elemento-seguimiento">
    <div class="fila-elemento">
        <span class="estado-elemento {{ $elemento->completado ? 'marcado' : '' }}" aria-hidden="true">{{ $elemento->completado ? '✓' : '○' }}</span>
        <div class="texto-elemento">
            <span class="{{ $elemento->completado ? 'texto-finalizado' : '' }}">{{ $elemento->titulo }}</span>
            <small class="d-block texto-secundario">{{ $elemento->completado ? 'Completado' : 'Pendiente' }}</small>
        </div>
        @if($editable)
            <form method="POST" action="{{ route('elementos.estado', [$tarea, $subtarea, $elemento]) }}" data-guardar-tarea>
                @include('seguimiento.version', ['formulario' => 'estado-elemento-'.$idEle])
                @method('PATCH')
                <input type="hidden" name="completado" value="{{ $elemento->completado ? '0' : '1' }}">
                <button type="submit" class="btn btn-light btn-sm" aria-label="{{ ($elemento->completado ? 'Desmarcar: ' : 'Marcar: ').$elemento->titulo }}">{{ $elemento->completado ? 'Desmarcar' : 'Marcar' }}</button>
            </form>
        @endif
    </div>
    @if($editable)
        <details class="editor-elemento" @if(old('formulario') === $formEle) open @endif>
            <summary class="enlace-editor">Editar elemento</summary>
            <form method="POST" action="{{ route('elementos.update', [$tarea, $subtarea, $elemento]) }}" class="mt-2" data-guardar-tarea>
                @include('seguimiento.version', ['formulario' => $formEle])
                @method('PUT')
                <label class="form-label" for="titulo-ele-{{ $idEle }}">Título *</label>
                <input id="titulo-ele-{{ $idEle }}" class="form-control mb-2" name="titulo" maxlength="150" required value="{{ old('formulario') === $formEle ? old('titulo') : $elemento->titulo }}">
                <label class="form-label" for="posicion-ele-{{ $idEle }}">Orden (0 es el primero)</label>
                <input id="posicion-ele-{{ $idEle }}" class="form-control campo-orden mb-2" name="posicion" type="number" min="0" max="{{ max(0, $subtarea->elementos->count() - 1) }}" required value="{{ old('formulario') === $formEle ? old('posicion') : $elemento->posicion }}">
                <button type="submit" class="btn btn-light btn-sm">Guardar elemento</button>
            </form>
        </details>
    @endif
</div>
