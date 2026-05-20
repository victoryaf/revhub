<?php
session_start();
include 'php/conexion.php';

//compruebo que me han pasado un id de evento válido, sino redirijo a eventos.php
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /revhub/eventos.php');
    exit;
}

$id = (int)$_GET['id'];

//obtengo los datos del evento junto con el nombre del organizador
$result = mysqli_query($conexion,
    "SELECT e.*, u.username, u.nombre AS org_nombre, u.id_usuario AS org_id
     FROM eventos e
     JOIN usuarios u ON e.id_usuario = u.id_usuario
     WHERE e.id_evento = $id"
);
//sino existe el evento redirijo a eventos.php
if (mysqli_num_rows($result) === 0) {
    header('Location: /revhub/eventos.php');
    exit;
}

$evento = mysqli_fetch_assoc($result);

//cuento el número de inscritos para mostrar la ocupación del evento
$inscritos = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(DISTINCT id_usuario) as total FROM inscripciones WHERE id_evento = $id"
))['total'];

//compruebo si el usuario actual es el organizador del evento o admin para mostrarle opciones adicionales
$es_organizador = isset($_SESSION['usuario']) && ($evento['id_usuario'] == $_SESSION['usuario'] || $_SESSION['rol'] === 'admin');

//compruebo si el usuario ya está inscrito para mostrarle la opción de desinscribirse o el formulario de inscripción
$inscrito = false;
if (isset($_SESSION['usuario'])) {
    $uid   = $_SESSION['usuario'];
    $check = mysqli_query($conexion,
        "SELECT id_inscripcion FROM inscripciones
         WHERE id_usuario = $uid AND id_evento = $id"
    );
    $inscrito = mysqli_num_rows($check) > 0;
}

//busco los vehiculos del usuario para mostrar solo los que cumplen los requisitos del evento
$vehiculos_usuario = null;
if (isset($_SESSION['usuario']) && !$inscrito) {
    $uid = $_SESSION['usuario'];

    //filtro por tipos admitidos
    if (!empty($evento['tipos_admitidos'])) {
        $tipos = explode(',', $evento['tipos_admitidos']);
        $conds = [];
        foreach ($tipos as $t) {
            $t_e = mysqli_real_escape_string($conexion, trim($t));
            $conds[] = "FIND_IN_SET('$t_e', tipo_vehiculo)";
        }
        $where_v = "id_usuario = $uid AND (" . implode(' OR ', $conds) . ")";
    } else {
        $where_v = "id_usuario = $uid";
    }

    //filtro por marcas admitidas
    if (!empty($evento['marcas_admitidas'])) {
        $marcas = explode(',', $evento['marcas_admitidas']);
        $marcas_conds = [];
        foreach ($marcas as $m) {
            $m_e = mysqli_real_escape_string($conexion, trim($m));
            $marcas_conds[] = "marca = '$m_e'";
        }
        $where_v .= " AND (" . implode(' OR ', $marcas_conds) . ")";
    }

    $vehiculos_usuario = mysqli_query($conexion, "SELECT * FROM vehiculos WHERE $where_v");
}

//para mostrar las restricciones del evento en la página de detalles, creo dos arrays con los tipos y marcas admitidos
$tipos_admitidos  = !empty($evento['tipos_admitidos'])  ? explode(',', $evento['tipos_admitidos'])  : [];
$marcas_admitidas = !empty($evento['marcas_admitidas']) ? explode(',', $evento['marcas_admitidas']) : [];

/* --- Proceso las acciones del formulario --- */

//comentar evento
$error_com = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'comentar') {
    if (!isset($_SESSION['usuario'])) {
        $error_com = 'Debes iniciar sesión para comentar.';
    } elseif (empty(trim($_POST['comentario']))) {
        $error_com = 'El comentario no puede estar vacío.';
    } else {
        $uid   = $_SESSION['usuario'];
        $texto = mysqli_real_escape_string($conexion, trim($_POST['comentario']));
        mysqli_query($conexion,
            "INSERT INTO comentarios (id_usuario, id_evento, texto) VALUES ($uid, $id, '$texto')"
        );
        header("Location: /revhub/evento.php?id=$id");
        exit;
    }
}

//inscribirse al evento
$error_ins = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'inscribirse') {
    if (!isset($_SESSION['usuario'])) {
        $error_ins = 'Debes iniciar sesión.';
    } elseif (empty($_POST['id_vehiculo']) || !is_numeric($_POST['id_vehiculo'])) {
        $error_ins = 'Selecciona un vehículo.';
    } else {
        $uid         = $_SESSION['usuario'];
        $id_vehiculo = (int)$_POST['id_vehiculo'];

        //comprobar que el vehículo pertenece al usuario
        $check_v = mysqli_query($conexion,
            "SELECT id_vehiculo, marca, tipo_vehiculo FROM vehiculos
             WHERE id_vehiculo = $id_vehiculo AND id_usuario = $uid"
        );

        if (mysqli_num_rows($check_v) === 0) {
            $error_ins = 'Vehículo no válido.';
        } else {
            $datos_v = mysqli_fetch_assoc($check_v);
            $valido  = true;

            //compruebo que el vehículo cumple los requisitos del evento
            if (!empty($evento['tipos_admitidos'])) {
                $tipos_ev  = array_map('trim', explode(',', strtolower($evento['tipos_admitidos'])));
                $tipos_v   = array_map('trim', explode(',', strtolower($datos_v['tipo_vehiculo'])));
                if (empty(array_intersect($tipos_v, $tipos_ev))) {
                    $valido    = false;
                    $error_ins = 'Tu vehículo no es del tipo admitido en este evento (' . implode(', ', $tipos_ev) . ').';
                }
            }

            if ($valido && !empty($evento['marcas_admitidas'])) {
                $marcas_ev = array_map('trim', explode(',', strtolower($evento['marcas_admitidas'])));
                if (!in_array(strtolower(trim($datos_v['marca'])), $marcas_ev)) {
                    $valido    = false;
                    $error_ins = 'La marca de tu vehículo no está admitida en este evento (' . implode(', ', $marcas_ev) . ').';
                }
            }

            if ($valido) {
                mysqli_query($conexion,
                    "INSERT INTO inscripciones (id_usuario, id_evento, id_vehiculo) VALUES ($uid, $id, $id_vehiculo)"
                );
                header("Location: /revhub/evento.php?id=$id");
                exit;
            }
        }
    }
}

/* --- Inscripción manual por organizador --- */
$error_ins_manual = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'inscribir_manual' && $es_organizador) {
    $id_usuario_ins  = (int)$_POST['id_usuario_ins'];
    $id_vehiculo_ins = (int)$_POST['id_vehiculo_ins'];

    if (empty($id_usuario_ins) || empty($id_vehiculo_ins)) {
        $error_ins_manual = 'Selecciona usuario y vehículo.';
    } else {

        //compruebo que el vehiculo no esté ya inscrito en el evento
        $check_ya = mysqli_query($conexion,
            "SELECT id_inscripcion FROM inscripciones WHERE id_usuario = $id_usuario_ins AND id_evento = $id"
        );

        if (mysqli_num_rows($check_ya) > 0) {
            $error_ins_manual = 'Este usuario ya está inscrito.';
        } else {
            mysqli_query($conexion,
                "INSERT INTO inscripciones (id_usuario, id_evento, id_vehiculo)
                 VALUES ($id_usuario_ins, $id, $id_vehiculo_ins)"
            );
            header("Location: /revhub/evento.php?id=$id");
            exit;
        }
    }
}

/* --- Enviar mensaje al organizador --- */
$error_msg = '';
$ok_msg    = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'mensaje') {
    if (!isset($_SESSION['usuario'])) {
        $error_msg = 'Debes iniciar sesión.';
    } elseif (empty(trim($_POST['texto_mensaje']))) {
        $error_msg = 'El mensaje no puede estar vacío.';
    } else {
        $uid            = $_SESSION['usuario'];
        $id_destinatario= (int)$evento['org_id'];
        $asunto_e       = mysqli_real_escape_string($conexion, 'Consulta sobre: ' . $evento['nombre']);
        $texto_e        = mysqli_real_escape_string($conexion, trim($_POST['texto_mensaje']));

        mysqli_query($conexion,
            "INSERT INTO mensajes (id_remitente, id_destinatario, id_evento, asunto, texto)
             VALUES ($uid, $id_destinatario, $id, '$asunto_e', '$texto_e')"
        );
        $ok_msg = 'Mensaje enviado al organizador.';
    }
}

/* --- Desinscribirse --- */
if (isset($_GET['desinscribirse']) && isset($_SESSION['usuario'])) {
    $uid = $_SESSION['usuario'];
    mysqli_query($conexion, "DELETE FROM inscripciones WHERE id_usuario = $uid AND id_evento = $id");
    header("Location: /revhub/evento.php?id=$id");
    exit;
}

/* --- Expulsar asistente (solo para organizadores y administradores) --- */
if (isset($_GET['expulsar']) && is_numeric($_GET['expulsar']) && $es_organizador) {
    $id_exp = (int)$_GET['expulsar'];
    mysqli_query($conexion, "DELETE FROM inscripciones WHERE id_usuario = $id_exp AND id_evento = $id");
    header("Location: /revhub/evento.php?id=$id");
    exit;
}

/* --- Borrar comentario --- */
if (isset($_GET['borrar_com']) && is_numeric($_GET['borrar_com']) && isset($_SESSION['usuario'])) {
    $id_com = (int)$_GET['borrar_com'];
    $uid    = $_SESSION['usuario'];
    $check_com = mysqli_query($conexion,
        "SELECT id_comentario FROM comentarios
         WHERE id_comentario = $id_com AND (id_usuario = $uid OR $es_organizador)"
    );
    if (mysqli_num_rows($check_com) > 0) {
        mysqli_query($conexion, "DELETE FROM comentarios WHERE id_comentario = $id_com");
    }
    header("Location: /revhub/evento.php?id=$id");
    exit;
}

//calculo el porcentaje de plazas ocupadas para mostrar la barra de ocupación
$pct = $evento['max_participantes'] > 0 ? round($inscritos / $evento['max_participantes'] * 100) : 0;

//color de la barra según el porcentaje de ocupación
if ($pct < 50) {
    $color_barra = '#27AE60';
} elseif ($pct < 80) {
    $color_barra = '#B7770D';
} else {
    $color_barra = '#C0392B';
}

include 'includes/cabecera.php';
?>

<main>
    <div class="contenedor">
        <a href="/revhub/eventos.php" class="volver">&larr; Volver a eventos</a>

        <div class="evento-layout">

            <!-- Contenido principal -->
            <div class="evento-main">
                <div class="evento-imagen">
                    <?php if ($evento['cartel']): ?>
                        <img src="/revhub/img/eventos/<?= htmlspecialchars($evento['cartel']) ?>"
                             alt="<?= htmlspecialchars($evento['nombre']) ?>">
                    <?php else: ?>
                        <span>Sin imagen</span>
                    <?php endif; ?>
                </div>

                <!-- Etiquetas del evento -->
                <div class="evento-etiqueta">
                    <span class="etiqueta etiqueta-<?= $evento['tipo_evento'] ?>"><?= htmlspecialchars($evento['tipo_evento']) ?></span>
                    <?php foreach ($tipos_admitidos as $ta): ?>
                        <span class="etiqueta etiqueta-tipo-admitido"><?= htmlspecialchars(trim($ta)) ?></span>
                    <?php endforeach; ?>
                    <?php if (!empty($marcas_admitidas)): ?>
                        <span class="etiqueta etiqueta-restriccion">
                            <i class="fa-solid fa-filter"></i>
                            <?= htmlspecialchars(implode(', ', $marcas_admitidas)) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Detalles del evento -->
                <h1><?= htmlspecialchars($evento['nombre']) ?></h1>
                <p class="evento-descripcion"><?= nl2br(htmlspecialchars($evento['descripcion'])) ?></p>

                <div class="evento-meta-grid">
                    <div class="meta-item">
                        <span class="meta-label">Fecha</span>
                        <span><?= date('d/m/Y', strtotime($evento['fecha'])) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Hora</span>
                        <span><?= substr($evento['hora'], 0, 5) ?>h</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Ubicación</span>
                        <span><?= htmlspecialchars($evento['ubicacion']) ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Plazas</span>
                        <span><?= $inscritos ?>/<?= $evento['max_participantes'] ?></span>
                    </div>
                </div>

                <!-- Botones organizador -->
                <?php if ($es_organizador): ?>
                <div class="acciones-organizador">
                    <a href="/revhub/editar_evento.php?id=<?= $id ?>" class="btn-secundario">
                        <i class="fa-solid fa-pen"></i> Editar evento
                    </a>
                    <a href="/revhub/admin.php?eliminar_evento=<?= $id ?>"
                       class="btn-peligro"
                       onclick="return confirm('¿Eliminar este evento?')">
                       <i class="fa-solid fa-trash"></i> Eliminar
                    </a>
                </div>
                <?php endif; ?>

                <!-- Comentarios -->
                <div class="comentarios">
                    <h3>Comentarios</h3>

                    <?php if ($ok_msg): ?>
                        <div class="alerta alerta-ok"><?= $ok_msg ?></div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['usuario'])): ?>
                        <?php if ($error_com): ?>
                            <div class="alerta alerta-error"><?= $error_com ?></div>
                        <?php endif; ?>
                        <form method="POST" action="" class="form-comentario">
                            <input type="hidden" name="accion" value="comentar">
                            <textarea name="comentario" placeholder="Escribe un comentario..." rows="3"></textarea>
                            <button type="submit" class="btn">Publicar</button>
                        </form>
                    <?php else: ?>
                        <p class="aviso-login">
                            <button class="btn-texto" onclick="abrirModal('modal-login')">Inicia sesión</button>
                            para comentar.
                        </p>
                    <?php endif; ?>

                    <?php
                    //cargo los comentarios ordenados por fecha (los más recientes primero) junto con el nombre del usuario que lo ha escrito
                    $comentarios = mysqli_query($conexion,
                        "SELECT c.*, u.username FROM comentarios c
                         JOIN usuarios u ON c.id_usuario = u.id_usuario
                         WHERE c.id_evento = $id ORDER BY c.fecha DESC"
                    );
                    ?>
                    <?php if (mysqli_num_rows($comentarios) > 0): ?>
                        <?php while ($com = mysqli_fetch_assoc($comentarios)): ?>
                        <div class="comentario">
                            <div class="comentario-header">
                                <strong><?= htmlspecialchars($com['username']) ?></strong>
                                <div class="comentario-acciones">
                                    <span><?= date('d/m/Y H:i', strtotime($com['fecha'])) ?></span>
                                    <?php if (isset($_SESSION['usuario']) &&
                                        ($com['id_usuario'] == $_SESSION['usuario'] || $es_organizador)): ?>
                                        <a href="/revhub/evento.php?id=<?= $id ?>&borrar_com=<?= $com['id_comentario'] ?>"
                                           class="btn-borrar-com"
                                           onclick="return confirm('¿Eliminar este comentario?')">
                                           <i class="fa-solid fa-xmark"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p><?= nl2br(htmlspecialchars($com['texto'])) ?></p>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="sin-resultados">Aún no hay comentarios.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Columna lateral -->
            <div class="evento-lateral">

                <!-- Inscripción -->
                <div class="lateral-card">
                    <h4>Inscripción</h4>
                    <div class="barra-plazas">
                        <div class="barra-fill" style="width:<?= $pct ?>%; background:<?= $color_barra ?>"></div>
                    </div>
                    <p class="plazas-texto <?= $pct < 50 ? 'estado-activo' : ($pct < 80 ? 'estado-pendiente' : 'estado-bloqueado') ?>">
                        <?= $inscritos ?> de <?= $evento['max_participantes'] ?> plazas ocupadas
                    </p>

                    <?php if (!empty($tipos_admitidos) || !empty($marcas_admitidas)): ?>
                        <div class="restricciones-resumen">
                            <?php if (!empty($tipos_admitidos)): ?>
                                <p class="tipos-admitidos-label">Tipos: <?= implode(', ', $tipos_admitidos) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($marcas_admitidas)): ?>
                                <p class="tipos-admitidos-label">Marcas: <?= implode(', ', $marcas_admitidas) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['usuario'])): ?>
                        <?php if ($inscrito): ?>
                            <p class="alerta alerta-ok">Ya estás inscrito.</p>
                            <a href="/revhub/evento.php?id=<?= $id ?>&desinscribirse=1"
                               class="btn-peligro btn-full"
                               onclick="return confirm('¿Cancelar inscripción?')">
                               Cancelar inscripción
                            </a>
                        <?php elseif ($inscritos >= $evento['max_participantes']): ?>
                            <p class="alerta alerta-error">Evento completo.</p>
                        <?php else: ?>
                            <button class="btn btn-full" onclick="abrirModal('modal-inscripcion')">
                                Apuntarse
                            </button>
                            <?php if (!empty($tipos_admitidos) || !empty($marcas_admitidas)): ?>
                                <button class="btn-secundario btn-full" style="margin-top:8px;"
                                        onclick="abrirModal('modal-mensaje')">
                                    <i class="fa-regular fa-envelope"></i> Mi coche no cumple los requisitos
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-full" onclick="abrirModal('modal-login')">
                            Inicia sesión para inscribirte
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Organizador -->
                <div class="lateral-card">
                    <h4>Organizador</h4>
                    <p><?= htmlspecialchars($evento['org_nombre']) ?> (@<?= htmlspecialchars($evento['username']) ?>)</p>
                    <?php if (isset($_SESSION['usuario']) && $_SESSION['usuario'] != $evento['org_id']): ?>
                        <button class="btn-secundario btn-full" style="margin-top:10px;"
                                onclick="abrirModal('modal-mensaje')">
                            <i class="fa-regular fa-envelope"></i> Contactar
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Asistentes -->
                <div class="lateral-card">
                    <h4>Asistentes (<?= $inscritos ?>)</h4>

                    <!-- Inscripción manual por organizador -->
                    <?php if ($es_organizador): ?>
                        <button class="btn-accion btn-full" style="margin-bottom:10px;"
                                onclick="abrirModal('modal-ins-manual')">
                            <i class="fa-solid fa-user-plus"></i> Inscribir manualmente
                        </button>
                    <?php endif; ?>

                    <?php
                    $asistentes = mysqli_query($conexion,
                        "SELECT DISTINCT u.id_usuario, u.username, v.marca, v.modelo, v.tipo_vehiculo
                         FROM inscripciones i
                         JOIN usuarios u ON i.id_usuario = u.id_usuario
                         JOIN vehiculos v ON i.id_vehiculo = v.id_vehiculo
                         WHERE i.id_evento = $id"
                    );
                    ?>
                    <?php if (mysqli_num_rows($asistentes) > 0): ?>
                        <ul class="lista-asistentes">
                            <?php while ($a = mysqli_fetch_assoc($asistentes)): ?>
                            <li>
                                <div>
                                    <strong><?= htmlspecialchars($a['username']) ?></strong>
                                    <span><?= htmlspecialchars($a['marca']) ?> <?= htmlspecialchars($a['modelo']) ?></span>
                                </div>
                                <?php if ($es_organizador && $a['id_usuario'] != $_SESSION['usuario']): ?>
                                    <a href="/revhub/evento.php?id=<?= $id ?>&expulsar=<?= $a['id_usuario'] ?>"
                                       class="btn-peligro btn-sm"
                                       onclick="return confirm('¿Expulsar a <?= htmlspecialchars($a['username']) ?>?')">
                                       Expulsar
                                    </a>
                                <?php endif; ?>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="sin-resultados">Nadie inscrito aún.</p>
                    <?php endif; ?>
                </div>

                <!-- Mapa de ruta (para eventos de tipo ruta) -->
                <?php if ($evento['tipo_evento'] === 'ruta' && !empty($evento['salida']) && !empty($evento['destino'])): ?>
                <div class="lateral-card">
                    <h4><i class="fa-solid fa-route"></i> Ruta</h4>
                    <p class="tipos-admitidos-label">
                        <i class="fa-solid fa-circle-dot" style="color:#C0392B"></i>
                        <?= htmlspecialchars($evento['salida']) ?>
                    </p>
                    <?php if (!empty($evento['puntos_intermedios'])): ?>
                        <?php foreach (explode(';', $evento['puntos_intermedios']) as $parada): ?>
                            <?php if (trim($parada)): ?>
                            <p class="tipos-admitidos-label">
                                <i class="fa-solid fa-circle" style="color:#C0392B;font-size:8px;"></i>
                                <?= htmlspecialchars(trim($parada)) ?>
                            </p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <p class="tipos-admitidos-label">
                        <i class="fa-solid fa-location-dot" style="color:#C0392B"></i>
                        <?= htmlspecialchars($evento['destino']) ?>
                    </p>
                    <p class="ruta-distancia" id="ruta-distancia">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span id="km-texto">Calculando distancia...</span>
                    </p>
                    <div id="mapa-ruta" class="mapa-contenedor"></div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<!-- ===== MODAL INSCRIPCIÓN ===== -->
<?php if (isset($_SESSION['usuario']) && !$inscrito && $inscritos < $evento['max_participantes']): ?>
<div class="modal-overlay" id="modal-inscripcion" <?= $error_ins ? 'style="display:flex;"' : '' ?>>
    <div class="modal">
        <button class="modal-cerrar" onclick="cerrarModal('modal-inscripcion')">&times;</button>
        <h2>Apuntarse al evento</h2>
        <p class="subtitulo"><?= htmlspecialchars($evento['nombre']) ?></p>

        <?php if ($error_ins): ?>
            <div class="alerta alerta-error"><?= $error_ins ?></div>
        <?php endif; ?>

        <?php if ($vehiculos_usuario && mysqli_num_rows($vehiculos_usuario) > 0): ?>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="inscribirse">
                <div class="form-group">
                    <label for="id_vehiculo">Vehículo con el que asistirás</label>
                    <?php if (!empty($tipos_admitidos) || !empty($marcas_admitidas)): ?>
                        <small class="form-ayuda">
                            <?php if (!empty($tipos_admitidos)): ?>Tipos admitidos: <?= implode(', ', $tipos_admitidos) ?><?php endif; ?>
                            <?php if (!empty($marcas_admitidas)): ?> · Marcas admitidas: <?= implode(', ', $marcas_admitidas) ?><?php endif; ?>
                        </small>
                    <?php endif; ?>
                    <select id="id_vehiculo" name="id_vehiculo">
                        <option value="">-- Elige un vehículo --</option>
                        <?php while ($v = mysqli_fetch_assoc($vehiculos_usuario)): ?>
                            <option value="<?= $v['id_vehiculo'] ?>">
                                <?= htmlspecialchars($v['marca']) ?> <?= htmlspecialchars($v['modelo']) ?>
                                (<?= htmlspecialchars($v['matricula']) ?>) — <?= htmlspecialchars($v['tipo_vehiculo']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-full">Confirmar inscripción</button>
            </form>
        <?php else: ?>
            <div class="alerta alerta-info">
                <?php if (!empty($tipos_admitidos) || !empty($marcas_admitidas)): ?>
                    Ninguno de tus vehículos cumple los requisitos de este evento.
                    <?php if (!empty($tipos_admitidos)): ?>Tipos admitidos: <?= implode(', ', $tipos_admitidos) ?>.<?php endif; ?>
                    <?php if (!empty($marcas_admitidas)): ?>Marcas admitidas: <?= implode(', ', $marcas_admitidas) ?>.<?php endif; ?>
                    <a href="/revhub/vehiculos.php">Gestiona tus vehículos</a> o contacta con el organizador.
                <?php else: ?>
                    No tienes vehículos registrados. <a href="/revhub/vehiculos.php">Añade uno aquí</a>.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== MODAL MENSAJE AL ORGANIZADOR ===== -->
<?php if (isset($_SESSION['usuario']) && $_SESSION['usuario'] != $evento['org_id']): ?>
<div class="modal-overlay" id="modal-mensaje" <?= $error_msg ? 'style="display:flex;"' : '' ?>>
    <div class="modal">
        <button class="modal-cerrar" onclick="cerrarModal('modal-mensaje')">&times;</button>
        <h2>Contactar con el organizador</h2>
        <p class="subtitulo">
            Mensaje privado a <?= htmlspecialchars($evento['username']) ?>
            sobre el evento "<?= htmlspecialchars($evento['nombre']) ?>"
        </p>

        <?php if ($error_msg): ?>
            <div class="alerta alerta-error"><?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="accion" value="mensaje">
            <div class="form-group">
                <label for="texto_mensaje">Mensaje</label>
                <textarea id="texto_mensaje" name="texto_mensaje" rows="5"
                          placeholder="Explica tu situación al organizador..."></textarea>
            </div>
            <button type="submit" class="btn btn-full">Enviar mensaje</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ===== MODAL INSCRIPCIÓN MANUAL (organizador) ===== -->
<?php if ($es_organizador): ?>
<div class="modal-overlay" id="modal-ins-manual" <?= $error_ins_manual ? 'style="display:flex;"' : '' ?>>
    <div class="modal modal-grande">
        <button class="modal-cerrar" onclick="cerrarModal('modal-ins-manual')">&times;</button>
        <h2>Inscribir manualmente</h2>
        <p class="subtitulo">Inscribe a un usuario saltándose las restricciones del evento</p>

        <?php if ($error_ins_manual): ?>
            <div class="alerta alerta-error"><?= $error_ins_manual ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="form-ins-manual">
            <input type="hidden" name="accion" value="inscribir_manual">

            <div class="form-group">
                <label for="id_usuario_ins">Usuario</label>
                <select id="id_usuario_ins" name="id_usuario_ins" onchange="cargarVehiculos(this.value)">
                    <option value="">-- Selecciona un usuario --</option>
                    <?php
                    $usuarios_todos = mysqli_query($conexion,
                        "SELECT id_usuario, username FROM usuarios
                         WHERE rol != 'bloqueado' ORDER BY username ASC"
                    );
                    while ($u = mysqli_fetch_assoc($usuarios_todos)):
                        /* No mostrar los ya inscritos */
                        $ya = mysqli_fetch_assoc(mysqli_query($conexion,
                            "SELECT id_inscripcion FROM inscripciones
                             WHERE id_usuario = {$u['id_usuario']} AND id_evento = $id"
                        ));
                        if ($ya) continue;
                    ?>
                        <option value="<?= $u['id_usuario'] ?>">
                            <?= htmlspecialchars($u['username']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group" id="grupo-vehiculo" style="display:none;">
                <label for="id_vehiculo_ins">Vehículo</label>
                <select id="id_vehiculo_ins" name="id_vehiculo_ins">
                    <option value="">-- Primero selecciona un usuario --</option>
                </select>
            </div>

            <button type="submit" class="btn btn-full">Inscribir</button>
        </form>
    </div>
</div>

<script>
/* Cargar vehículos del usuario seleccionado por AJAX simple */
function cargarVehiculos(idUsuario) {
    var grupo = document.getElementById('grupo-vehiculo');
    var select = document.getElementById('id_vehiculo_ins');

    if (!idUsuario) {
        grupo.style.display = 'none';
        return;
    }

    /* Hacer petición AJAX para obtener los vehículos del usuario */
    fetch('/revhub/get_vehiculos.php?id_usuario=' + idUsuario)
        .then(function(r) { return r.json(); })
        .then(function(vehiculos) {
            select.innerHTML = '<option value="">-- Elige un vehículo --</option>';
            if (vehiculos.length === 0) {
                select.innerHTML = '<option value="">Este usuario no tiene vehículos</option>';
            } else {
                vehiculos.forEach(function(v) {
                    var opt = document.createElement('option');
                    opt.value = v.id_vehiculo;
                    opt.textContent = v.marca + ' ' + v.modelo + ' (' + v.matricula + ')';
                    select.appendChild(opt);
                });
            }
            grupo.style.display = 'block';
        })
        .catch(function() {
            select.innerHTML = '<option value="">Error al cargar vehículos</option>';
            grupo.style.display = 'block';
        });
}
</script>
<?php endif; ?>

<?php if ($evento['tipo_evento'] === 'ruta' && !empty($evento['salida']) && !empty($evento['destino'])): ?>
<!-- Leaflet CSS y JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* Geocodificar y trazar ruta incluyendo puntos intermedios */
var salida   = <?= json_encode($evento['salida']) ?>;
var destino  = <?= json_encode($evento['destino']) ?>;
var puntos = <?= json_encode($evento['puntos_intermedios'] ?? '') ?>;

/* Separar puntos intermedios */
var puntosIntermedios = puntos
    ? puntos.split(';').map(function(p) { return p.trim(); }).filter(function(p) { return p.length > 0; })
    : [];

function geocodificar(lugar) {
    return fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(lugar))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.length === 0) throw new Error('No encontrado: ' + lugar);
            return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
        });
}

/* Geocodificar todos los puntos en orden: salida + intermedios + destino */
var todosLugares = [salida].concat(puntosIntermedios).concat([destino]);

Promise.all(todosLugares.map(geocodificar)).then(function(coords) {
    var puntoSalida  = coords[0];
    var puntoDestino = coords[coords.length - 1];

    var mapa = L.map('mapa-ruta').setView(puntoSalida, 8);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(mapa);

    /* Icono para salida */
    var iconoSalida = L.divIcon({
        html: '<div style="background:#27AE60;width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4);"></div>',
        className: '', iconAnchor: [7, 7]
    });
    /* Icono para destino */
    var iconoDestino = L.divIcon({
        html: '<div style="background:#C0392B;width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4);"></div>',
        className: '', iconAnchor: [7, 7]
    });
    /* Icono para puntos intermedios */
    var iconoIntermedio = L.divIcon({
        html: '<div style="background:#8E8E93;width:10px;height:10px;border-radius:50%;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4);"></div>',
        className: '', iconAnchor: [5, 5]
    });

    L.marker(puntoSalida,  { icon: iconoSalida }).addTo(mapa).bindPopup('Salida: ' + salida);
    L.marker(puntoDestino, { icon: iconoDestino  }).addTo(mapa).bindPopup('Destino: ' + destino);

    /* Marcadores para puntos intermedios */
    for (var i = 1; i < coords.length - 1; i++) {
        L.marker(coords[i], { icon: iconoIntermedio })
            .addTo(mapa)
            .bindPopup('Parada: ' + puntosIntermedios[i - 1]);
    }

    /* Construir URL con todos los puntos */
    var waypoints = coords.map(function(c) { return c[1] + ',' + c[0]; }).join(';');
    var url = 'https://router.project-osrm.org/route/v1/driving/' + waypoints
        + '?overview=full&geometries=geojson';

    fetch(url).then(function(r) { return r.json(); }).then(function(data) {
        if (data.routes && data.routes.length > 0) {
            L.geoJSON(data.routes[0].geometry, {
                style: { color: '#C0392B', weight: 4, opacity: 0.8 }
            }).addTo(mapa);

            /* Ajustar vista para mostrar toda la ruta */
            var bounds = L.geoJSON(data.routes[0].geometry).getBounds();
            mapa.fitBounds(bounds, { padding: [20, 20] });

            /* Mostrar distancia total en km */
            var km = Math.round(data.routes[0].distance / 1000);
            var kmTexto = document.getElementById('km-texto');
            if (kmTexto) {
                kmTexto.textContent = 'Distancia total: ' + km + ' km';
            }
        }
    });
}).catch(function(e) {
    document.getElementById('mapa-ruta').innerHTML =
        '<p style="padding:12px;color:#8E8E93;font-size:13px;">No se pudo cargar el mapa.</p>';
});
</script>
<?php endif; ?>

<?php include 'includes/pie.php'; ?>