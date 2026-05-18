// Cerrar alertas automáticamente después de 3 segundos
window.onload = function() {
    var alertas = document.querySelectorAll('.alerta');
    alertas.forEach(function(alerta) {
        setTimeout(function() {
            alerta.style.display = 'none';
        }, 3000);
    });
}