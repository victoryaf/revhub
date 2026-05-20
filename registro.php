<?php
// proceso antes de la cabecera para poder redirigir
session_start();
include 'php/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $password2 = $_POST['password2'];

    if (empty($nombre) || empty($apellidos) || empty($username) || empty($email) || empty($password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // valido que el email tenga formato correcto
        $error = 'El email no tiene un formato válido.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } else {
        $email_esc    = mysqli_real_escape_string($conexion, $email);
        $username_esc = mysqli_real_escape_string($conexion, $username);
        
        // compruebo que el email o el username no existan ya
        $existe = mysqli_query($conexion,
            "SELECT id_usuario FROM usuarios
             WHERE email = '$email_esc' OR username = '$username_esc'"
        );

        if (mysqli_num_rows($existe) > 0) {
            $error = 'El email o el nombre de usuario ya están en uso.';
        } else {
            //cifrado de la contraseña
            $hash          = password_hash($password, PASSWORD_BCRYPT);
            $nombre_esc    = mysqli_real_escape_string($conexion, $nombre);
            $apellidos_esc = mysqli_real_escape_string($conexion, $apellidos);

            $insertar = mysqli_query($conexion,
                "INSERT INTO usuarios (nombre, apellidos, username, email, contrasena)
                 VALUES ('$nombre_esc', '$apellidos_esc', '$username_esc', '$email_esc', '$hash')"
            );
            
            // si se ha insertado correctamente, inicio sesión y redirijo al inicio
            if ($insertar) {
                $id = mysqli_insert_id($conexion);
                $_SESSION['usuario']  = $id;
                $_SESSION['username'] = $username;
                $_SESSION['nombre']   = $nombre;
                $_SESSION['rol']      = 'usuario';

                header('Location: /revhub/index.php');
                exit;
            } else {
                $error = 'Error al crear la cuenta. Inténtalo de nuevo.';
            }
        }
    }
}

include 'includes/cabecera.php';
?>

<main>
    <div class="contenedor">
        <div class="formulario">
            <h2>Crear cuenta</h2>
            <p class="subtitulo">Únete a la comunidad del motor</p>

            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-2col">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre"
                               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                               placeholder="Nombre">
                    </div>
                    <div class="form-group">
                        <label for="apellidos">Apellidos</label>
                        <input type="text" id="apellidos" name="apellidos"
                               value="<?= htmlspecialchars($_POST['apellidos'] ?? '') ?>"
                               placeholder="1Apellido 2Apellido">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Nombre de usuario</label>
                    <input type="text" id="username" name="username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           placeholder="nombre de usuario">
                </div>

                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="email@email.com">
                </div>

                <div class="form-2col">
                    <div class="form-group">
                        <label for="password">Contraseña</label>
                        <input type="password" id="password" name="password"
                               placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group">
                        <label for="password2">Confirmar contraseña</label>
                        <input type="password" id="password2" name="password2"
                               placeholder="Repite la contraseña">
                    </div>
                </div>

                <button type="submit" class="btn btn-full">Crear cuenta</button>
            </form>

            <p class="form-pie">
                ¿Ya tienes cuenta? <a href="/revhub/login.php">Inicia sesión</a>
            </p>
        </div>
    </div>
</main>

<?php include 'includes/pie.php'; ?>