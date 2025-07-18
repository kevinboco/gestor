<?php
// Mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Forzar salida como JSON
header('Content-Type: application/json');

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Recibir los datos del fetch
$data = json_decode(file_get_contents("php://input"), true);
$hojas = $data['hojas'] ?? [];
$archivo = basename($data['archivo'] ?? '');
$ruta = __DIR__ . "/archivos_excel/" . $archivo;

if (!$archivo || !file_exists($ruta)) {
    echo json_encode(['mensaje' => '❌ Archivo no encontrado']);
    exit;
}

try {
    // Cargar el archivo original
    $spreadsheet = IOFactory::load($ruta);

    foreach ($hojas as $i => $hojaDatos) {
        $sheet = $spreadsheet->getSheet($i);
        $sheet->fromArray($hojaDatos, null, 'A1');
    }

    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($ruta);

    echo json_encode(['mensaje' => '✅ Archivo actualizado correctamente']);
} catch (Exception $e) {
    echo json_encode(['mensaje' => '❌ Error al guardar: ' . $e->getMessage()]);
}
