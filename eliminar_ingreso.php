<?php
include 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'];

$stmt = $conexion->prepare("DELETE FROM ingresos_entidades WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo 'ok';
} else {
    echo 'error';
}
?>
