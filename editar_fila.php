<?php
include 'conexion.php';

$tabla = $_POST['tabla'];
$id = (int)$_POST['id'];
$columnas = $_POST['columnas'];

$set = [];
foreach ($columnas as $campo => $valor) {
    $valor = $conexion->real_escape_string($valor);
    $set[] = "`$campo` = '$valor'";
}

$sql = "UPDATE `$tabla` SET " . implode(', ', $set) . " WHERE id = $id";

if ($conexion->query($sql)) {
    header("Location: ver_tabla.php?tabla=$tabla");
} else {
    echo "Error al actualizar: " . $conexion->error;
}
?>