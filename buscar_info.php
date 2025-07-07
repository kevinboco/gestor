<?php
include 'conexion.php';

$cedula = $_POST['cedula'] ?? '';

if (!$cedula) {
    echo json_encode(['error' => 'Cédula vacía']);
    exit;
}
$stmt = $conexion->prepare("SELECT 
    `PRIMER NOMBRE`, 
    `SEGUNDO NOMBRE`, 
    `PRIMER APELLIDO`, 
    `SEGUNDO APELLIDO`, 
    `CARGO` 
FROM `base de datos` 
WHERE `CEDULA` = ?");

$stmt->bind_param("s", $cedula);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
    'PRIMER_NOMBRE'    => $row['PRIMER NOMBRE'],
    'SEGUNDO_NOMBRE'   => $row['SEGUNDO NOMBRE'],
    'PRIMER_APELLIDO'  => $row['PRIMER APELLIDO'],
    'SEGUNDO_APELLIDO' => $row['SEGUNDO APELLIDO'],
    'CARGO_ENTIDAD'    => $row['CARGO']
]);

} else {
    echo json_encode(['error' => 'No encontrado']);
}
