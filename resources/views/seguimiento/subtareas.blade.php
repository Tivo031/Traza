<section class="superficie mb-4" id="subtareas">
    <div class="cabecera-seguimiento">
        <h2>Subtareas <span class="texto-secundario">({{ $tarea->subtareas_count }})</span></h2>
        <span class="etiqueta categoria-1">{{ $tarea->subtareas_completadas_count }} completadas</span>
    </div>
    @if($avance !== null)
        <div class="avance-tarea"><span>Avance por subtareas</span><strong>{{ $avance }} %</strong></div>
        <progress max="100" value="{{ $avance }}" aria-label="Avance de subtareas">{{ $avance }} %</progress>
    @else
        <p class="texto-secundario">Sin subtareas.</p>
    @endif
    <p class="form-text">Completar el 100 % no cierra la tarea. Todavía debe pasar por revisión.</p>
    @if(! $editable)
        <div class="alert alert-light texto-mini">El trabajo es de solo lectura en revisión y en Completado.</div>
    @endif
    @foreach($tarea->subtareas as $subtarea)
        @include('seguimiento.subtarea')
    @endforeach
    @if($editable)
        <details class="editor-seguimiento" @if(old('formulario') === 'subtarea-nueva') open @endif>
            <summary class="enlace-editor">Agregar subtarea</summary>
            <form method="POST" action="{{ route('subtareas.store', $tarea) }}" class="mt-3" data-guardar-tarea>
                @include('seguimiento.version', ['formulario' => 'subtarea-nueva'])
                <label class="form-label" for="nueva-subtarea-titulo">Título *</label>
                <input class="form-control mb-3" id="nueva-subtarea-titulo" name="titulo" maxlength="150" required value="{{ old('formulario') === 'subtarea-nueva' ? old('titulo') : '' }}">
                <label class="form-label" for="nueva-subtarea-descripcion">Descripción</label>
                <textarea class="form-control mb-3" id="nueva-subtarea-descripcion" name="descripcion" rows="2" maxlength="10000">{{ old('formulario') === 'subtarea-nueva' ? old('descripcion') : '' }}</textarea>
                <button type="submit" class="btn btn-primary btn-sm">Guardar subtarea</button>
            </form>
        </details>
    @endif
</section>
