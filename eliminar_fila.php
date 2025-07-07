<?php
include 'conexion.php';

$tabla = $_GET['tabla'];
$id = (int)$_GET['id'];

$sql = "DELETE FROM `$tabla` WHERE id = $id";

if ($conexion->query($sql)) {
    header("Location: ver_tabla.php?tabla=$tabla");
} else {
    echo "Error al eliminar fila: " . $conexion->error;
}
?>