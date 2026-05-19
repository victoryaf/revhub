<?php include 'includes/cabecera.php'; ?>
<?php include 'php/conexion.php'; ?>

<main>
    <div class="contenedor">
        <div class="seccion-header">
            <h2>Eventos</h2>
        </div>

        <form method="GET" action="" class="filtros">
            <select name="tipo" onchange="this.form.submit()">
                <option value="">Todos los tipos</option>
                <option value="quedada"     <?= ($_GET['tipo'] ?? '') === 'quedada'     ? 'selected' : '' ?>>Quedada</option>
                <option value="ruta"        <?= ($_GET['tipo'] ?? '') === 'ruta'        ? 'selected' : '' ?>>Ruta</option>
                <option value="exposicion"  <?= ($_GET['tipo'] ?? '') === 'exposicion'  ? 'selected' : '' ?>>Exposición</option>
                <option value="competicion" <?= ($_GET['tipo'] ?? '') === 'competicion' ? 'selected' : '' ?>>Competición</option>
                <option value="otro"        <?= ($_GET['tipo'] ?? '') === 'otro'        ? 'selected' : '' ?>>Otro</option>
            </select>
            <input type="text" name="buscar" placeholder="Buscar por nombre o ubicación..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
            <button type="submit" class="btn">Buscar</button>
            <?php if (!empty($_GET['tipo']) || !empty($_GET['buscar'])): ?>
                <a href="/revhub/eventos.php" class="btn-outline">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php
        $where = "WHERE fecha >= CURDATE()";

        if (!empty($_GET['tipo'])) {
            $tipo = mysqli_real_escape_string($conexion, $_GET['tipo']);
            $where .= " AND tipo_evento = '$tipo'";
        }

        if (!empty($_GET['buscar'])) {
            $buscar = mysqli_real_escape_string($conexion, $_GET['buscar']);
            $where .= " AND (nombre LIKE '%$buscar%' OR ubicacion LIKE '%$buscar%')";
        }

        $resultado = mysqli_query($conexion, "SELECT * FROM eventos $where ORDER BY fecha ASC");
        $total = mysqli_num_rows($resultado);
        ?>

        <p class="total-resultados"><?= $total ?> evento<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?></p>

        <?php if ($total > 0): ?>
        <div class="eventos-grid">
            <?php while ($evento = mysqli_fetch_assoc($resultado)): ?>
            <div class="tarjeta">
                <div class="tarjeta-imagen">
                    <?php if ($evento['cartel']): ?>
                        <img src="/revhub/img/eventos/<?= htmlspecialchars($evento['cartel']) ?>" alt="<?= htmlspecialchars($evento['nombre']) ?>">
                    <?php else: ?>
                        <span>Sin imagen</span>
                    <?php endif; ?>
                </div>
                <div class="tarjeta-body">
                    <span class="badge badge-<?= $evento['tipo_evento'] ?>"><?= htmlspecialchars($evento['tipo_evento']) ?></span>
                    <h3><?= htmlspecialchars($evento['nombre']) ?></h3>
                    <div class="tarjeta-meta">
                        <i class="fa-regular fa-calendar"></i>
                        <?= date('d/m/Y', strtotime($evento['fecha'])) ?> &middot; <?= substr($evento['hora'], 0, 5) ?>h
                    </div>
                    <div class="tarjeta-meta">
                        <i class="fa-solid fa-location-dot"></i>
                        <?= htmlspecialchars($evento['ubicacion']) ?>
                    </div>
                </div>
                <div class="tarjeta-footer">
                    <?php
                    $id = $evento['id_evento'];
                    $ins = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(DISTINCT id_usuario) as total FROM inscripciones WHERE id_evento = $id"))['total'];
                    ?>
                    <span class="plazas"><?= $ins ?>/<?= $evento['max_participantes'] ?> plazas</span>
                    <a href="/revhub/evento.php?id=<?= $id ?>" class="btn">Ver más</a>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <p class="sin-resultados">No se encontraron eventos.</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/pie.php'; ?>