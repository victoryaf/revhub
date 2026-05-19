<?php
include 'includes/cabecera.php';
include 'php/conexion.php';

/* --- Comprobar que el usuario está logueado --- */
if (!isset($_SESSION['usuario'])) {
    header('Location: /revhub/login.php');
    exit;
}

/* --- Comprobar que llega un id de evento válido --- */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /revhub/eventos.php');
    exit;
}

$id_evento  = (int)$_GET['id'];
$id_usuario = $_SESSION['usuario'];

/* --- Obtener el evento --- */
$result = mysqli_query($conexion, "SELECT * FROM eventos WHERE id_evento = $id_evento");

if (mysqli_num_rows($result) === 0) {
    header('Location: /revhub/eventos.php');
    exit;
}

$evento = mysqli_fetch_assoc($result);

/* --- Comprobar si ya está inscrito --- */
$check = mysqli_query($conexion,
    "SELECT id_inscripcion FROM inscripciones
     WHERE id_usuario = $id_usuario AND id_evento = $id_evento"
);

if (mysqli_num_rows($check) > 0) {
    header("Location: /revhub/evento.php?id=$id_evento");
    exit;
}

/* --- Comprobar plazas disponibles --- */
$inscritos = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(DISTINCT id_usuario) as total FROM inscripciones WHERE id_evento = $id_evento"
))['total'];

if ($inscritos >= $evento['max_participantes']) {
    header("Location: /revhub/evento.php?id=$id_evento");
    exit;
}

/* --- Obtener vehículos del usuario --- */
$vehiculos = mysqli_query($conexion,
    "SELECT * FROM vehiculos WHERE id_usuario = $id_usuario"
);

/* --- Procesar el formulario --- */
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['id_vehiculo']) || !is_numeric($_POST['id_vehiculo'])) {
        $error = 'Debes seleccionar un vehículo.';
    } else {
        $id_vehiculo = (int)$_POST['id_vehiculo'];

        /* Comprobar que el vehículo pertenece al usuario */
        $check_v = mysqli_query($conexion,
            "SELECT id_vehiculo FROM vehiculos
             WHERE id_vehiculo = $id_vehiculo AND id_usuario = $id_usuario"
        );

        if (mysqli_num_rows($check_v) === 0) {
            $error = 'Vehículo no válido.';
        } else {
            mysqli_query($conexion,
                "INSERT INTO inscripciones (id_usuario, id_evento, id_vehiculo)
                 VALUES ($id_usuario, $id_evento, $id_vehiculo)"
            );
            header("Location: /revhub/evento.php?id=$id_evento");
            exit;
        }
    }
}
?>

<main>
    <div class="contenedor">
        <a href="/revhub/evento.php?id=<?= $id_evento ?>" class="volver">&larr; Volver al evento</a>

        <div class="formulario">
            <h2>Inscribirse en el evento</h2>
            <p class="subtitulo"><?= htmlspecialchars($evento['nombre']) ?></p>

            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if (mysqli_num_rows($vehiculos) > 0): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="id_vehiculo">Selecciona el vehículo con el que asistirás</label>
                        <select id="id_vehiculo" name="id_vehiculo">
                            <option value="">-- Elige un vehículo --</option>
                            <?php while ($v = mysqli_fetch_assoc($vehiculos)): ?>
                                <option value="<?= $v['id_vehiculo'] ?>">
                                    <?= htmlspecialchars($v['marca']) ?> <?= htmlspecialchars($v['modelo']) ?>
                                    (<?= htmlspecialchars($v['matricula']) ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-full">Confirmar inscripción</button>
                </form>
            <?php else: ?>
                <div class="alerta alerta-info">
                    No tienes ningún vehículo registrado.
                    <a href="/revhub/vehiculos.php">Añade uno aquí</a> antes de inscribirte.
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/pie.php'; ?>