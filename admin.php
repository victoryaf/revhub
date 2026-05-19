<?php
include 'includes/cabecera.php';
include 'php/conexion.php';

/* --- Solo admin --- */
if (!isset($_SESSION['usuario']) || $_SESSION['rol'] !== 'admin') {
    header('Location: /revhub/index.php');
    exit;
}

$ok    = '';
$error = '';

/* --- Bloquear usuario --- */
if (isset($_GET['bloquear']) && is_numeric($_GET['bloquear'])) {
    $id_u = (int)$_GET['bloquear'];
    mysqli_query($conexion, "UPDATE usuarios SET rol = 'bloqueado' WHERE id_usuario = $id_u");
    $ok = 'Usuario bloqueado.';
}

/* --- Desbloquear usuario --- */
if (isset($_GET['desbloquear']) && is_numeric($_GET['desbloquear'])) {
    $id_u = (int)$_GET['desbloquear'];
    mysqli_query($conexion, "UPDATE usuarios SET rol = 'usuario' WHERE id_usuario = $id_u");
    $ok = 'Usuario desbloqueado.';
}

/* --- Cambiar rol --- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'cambiar_rol') {
    $id_u = (int)$_POST['id_usuario'];
    $rol  = mysqli_real_escape_string($conexion, $_POST['rol']);
    $roles_validos = ['usuario', 'organizador', 'admin'];
    if (in_array($rol, $roles_validos)) {
        mysqli_query($conexion, "UPDATE usuarios SET rol = '$rol' WHERE id_usuario = $id_u");
        $ok = 'Rol actualizado correctamente.';
    }
}

/* --- Eliminar evento --- */
if (isset($_GET['eliminar_evento']) && is_numeric($_GET['eliminar_evento'])) {
    $id_e = (int)$_GET['eliminar_evento'];
    mysqli_query($conexion, "DELETE FROM inscripciones WHERE id_evento = $id_e");
    mysqli_query($conexion, "DELETE FROM comentarios WHERE id_evento = $id_e");
    mysqli_query($conexion, "DELETE FROM eventos WHERE id_evento = $id_e");
    $ok = 'Evento eliminado.';
}

/* --- Eliminar comentario --- */
if (isset($_GET['eliminar_comentario']) && is_numeric($_GET['eliminar_comentario'])) {
    $id_c = (int)$_GET['eliminar_comentario'];
    mysqli_query($conexion, "DELETE FROM comentarios WHERE id_comentario = $id_c");
    $ok = 'Comentario eliminado.';
}

/* --- Estadísticas --- */
$num_usuarios  = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as t FROM usuarios"))['t'];
$num_eventos   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as t FROM eventos"))['t'];
$num_vehiculos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as t FROM vehiculos"))['t'];
$num_inscrip   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) as t FROM inscripciones"))['t'];

/* --- Listados --- */
$usuarios    = mysqli_query($conexion, "SELECT * FROM usuarios ORDER BY fecha_registro DESC");
$eventos     = mysqli_query($conexion,
    "SELECT e.*, u.username FROM eventos e
     JOIN usuarios u ON e.id_usuario = u.id_usuario
     ORDER BY e.fecha DESC"
);
$comentarios = mysqli_query($conexion,
    "SELECT c.*, u.username, e.nombre AS nombre_evento
     FROM comentarios c
     JOIN usuarios u ON c.id_usuario = u.id_usuario
     JOIN eventos e ON c.id_evento = e.id_evento
     ORDER BY c.fecha DESC LIMIT 10"
);
?>

<main>
    <div class="contenedor">
        <h2 class="pagina-titulo">Panel de administración</h2>

        <?php if ($ok): ?>
            <div class="alerta alerta-ok"><?= $ok ?></div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="admin-stats">
            <div class="stat-card">
                <span><?= $num_usuarios ?></span>
                <small>Usuarios</small>
            </div>
            <div class="stat-card">
                <span><?= $num_eventos ?></span>
                <small>Eventos</small>
            </div>
            <div class="stat-card">
                <span><?= $num_vehiculos ?></span>
                <small>Vehículos</small>
            </div>
            <div class="stat-card">
                <span><?= $num_inscrip ?></span>
                <small>Inscripciones</small>
            </div>
        </div>

        <!-- Gestión de usuarios -->
        <div class="sidebar-card">
            <h3>Gestión de usuarios</h3>
            <div class="tabla-scroll">
                <table class="tabla-admin">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
                        <tr class="<?= $u['rol'] === 'bloqueado' ? 'fila-bloqueado' : '' ?>">
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php if ($u['rol'] === 'bloqueado'): ?>
                                    <span class="estado-bloqueado">Bloqueado</span>
                                <?php elseif ($u['id_usuario'] == $_SESSION['usuario']): ?>
                                    <span class="badge-rol badge-<?= $u['rol'] ?>"><?= $u['rol'] ?></span>
                                <?php else: ?>
                                    <form method="POST" action="" class="form-rol">
                                        <input type="hidden" name="accion" value="cambiar_rol">
                                        <input type="hidden" name="id_usuario" value="<?= $u['id_usuario'] ?>">
                                        <select name="rol" onchange="this.form.submit()" class="select-rol">
                                            <option value="usuario"     <?= $u['rol'] === 'usuario'     ? 'selected' : '' ?>>Usuario</option>
                                            <option value="organizador" <?= $u['rol'] === 'organizador' ? 'selected' : '' ?>>Organizador</option>
                                            <option value="admin"       <?= $u['rol'] === 'admin'       ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
                            <td>
                                <?php if ($u['id_usuario'] != $_SESSION['usuario']): ?>
                                    <?php if ($u['rol'] === 'bloqueado'): ?>
                                        <a href="/revhub/admin.php?desbloquear=<?= $u['id_usuario'] ?>"
                                           class="btn-accion btn-sm">
                                           Desbloquear
                                        </a>
                                    <?php else: ?>
                                        <a href="/revhub/admin.php?bloquear=<?= $u['id_usuario'] ?>"
                                           class="btn-peligro btn-sm"
                                           onclick="return confirm('¿Bloquear a <?= htmlspecialchars($u['username']) ?>?')">
                                           Bloquear
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="texto-gris">Tú</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gestión de eventos -->
        <div class="sidebar-card">
            <h3>Gestión de eventos</h3>
            <div class="tabla-scroll">
                <table class="tabla-admin">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Organizador</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ev = mysqli_fetch_assoc($eventos)): ?>
                        <tr>
                            <td><?= htmlspecialchars($ev['nombre']) ?></td>
                            <td><span class="badge badge-<?= $ev['tipo_evento'] ?>"><?= $ev['tipo_evento'] ?></span></td>
                            <td><?= date('d/m/Y', strtotime($ev['fecha'])) ?></td>
                            <td><?= htmlspecialchars($ev['username']) ?></td>
                            <td class="acciones-td">
                                <a href="/revhub/evento.php?id=<?= $ev['id_evento'] ?>"
                                   class="btn-accion btn-sm">Ver</a>
                                <a href="/revhub/admin.php?eliminar_evento=<?= $ev['id_evento'] ?>"
                                   class="btn-peligro btn-sm"
                                   onclick="return confirm('¿Eliminar este evento?')">
                                   Eliminar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Últimos comentarios -->
        <div class="sidebar-card">
            <h3>Últimos comentarios</h3>
            <div class="tabla-scroll">
                <table class="tabla-admin">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Evento</th>
                            <th>Comentario</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($com = mysqli_fetch_assoc($comentarios)): ?>
                        <tr>
                            <td><?= htmlspecialchars($com['username']) ?></td>
                            <td><?= htmlspecialchars($com['nombre_evento']) ?></td>
                            <td class="td-texto"><?= htmlspecialchars(substr($com['texto'], 0, 60)) ?>...</td>
                            <td><?= date('d/m/Y', strtotime($com['fecha'])) ?></td>
                            <td>
                                <a href="/revhub/admin.php?eliminar_comentario=<?= $com['id_comentario'] ?>"
                                   class="btn-peligro btn-sm"
                                   onclick="return confirm('¿Eliminar este comentario?')">
                                   Eliminar
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<?php include 'includes/pie.php'; ?>