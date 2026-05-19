<?php include 'includes/cabecera.php'; ?>

<main>
    <div class="banner">
        <div class="banner-inner">
            <h1>Organiza tu próxima <span>quedada</span></h1>
            <p>La plataforma para gestionar eventos del mundo del motor. Coches, motos y mucha comunidad.</p>
            <div class="banner-btns">
                <a href="/revhub/eventos.php" class="btn">Ver eventos</a>
                <?php if (!isset($_SESSION['usuario'])): ?>
                    <a href="/revhub/registro.php" class="enlace">Crear cuenta</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="contenedor">
        <div class="seccion-header">
            <h2>Próximos eventos</h2>
            <a href="/revhub/eventos.php">Ver todos &rarr;</a>
        </div>

        <div class="eventos-grid">
            <?php
            include 'php/conexion.php';
            $resultado = mysqli_query($conexion, "SELECT * FROM eventos WHERE fecha >= CURDATE() ORDER BY fecha ASC LIMIT 3");

            if (mysqli_num_rows($resultado) > 0):
                while ($evento = mysqli_fetch_assoc($resultado)):
            ?>
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
                    <a href="/revhub/evento.php?id=<?= $evento['id_evento'] ?>" class="btn">Ver más</a>
                </div>
            </div>
            <?php
                endwhile;
            else:
            ?>
                <p>No hay eventos disponibles.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/pie.php'; ?>