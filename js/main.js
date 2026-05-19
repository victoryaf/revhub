/* ============================================================
   RevHub — JavaScript global
   ============================================================ */

/* --- Abrir modal --- */
function abrirModal(id) {
    var modal = document.getElementById(id);
    if (modal) {
        modal.style.display = 'flex';
    }
}

/* --- Cerrar modal --- */
function cerrarModal(id) {
    var modal = document.getElementById(id);
    if (modal) {
        modal.style.display = 'none';
    }
}

/* --- Cerrar modal al hacer clic fuera --- */
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
    }
});

/* --- Cerrar modal con Escape --- */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var modales = document.querySelectorAll('.modal-overlay');
        modales.forEach(function(m) {
            m.style.display = 'none';
        });
    }
});