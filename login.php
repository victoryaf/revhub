<?php include 'includes/cabecera.php'; ?>

<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'php/conexion.php';

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Introduce el email y la contraseña.';
    } else {
        $email_esc = mysqli_real_escape_string($conexion, $email);

        $resultado = mysqli_query($conexion,
            "SELECT * FROM usuarios WHERE email = '$email_esc'"
        );

        if (mysqli_num_rows($resultado) === 1) {
            $usuario = mysqli_fetch_assoc($resultado);

            if (password_verify($password, $usuario['contrasena'])) {
                $_SESSION['usuario']    = $usuario['id_usuario'];
                $_SESSION['username']   = $usuario['username'];
                $_SESSION['nombre']     = $usuario['nombre'];
                $_SESSION['rol']        = $usuario['rol'];

                header('Location: /revhub/index.php');
                exit;
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'No existe ninguna cuenta con ese email.';
        }
    }
}
?>

<main>
    <div class="contenedor">
        <div class="formulario">
            <h2>Iniciar sesión</h2>
            <p class="subtitulo">Bienvenido de nuevo</p>

            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email"
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