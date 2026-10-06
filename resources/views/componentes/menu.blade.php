<aside id="menu-lateral" class="menu-lateral" aria-label="Menú principal">
    <a class="marca" href="{{ route('proyectos.index') }}"><img src="{{ asset('traza/img/favicon.svg') }}" alt=""><span><span class="marca-palabra">traza</span><span class="marca-subtitulo">gestión de proyectos</span></span></a>
    <div class="espacio-actual"><span class="espacio-icono" data-icono="proyectos"></span><div><strong>Equipo de desarrollo</strong><small>Proyecto final &middot; Calidad del Software</small></div></div>
    <nav>
        <p class="menu-titulo">ESPACIO DE TRABAJO</p>
        <a class="menu-enlace {{ request()->routeIs('proyectos.*', 'tableros.*', 'asignaciones.*') ? 'activo' : '' }}" href="{{ route('proyectos.index') }}"><span data-icono="proyectos"></span>Proyectos</a>
        @if(auth()->user()->esAdministrador())
            <div class="menu-grupo"><p class="menu-titulo">ADMINISTRACIÓN</p>
                <a class="menu-enlace {{ request()->routeIs('usuarios.*') ? 'activo' : '' }}" href="{{ route('usuarios.index') }}"><span data-icono="usuarios"></span>Usuarios</a>
            </div>
        @endif
    </nav>
    <div class="menu-inferior">
        <div class="nota-demo"><strong><span data-icono="escudo"></span>Acceso autenticado</strong>Proyectos y tableros vinculados a tu cuenta y a tus permisos.</div>
        <div class="usuario-menu"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nombre, 0, 1)) }}</span><div><strong>{{ auth()->user()->nombre }}</strong><span class="texto-mini">{{ auth()->user()->rol->nombre }}</span></div></div>
    </div>
</aside>
