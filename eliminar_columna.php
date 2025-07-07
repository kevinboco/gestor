<?php
include 'conexion.php';

$tabla = $_POST['tabla'] ?? '';
$columna = $_POST['columna'] ?? '';

if (!$tabla || !$columna) {
    http_response_code(400);
    echo "Faltan datos";
    exit;
}

$sql = "ALTER TABLE `$tabla` DROP COLUMN `$columna`";
if ($conexion->query($sql)) {
    echo "Columna eliminada";
} else {
    http_response_code(500);
    echo "Error: " . $conexion->error;
}
?>
