@extends('layouts.aplicacion')
@section('titulo', 'Panel del proyecto')
@push('estilos')
<link rel="stylesheet" href="{{ asset('traza/css/seguimiento.css') }}">
@endpush
@section('contenido')
<a class="volver-tablero" href="{{ route('proyectos.show', $proyecto) }}"><span data-icono="volver"></span>Volver a tableros</a>
<div class="cabecera-pagina">
    <div><span class="etiqueta-superior">PANEL DEL PROYECTO</span><h1>{{ $proyecto->nombre }}</h1><p>Resumen del trabajo al consultar esta página.</p></div>
    <a class="btn btn-light" href="{{ route('panel.show', $proyecto) }}">Actualizar indicadores</a>
</div>
@if(! auth()->user()->esAdministrador())
    <div class="alert alert-light">Este resumen incluye únicamente tus tableros con asignación activa.</div>
@endif
<div class="resumen-seguimiento">
    <article class="superficie indicador-seguimiento"><span>Total de tareas</span><strong>{{ $resumen['total'] }}</strong><small>Todos los estados</small></article>
    <article class="superficie indicador-seguimiento"><span>Completadas</span><strong>{{ $resumen['completadas'] }}</strong><small>Validadas y cerradas</small></article>
    <article class="superficie indicador-seguimiento"><span>En progreso</span><strong>{{ $resumen['en_progreso'] }}</strong><small>Trabajo en desarrollo</small></article>
    <article class="superficie indicador-seguimiento"><span>Vencidas</span><strong>{{ $resumen['vencidas'] }}</strong><small>Límite anterior al día de hoy</small></article>
</div>
<p class="texto-mini texto-secundario mt-3">Una tarea vencida también puede estar En progreso o En revisión. No sumes estos cuatro indicadores como si fueran grupos separados. Hoy: {{ $hoy }} (Guatemala).</p>
<section class="superficie mt-4">
    <h2>Resumen por tablero</h2>
    @if($tableros->isEmpty())
        <p class="texto-secundario mb-0">El proyecto todavía no tiene tableros disponibles.</p>
    @else
        <div class="table-responsive">
            <table class="table tabla-panel align-middle mb-0">
                <thead><tr><th scope="col">Tablero</th><th scope="col">Total</th><th scope="col">Completadas</th><th scope="col">En progreso</th><th scope="col">Vencidas</th></tr></thead>
                <tbody>
                    @foreach($tableros as $tablero)
                        @php
                            $fila = $grupos->get($tablero->id_tablero);
                        @endphp
                        <tr>
                            <th scope="row"><a href="{{ route('tableros.show', $tablero) }}">{{ $tablero->nombre }}</a></th>
                            <td>{{ (int) ($fila?->total ?? 0) }}</td>
                            <td>{{ (int) ($fila?->completadas ?? 0) }}</td>
                            <td>{{ (int) ($fila?->en_progreso ?? 0) }}</td>
                            <td>{{ (int) ($fila?->vencidas ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
