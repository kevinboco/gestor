
<?php
include 'conexion.php';

$tabla = $_POST['tabla'] ?? '';
$id = $_POST['id'] ?? '';
$columna = $_POST['columna'] ?? '';

if (!$tabla || !$id || !$columna) {
    http_response_code(400);
    exit('Faltan datos');
}

// Obtener el nombre del archivo actual
$stmt = $conexion->prepare("SELECT `$columna` FROM `$tabla` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($archivo);
$stmt->fetch();
$stmt->close();

if ($archivo && file_exists("archivos/$archivo")) {
    unlink("archivos/$archivo");
}

// Limpiar el campo en la base de datos
$stmt = $conexion->prepare("UPDATE `$tabla` SET `$columna` = NULL WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

echo "ok";