@extends('layouts.aplicacion')
@section('titulo', 'Proyectos')
@section('contenido')
<div class="cabecera-pagina">
    <div><span class="etiqueta-superior">ESPACIO DE TRABAJO</span><h1>Proyectos y tableros</h1><p>Organiza el trabajo del equipo desde un solo lugar.</p></div>
    @if(auth()->user()->esAdministrador())
        <a href="{{ route('proyectos.create') }}" class="btn btn-primary"><span data-icono="mas"></span>Nuevo proyecto</a>
    @endif
</div>
@if($proyectos->isEmpty())
    <section class="superficie vacio">
        <span data-icono="proyectos"></span>
        <h2>{{ auth()->user()->esAdministrador() ? 'Crea tu primer proyecto' : 'Aún no tienes tableros asignados' }}</h2>
        <p>{{ auth()->user()->esAdministrador() ? 'Agrega un proyecto y después organiza sus tableros.' : 'Pide al Administrador que te asigne al tablero de tu equipo.' }}</p>
    </section>
@else
    <div class="rejilla-proyectos">
    @foreach($proyectos as $proyecto)
        <article class="tarjeta-proyecto">
            <div class="icono-proyecto"><span data-icono="proyectos"></span></div>
            <h2><a href="{{ route('proyectos.show', $proyecto) }}">{{ $proyecto->nombre }}</a></h2>
            <p>{{ \Illuminate\Support\Str::limit($proyecto->descripcion ?: 'Sin descripción.', 180) }}</p>
            <div class="lista-tableros">
                @forelse($proyecto->tableros->take(3) as $tablero)
                    <a class="enlace-tablero" href="{{ route('tableros.show', $tablero) }}"><span data-icono="tablero"></span><span>{{ $tablero->nombre }}</span><span data-icono="derecha"></span></a>
                @empty
                    <span class="texto-mini texto-secundario">Sin tableros disponibles.</span>
                @endforelse
            </div>
            <a class="btn btn-light btn-sm" href="{{ route('proyectos.show', $proyecto) }}">Ver proyecto <span data-icono="flecha"></span></a>
            @if(auth()->user()->esAdministrador())
                <a class="btn btn-sm btn-suave" href="{{ route('proyectos.edit', $proyecto) }}" aria-label="Editar proyecto {{ $proyecto->nombre }}">Editar</a>
            @endif
        </article>
    @endforeach
    </div>
    <div class="mt-4">{{ $proyectos->links() }}</div>
@endif
@endsection
