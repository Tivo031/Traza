/* Iconos SVG locales. No se descargan fuentes ni se requiere una cuenta externa. */
window.Iconos = (() => {
  const trazos = {
    tablero: '<rect x="3" y="4" width="18" height="16" rx="3"/><path d="M9 4v16m6-16v16M6 8v4m6-4v7m6-7v3"/>',
    proyectos: '<path d="M3 7a2 2 0 0 1 2-2h5l2 3h7a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z"/>',
    panel: '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
    usuarios: '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6m3 10v-3a6 6 0 0 0-3-5"/>',
    mas: '<path d="M12 5v14M5 12h14"/>',
    buscar: '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4 4"/>',
    flecha: '<path d="M5 12h14m-6-6 6 6-6 6"/>',
    volver: '<path d="M19 12H5m6-6-6 6 6 6"/>',
    abajo: '<path d="m6 9 6 6 6-6"/>',
    derecha: '<path d="m9 5 7 7-7 7"/>',
    calendario: '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-14 4h2m6 0h2"/>',
    check: '<path d="m5 12 4 4L19 6"/>',
    lista: '<path d="m3 6 1 1 2-2m3 1h12M3 12h3m3 0h12M3 18h3m3 0h12"/>',
    comentario: '<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5H4l-1 1v-9.5a9 9 0 0 1 18 0Z"/><path d="M7 10h10M7 14h6"/>',
    reloj: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    puntos: '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
    mover: '<path d="M4 8h16m-4-4 4 4-4 4M20 16H4m4-4-4 4 4 4"/>',
    editar: '<path d="m16 3 5 5-12 12H4v-5ZM14 5l5 5"/>',
    salir: '<path d="M10 4H4v16h6m4-12 4 4-4 4M8 12h13"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    reiniciar: '<path d="M3 10a9 9 0 1 1 2 8M3 3v7h7"/>',
    ayuda: '<circle cx="12" cy="12" r="9"/><path d="M9 8a3 3 0 0 1 6 0c0 2-3 2-3 5m0 3v1"/>',
    escudo: '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6Z"/><path d="m8 12 3 3 5-6"/>',
    defecto: '<rect x="7" y="7" width="10" height="13" rx="5"/><path d="m9 3 1 4m5-4-1 4M3 10h4m10 0h4M3 15h4m10 0h4M5 21l3-3m8 0 3 3M12 8v12"/>',
    aviso: '<path d="m12 3 10 18H2Z"/><path d="M12 9v5m0 3v1"/>',
    circulo: '<circle cx="12" cy="12" r="9"/>',
    correo: '<rect x="3" y="5" width="18" height="14" rx="3"/><path d="m3 7 9 6 9-6"/>',
    candado: '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/>',
    persona: '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
    ojo: '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    rayo: '<path d="m13 2-9 12h7l-1 8 10-13h-7Z"/>',
    enlace: '<path d="m10 13 4-4m-6 1-3 3a4 4 0 0 0 6 6l3-3m-4-8 3-3a4 4 0 0 1 6 6l-3 3"/>',
    cerrar: '<path d="m6 6 12 12M18 6 6 18"/>',
    bandera: '<path d="M5 22V3h7l2 3h6v11h-7l-2-3H5"/>',
    progreso: '<path d="M12 3a9 9 0 1 1-9 9m9-9v9H3"/>',
    agarrar: '<circle cx="9" cy="5" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="19" r="1"/>'
  };
  function svg(nombre, clase = '') {
    return `<svg class="icono ${clase}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${trazos[nombre] || trazos.circulo}</svg>`;
  }
  return { svg };
})();
