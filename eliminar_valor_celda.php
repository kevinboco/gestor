<?php
include 'conexion.php';

$tabla = $_GET['tabla'];
$columna = $_GET['columna'];
$id = (int)$_GET['id'];

$sql = "UPDATE `$tabla` SET `$columna` = NULL WHERE id = $id";

if ($conexion->query($sql)) {
    header("Location: ver_tabla.php?tabla=$tabla");
} else {
    echo "Error al borrar valor: " . $conexion->error;
}
?>