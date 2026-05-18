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

            $sql = "SELECT * FROM eventos ORDER BY fecha ASC LIMIT 3";
            $resultado = mysqli_query($conexion, $sql);

            if (mysqli_num_rows($resultado) > 0):
                while ($evento = mysqli_fetch_assoc($resultado)):
            ?>
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
                    <span class="badge badge-<?= $evento['tipo_evento'] ?>">
                        <?= htmlspecialchars($evento['tipo_evento']) ?>
                    </span>
                    <h3><?= htmlspecialchars($evento['nombre']) ?></h3>
                    <div class="tarjeta-meta">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <?= date('d/m/Y', strtotime($evento['fecha'])) ?> &middot; <?= substr($evento['hora'], 0, 5) ?>h
                    </div>
                    <div class="tarjeta-meta">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
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