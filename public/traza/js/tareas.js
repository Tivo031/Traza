/* Sin datos simulados ni localStorage: formularios a Laravel con CSRF.
 * Arrastre HTML5 en escritorio; Abrir / Mover funciona con teclado y en movil. */
(() => {
    'use strict';
    const $ = (selector, raiz = document) => raiz.querySelector(selector);
    document.querySelectorAll('form[data-guardar-tarea]').forEach(formulario => {
        formulario.addEventListener('submit', evento => {
            if (evento.defaultPrevented || !formulario.checkValidity()) return;
            if (formulario.dataset.enviando === '1') { evento.preventDefault(); return; }
            formulario.dataset.enviando = '1';
            formulario.querySelectorAll('button[type="submit"]').forEach(boton => {
                boton.disabled = true; boton.textContent = 'Guardando...';
            });
        });
    });
    window.addEventListener('pageshow', evento => { if (evento.persisted) window.location.reload(); });
    const destinoDetalle = $('[data-destino-detalle]');
    const motivo = $('[data-motivo-detalle]');
    if (destinoDetalle && motivo) {
        const actualizar = () => { motivo.required = destinoDetalle.selectedOptions[0]?.dataset.rechazo === '1'; };
        destinoDetalle.addEventListener('change', actualizar); actualizar();
    }
    const kanban = $('[data-kanban]');
    const modalNodo = $('#modal-mover-tarea');
    if (!kanban || !modalNodo || !window.bootstrap) return;
    const modal = new bootstrap.Modal(modalNodo);
    const formulario = $('#form-mover-tarea');
    let arrastrada = null;
    const limpiarZonas = () => kanban.querySelectorAll('.zona-destino').forEach(n => n.classList.remove('zona-destino'));
    kanban.addEventListener('dragstart', evento => {
        const asa = evento.target.closest('[data-arrastrar]');
        if (!asa) { evento.preventDefault(); return; }
        arrastrada = asa.closest('[data-tarea]');
        arrastrada.classList.add('arrastrando');
        evento.dataTransfer.effectAllowed = 'move';
        evento.dataTransfer.setData('text/plain', arrastrada.dataset.tarea);
    });
    kanban.addEventListener('dragover', evento => {
        const lista = evento.target.closest('[data-lista-columna]');
        if (!arrastrada || !lista) return;
        evento.preventDefault(); evento.dataTransfer.dropEffect = 'move';
        limpiarZonas(); lista.classList.add('zona-destino');
    });
    kanban.addEventListener('dragend', () => {
        arrastrada?.classList.remove('arrastrando'); arrastrada = null; limpiarZonas();
    });
    kanban.addEventListener('drop', evento => {
        const lista = evento.target.closest('[data-lista-columna]');
        if (!arrastrada || !lista) return;
        evento.preventDefault(); limpiarZonas();
        const tarjeta = arrastrada;
        const origen = tarjeta.closest('[data-lista-columna]');
        const otras = [...lista.querySelectorAll('[data-tarea]')].filter(n => n !== tarjeta);
        let posicion = otras.findIndex(n => evento.clientY < n.getBoundingClientRect().top + n.offsetHeight / 2);
        if (posicion < 0) posicion = otras.length;
        // La tarjeta no se cambia de columna visualmente antes de que el servidor confirme.
        formulario.action = tarjeta.dataset.moverUrl;
        const campo = nombre => formulario.elements.namedItem(nombre);
        campo('id_columna_esperada').value = tarjeta.dataset.columna;
        campo('revision_esperada').value = tarjeta.dataset.revision;
        campo('id_columna_destino').value = lista.dataset.listaColumna;
        campo('posicion_destino').value = String(posicion);
        const inicio = origen.dataset.estado === 'POR_HACER' && lista.dataset.estado === 'EN_PROGRESO';
        const rechazo = origen.dataset.estado === 'EN_REVISION' && lista.dataset.estado === 'EN_PROGRESO';
        const revision = origen.dataset.estado === 'EN_REVISION' || lista.dataset.estado === 'EN_REVISION';
        $('#grupo-responsable-movimiento').hidden = !inicio;
        campo('id_responsable').disabled = !inicio; campo('id_responsable').required = inicio;
        campo('id_responsable').value = '';
        $('#grupo-observacion-movimiento').hidden = !rechazo;
        campo('observacion').disabled = !rechazo; campo('observacion').required = rechazo; campo('observacion').value = '';
        $('#grupo-criterio-movimiento').hidden = !revision;
        $('#criterio-movimiento').textContent = tarjeta.dataset.criterio;
        $('#resumen-movimiento').textContent = `#${tarjeta.dataset.tarea} ${tarjeta.dataset.titulo}\n${origen.dataset.nombre} -> ${lista.dataset.nombre}`;
        $('#titulo-movimiento').textContent = origen === lista ? 'Confirmar nuevo orden' : 'Confirmar movimiento';
        $('#confirmar-movimiento').textContent = lista.dataset.estado === 'COMPLETADO' ? 'Aprobar y completar' : (rechazo ? 'Devolver con observación' : 'Confirmar');
        modal.show();
    });
})();
