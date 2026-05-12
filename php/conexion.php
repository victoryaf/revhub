<?php

$host     = "localhost";
$usuario  = "root";
$password = "";
$bd       = "revhub";

$conexion = mysqli_connect($host, $usuario, $password, $bd);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conexion, "utf8mb4");

?>