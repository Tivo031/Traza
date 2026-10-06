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
    <link rel="stylesheet" href="{{ asset('traza/css/organizacion.css') }}">
    @stack('estilos')
</head>
<body>
    <a class="salto-contenido" href="#contenido">Saltar al contenido</a>
    @include('componentes.menu')
    <div id="fondo-menu" class="fondo-menu" hidden></div>
    <div class="cuerpo-app">
        <header class="cabecera-app">
            <div class="migas">
                <button class="btn-texto boton-menu-movil" id="abrir-menu" aria-controls="menu-lateral" aria-expanded="false" aria-label="Abrir menú"><span data-icono="menu"></span></button>
                <a href="{{ route('proyectos.index') }}">Mi espacio</a><span data-icono="derecha"></span><strong>@yield('titulo')</strong>
            </div>
            <div class="herramientas-cabecera">
                <span class="texto-mini d-none d-sm-inline">{{ auth()->user()->nombre }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-light btn-sm" type="submit"><span data-icono="salir"></span>Cerrar sesión</button></form>
            </div>
        </header>
        <main class="contenido" id="contenido" tabindex="-1">
            @include('componentes.mensajes')
            @yield('contenido')
            <footer class="pie-app"><span>Traza &middot; Calidad del Software</span><span>Organización del equipo</span></footer>
        </main>
    </div>
<script src="{{ asset('traza/vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('traza/js/iconos.js') }}"></script>
<script src="{{ asset('traza/js/interfaz.js') }}"></script>
<script src="{{ asset('traza/js/organizacion.js') }}"></script>
@stack('scripts')
</body>
</html>
