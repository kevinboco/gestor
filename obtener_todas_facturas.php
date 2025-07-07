<?php
include 'conexion.php';

$cedula = $_GET['cedula'] ?? '';
$result = $conexion->query("SELECT * FROM facturas WHERE CEDULA = '$cedula'");

$facturas = [];
while ($row = $result->fetch_assoc()) {
    $facturas[] = $row;
}

header('Content-Type: application/json');
echo json_encode($facturas);
