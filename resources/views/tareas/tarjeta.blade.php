@php
    $vista = \App\Support\VistaTarea::class;
    $codigo = $vista::codigo($tarea);
    $avance = $vista::porcentaje($tarea);
    $puedeArrastrar = $vista::editable($tarea) || ($codigo === 'EN_REVISION' && auth()->user()->esAdministrador());
@endphp
<article class="tarjeta-tarea tarea-real {{ $vista::vencida($tarea) ? 'tarea-vencida' : '' }}"
    data-tarea="{{ $tarea->id_tarea }}" data-columna="{{ $tarea->id_columna }}"
    data-revision="{{ $vista::revision($tarea) }}" data-mover-url="{{ route('tareas.mover', $tarea) }}"
    data-titulo="{{ $tarea->titulo }}" data-criterio="{{ $tarea->criterio_aceptacion }}">
    <div class="linea-etiquetas"><span class="etiqueta {{ $tarea->tipo->codigo === 'DEFECTO' ? 'defecto' : 'categoria-1' }}">{{ $tarea->tipo->nombre }}</span><span class="id-tarea">#{{ $tarea->id_tarea }}</span>
    @if($puedeArrastrar)<button class="btn-texto agarrador-tarea" type="button" draggable="true" data-arrastrar aria-label="Arrastrar tarea {{ $tarea->id_tarea }}" title="Arrastrar; también puedes usar Abrir / Mover"><span data-icono="agarrar"></span></button>@endif</div>
    <h3><a href="{{ route('tareas.show', $tarea) }}">{{ $tarea->titulo }}</a></h3>
    <div class="grupo-en-linea mt-2"><span class="etiqueta {{ $vista::prioridad($tarea->prioridad->codigo) }}">{{ $tarea->prioridad->nombre }}</span><span class="etiqueta categoria-2">{{ $tarea->categoria->nombre }}</span></div>
    <p class="texto-mini mt-3 mb-1"><span data-icono="persona"></span>{{ $tarea->responsable?->nombre ?? 'Sin responsable' }}</p>
    <p class="texto-mini mb-2 {{ $vista::vencida($tarea) ? 'text-danger' : 'texto-secundario' }}"><span data-icono="calendario"></span>{{ $tarea->fecha_limite?->format('d/m/Y') ?? 'Sin fecha límite' }} @if($vista::vencida($tarea))<strong> &middot; Vencida</strong>@endif</p>
    @if($avance !== null)<div class="avance-tarea"><span>{{ $tarea->subtareas_completadas_count }}/{{ $tarea->subtareas_count }} subtareas</span><strong>{{ $avance }} %</strong></div><progress value="{{ $avance }}" max="100" aria-label="Avance de subtareas">{{ $avance }} %</progress>@else<p class="texto-mini texto-secundario mb-2">Sin subtareas</p>@endif
    <a class="btn btn-light btn-sm w-100 mt-2" href="{{ route('tareas.show', $tarea) }}">{{ $codigo === 'COMPLETADO' ? 'Consultar' : 'Abrir / Mover' }}</a>
</article>
