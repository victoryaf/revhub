<?php
include 'includes/cabecera.php';
include 'php/conexion.php';

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] !== 'organizador' && $_SESSION['rol'] !== 'admin')) {
    header('Location: /revhub/index.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /revhub/eventos.php');
    exit;
}

$id         = (int)$_GET['id'];
$id_usuario = $_SESSION['usuario'];

$result = mysqli_query($conexion, "SELECT * FROM eventos WHERE id_evento = $id");
if (mysqli_num_rows($result) === 0) {
    header('Location: /revhub/eventos.php');
    exit;
}

$evento = mysqli_fetch_assoc($result);

if ($evento['id_usuario'] != $id_usuario && $_SESSION['rol'] !== 'admin') {
    header('Location: /revhub/eventos.php');
    exit;
}

$error = '';

$tipos_disponibles  = ['clasico','tuning','deportivo','moto','otro'];
$marcas_disponibles = [
    'Alfa Romeo','Aston Martin','Audi','BMW','Bentley','Bugatti','Cadillac','Chevrolet',
    'Chrysler','Citroën','Dodge','Ferrari','Fiat','Ford','Honda','Hyundai','Jaguar','Jeep',
    'Kawasaki','Kia','Lamborghini','Land Rover','Lexus','Maserati','Mazda','Mercedes-Benz',
    'Mini','Mitsubishi','Nissan','Opel','Peugeot','Porsche','Renault','Rolls-Royce','SEAT',
    'Skoda','Subaru','Suzuki','Toyota','Triumph','Volkswagen','Volvo','Yamaha'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre            = trim($_POST['nombre']);
    $descripcion       = trim($_POST['descripcion']);
    $fecha             = trim($_POST['fecha']);
    $hora              = trim($_POST['hora']);
    $ubicacion         = trim($_POST['ubicacion']);
    $max_participantes = trim($_POST['max_participantes']);
    $tipo_evento       = trim($_POST['tipo_evento']);
    $tipos_admitidos   = isset($_POST['tipos_admitidos'])  ? implode(',', $_POST['tipos_admitidos'])  : '';
    $marcas_admitidas  = isset($_POST['marcas_admitidas']) ? implode(',', $_POST['marcas_admitidas']) : '';

    if (empty($nombre) || empty($fecha) || empty($hora) || empty($ubicacion) || empty($max_participantes) || empty($tipo_evento)) {
        $error = 'Todos los campos excepto descripción, cartel y filtros son obligatorios.';
    } else {
        $nombre_e  = mysqli_real_escape_string($conexion, $nombre);
        $desc_e    = mysqli_real_escape_string($conexion, $descripcion);
        $fecha_e   = mysqli_real_escape_string($conexion, $fecha);
        $hora_e    = mysqli_real_escape_string($conexion, $hora);
        $ubic_e    = mysqli_real_escape_string($conexion, $ubicacion);
        $max_e     = (int)$max_participantes;
        $tipo_e    = mysqli_real_escape_string($conexion, $tipo_evento);
        $tipos_e   = mysqli_real_escape_string($conexion, $tipos_admitidos);
        $marcas_e  = mysqli_real_escape_string($conexion, $marcas_admitidas);

        $cartel_sql = '';
        if (isset($_FILES['cartel']) && $_FILES['cartel']['error'] === 0) {
            $ext  = strtolower(pathinfo($_FILES['cartel']['name'], PATHINFO_EXTENSION));
            $perm = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $perm)) {
                $error = 'El cartel debe ser JPG, PNG o WebP.';
            } elseif ($_FILES['cartel']['size'] > 2*1024*1024) {
                $error = 'El cartel no puede superar 20 MB.';
            } else {
                $nc = uniqid('e_') . '.' . $ext;
                move_uploaded_file($_FILES['cartel']['tmp_name'],
                    $_SERVER['DOCUMENT_ROOT'] . '/revhub/img/eventos/' . $nc);
                $cartel_sql = ", cartel = '" . mysqli_real_escape_string($conexion, $nc) . "'";
            }
        }

        if (empty($error)) {
            mysqli_query($conexion,
                "UPDATE eventos SET
                    nombre = '$nombre_e', descripcion = '$desc_e', fecha = '$fecha_e',
                    hora = '$hora_e', ubicacion = '$ubic_e', max_participantes = $max_e,
                    tipo_evento = '$tipo_e', tipos_admitidos = '$tipos_e', marcas_admitidas = '$marcas_e'
                    $cartel_sql
                 WHERE id_evento = $id"
            );
            header("Location: /revhub/evento.php?id=$id");
            exit;
        }
    }
}

$tipos_actuales  = !empty($evento['tipos_admitidos'])  ? explode(',', $evento['tipos_admitidos'])  : [];
$marcas_actuales = !empty($evento['marcas_admitidas']) ? explode(',', $evento['marcas_admitidas']) : [];
?>

<main>
    <div class="contenedor">
        <a href="/revhub/evento.php?id=<?= $id ?>" class="volver">&larr; Volver al evento</a>

        <div class="formulario formulario-ancho">
            <h2>Editar evento</h2>
            <p class="subtitulo"><?= htmlspecialchars($evento['nombre']) ?></p>

            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">

                <div class="form-group">
                    <label for="nombre">Nombre del evento</label>
                    <input type="text" id="nombre" name="nombre"
                           value="<?= htmlspecialchars($evento['nombre']) ?>">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion"><?= htmlspecialchars($evento['descripcion']) ?></textarea>
                </div>

                <div class="form-2col">
                    <div class="form-group">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha"
                               value="<?= htmlspecialchars($evento['fecha']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="hora">Hora</label>
                        <input type="time" id="hora" name="hora"
                               value="<?= substr($evento['hora'], 0, 5) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="ubicacion">Ubicación</label>
                    <input type="text" id="ubicacion" name="ubicacion"
                           value="<?= htmlspecialchars($evento['ubicacion']) ?>">
                </div>

                <div class="form-2col">
                    <div class="form-group">
                        <label for="tipo_evento">Tipo de evento</label>
                        <select id="tipo_evento" name="tipo_evento">
                            <option value="quedada"    <?= $evento['tipo_evento'] === 'quedada'    ? 'selected':'' ?>>Quedada</option>
                            <option value="ruta"       <?= $evento['tipo_evento'] === 'ruta'       ? 'selected':'' ?>>Ruta</option>
                            <option value="exposicion" <?= $evento['tipo_evento'] === 'exposicion' ? 'selected':'' ?>>Exposición</option>
                            <option value="competicion"<?= $evento['tipo_evento'] === 'competicion'? 'selected':'' ?>>Competición</option>
                            <option value="otro"       <?= $evento['tipo_evento'] === 'otro'       ? 'selected':'' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="max_participantes">Plazas máximas</label>
                        <input type="number" id="max_participantes" name="max_participantes"
                               min="1" value="<?= $evento['max_participantes'] ?>">
                    </div>
                </div>

                <div class="filtros-admitidos">
                    <h3>Restricciones de vehículos <span class="badge-opcional">Opcional</span></h3>
                    <p class="subtitulo">Deja en blanco para admitir cualquier vehículo</p>

                    <div class="form-group">
                        <label>Tipos admitidos</label>
                        <div class="checkboxes-grupo">
                            <?php foreach ($tipos_disponibles as $t): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="tipos_admitidos[]" value="<?= $t ?>"
                                       <?= in_array($t, $tipos_actuales) ? 'checked' : '' ?>>
                                <?= ucfirst($t) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Marcas admitidas</label>
                        <small class="form-ayuda">Deja sin marcar para admitir todas las marcas</small>
                        <div class="checkboxes-grupo checkboxes-marcas">
                            <?php foreach ($marcas_disponibles as $m): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="marcas_admitidas[]" value="<?= $m ?>"
                                       <?= in_array($m, $marcas_actuales) ? 'checked' : '' ?>>
                                <?= $m ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nuevo cartel (opcional)</label>
                    <?php if ($evento['cartel']): ?>
                        <img src="/revhub/img/eventos/<?= htmlspecialchars($evento['cartel']) ?>"
                             alt="Cartel actual" class="cartel-preview">
                    <?php endif; ?>
                    <input type="file" name="cartel"
                           accept="image/jpg,image/jpeg,image/png,image/webp">
                    <small class="form-ayuda">Deja vacío para mantener el actual</small>
                </div>

                <button type="submit" class="btn btn-full">Guardar cambios</button>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/pie.php'; ?>