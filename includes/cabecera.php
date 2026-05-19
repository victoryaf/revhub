<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* --- Procesar login desde el modal --- */
$error_login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'login') {
    include_once 'php/conexion.php';

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_login = 'Introduce el email o usuario y la contraseña.';
    } else {
        $email_esc = mysqli_real_escape_string($conexion, $email);

        $resultado = mysqli_query($conexion,
            "SELECT * FROM usuarios
             WHERE email = '$email_esc' OR username = '$email_esc'"
        );

        if (mysqli_num_rows($resultado) === 1) {
            $usuario = mysqli_fetch_assoc($resultado);

            if (password_verify($password, $usuario['contrasena'])) {
                $_SESSION['usuario']  = $usuario['id_usuario'];
                $_SESSION['username'] = $usuario['username'];
                $_SESSION['nombre']   = $usuario['nombre'];
                $_SESSION['rol']      = $usuario['rol'];

                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } else {
                $error_login = 'Contraseña incorrecta.';
            }
        } else {
            $error_login = 'No existe ninguna cuenta con ese email o nombre de usuario.';
        }
    }
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
    <link rel="icon" type="image/png" href="/revhub/img/logo.png">
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
                <button class="btn-nav" onclick="abrirModal('modal-login')">Entrar</button>
                <a href="/revhub/registro.php" class="btn">Registro</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- ===== MODAL LOGIN ===== -->
<div class="modal-overlay" id="modal-login" <?= $error_login ? 'style="display:flex;"' : '' ?>>
    <div class="modal">
        <button class="modal-cerrar" onclick="cerrarModal('modal-login')">&times;</button>
        <h2>Iniciar sesión</h2>
        <p class="subtitulo">Bienvenido de nuevo</p>

        <?php if ($error_login): ?>
            <div class="alerta alerta-error"><?= $error_login ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="accion" value="login">

            <div class="form-group">
                <label for="login-email">Email o nombre de usuario</label>
                <input type="text" id="login-email" name="email"
                       placeholder="Email o nombre de usuario">
            </div>
            <div class="form-group">
                <label for="login-password">Contraseña</label>
                <input type="password" id="login-password" name="password"
                       placeholder="Tu contraseña">
            </div>

            <button type="submit" class="btn btn-full">Entrar</button>
        </form>

        <p class="form-pie">
            <a href="/revhub/recuperar.php">¿Olvidaste tu contraseña?</a>
        </p>
        <p class="form-pie">
            ¿No tienes cuenta? <a href="/revhub/registro.php">Regístrate</a>
        </p>
    </div>
</div>