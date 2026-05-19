<?php
include 'includes/cabecera.php';
include 'php/conexion.php';

/* Cargar PHPMailer */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$ok    = '';
$error = '';

/* --- Paso 1: Solicitar email --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'solicitar') {
    $email     = trim($_POST['email']);
    $email_esc = mysqli_real_escape_string($conexion, $email);

    $resultado = mysqli_query($conexion,
        "SELECT id_usuario, nombre FROM usuarios WHERE email = '$email_esc'"
    );

    if (mysqli_num_rows($resultado) === 0) {
        $error = 'No existe ninguna cuenta con ese email.';
    } else {
        $usuario = mysqli_fetch_assoc($resultado);

        /* Generar token único */
        $token   = bin2hex(random_bytes(32));
        $expira  = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $uid     = $usuario['id_usuario'];
        $token_e = mysqli_real_escape_string($conexion, $token);

        /* Borrar tokens anteriores del usuario */
        mysqli_query($conexion,
            "DELETE FROM recuperar_password WHERE id_usuario = $uid"
        );

        /* Guardar token */
        mysqli_query($conexion,
            "INSERT INTO recuperar_password (id_usuario, token, expira)
             VALUES ($uid, '$token_e', '$expira')"
        );

        /* Enviar email */
        $enlace = 'http://localhost/revhub/recuperar.php?token=' . $token;

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'vausinfernandez@gmail.com';
            $mail->Password   = 'gkcybyocwhpkxeoo'; 
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('vausinfernandez@gmail.com', 'RevHub');
            $mail->addAddress($email, $usuario['nombre']);
            $mail->CharSet = 'UTF-8';

            $mail->isHTML(true);
            $mail->Subject = 'Recuperar contraseña — RevHub';
            $mail->Body    = '
                <p>Hola ' . htmlspecialchars($usuario['nombre']) . ',</p>
                <p>Recibimos una solicitud para restablecer tu contraseña en RevHub.</p>
                <p>Haz clic en el siguiente enlace para crear una nueva contraseña. El enlace caduca en 1 hora.</p>
                <p><a href="' . $enlace . '" style="background:#C0392B;color:#fff;padding:10px 20px;border-radius:5px;text-decoration:none;">
                    Restablecer contraseña
                </a></p>
                <p>Si no solicitaste este cambio ignora este email.</p>
                <p>— El equipo de RevHub</p>
            ';

            $mail->send();
            $ok = 'Te hemos enviado un email con las instrucciones. Revisa también la carpeta de spam.';
        } catch (Exception $e) {
            $error = 'No se pudo enviar el email. Inténtalo más tarde.';
        }
    }
}

/* --- Paso 2: Cambiar contraseña con token --- */
$token_valido = null;
if (isset($_GET['token'])) {
    $token_get = mysqli_real_escape_string($conexion, $_GET['token']);
    $resultado = mysqli_query($conexion,
        "SELECT r.*, u.email FROM recuperar_password r
         JOIN usuarios u ON r.id_usuario = u.id_usuario
         WHERE r.token = '$token_get' AND r.expira > NOW()"
    );

    if (mysqli_num_rows($resultado) === 1) {
        $token_valido = mysqli_fetch_assoc($resultado);
    } else {
        $error = 'El enlace no es válido o ha caducado. Solicita uno nuevo.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar') {
    $token_post = mysqli_real_escape_string($conexion, $_POST['token']);
    $password   = $_POST['password'];
    $password2  = $_POST['password2'];

    $resultado = mysqli_query($conexion,
        "SELECT * FROM recuperar_password WHERE token = '$token_post' AND expira > NOW()"
    );

    if (mysqli_num_rows($resultado) === 0) {
        $error = 'El enlace no es válido o ha caducado.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
        $token_valido = mysqli_fetch_assoc($resultado);
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
        $token_valido = mysqli_fetch_assoc($resultado);
    } else {
        $fila  = mysqli_fetch_assoc($resultado);
        $uid   = $fila['id_usuario'];
        $hash  = password_hash($password, PASSWORD_BCRYPT);
        $hash_e = mysqli_real_escape_string($conexion, $hash);

        mysqli_query($conexion,
            "UPDATE usuarios SET contrasena = '$hash_e' WHERE id_usuario = $uid"
        );
        mysqli_query($conexion,
            "DELETE FROM recuperar_password WHERE id_usuario = $uid"
        );

        header('Location: /revhub/index.php?login=1');
        exit;
    }
}
?>

<main>
    <div class="contenedor">
        <div class="formulario">

            <?php if ($token_valido): ?>
                <!-- Formulario nueva contraseña -->
                <h2>Nueva contraseña</h2>
                <p class="subtitulo">Elige una contraseña segura</p>

                <?php if ($error): ?>
                    <div class="alerta alerta-error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="accion" value="cambiar">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token_valido['token']) ?>">

                    <div class="form-group">
                        <label for="password">Nueva contraseña</label>
                        <input type="password" id="password" name="password"
                               placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="form-group">
                        <label for="password2">Confirmar contraseña</label>
                        <input type="password" id="password2" name="password2"
                               placeholder="Repite la contraseña">
                    </div>
                    <button type="submit" class="btn btn-full">Guardar contraseña</button>
                </form>

            <?php elseif ($ok): ?>
                <!-- Mensaje de éxito -->
                <h2>Recuperar contraseña</h2>
                <div class="alerta alerta-ok"><?= $ok ?></div>
                <p class="form-pie"><a href="/revhub/index.php">Volver al inicio</a></p>

            <?php else: ?>
                <!-- Formulario solicitar email -->
                <h2>Recuperar contraseña</h2>
                <p class="subtitulo">Te enviaremos un enlace para restablecer tu contraseña</p>

                <?php if ($error): ?>
                    <div class="alerta alerta-error"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="accion" value="solicitar">
                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email"
                               placeholder="email@email.com">
                    </div>
                    <button type="submit" class="btn btn-full">Enviar enlace</button>
                </form>

                <p class="form-pie"><a href="/revhub/index.php">Volver al inicio</a></p>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php include 'includes/pie.php'; ?>