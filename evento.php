<?php include 'includes/cabecera.php'; ?>

<?php
include 'php/conexion.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /revhub/eventos.php');
    exit;
}

$id     = (int)$_GET['id'];
$result = mysqli_query($conexion, "SELECT e.*, u.username, u.nombre AS org_nombre
                                    FROM eventos e
                                    JOIN usuarios u ON e.id_usuario = u.id_usuario
                                    WHERE e.id_evento = $id");

if (mysqli_num_rows($result) === 0) {
    header('Location: /revhub/eventos.php');
    exit;
}

$evento = mysqli_fetch_assoc($result);

// Número de inscritos
$inscritos = mysqli_fetch_assoc(mysqli_query($conexion,
    "SELECT COUNT(DISTINCT id_usuario) as total FROM inscripciones WHERE id_evento = $id"
))['total'];

// Comentarios
$comentarios = mysqli_query($conexion,
    "SELECT c.*, u.username FROM comentarios c
     JOIN usuarios u ON c.id_usuario = u.id_usuario
     WHERE c.id_evento = $id ORDER BY c.fecha DESC"
);

// Asistentes
$asistentes = mysqli_query($conexion,
    "SELECT DISTINCT u.username, v.marca, v.modelo
     FROM inscripciones i
     JOIN usuarios u ON i.id_usuario = u.id_usuario
     JOIN vehiculos v ON i.id_vehiculo = v.id_vehiculo
     WHERE i.id_evento = $id"
);

// ¿Está inscrito el usuario?
$inscrito = false;
if (isset($_SESSION['usuario'])) {
    $uid = $_SESSION['usuario'];
    $check = mysqli_query($conexion,
        "SELECT id_inscripcion FROM inscripciones
         WHERE id_usuario = $uid AND id_evento = $id"
    );
    $inscrito = mysqli_num_rows($check) > 0;
}

// Procesar comentario
$error_com = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario'])) {
    if (!isset($_SESSION['usuario'])) {
        $error_com = 'Debes iniciar sesión para comentar.';
    } elseif (empty(trim($_POST['comentario']))) {
        $error_com = 'El comentario no puede estar vacío.';
    } else {
        $uid   = $_SESSION['usuario'];
        $texto = mysqli_real_escape_string($conexion, trim($_POST['comentario']));
        mysqli_query($conexion,
            "INSERT INTO comentarios (id_usuario, id_evento, texto)
             VALUES ($uid, $id, '$texto')"
        );
        header("Location: /revhub/evento.php?id=$id");
        exit;
    }
}
?>

<main>
    <div class="contenedor">

        <a href="/revhub/eventos.php" class="volver">&larr; Volver a eventos</a>

        <div class="evento-layout">

            <!-- Contenido principal -->
            <div class="evento-main">

                <!-- Imagen -->
                <div class="evento-imagen">
                    <?php if ($evento['cartel']): ?>
                        <img src="/revhub/img/eventos/<?= htmlspecialchars($evento['cartel']) ?>"
                             alt="<?= htmlspecialchars($evento['nombre']) ?>">
                    <?php else: ?>
                        <span>Sin imagen</span>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <span class="badge badge-<?= $evento['tipo_evento'] ?>"><?= htmlspecialchars($evento['tipo_evento']) ?></span>
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

                <!-- Comentarios -->
                <div class="comentarios">
                    <h3>Comentarios</h3>

                    <?php if (isset($_SESSION['usuario'])): ?>
                        <?php if ($error_com): ?>
                            <div class="alerta alerta-error"><?= $error_com ?></div>
                        <?php endif; ?>
                        <form method="POST" action="" class="form-comentario">
                            <textarea name="comentario" placeholder="Escribe un comentario..." rows="3"></textarea>
                            <button type="submit" class="btn">Publicar</button>
                        </form>
                    <?php else: ?>
                        <p class="aviso-login"><a href="/revhub/login.php">Inicia sesión</a> para comentar.</p>
                    <?php endif; ?>

                    <?php if (mysqli_num_rows($comentarios) > 0): ?>
                        <?php while ($com = mysqli_fetch_assoc($comentarios)): ?>
                        <div class="comentario">
                            <div class="comentario-header">
                                <strong><?= htmlspecialchars($com['username']) ?></strong>
                                <span><?= date('d/m/Y H:i', strtotime($com['fecha'])) ?></span>
                            </div>
                            <p><?= nl2br(htmlspecialchars($com['texto'])) ?></p>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="sin-resultados">Aún no hay comentarios.</p>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="evento-sidebar">

                <!-- Inscripción -->
                <div class="sidebar-card">
                    <h4>Inscripción</h4>
                    <div class="barra-plazas">
                        <?php $pct = $evento['max_participantes'] > 0 ? round($inscritos / $evento['max_participantes'] * 100) : 0; ?>
                        <div class="barra-fill" style="width:<?= $pct ?>%"></div>
                    </div>
                    <p class="plazas-texto"><?= $inscritos ?> de <?= $evento['max_participantes'] ?> plazas ocupadas</p>

                    <?php if (isset($_SESSION['usuario'])): ?>
                        <?php if ($inscrito): ?>
                            <p class="alerta alerta-ok">Ya estás inscrito en este evento.</p>
                            <a href="/revhub/desinscribirse.php?id=<?= $id ?>" class="btn-peligro btn-full">Cancelar inscripción</a>
                        <?php elseif ($inscritos >= $evento['max_participantes']): ?>
                            <p class="alerta alerta-error">Evento completo.</p>
                        <?php else: ?>
                            <a href="/revhub/inscribirse.php?id=<?= $id ?>" class="btn btn-full">Apuntarse</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="/revhub/login.php" class="btn btn-full">Inicia sesión para inscribirte</a>
                    <?php endif; ?>
                </div>

                <!-- Organizador -->
                <div class="sidebar-card">
                    <h4>Organizador</h4>
                    <p><?= htmlspecialchars($evento['org_nombre']) ?> (<?= htmlspecialchars($evento['username']) ?>)</p>
                </div>

                <!-- Asistentes -->
                <div class="sidebar-card">
                    <h4>Asistentes (<?= $inscritos ?>)</h4>
                    <?php if (mysqli_num_rows($asistentes) > 0): ?>
                        <ul class="lista-asistentes">
                            <?php while ($a = mysqli_fetch_assoc($asistentes)): ?>
                            <li>
                                <strong><?= htmlspecialchars($a['username']) ?></strong>
                                — <?= htmlspecialchars($a['marca']) ?> <?= htmlspecialchars($a['modelo']) ?>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="sin-resultados">Nadie inscrito aún.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</main>

<?php include 'includes/pie.php'; ?>