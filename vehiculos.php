<?php
include 'includes/cabecera.php';
include 'php/conexion.php';

//solo usuarios registrados pueden acceder a esta pagina
if (!isset($_SESSION['usuario'])) {
    header('Location: /revhub/index.php');
    exit;
}

$id_usuario = $_SESSION['usuario'];
$ok    = '';
$error = '';

/* --- Eliminar vehículo --- */
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id_v = (int)$_GET['eliminar'];
    $check = mysqli_query($conexion,
        "SELECT id_vehiculo FROM vehiculos WHERE id_vehiculo = $id_v AND id_usuario = $id_usuario"
    );
    if (mysqli_num_rows($check) > 0) {
        //borro la imagen si existe y el vehículo de la base de datos
        $img = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT imagen FROM vehiculos WHERE id_vehiculo = $id_v"));
        if ($img['imagen']) {
            $ruta = $_SERVER['DOCUMENT_ROOT'] . '/revhub/img/vehiculos/' . $img['imagen'];
            if (file_exists($ruta)) unlink($ruta);
        }
        mysqli_query($conexion, "DELETE FROM vehiculos WHERE id_vehiculo = $id_v");
        $ok = 'Vehículo eliminado correctamente.';
    }
}

/* --- Añadir vehículo --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'añadir') {
    $marca          = trim($_POST['marca']);
    $modelo         = trim($_POST['modelo']);
    $anio           = trim($_POST['anio']);
    $color          = trim($_POST['color']);
    $tipos          = isset($_POST['tipo_vehiculo']) ? implode(',', $_POST['tipo_vehiculo']) : '';
    $matricula      = trim($_POST['matricula']);
    $descripcion    = trim($_POST['descripcion']);
    $modificaciones = trim($_POST['modificaciones']);
    
    //compruebo que los campos obligatorios no esten vacios
    if (empty($marca) || empty($modelo) || empty($anio) || empty($color) || empty($tipos) || empty($matricula)) {
        $error = 'Los campos marca, modelo, año, color, tipo y matrícula son obligatorios.';
    } else {
        $marca_e  = mysqli_real_escape_string($conexion, $marca);
        $modelo_e = mysqli_real_escape_string($conexion, $modelo);
        $anio_e   = mysqli_real_escape_string($conexion, $anio);
        $color_e  = mysqli_real_escape_string($conexion, $color);
        $tipos_e  = mysqli_real_escape_string($conexion, $tipos);
        $mat_e    = mysqli_real_escape_string($conexion, $matricula);
        $desc_e   = mysqli_real_escape_string($conexion, $descripcion);
        $mods_e   = mysqli_real_escape_string($conexion, $modificaciones);

        //la matrícula debe ser única, compruebo que no exista ya otra igual
        $check_m = mysqli_query($conexion, "SELECT id_vehiculo FROM vehiculos WHERE matricula = '$mat_e'");
        if (mysqli_num_rows($check_m) > 0) {
            $error = 'Ya existe un vehículo con esa matrícula.';
        } else {
            //subida de imagen (opcional)
            $img_sql = 'NULL';
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
                $ext  = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                $perm = ['jpg','jpeg','png','webp'];
                if (!in_array($ext, $perm)) {
                    $error = 'La imagen debe ser JPG, PNG o WebP.';
                } elseif ($_FILES['imagen']['size'] > 20*1024*1024) {
                    $error = 'La imagen no puede superar 20 MB.';
                } else {
                    $nombre_img = uniqid('v_') . '.' . $ext;
                    move_uploaded_file($_FILES['imagen']['tmp_name'],
                        $_SERVER['DOCUMENT_ROOT'] . '/revhub/img/vehiculos/' . $nombre_img);
                    $img_sql = "'" . mysqli_real_escape_string($conexion, $nombre_img) . "'";
                }
            }
            if (empty($error)) {
                mysqli_query($conexion,
                    "INSERT INTO vehiculos (id_usuario, marca, modelo, anio, color, tipo_vehiculo, matricula, descripcion, modificaciones, imagen)
                     VALUES ($id_usuario,'$marca_e','$modelo_e','$anio_e','$color_e','$tipos_e','$mat_e','$desc_e','$mods_e',$img_sql)"
                );
                $ok = 'Vehículo añadido correctamente.';
            }
        }
    }
}

/* --- Editar vehículo --- */
$vehiculo_editar = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $id_v           = (int)$_POST['id_vehiculo'];
    $marca          = trim($_POST['marca']);
    $modelo         = trim($_POST['modelo']);
    $anio           = trim($_POST['anio']);
    $color          = trim($_POST['color']);
    $tipos          = isset($_POST['tipo_vehiculo']) ? implode(',', $_POST['tipo_vehiculo']) : '';
    $matricula      = trim($_POST['matricula']);
    $descripcion    = trim($_POST['descripcion']);
    $modificaciones = trim($_POST['modificaciones']);

    /* Verificar que es del usuario */
    $check = mysqli_query($conexion,
        "SELECT id_vehiculo FROM vehiculos WHERE id_vehiculo = $id_v AND id_usuario = $id_usuario"
    );

    if (mysqli_num_rows($check) === 0) {
        $error = 'Vehículo no válido.';
    } elseif (empty($marca) || empty($modelo) || empty($anio) || empty($color) || empty($tipos) || empty($matricula)) {
        $error = 'Los campos marca, modelo, año, color, tipo y matrícula son obligatorios.';
        /* Reabrir modal con datos */
        $vehiculo_editar = ['id_vehiculo' => $id_v, 'marca' => $marca, 'modelo' => $modelo,
            'anio' => $anio, 'color' => $color, 'tipo_vehiculo' => $tipos,
            'matricula' => $matricula, 'descripcion' => $descripcion, 'modificaciones' => $modificaciones];
    } else {
        $marca_e  = mysqli_real_escape_string($conexion, $marca);
        $modelo_e = mysqli_real_escape_string($conexion, $modelo);
        $anio_e   = mysqli_real_escape_string($conexion, $anio);
        $color_e  = mysqli_real_escape_string($conexion, $color);
        $tipos_e  = mysqli_real_escape_string($conexion, $tipos);
        $mat_e    = mysqli_real_escape_string($conexion, $matricula);
        $desc_e   = mysqli_real_escape_string($conexion, $descripcion);
        $mods_e   = mysqli_real_escape_string($conexion, $modificaciones);

        /* Comprobar matrícula única (excepto la del propio vehículo) */
        $check_m = mysqli_query($conexion,
            "SELECT id_vehiculo FROM vehiculos WHERE matricula = '$mat_e' AND id_vehiculo != $id_v"
        );
        if (mysqli_num_rows($check_m) > 0) {
            $error = 'Ya existe otro vehículo con esa matrícula.';
        } else {
            //subida de imagen (opcional)
            $img_sql = '';
            if (isset($_FILES['imagen_editar']) && $_FILES['imagen_editar']['error'] === 0) {
                $ext  = strtolower(pathinfo($_FILES['imagen_editar']['name'], PATHINFO_EXTENSION));
                $perm = ['jpg','jpeg','png','webp'];
                if (!in_array($ext, $perm)) {
                    $error = 'La imagen debe ser JPG, PNG o WebP.';
                } elseif ($_FILES['imagen_editar']['size'] > 20*1024*1024) {
                    $error = 'La imagen no puede superar 20 MB.';
                } else {
                    $nombre_img = uniqid('v_') . '.' . $ext;
                    move_uploaded_file($_FILES['imagen_editar']['tmp_name'],
                        $_SERVER['DOCUMENT_ROOT'] . '/revhub/img/vehiculos/' . $nombre_img);
                    $img_sql = ", imagen = '" . mysqli_real_escape_string($conexion, $nombre_img) . "'";
                }
            }
            if (empty($error)) {
                mysqli_query($conexion,
                    "UPDATE vehiculos SET marca='$marca_e', modelo='$modelo_e', anio='$anio_e',
                     color='$color_e', tipo_vehiculo='$tipos_e', matricula='$mat_e',
                     descripcion='$desc_e', modificaciones='$mods_e' $img_sql
                     WHERE id_vehiculo = $id_v AND id_usuario = $id_usuario"
                );
                $ok = 'Vehículo actualizado correctamente.';
            }
        }
    }
}

/* --- Cargar datos para editar (GET) --- */
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_v = (int)$_GET['editar'];
    $res  = mysqli_query($conexion,
        "SELECT * FROM vehiculos WHERE id_vehiculo = $id_v AND id_usuario = $id_usuario"
    );
    if (mysqli_num_rows($res) > 0) {
        $vehiculo_editar = mysqli_fetch_assoc($res);
    }
}
//cargar vehículos del usuario para mostrarlos en la página
$vehiculos = mysqli_query($conexion,
    "SELECT * FROM vehiculos WHERE id_usuario = $id_usuario ORDER BY marca ASC"
);

$tipos_disponibles = ['clasico','tuning','deportivo','moto','otro'];
$marcas_disponibles = [
    'Alfa Romeo','Aston Martin','Audi','BMW','Bentley','Bugatti','Cadillac','Chevrolet',
    'Chrysler','Citroën','Dodge','Ferrari','Fiat','Ford','Honda','Hyundai','Jaguar','Jeep',
    'Kawasaki','Kia','Lamborghini','Land Rover','Lexus','Maserati','Mazda','Mercedes-Benz',
    'Mini','Mitsubishi','Nissan','Opel','Peugeot','Porsche','Renault','Rolls-Royce','SEAT',
    'Skoda','Subaru','Suzuki','Toyota','Triumph','Volkswagen','Volvo','Yamaha'
];
?>

<main>
    <div class="contenedor">
        <div class="seccion-header">
            <h2 class="pagina-titulo">Mis vehículos</h2>
            <button class="btn" onclick="abrirModal('modal-añadir-vehiculo')">
                <i class="fa-solid fa-plus"></i> Añadir vehículo
            </button>
        </div>

        <?php if ($ok): ?>
            <div class="alerta alerta-ok"><?= $ok ?></div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($vehiculos) > 0): ?>
            <div class="vehiculos-grid">
                <?php while ($v = mysqli_fetch_assoc($vehiculos)): ?>
                <div class="tarjeta-vehiculo">
                    <!-- foto del vehículo o icono genérico -->
                    <div class="vehiculo-cabecera">
                        <?php if ($v['imagen']): ?>
                            <img src="/revhub/img/vehiculos/<?= htmlspecialchars($v['imagen']) ?>"
                                 alt="<?= htmlspecialchars($v['marca']) ?> <?= htmlspecialchars($v['modelo']) ?>">
                        <?php else: ?>
                            <i class="fa-solid fa-car"></i>
                        <?php endif; ?>
                    </div>
                    <!-- datos del vehículo -->
                    <div class="vehiculo-cuerpo">
                        <h3><?= htmlspecialchars($v['marca']) ?> <?= htmlspecialchars($v['modelo']) ?></h3>
                        <div class="vehiculo-datos">
                            <div class="vehiculo-dato">
                                <span>Año</span>
                                <span><?= htmlspecialchars($v['anio']) ?></span>
                            </div>
                            <div class="vehiculo-dato">
                                <span>Color</span>
                                <span><?= htmlspecialchars($v['color']) ?></span>
                            </div>
                            <div class="vehiculo-dato">
                                <span>Tipo</span>
                                <span><?= htmlspecialchars($v['tipo_vehiculo']) ?></span>
                            </div>
                            <div class="vehiculo-dato">
                                <span>Matrícula</span>
                                <span class="vehiculo-matricula"><?= htmlspecialchars($v['matricula']) ?></span>
                            </div>
                        </div>
                        <?php if ($v['descripcion']): ?>
                            <p class="vehiculo-descripcion"><?= htmlspecialchars($v['descripcion']) ?></p>
                        <?php endif; ?>
                        <?php if ($v['modificaciones']): ?>
                            <p class="vehiculo-descripcion"><strong>Modificaciones:</strong> <?= htmlspecialchars($v['modificaciones']) ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- botones de editar y eliminar -->
                    <div class="vehiculo-pie">
                        <a href="/revhub/vehiculos.php?editar=<?= $v['id_vehiculo'] ?>"
                           class="btn-secundario btn-sm">
                            <i class="fa-solid fa-pen"></i> Editar
                        </a>
                        <a href="/revhub/vehiculos.php?eliminar=<?= $v['id_vehiculo'] ?>"
                           class="btn-peligro btn-sm"
                           onclick="return confirm('¿Eliminar este vehículo?')">
                            Eliminar
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="sin-resultados">No tienes vehículos registrados todavía.</p>
        <?php endif; ?>
    </div>
</main>

<!-- ===== MODAL AÑADIR VEHÍCULO ===== -->
<div class="modal-overlay" id="modal-añadir-vehiculo"
     <?= ($error && !$vehiculo_editar) ? 'style="display:flex;"' : '' ?>>
    <div class="modal modal-grande">
        <button class="modal-cerrar" onclick="cerrarModal('modal-añadir-vehiculo')">&times;</button>
        <h2>Añadir vehículo</h2>
        <p class="subtitulo">Registra un nuevo vehículo</p>

        <?php if ($error && !$vehiculo_editar): ?>
            <div class="alerta alerta-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="añadir">

            <div class="form-2col">
                <div class="form-group">
                    <label for="marca">Marca</label>
                    <input type="text" id="marca" name="marca"
                           placeholder="Busca o escribe una marca..."
                           list="lista-marcas"
                           value="<?= htmlspecialchars($_POST['marca'] ?? '') ?>">
                    <datalist id="lista-marcas">
                        <?php foreach ($marcas_disponibles as $m): ?>
                            <option value="<?= $m ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label for="modelo">Modelo</label>
                    <input type="text" id="modelo" name="modelo" placeholder="Modelo"
                           value="<?= htmlspecialchars($_POST['modelo'] ?? '') ?>">
                </div>
            </div>

            <div class="form-2col">
                <div class="form-group">
                    <label for="anio">Año</label>
                    <input type="number" id="anio" name="anio"
                           placeholder="2000" min="1900" max="2099"
                           value="<?= htmlspecialchars($_POST['anio'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="text" id="color" name="color" placeholder="Color"
                           value="<?= htmlspecialchars($_POST['color'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Tipo de vehículo</label>
                <small class="form-ayuda">Puedes seleccionar varios tipos</small>
                <div class="checkboxes-grupo">
                    <?php
                    $tipos_post = $_POST['tipo_vehiculo'] ?? [];
                    foreach ($tipos_disponibles as $t):
                    ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="tipo_vehiculo[]" value="<?= $t ?>"
                               <?= in_array($t, $tipos_post) ? 'checked' : '' ?>>
                        <?= ucfirst($t) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="matricula">Matrícula</label>
                <input type="text" id="matricula" name="matricula" placeholder="0000-AAA"
                       value="<?= htmlspecialchars($_POST['matricula'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="imagen">Foto del vehículo</label>
                <input type="file" id="imagen" name="imagen"
                       accept="image/jpg,image/jpeg,image/png,image/webp">
                <small class="form-ayuda">JPG, PNG o WebP · Máximo 2 MB</small>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion"
                          placeholder="Descripción del vehículo..."><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="modificaciones">Modificaciones</label>
                <textarea id="modificaciones" name="modificaciones"
                          placeholder="Modificaciones realizadas..."><?= htmlspecialchars($_POST['modificaciones'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-full">Añadir vehículo</button>
        </form>
    </div>
</div>

<!-- ===== MODAL EDITAR VEHÍCULO ===== -->
<?php if ($vehiculo_editar): ?>
<div class="modal-overlay" id="modal-editar-vehiculo" style="display:flex;">
    <div class="modal modal-grande">
        <button class="modal-cerrar"
                onclick="window.location.href='/revhub/vehiculos.php'">&times;</button>
        <h2>Editar vehículo</h2>
        <p class="subtitulo"><?= htmlspecialchars($vehiculo_editar['marca']) ?> <?= htmlspecialchars($vehiculo_editar['modelo']) ?></p>

        <?php if ($error && $vehiculo_editar): ?>
            <div class="alerta alerta-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id_vehiculo" value="<?= $vehiculo_editar['id_vehiculo'] ?>">

            <div class="form-2col">
                <div class="form-group">
                    <label>Marca</label>
                    <input type="text" name="marca"
                           list="lista-marcas-editar"
                           value="<?= htmlspecialchars($vehiculo_editar['marca']) ?>">
                    <datalist id="lista-marcas-editar">
                        <?php foreach ($marcas_disponibles as $m): ?>
                            <option value="<?= $m ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group">
                    <label>Modelo</label>
                    <input type="text" name="modelo"
                           value="<?= htmlspecialchars($vehiculo_editar['modelo']) ?>">
                </div>
            </div>

            <div class="form-2col">
                <div class="form-group">
                    <label>Año</label>
                    <input type="number" name="anio" min="1900" max="2099"
                           value="<?= htmlspecialchars($vehiculo_editar['anio']) ?>">
                </div>
                <div class="form-group">
                    <label>Color</label>
                    <input type="text" name="color"
                           value="<?= htmlspecialchars($vehiculo_editar['color']) ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Tipo de vehículo</label>
                <small class="form-ayuda">Puedes seleccionar varios tipos</small>
                <div class="checkboxes-grupo">
                    <?php
                    $tipos_actuales = explode(',', $vehiculo_editar['tipo_vehiculo']);
                    foreach ($tipos_disponibles as $t):
                    ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="tipo_vehiculo[]" value="<?= $t ?>"
                               <?= in_array($t, $tipos_actuales) ? 'checked' : '' ?>>
                        <?= ucfirst($t) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Matrícula</label>
                <input type="text" name="matricula"
                       value="<?= htmlspecialchars($vehiculo_editar['matricula']) ?>">
            </div>

            <div class="form-group">
                <label>Nueva foto (opcional)</label>
                <?php if ($vehiculo_editar['imagen'] ?? null): ?>
                    <img src="/revhub/img/vehiculos/<?= htmlspecialchars($vehiculo_editar['imagen']) ?>"
                         alt="Foto actual" class="cartel-preview">
                <?php endif; ?>
                <input type="file" name="imagen_editar"
                       accept="image/jpg,image/jpeg,image/png,image/webp">
                <small class="form-ayuda">Deja vacío para mantener la foto actual</small>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion"><?= htmlspecialchars($vehiculo_editar['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Modificaciones</label>
                <textarea name="modificaciones"><?= htmlspecialchars($vehiculo_editar['modificaciones'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-full">Guardar cambios</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/pie.php'; ?>