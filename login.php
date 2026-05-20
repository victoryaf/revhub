<?php include 'includes/cabecera.php'; ?>

<?php
$error_login = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'login') {
    include_once 'php/conexion.php';

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // compruebo que no esten vacios
    if (empty($email) || empty($password)) {
        $error_login = 'Introduce el email o usuario y la contraseña.';
    } else {
        $email_esc = mysqli_real_escape_string($conexion, $email);

        // busco por email o por nombre de usuario
        $resultado = mysqli_query($conexion,
            "SELECT * FROM usuarios WHERE email = '$email_esc' OR username = '$email_esc'"
        );

        if (mysqli_num_rows($resultado) === 1) {
            $usuario = mysqli_fetch_assoc($resultado);

            // verifico la contraseña
            if (password_verify($password, $usuario['contrasena'])) {

                // si esta bloqueado no puede entrar
                if ($usuario['rol'] === 'bloqueado') {
                    $error_login = 'Tu cuenta ha sido bloqueada. Contacta con el administrador.';
                } else {
                    // guardo sus datos en sesion
                    $_SESSION['usuario']  = $usuario['id_usuario'];
                    $_SESSION['username'] = $usuario['username'];
                    $_SESSION['nombre']   = $usuario['nombre'];
                    $_SESSION['rol']      = $usuario['rol'];
                    //redirijo a la pagina de inicio
                    $url = "/revhub/index.php";
                    header('Location: ' . $url);
                    exit;
                }
            } else {
                $error_login = 'Contraseña incorrecta.';
            }
        } else {
            $error_login = 'No existe ninguna cuenta con ese email o nombre de usuario.';
        }
    }
}
?>

<main>
    <div class="contenedor">
        <div class="formulario">
            <h2>Iniciar sesión</h2>
            <p class="subtitulo">Bienvenido de nuevo</p>

            <?php if ($error_login): ?>
                <div class="alerta alerta-error"><?= $error_login ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Correo electrónico o nombre de usuario</label>
                    <input type="text" id="email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="email@email.com">
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Tu contraseña">
                </div>

                <button type="submit" class="btn" style="width:100%;">Entrar</button>
            </form>

            <p class="form-pie">
                ¿Olvidaste tu contraseña? <a href="/revhub/recuperar.php">Recupérala aquí</a>
            </p>
            <p class="form-pie">
                ¿No tienes cuenta? <a href="/revhub/registro.php">Regístrate</a>
            </p>
        </div>
    </div>
</main>

<?php include 'includes/pie.php'; ?>