<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RevHub</title>
    <link rel="stylesheet" href="/revhub/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header>
    <div class="nav">
        <a href="/revhub/index.php" class="logo">
            <img src="/revhub/img/logo.png" alt="RevHub">
            <span>rev<strong>hub</strong></span>
        </a>
        <nav>
            <a href="/revhub/eventos.php">Eventos</a>

            <?php if (isset($_SESSION['usuario'])): ?>
                <a href="/revhub/vehiculos.php">Vehículos</a>

                <?php if ($_SESSION['rol'] === 'organizador' || $_SESSION['rol'] === 'admin'): ?>
                    <a href="/revhub/crear_evento.php">Crear evento</a>
                <?php endif; ?>

                <?php if ($_SESSION['rol'] === 'admin'): ?>
                    <a href="/revhub/admin.php">Admin</a>
                <?php endif; ?>

                <a href="/revhub/perfil.php" class="nav-icono">
                    <i class="fa-regular fa-user"></i>
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </a>

                <a href="/revhub/logout.php" class="nav-icono" title="Cerrar sesión">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>

            <?php else: ?>
                <a href="/revhub/login.php">Entrar</a>
                <a href="/revhub/registro.php" class="btn">Registro</a>
            <?php endif; ?>
        </nav>
    </div>
</header>