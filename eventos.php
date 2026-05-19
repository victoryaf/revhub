<?php
include 'includes/cabecera.php';
include 'php/conexion.php';
?>

<main>
    <div class="contenedor">
        <div class="seccion-header">
            <h2>Eventos</h2>
        </div>

        <!-- Filtros de búsqueda -->
        <form method="GET" action="" class="filtros">
            <?php if (isset($_GET['mis_coches'])): ?>
                <input type="hidden" name="mis_coches" value="1">
            <?php endif; ?>
            <select name="tipo" onchange="this.form.submit()">
                <option value="">Todos los tipos</option>
                <option value="quedada"    <?= ($_GET['tipo'] ?? '') === 'quedada'    ? 'selected':'' ?>>Quedada</option>
                <option value="ruta"       <?= ($_GET['tipo'] ?? '') === 'ruta'       ? 'selected':'' ?>>Ruta</option>
                <option value="exposicion" <?= ($_GET['tipo'] ?? '') === 'exposicion' ? 'selected':'' ?>>Exposición</option>
                <option value="competicion"<?= ($_GET['tipo'] ?? '') === 'competicion'? 'selected':'' ?>>Competición</option>
                <option value="otro"       <?= ($_GET['tipo'] ?? '') === 'otro'       ? 'selected':'' ?>>Otro</option>
            </select>
            <input type="text" name="buscar"
                   placeholder="Buscar por nombre o ubicación..."
                   value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
            <button type="submit" class="btn">Buscar</button>
            <?php if (!empty($_GET['tipo']) || !empty($_GET['buscar'])): ?>
                <a href="/revhub/eventos.php<?= isset($_GET['mis_coches']) ? '?mis_coches=1' : '' ?>"
                   class="btn-outline">Limpiar</a>
            <?php endif; ?>
        </form>

        <!-- Chips mis coches -->
        <?php if (isset($_SESSION['usuario'])): ?>
        <div class="filtros-chips">
            <?php
            /* Construir URLs conservando tipo y buscar */
            $params_base = [];
            if (!empty($_GET['tipo']))   $params_base['tipo']   = $_GET['tipo'];
            if (!empty($_GET['buscar'])) $params_base['buscar'] = $_GET['buscar'];

            $url_todos     = '/revhub/eventos.php' . (!empty($params_base) ? '?' . http_build_query($params_base) : '');
            $url_mis_coches= '/revhub/eventos.php?' . http_build_query(array_merge($params_base, ['mis_coches' => '1']));
            ?>
            <a href="<?= $url_todos ?>"
               class="chip <?= !isset($_GET['mis_coches']) ? 'chip-activo' : '' ?>">
                Todos los eventos
            </a>
            <a href="<?= $url_mis_coches ?>"
               class="chip <?= isset($_GET['mis_coches']) ? 'chip-activo' : '' ?>">
                <i class="fa-solid fa-car"></i> Solo los que puedo inscribir
            </a>
        </div>
        <?php endif; ?>

        <?php
        /* --- Construir query --- */
        $where = "WHERE fecha >= CURDATE()";

        if (!empty($_GET['tipo'])) {
            $tipo   = mysqli_real_escape_string($conexion, $_GET['tipo']);
            $where .= " AND tipo_evento = '$tipo'";
        }

        if (!empty($_GET['buscar'])) {
            $buscar = mysqli_real_escape_string($conexion, $_GET['buscar']);
            $where .= " AND (nombre LIKE '%$buscar%' OR ubicacion LIKE '%$buscar%')";
        }

        $resultado = mysqli_query($conexion, "SELECT * FROM eventos $where ORDER BY fecha ASC");
        $eventos_todos = [];
        while ($ev = mysqli_fetch_assoc($resultado)) {
            $eventos_todos[] = $ev;
        }

        /* --- Filtro mis coches --- */
        if (isset($_GET['mis_coches']) && isset($_SESSION['usuario'])) {
            $uid = $_SESSION['usuario'];

            $mis_v = mysqli_query($conexion,
                "SELECT marca, tipo_vehiculo FROM vehiculos WHERE id_usuario = $uid"
            );

            $mis_marcas = [];
            $mis_tipos  = [];
            while ($mv = mysqli_fetch_assoc($mis_v)) {
                $mis_marcas[] = strtolower(trim($mv['marca']));
                foreach (explode(',', $mv['tipo_vehiculo']) as $t) {
                    $mis_tipos[] = strtolower(trim($t));
                }
            }

            $eventos_todos = array_filter($eventos_todos, function($ev) use ($mis_marcas, $mis_tipos) {
                /* Comprobar tipos */
                if (!empty($ev['tipos_admitidos'])) {
                    $tipos_ev = array_map(function($t){ return strtolower(trim($t)); },
                        explode(',', $ev['tipos_admitidos']));
                    if (empty(array_intersect($mis_tipos, $tipos_ev))) return false;
                }
                /* Comprobar marcas */
                if (!empty($ev['marcas_admitidas'])) {
                    $marcas_ev = array_map(function($m){ return strtolower(trim($m)); },
                        explode(',', $ev['marcas_admitidas']));
                    if (empty(array_intersect($mis_marcas, $marcas_ev))) return false;
                }
                return true;
            });
        }

        $total = count($eventos_todos);
        ?>

        <p class="total-resultados">
            <?= $total ?> evento<?= $total !== 1 ? 's' : '' ?> encontrado<?= $total !== 1 ? 's' : '' ?>
        </p>

        <?php if ($total > 0): ?>
        <div class="eventos-grid">
            <?php foreach ($eventos_todos as $evento): ?>
            <div class="tarjeta">
                <div class="tarjeta-imagen">
                    <?php if ($evento['cartel']): ?>
                        <img src="/revhub/img/eventos/<?= htmlspecialchars($evento['cartel']) ?>"
                             alt="<?= htmlspecialchars($evento['nombre']) ?>">
                    <?php else: ?>
                        <span>Sin imagen</span>
                    <?php endif; ?>
                </div>
                <div class="tarjeta-body">
                    <div class="tarjeta-badges">
                        <span class="badge badge-<?= $evento['tipo_evento'] ?>">
                            <?= htmlspecialchars($evento['tipo_evento']) ?>
                        </span>
                        <?php if (!empty($evento['marcas_admitidas'])): ?>
                            <span class="badge-restriccion">
                                <i class="fa-solid fa-filter"></i>
                                <?= htmlspecialchars($evento['marcas_admitidas']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <h3><?= htmlspecialchars($evento['nombre']) ?></h3>
                    <div class="tarjeta-meta">
                        <i class="fa-regular fa-calendar"></i>
                        <?= date('d/m/Y', strtotime($evento['fecha'])) ?>
                        &middot; <?= substr($evento['hora'], 0, 5) ?>h
                    </div>
                    <div class="tarjeta-meta">
                        <i class="fa-solid fa-location-dot"></i>
                        <?= htmlspecialchars($evento['ubicacion']) ?>
                    </div>
                </div>
                <div class="tarjeta-footer">
                    <?php
                    $id  = $evento['id_evento'];
                    $ins = mysqli_fetch_assoc(mysqli_query($conexion,
                        "SELECT COUNT(DISTINCT id_usuario) as total FROM inscripciones WHERE id_evento = $id"
                    ))['total'];
                    ?>
                    <span class="plazas"><?= $ins ?>/<?= $evento['max_participantes'] ?> plazas</span>
                    <a href="/revhub/evento.php?id=<?= $id ?>" class="btn">Ver más</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="sin-resultados">
                <?php if (isset($_GET['mis_coches'])): ?>
                    No hay eventos en los que puedas inscribir ninguno de tus vehículos.
                <?php else: ?>
                    No se encontraron eventos.
                <?php endif; ?>
            </p>
        <?php endif; ?>

    </div>
</main>

<?php include 'includes/pie.php'; ?>