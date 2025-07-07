<?php
include 'conexion.php';

$tabla = $_POST['tabla'] ?? '';
$anterior = $_POST['anterior'] ?? '';
$nuevo = $_POST['nuevo'] ?? '';

if ($tabla && $anterior && $nuevo) {
    $nuevo = preg_replace('/[^a-zA-Z0-9_]/', '', $nuevo); // Limpia caracteres peligrosos
    $sql = "ALTER TABLE `$tabla` CHANGE `$anterior` `$nuevo` VARCHAR(255)";
    if ($conexion->query($sql)) {
        http_response_code(200);
    } else {
        http_response_code(500);
        echo "Error: " . $conexion->error;
    }
} else {
    http_response_code(400);
    echo "Parámetros incompletos.";
}
?>
