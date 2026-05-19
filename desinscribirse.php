<?php
include 'php/conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* --- Comprobar sesión --- */
if (!isset($_SESSION['usuario'])) {
    header('Location: /revhub/login.php');
    exit;
}

/* --- Comprobar id de evento válido --- */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: /revhub/eventos.php');
    exit;
}

$id_evento  = (int)$_GET['id'];
$id_usuario = $_SESSION['usuario'];

/* --- Eliminar la inscripción --- */
mysqli_query($conexion,
    "DELETE FROM inscripciones
     WHERE id_usuario = $id_usuario AND id_evento = $id_evento"
);

header("Location: /revhub/evento.php?id=$id_evento");
exit;
?>