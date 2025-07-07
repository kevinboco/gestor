<?php
include 'conexion.php';

$nombre_tabla = $_POST['nombre_tabla'];
$campos = $_POST['campos'];
$tipos = $_POST['tipos'];

$sql = "CREATE TABLE `$nombre_tabla` (id INT AUTO_INCREMENT PRIMARY KEY";

foreach ($campos as $i => $campo) {
    $tipo = $tipos[$i];
    $sql .= ", `$campo` $tipo";
}
$sql .= ")";

if ($conexion->query($sql) === TRUE) {
    echo "Tabla '$nombre_tabla' creada con éxito.<br><a href='index.php'>Volver</a>";
} else {
    echo "Error: " . $conexion->error;
}
?>