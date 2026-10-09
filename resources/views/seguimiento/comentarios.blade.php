<section class="superficie mb-4" id="comentarios">
    <h2>Comentarios</h2>
    @if($codigo !== 'COMPLETADO')
        <form method="POST" action="{{ route('comentarios.store', $tarea) }}" class="mb-4" data-guardar-tarea>
            @include('seguimiento.version', ['formulario' => 'comentario-nuevo'])
            <label class="form-label" for="nuevo-comentario">Nueva observación</label>
            <textarea id="nuevo-comentario" name="contenido" class="form-control mb-2" rows="3" maxlength="5000" required placeholder="Describe el avance, la revisión o una observación del trabajo.">{{ old('formulario') === 'comentario-nuevo' ? old('contenido') : '' }}</textarea>
            <p class="form-text">Hasta 5 000 caracteres. Una vez publicado, no se edita ni se elimina.</p>
            <button type="submit" class="btn btn-primary btn-sm">Publicar comentario</button>
        </form>
    @else
        <p class="texto-secundario texto-mini">Tarea completada: los comentarios son de solo lectura.</p>
    @endif
    @forelse($comentarios as $comentario)
        <article class="evento-tarea">
            <strong>{{ $comentario->autor->nombre }}</strong>
            <span class="texto-mini texto-secundario"> &middot; {{ $comentario->fecha_creacion->copy()->setTimezone('America/Guatemala')->format('d/m/Y H:i') }}</span>
            <p class="descripcion-con-saltos mb-0">{{ $comentario->contenido }}</p>
        </article>
    @empty
        <p class="texto-secundario">Sin comentarios.</p>
    @endforelse
    <div class="mt-3">{{ $comentarios->withQueryString()->fragment('comentarios')->links() }}</div>
</section>
