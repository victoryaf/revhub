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
        document.querySelectorAll('.modal-overlay').forEach(function(m) {
            m.style.display = 'none';
        });
    }
});

/* --- Menú hamburguesa --- */
function toggleMenu() {
    var nav = document.getElementById('nav-movil');
    if (nav) {
        nav.classList.toggle('abierto');
    }
}

/* --- Cerrar menú al hacer clic fuera --- */
document.addEventListener('click', function(e) {
    var nav = document.getElementById('nav-movil');
    var hamburguesa = document.querySelector('.hamburguesa');
    if (nav && nav.classList.contains('abierto')) {
        if (!nav.contains(e.target) && e.target !== hamburguesa && !hamburguesa.contains(e.target)) {
            nav.classList.remove('abierto');
        }
    }
});