<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <title>@yield('titulo', 'Proyectos') | Traza</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('traza/img/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('traza/vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('traza/css/estilos.css') }}">
    <link rel="stylesheet" href="{{ asset('traza/css/laravel.css') }}">
</head>
<body>
<div class="pagina-acceso">
<aside class="acceso-presentacion">
      <a class="marca" href="{{ route('login') }}"><img src="{{ asset('traza/img/favicon.svg') }}" alt=""><span><span class="marca-palabra">traza</span><span class="marca-subtitulo">gestión de proyectos</span></span></a>
      <div><div class="acceso-titular"><span class="etiqueta-superior">DEL PLAN AL RESULTADO</span><h1>Menos pendientes.<br><span>Más avances.</span></h1><p>Un solo lugar para organizar el trabajo, cuidar los detalles y construir en equipo.</p></div><div class="mini-tablero" aria-hidden="true"><div class="mini-columna"><small>Por hacer</small><div class="mini-tarjeta"><div class="mini-raya corta"></div><div class="mini-raya"></div></div><div class="mini-tarjeta"><div class="mini-raya"></div></div></div><div class="mini-columna"><small>En progreso</small><div class="mini-tarjeta"><div class="mini-raya corta"></div><div class="mini-raya"></div></div></div><div class="mini-columna"><small>En revisión</small><div class="mini-tarjeta"><div class="mini-raya corta"></div><div class="mini-raya"></div></div><div class="mini-tarjeta"><div class="mini-raya"></div></div></div><div class="mini-columna"><small>Completado</small><div class="mini-tarjeta"><div class="mini-raya corta"></div><div class="mini-raya"></div></div><div class="mini-tarjeta"><div class="mini-raya"></div></div></div></div></div>
      <div class="acceso-nota"><span data-icono="escudo"></span>Planifica. Construye. Revisa. Completa.</div>
    </aside>
    <main class="acceso-formulario">
        <div class="caja-acceso">
            <span class="etiqueta-superior texto-secundario">TU ESPACIO DE TRABAJO</span>
            <h2 class="mt-3">@yield('encabezado')</h2>
            <p>@yield('descripcion')</p>
            @include('componentes.mensajes')
            @yield('formulario')
        </div>
    </main>
</div>
<script src="{{ asset('traza/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('traza/js/iconos.js') }}"></script>
<script src="{{ asset('traza/js/interfaz.js') }}"></script>
</body>
</html>
