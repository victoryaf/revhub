<?php
session_start();
include 'php/conexion.php';

if (!isset($_SESSION['usuario']) || ($_SESSION['rol'] !== 'organizador' && $_SESSION['rol'] !== 'admin')) {
    header('Location: /revhub/index.php');
    exit;
}

$id_usuario = $_SESSION['usuario'];
$error      = '';

$tipos_disponibles  = ['clasico','tuning','deportivo','moto','otro'];
$marcas_disponibles = [
    'Alfa Romeo','Aston Martin','Audi','BMW','Bentley','Bugatti','Cadillac','Chevrolet',
    'Chrysler','Citroën','Dodge','Ferrari','Fiat','Ford','Honda','Hyundai','Jaguar','Jeep',
    'Kawasaki','Kia','Lamborghini','Land Rover','Lexus','Maserati','Mazda','Mercedes-Benz',
    'Mini','Mitsubishi','Nissan','Opel','Peugeot','Porsche','Renault','Rolls-Royce','SEAT',
    'Skoda','Subaru','Suzuki','Toyota','Triumph','Volkswagen','Volvo','Yamaha'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre                   = trim($_POST['nombre']);
    $descripcion              = trim($_POST['descripcion']);
    $fecha                    = trim($_POST['fecha']);
    $hora                     = trim($_POST['hora']);
    $ubicacion                = trim($_POST['ubicacion']);
    $salida                   = trim($_POST['salida'] ?? '');
    $destino                  = trim($_POST['destino'] ?? '');
    $puntos_intermedios       = trim($_POST['puntos_intermedios'] ?? '');
    $max_participantes        = trim($_POST['max_participantes']);
    $tipo_evento              = trim($_POST['tipo_evento']);
    $fecha_limite_inscripcion = trim($_POST['fecha_limite_inscripcion'] ?? '');
    $tipos_admitidos          = isset($_POST['tipos_admitidos'])  ? implode(',', $_POST['tipos_admitidos'])  : '';
    $marcas_admitidas         = isset($_POST['marcas_admitidas']) ? implode(',', $_POST['marcas_admitidas']) : '';

    if (empty($nombre) || empty($fecha) || empty($hora) || empty($ubicacion) || empty($max_participantes) || empty($tipo_evento)) {
        $error = 'Todos los campos excepto descripción, cartel y filtros son obligatorios.';
    } else {
        $nombre_e  = mysqli_real_escape_string($conexion, $nombre);
        $desc_e    = mysqli_real_escape_string($conexion, $descripcion);
        $fecha_e   = mysqli_real_escape_string($conexion, $fecha);
        $hora_e    = mysqli_real_escape_string($conexion, $hora);
        $ubic_e    = mysqli_real_escape_string($conexion, $ubicacion);
        $salida_e  = mysqli_real_escape_string($conexion, $salida);
        $destino_e = mysqli_real_escape_string($conexion, $destino);
        $puntos_e  = mysqli_real_escape_string($conexion, $puntos_intermedios);
        $max_e     = (int)$max_participantes;
        $tipo_e    = mysqli_real_escape_string($conexion, $tipo_evento);
        $tipos_e   = mysqli_real_escape_string($conexion, $tipos_admitidos);
        $marcas_e  = mysqli_real_escape_string($conexion, $marcas_admitidas);
        $plazo_e   = mysqli_real_escape_string($conexion, $fecha_limite_inscripcion);

        $salida_sql  = $salida               ? "'$salida_e'"  : 'NULL';
        $destino_sql = $destino              ? "'$destino_e'" : 'NULL';
        $puntos_sql  = $puntos_intermedios   ? "'$puntos_e'"  : 'NULL';
        $plazo_sql   = $fecha_limite_inscripcion ? "'$plazo_e'" : 'NULL';

        $cartel_sql = 'NULL';
        if (isset($_FILES['cartel']) && $_FILES['cartel']['error'] === 0) {
            $ext  = strtolower(pathinfo($_FILES['cartel']['name'], PATHINFO_EXTENSION));
            $perm = ['jpg','jpeg','png','webp'];
            if (!in_array($ext, $perm)) {
                $error = 'El cartel debe ser JPG, PNG o WebP.';
            } elseif ($_FILES['cartel']['size'] > 20*1024*1024) {
                $error = 'El cartel no puede superar 20 MB.';
            } else {
                $nc = uniqid('e_') . '.' . $ext;
                move_uploaded_file($_FILES['cartel']['tmp_name'],
                    $_SERVER['DOCUMENT_ROOT'] . '/revhub/img/eventos/' . $nc);
                $cartel_sql = "'" . mysqli_real_escape_string($conexion, $nc) . "'";
            }
        }

        if (empty($error)) {
            $res = mysqli_query($conexion,
                "INSERT INTO eventos (id_usuario, nombre, descripcion, fecha, hora, ubicacion,
                                      salida, destino, puntos_intermedios, max_participantes,
                                      tipo_evento, tipos_admitidos, marcas_admitidas, cartel,
                                      fecha_limite_inscripcion)
                 VALUES ($id_usuario,'$nombre_e','$desc_e','$fecha_e','$hora_e','$ubic_e',
                         $salida_sql,$destino_sql,$puntos_sql,$max_e,'$tipo_e',
                         '$tipos_e','$marcas_e',$cartel_sql,$plazo_sql)"
            );
            if ($res) {
                header('Location: /revhub/evento.php?id=' . mysqli_insert_id($conexion));
                exit;
            } else {
                $error = 'Error al crear el evento.';
            }
        }
    }
}

include 'includes/cabecera.php';
?>

<main>
    <div class="contenedor">
        <div class="formulario formulario-ancho">
            <h2>Crear evento</h2>
            <p class="subtitulo">Rellena los datos del nuevo evento</p>

            <?php if ($error): ?>
                <div class="alerta alerta-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">

                <div class="form-group">
                    <label for="nombre">Nombre del evento</label>
                    <input type="text" id="nombre" name="nombre"
                           placeholder="Nombre del evento"
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion"
                              placeholder="Describe el evento..."><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="form-2col">
                    <div class="form-group">
                        <label for="fecha">Fecha del evento</label>
                        <input type="date" id="fecha" name="fecha"
                               value="<?= htmlspecialchars($_POST['fecha'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="hora">Hora</label>
                        <input type="time" id="hora" name="hora"
                               value="<?= htmlspecialchars($_POST['hora'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="ubicacion">Ubicación general</label>
                    <input type="text" id="ubicacion" name="ubicacion"
                           placeholder="Ciudad, provincia..."
                           value="<?= htmlspecialchars($_POST['ubicacion'] ?? '') ?>">
                </div>

                <div class="form-2col">
                    <div class="form-group">
                        <label for="tipo_evento">Tipo de evento</label>
                        <select id="tipo_evento" name="tipo_evento" onchange="toggleRuta(this.value)">
                            <option value="">-- Selecciona --</option>
                            <option value="quedada"    <?= ($_POST['tipo_evento'] ?? '') === 'quedada'    ? 'selected':'' ?>>Quedada</option>
                            <option value="ruta"       <?= ($_POST['tipo_evento'] ?? '') === 'ruta'       ? 'selected':'' ?>>Ruta</option>
                            <option value="exposicion" <?= ($_POST['tipo_evento'] ?? '') === 'exposicion' ? 'selected':'' ?>>Exposición</option>
                            <option value="competicion"<?= ($_POST['tipo_evento'] ?? '') === 'competicion'? 'selected':'' ?>>Competición</option>
                            <option value="otro"       <?= ($_POST['tipo_evento'] ?? '') === 'otro'       ? 'selected':'' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="max_participantes">Plazas máximas</label>
                        <input type="number" id="max_participantes" name="max_participantes"
                               placeholder="50" min="1"
                               value="<?= htmlspecialchars($_POST['max_participantes'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="fecha_limite_inscripcion">Plazo límite de inscripción <span class="badge-opcional">Opcional</span></label>
                    <input type="date" id="fecha_limite_inscripcion" name="fecha_limite_inscripcion"
                           value="<?= htmlspecialchars($_POST['fecha_limite_inscripcion'] ?? '') ?>">
                    <small class="form-ayuda">Deja vacío si no hay fecha límite. Tras esta fecha no se podrán hacer nuevas inscripciones.</small>
                </div>

                <div id="campos-ruta" style="display:<?= ($_POST['tipo_evento'] ?? '') === 'ruta' ? 'block' : 'none' ?>">
                    <div class="filtros-admitidos">
                        <h3>Datos de la ruta <span class="badge-opcional">Solo rutas</span></h3>
                        <p class="subtitulo">Introduce el punto de salida y el destino para mostrar el mapa</p>
                        <div class="form-2col">
                            <div class="form-group">
                                <label for="salida">Punto de salida</label>
                                <input type="text" id="salida" name="salida"
                                       placeholder="Viana do Bolo, España"
                                       value="<?= htmlspecialchars($_POST['salida'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="destino">Destino</label>
                                <input type="text" id="destino" name="destino"
                                       placeholder="A Gudiña, España"
                                       value="<?= htmlspecialchars($_POST['destino'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="puntos_intermedios">Puntos intermedios</label>
                            <input type="text" id="puntos_intermedios" name="puntos_intermedios"
                                   placeholder="Vilariño de Conso, España; As Portas, España"
                                   value="<?= htmlspecialchars($_POST['puntos_intermedios'] ?? '') ?>">
                            <small class="form-ayuda">Separa cada parada con punto y coma ( ; ) en el orden en que quieres pasar por ellas</small>
                        </div>
                    </div>
                </div>

                <div class="filtros-admitidos">
                    <h3>Restricciones de vehículos <span class="badge-opcional">Opcional</span></h3>
                    <p class="subtitulo">Deja en blanco para admitir cualquier vehículo</p>

                    <div class="form-group">
                        <label>Tipos admitidos</label>
                        <div class="checkboxes-grupo">
                            <?php
                            $tipos_post = $_POST['tipos_admitidos'] ?? [];
                            foreach ($tipos_disponibles as $t):
                            ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="tipos_admitidos[]" value="<?= $t ?>"
                                       <?= in_array($t, $tipos_post) ? 'checked' : '' ?>>
                                <?= ucfirst($t) ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Marcas admitidas</label>
                        <small class="form-ayuda">Deja sin marcar para admitir todas las marcas</small>
                        <div class="checkboxes-grupo checkboxes-marcas">
                            <?php
                            $marcas_post = $_POST['marcas_admitidas'] ?? [];
                            foreach ($marcas_disponibles as $m):
                            ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="marcas_admitidas[]" value="<?= $m ?>"
                                       <?= in_array($m, $marcas_post) ? 'checked' : '' ?>>
                                <?= $m ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cartel">Cartel del evento</label>
                    <input type="file" id="cartel" name="cartel"
                           accept="image/jpg,image/jpeg,image/png,image/webp">
                    <small class="form-ayuda">JPG, PNG o WebP · Máximo 20 MB · Recomendado 800×450px</small>
                </div>

                <button type="submit" class="btn btn-full">Crear evento</button>
            </form>
        </div>
    </div>
</main>

<script>
function toggleRuta(tipo) {
    var campos = document.getElementById('campos-ruta');
    campos.style.display = tipo === 'ruta' ? 'block' : 'none';
}
</script>

<?php include 'includes/pie.php'; ?>