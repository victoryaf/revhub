<?php
/* Endpoint para cargar vehículos de un usuario por AJAX */
$conexion = null;
include 'php/conexion.php';

if (!isset($conexion) || !$conexion) {
    echo '[]';
    exit;
}

if (!isset($_GET['id_usuario']) || !is_numeric($_GET['id_usuario'])) {
    echo '[]';
    exit;
}

$id = (int)$_GET['id_usuario'];
$resultado = mysqli_query($conexion,
    "SELECT id_vehiculo, marca, modelo, matricula FROM vehiculos WHERE id_usuario = $id ORDER BY marca ASC"
);

$vehiculos = [];
while ($v = mysqli_fetch_assoc($resultado)) {
    $vehiculos[] = $v;
}

header('Content-Type: application/json');
echo json_encode($vehiculos);
?>