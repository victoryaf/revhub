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

                <a href="/revhub/perfil.php" class="nav-icono" title="Mi perfil">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                    </svg>
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </a>

                <a href="/revhub/logout.php" class="nav-icono" title="Cerrar sesión">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                    </svg>
                </a>

            <?php else: ?>

                <a href="/revhub/login.php">Entrar</a>
                <a href="/revhub/registro.php" class="btn">Registro</a>

            <?php endif; ?>
        </nav>
    </div>
</header>