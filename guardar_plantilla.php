<?php
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo "Datos inválidos";
    exit;
}

file_put_contents("plantilla_factura.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Guardado correctamente";
