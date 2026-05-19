<?php
http_response_code(404);
include 'includes/cabecera.php';
?>

<main>
    <div class="contenedor pagina-404">
        <h1>404</h1>
        <h2>Página no encontrada</h2>
        <p>La página que buscas no existe o ha sido eliminada.</p>
        <a href="/revhub/index.php" class="btn">Volver al inicio</a>
    </div>
</main>

<?php include 'includes/pie.php'; ?>