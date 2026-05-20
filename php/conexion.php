<?php
$conexion = mysqli_connect('localhost', 'root', '', 'revhub');

if (!$conexion) {
    die('Error de conexión: ' . mysqli_connect_error());
}

// Para que las ñ y acentos funcionen bien
mysqli_set_charset($conexion, 'utf8mb4');
?>