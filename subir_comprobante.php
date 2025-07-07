<?php
include 'conexion.php';

$tabla = $_POST['tabla'] ?? '';
$columna = $_POST['columna'] ?? '';
$id = $_POST['id'] ?? '';

if (!$tabla || !$columna || !$id || !isset($_FILES['archivo'])) {
    http_response_code(400);
    echo "Faltan datos";
    exit;
}

$archivo = $_FILES['archivo'];
$nombreTemporal = $archivo['tmp_name'];
$nombreOriginal = basename($archivo['name']);
$extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);

// Nombre único para evitar conflictos
$nuevoNombre = uniqid("comprobante_") . '.' . $extension;
$rutaDestino = __DIR__ . "/archivos/$nuevoNombre";

// 1. Verificar si ya existe un archivo para esta fila
$sqlExistente = $conexion->prepare("SELECT `$columna` FROM `$tabla` WHERE id = ?");
$sqlExistente->bind_param("i", $id);
$sqlExistente->execute();
$resultado = $sqlExistente->get_result();

if ($fila = $resultado->fetch_assoc()) {
    $archivoAnterior = $fila[$columna];
    $rutaAnterior = __DIR__ . "/archivos/" . $archivoAnterior;

    // 2. Eliminar el archivo anterior si existe
    if ($archivoAnterior && file_exists($rutaAnterior)) {
        unlink($rutaAnterior);
    }
}

$sqlExistente->close();

// 3. Mover el nuevo archivo
if (!move_uploaded_file($nombreTemporal, $rutaDestino)) {
    http_response_code(500);
    echo "No se pudo guardar el archivo.";
    exit;
}

// 4. Guardar el nuevo nombre en la base de datos
$stmt = $conexion->prepare("UPDATE `$tabla` SET `$columna` = ? WHERE id = ?");
$stmt->bind_param("si", $nuevoNombre, $id);
$stmt->execute();

echo "Archivo actualizado correctamente";
