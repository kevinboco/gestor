<?php
include 'conexion.php';

$cedula = $_GET['cedula'] ?? '';
if (!$cedula) {
    echo json_encode([]);
    exit;
}

$result = $conexion->query("SELECT * FROM facturas WHERE CEDULA = '$cedula'");
$facturas = [];

while ($fila = $result->fetch_assoc()) {
    $facturas[] = $fila;
}

echo json_encode($facturas);
?>
