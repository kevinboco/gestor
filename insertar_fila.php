<?php
include 'conexion.php';

$tabla = $_POST['tabla'] ?? '';
if (!$tabla) {
    http_response_code(400);
    exit("Falta la tabla.");
}

$columnas_result = $conexion->query("DESCRIBE `$tabla`");
$columnas = [];
foreach ($columnas_result as $col) {
    if ($col['Field'] !== 'id') {
        $columnas[] = $col['Field'];
    }
}

$campos = [];
$valores = [];
$tipos = '';

foreach ($columnas as $col) {
    if (isset($_POST[$col])) {
        $campos[] = "`$col`";
        $valores[] = $_POST[$col];
        $tipos .= 's';
    }
}

if (count($campos) > 0) {
    $stmt = $conexion->prepare("INSERT INTO `$tabla` (" . implode(',', $campos) . ") VALUES (" . str_repeat('?,', count($campos) - 1) . "?)");
    $stmt->bind_param($tipos, ...$valores);
    if ($stmt->execute()) {
        http_response_code(200);
    } else {
        http_response_code(500);
        echo "Error al insertar: " . $stmt->error;
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo "No se recibieron datos válidos.";
}
