/* Presentacion solamente: no decide permisos ni guarda usuarios. */
(() => {
    'use strict';
    document.querySelectorAll('[data-icono]').forEach(nodo => {
        nodo.innerHTML = window.Iconos.svg(nodo.dataset.icono);
    });
    document.querySelectorAll('[data-ver-clave]').forEach(boton => {
        boton.addEventListener('click', () => {
            const campo = document.getElementById(boton.dataset.verClave);
            const visible = campo.type === 'password';
            campo.type = visible ? 'text' : 'password';
            boton.setAttribute('aria-pressed', String(visible));
            boton.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });
    const menu = document.getElementById('menu-lateral');
    const boton = document.getElementById('abrir-menu');
    const fondo = document.getElementById('fondo-menu');
    if (!menu || !boton || !fondo) return;
    const cerrar = () => {
        menu.classList.remove('abierto'); fondo.hidden = true;
        boton.setAttribute('aria-expanded', 'false');
    };
    boton.addEventListener('click', () => {
        const abierto = menu.classList.toggle('abierto');
        fondo.hidden = !abierto; boton.setAttribute('aria-expanded', String(abierto));
    });
    fondo.addEventListener('click', cerrar);
    document.addEventListener('keydown', e => { if(e.key === 'Escape') cerrar(); });
})();
