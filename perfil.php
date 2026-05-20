<?php
include 'includes/cabecera.php';
include 'php/conexion.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: /revhub/index.php');
    exit;
}

$id_usuario = $_SESSION['usuario'];
$ok    = '';
$error = '';

/* --- Editar perfil --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $nombre      = trim($_POST['nombre']);
    $apellidos   = trim($_POST['apellidos']);
    $descripcion = trim($_POST['descripcion']);

    if (empty($nombre) || empty($apellidos)) {
        $error = 'El nombre y apellidos son obligatorios.';
    } else {
        $nombre_e      = mysqli_real_escape_string($conexion, $nombre);
        $apellidos_e   = mysqli_real_escape_string($conexion, $apellidos);
        $descripcion_e = mysqli_real_escape_string($conexion, $descripcion);

        $foto_sql = '';
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === 0) {
            $extension  = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extension, $permitidas)) {
                $error = 'La foto debe ser JPG, PNG o WebP.';
            } elseif ($_FILES['foto_perfil']['size'] > 20 * 1024 * 1024) {
                $error = 'La foto no puede superar 20 MB.';
            } else {
                $nombre_foto = uniqid('u_') . '.' . $extension;
                $destino     = $_SERVER['DOCUMENT_ROOT'] . '/revhub/img/perfiles/' . $nombre_foto;
                move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $destino);
                $foto_e   = mysqli_real_escape_string($conexion, $nombre_foto);
                $foto_sql = ", foto_perfil = '$foto_e'";
            }
        }

        if (empty($error)) {
            mysqli_query($conexion,
                "UPDATE usuarios
                 SET nombre = '$nombre_e', apellidos = '$apellidos_e', descripcion = '$descripcion_e' $foto_sql
                 WHERE id_usuario = $id_usuario"
            );
            $_SESSION['nombre'] = $nombre;
            $ok = 'Perfil actualizado correctamente.';
        }
    }
}

/* --- Responder mensaje --- */
$responder_msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'responder') {
    $id_dest   = (int)$_POST['id_destinatario'];
    $id_ev_r   = !empty($_POST['id_evento_r']) ? (int)$_POST['id_evento_r'] : null;
    $texto_r   = trim($_POST['texto_respuesta']);

    if (empty($texto_r)) {
        $dest_u = mysqli_fetch_assoc(mysqli_query($conexion,
            "SELECT username FROM usuarios WHERE id_usuario = $id_dest"
        ));
        $responder_msg = [
            'id_destinatario' => $id_dest,
            'id_evento_r'     => $id_ev_r,
            'username'        => $dest_u['username'],
            'error'           => 'El mensaje no puede estar vacío.'
        ];
    } else {
        $texto_e  = mysqli_real_escape_string($conexion, $texto_r);
        $asunto_e = mysqli_real_escape_string($conexion, 'Respuesta de ' . $_SESSION['username']);
        $id_ev_sql = $id_ev_r ? $id_ev_r : 'NULL';

        mysqli_query($conexion,
            "INSERT INTO mensajes (id_remitente, id_destinatario, id_evento, asunto, texto)
             VALUES ($id_usuario, $id_dest, $id_ev_sql, '$asunto_e', '$texto_e')"
        );
        $ok = 'Respuesta enviada correctamente.';
    }
}

/* --- Marcar mensaje como leído --- */
if (isset($_GET['leer']) && is_numeric($_GET['leer'])) {
    $id_msg = (int)$_GET['leer'];
    mysqli_query($conexion,
        "UPDATE mensajes SET leido = 1 WHERE id_mensaje = $id_msg AND id_destinatario = $id_usuario"
    );
    header('Location: /revhub/perfil.php');
    exit;
}

/* --- Eliminar mensaje --- */
if (isset($_GET['borrar_msg']) && is_numeric($_GET['borrar_msg'])) {
    $id_msg = (int)$_GET['borrar_msg'];
    mysqli_query($conexion,
        "DELETE FROM mensajes WHERE id_mensaje = $id_msg AND id_destinatario = $id_usuario"
    );
    header('Location: /revhub/perfil.php');
    exit;
}

/* --- Datos del usuario --- */
$usuario = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT * FROM usuarios WHERE id_usuario = $id_usuario"
));

/* --- Mensajes recibidos --- */
$mensajes = mysqli_query($conexion,
    "SELECT m.*, u.username AS remitente_username, u.id_usuario AS remitente_id,
            e.nombre AS nombre_evento
     FROM mensajes m
     JOIN usuarios u ON m.id_remitente = u.id_usuario
     LEFT JOIN eventos e ON m.id_evento = e.id_evento
     WHERE m.id_destinatario = $id_usuario
     ORDER BY m.fecha DESC"
);

$num_mensajes_nuevos = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(*) as total FROM mensajes
     WHERE id_destinatario = $id_usuario AND leido = 0"
))['total'];

/* --- Estadísticas --- */
$num_vehiculos   = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(*) as t FROM vehiculos WHERE id_usuario = $id_usuario"))['t'];
$num_asistencias = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(*) as t FROM inscripciones WHERE id_usuario = $id_usuario"))['t'];
$num_eventos_org = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(*) as t FROM eventos WHERE id_usuario = $id_usuario"))['t'];
?>

<main>
    <div class="contenedor">

        <?php if ($ok): ?>
            <div class="alerta alerta-ok"><?= $ok ?></div>
        <?php endif; ?>

        <div class="perfil-layout">

            <!-- Sidebar -->
            <div class="perfil-sidebar">
                <div class="sidebar-card">
                    <div class="perfil-avatar">
                        <?php if ($usuario['foto_perfil']): ?>
                            <img src="/revhub/img/perfiles/<?= htmlspecialchars($usuario['foto_perfil']) ?>"
                                 alt="<?= htmlspecialchars($usuario['username']) ?>">
                        <?php else: ?>
                            <i class="fa-regular fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <h2 class="perfil-nombre">
                        <?= htmlspecialchars($usuario['nombre']) ?>
                        <?= htmlspecialchars($usuario['apellidos']) ?>
                    </h2>
                    <p class="perfil-username">@<?= htmlspecialchars($usuario['username']) ?></p>
                    <span class="badge-rol badge-<?= $usuario['rol'] ?>"><?= $usuario['rol'] ?></span>

                    <?php if ($usuario['descripcion']): ?>
                        <p class="perfil-desc"><?= htmlspecialchars($usuario['descripcion']) ?></p>
                    <?php endif; ?>

                    <div class="perfil-stats">
                        <div class="stat">
                            <span><?= $num_asistencias ?></span>
                            <small>Asistencias</small>
                        </div>
                        <div class="stat">
                            <span><?= $num_vehiculos ?></span>
                            <small>Vehículos</small>
                        </div>
                        <?php if ($num_eventos_org > 0): ?>
                        <div class="stat">
                            <span><?= $num_eventos_org ?></span>
                            <small>Eventos</small>
                        </div>
                        <?php endif; ?>
                    </div>

                    <button class="btn btn-full" onclick="abrirModal('modal-editar-perfil')"
                            style="margin-top:14px;">
                        <i class="fa-solid fa-pen"></i> Editar perfil
                    </button>
                </div>
            </div>

            <!-- Contenido -->
            <div class="perfil-main">

                <!-- Mensajes -->
                <div class="sidebar-card">
                    <div class="seccion-header">
                        <h3>
                            <i class="fa-regular fa-envelope"></i> Mensajes
                            <?php if ($num_mensajes_nuevos > 0): ?>
                                <span class="badge-nuevo">
                                    <?= $num_mensajes_nuevos ?> nuevo<?= $num_mensajes_nuevos > 1 ? 's' : '' ?>
                                </span>
                            <?php endif; ?>
                        </h3>
                    </div>

                    <?php if (mysqli_num_rows($mensajes) > 0): ?>
                        <div class="lista-mensajes">
                            <?php while ($msg = mysqli_fetch_assoc($mensajes)): ?>
                            <div class="mensaje-item <?= !$msg['leido'] ? 'mensaje-nuevo' : '' ?>">
                                <div class="mensaje-cabecera">
                                    <div>
                                        <strong><?= htmlspecialchars($msg['remitente_username']) ?></strong>
                                        <?php if ($msg['nombre_evento']): ?>
                                            <span class="mensaje-evento">
                                                re: <?= htmlspecialchars($msg['nombre_evento']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mensaje-acciones">
                                        <span class="mensaje-fecha">
                                            <?= date('d/m/Y H:i', strtotime($msg['fecha'])) ?>
                                        </span>
                                        <?php if (!$msg['leido']): ?>
                                            <a href="/revhub/perfil.php?leer=<?= $msg['id_mensaje'] ?>"
                                               class="btn-accion btn-sm">
                                               <i class="fa-solid fa-check"></i> Leído
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn-accion btn-sm"
                                                onclick="abrirResponder(
                                                    <?= $msg['remitente_id'] ?>,
                                                    '<?= htmlspecialchars($msg['remitente_username']) ?>',
                                                    <?= $msg['id_evento'] ?? 'null' ?>
                                                )">
                                            <i class="fa-solid fa-reply"></i> Responder
                                        </button>
                                        <a href="/revhub/perfil.php?borrar_msg=<?= $msg['id_mensaje'] ?>"
                                           class="btn-borrar-com"
                                           onclick="return confirm('¿Eliminar este mensaje?')">
                                           <i class="fa-solid fa-xmark"></i>
                                        </a>
                                    </div>
                                </div>
                                <p class="mensaje-texto"><?= nl2br(htmlspecialchars($msg['texto'])) ?></p>
                                <?php if ($msg['id_evento']): ?>
                                    <a href="/revhub/evento.php?id=<?= $msg['id_evento'] ?>"
                                       class="mensaje-link">
                                        Ver evento <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="sin-resultados">No tienes mensajes.</p>
                    <?php endif; ?>
                </div>

                <!-- Mis vehículos -->
                <div class="sidebar-card">
                    <div class="seccion-header">
                        <h3><i class="fa-solid fa-car"></i> Mis vehículos</h3>
                        <a href="/revhub/vehiculos.php" class="btn-accion">Gestionar</a>
                    </div>
                    <?php
                    $vehiculos2 = mysqli_query($conexion,
                        "SELECT * FROM vehiculos WHERE id_usuario = $id_usuario ORDER BY marca ASC"
                    );
                    ?>
                    <?php if (mysqli_num_rows($vehiculos2) > 0): ?>
                        <ul class="lista-vehiculos">
                            <?php while ($v = mysqli_fetch_assoc($vehiculos2)): ?>
                            <li class="vehiculo-item">
                                <div class="vehiculo-item-img">
                                    <?php if ($v['imagen']): ?>
                                        <img src="/revhub/img/vehiculos/<?= htmlspecialchars($v['imagen']) ?>"
                                             alt="<?= htmlspecialchars($v['marca']) ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-car"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($v['marca']) ?> <?= htmlspecialchars($v['modelo']) ?></strong>
                                    <p><?= htmlspecialchars($v['anio']) ?> &middot; <?= htmlspecialchars($v['tipo_vehiculo']) ?> &middot;
                                    <span class="vehiculo-matricula"><?= htmlspecialchars($v['matricula']) ?></span></p>
                                </div>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="sin-resultados">No tienes vehículos. <a href="/revhub/vehiculos.php">Añade uno</a>.</p>
                    <?php endif; ?>
                </div>

                <!-- Historial -->
                <div class="sidebar-card">
                    <h3><i class="fa-regular fa-calendar"></i> Historial de eventos</h3>
                    <?php
                    $historial = mysqli_query($conexion,
                        "SELECT e.*, v.marca, v.modelo
                         FROM inscripciones i
                         JOIN eventos e ON i.id_evento = e.id_evento
                         JOIN vehiculos v ON i.id_vehiculo = v.id_vehiculo
                         WHERE i.id_usuario = $id_usuario
                         ORDER BY e.fecha DESC"
                    );
                    ?>
                    <?php if (mysqli_num_rows($historial) > 0): ?>
                        <ul class="lista-historial">
                            <?php while ($h = mysqli_fetch_assoc($historial)): ?>
                            <li>
                                <div>
                                    <strong><?= htmlspecialchars($h['nombre']) ?></strong>
                                    <p>
                                        <i class="fa-regular fa-calendar"></i>
                                        <?= date('d/m/Y', strtotime($h['fecha'])) ?>
                                        &middot;
                                        <i class="fa-solid fa-location-dot"></i>
                                        <?= htmlspecialchars($h['ubicacion']) ?>
                                    </p>
                                    <p>
                                        <i class="fa-solid fa-car"></i>
                                        <?= htmlspecialchars($h['marca']) ?> <?= htmlspecialchars($h['modelo']) ?>
                                    </p>
                                </div>
                                <a href="/revhub/evento.php?id=<?= $h['id_evento'] ?>"
                                   class="badge badge-<?= $h['tipo_evento'] ?>">
                                    <?= $h['tipo_evento'] ?>
                                </a>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="sin-resultados">Todavía no te has inscrito en ningún evento.</p>
                    <?php endif; ?>
                </div>

                <!-- Eventos organizados -->
                <?php if ($usuario['rol'] === 'organizador' || $usuario['rol'] === 'admin'): ?>
                <div class="sidebar-card">
                    <div class="seccion-header">
                        <h3><i class="fa-solid fa-star"></i> Eventos que organizo</h3>
                        <a href="/revhub/crear_evento.php" class="btn-accion">
                            <i class="fa-solid fa-plus"></i> Crear evento
                        </a>
                    </div>
                    <?php
                    $organiza = mysqli_query($conexion,
                        "SELECT * FROM eventos WHERE id_usuario = $id_usuario ORDER BY fecha DESC"
                    );
                    ?>
                    <?php if (mysqli_num_rows($organiza) > 0): ?>
                        <ul class="lista-historial">
                            <?php while ($ev = mysqli_fetch_assoc($organiza)): ?>
                            <li>
                                <div>
                                    <strong><?= htmlspecialchars($ev['nombre']) ?></strong>
                                    <p>
                                        <i class="fa-regular fa-calendar"></i>
                                        <?= date('d/m/Y', strtotime($ev['fecha'])) ?>
                                        &middot;
                                        <i class="fa-solid fa-location-dot"></i>
                                        <?= htmlspecialchars($ev['ubicacion']) ?>
                                    </p>
                                </div>
                                <a href="/revhub/evento.php?id=<?= $ev['id_evento'] ?>"
                                   class="btn-accion btn-sm">Ver</a>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="sin-resultados">Todavía no has creado ningún evento.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<!-- ===== MODAL RESPONDER ===== -->
<div class="modal-overlay" id="modal-responder"
     <?= $responder_msg ? 'style="display:flex;"' : '' ?>>
    <div class="modal">
        <button class="modal-cerrar" onclick="cerrarModal('modal-responder')">&times;</button>
        <h2>Responder mensaje</h2>
        <p class="subtitulo" id="responder-a">
            <?= $responder_msg ? 'A: ' . htmlspecialchars($responder_msg['username']) : '' ?>
        </p>

        <?php if ($responder_msg && isset($responder_msg['error'])): ?>
            <div class="alerta alerta-error"><?= $responder_msg['error'] ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="accion" value="responder">
            <input type="hidden" name="id_destinatario" id="input-destinatario"
                   value="<?= $responder_msg ? $responder_msg['id_destinatario'] : '' ?>">
            <input type="hidden" name="id_evento_r" id="input-evento-r"
                   value="<?= $responder_msg ? ($responder_msg['id_evento_r'] ?? '') : '' ?>">
            <div class="form-group">
                <label for="texto_respuesta">Mensaje</label>
                <textarea id="texto_respuesta" name="texto_respuesta" rows="5"
                          placeholder="Escribe tu respuesta..."></textarea>
            </div>
            <button type="submit" class="btn btn-full">
                <i class="fa-solid fa-paper-plane"></i> Enviar respuesta
            </button>
        </form>
    </div>
</div>

<!-- ===== MODAL EDITAR PERFIL ===== -->
<div class="modal-overlay" id="modal-editar-perfil">
    <div class="modal modal-grande">
        <button class="modal-cerrar" onclick="cerrarModal('modal-editar-perfil')">&times;</button>
        <h2>Editar perfil</h2>
        <p class="subtitulo">Actualiza tus datos</p>

        <?php if ($error): ?>
            <div class="alerta alerta-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="editar">
            <div class="form-2col">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre"
                           value="<?= htmlspecialchars($usuario['nombre']) ?>">
                </div>
                <div class="form-group">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos"
                           value="<?= htmlspecialchars($usuario['apellidos']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion"
                          placeholder="Cuéntanos algo sobre ti..."><?= htmlspecialchars($usuario['descripcion'] ?? '') ?></textarea>
            </div>
            <div class="form-group">
                <label for="foto_perfil">Foto de perfil</label>
                <input type="file" id="foto_perfil" name="foto_perfil"
                       accept="image/jpg,image/jpeg,image/png,image/webp">
                <small class="form-ayuda">JPG, PNG o WebP · Máximo 20 MB</small>
            </div>
            <button type="submit" class="btn btn-full">
                <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
            </button>
        </form>
    </div>
</div>

<script>
function abrirResponder(idDestinatario, username, idEvento) {
    document.getElementById('input-destinatario').value = idDestinatario;
    document.getElementById('input-evento-r').value     = idEvento || '';
    document.getElementById('responder-a').textContent  = 'A: ' + username;
    document.getElementById('texto_respuesta').value    = '';
    abrirModal('modal-responder');
}
</script>

<?php include 'includes/pie.php'; ?>