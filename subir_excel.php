<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$mensaje = '';
$hojas = [];
$nombresHojas = [];

$carpetaDestino = __DIR__ . '/archivos_excel/';
if (!is_dir($carpetaDestino)) mkdir($carpetaDestino, 0777, true);

// Procesar archivo subido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivoNombre = $_FILES['archivo']['name'];
    $archivoTemporal = $_FILES['archivo']['tmp_name'];
    $rutaGuardada = $carpetaDestino . basename($archivoNombre);

    if (move_uploaded_file($archivoTemporal, $rutaGuardada)) {
        try {
            $spreadsheet = IOFactory::load($rutaGuardada);
            $mensaje = "✅ Archivo <strong>$archivoNombre</strong> cargado con éxito.";

            foreach ($spreadsheet->getWorksheetIterator() as $index => $sheet) {
                $nombresHojas[] = $sheet->getTitle();
                $datos = $sheet->toArray();
                $tabla = '<div class="table-responsive"><table class="table table-bordered">';
                foreach ($datos as $i => $fila) {
                    $tabla .= '<tr>';
                    foreach ($fila as $celda) {
                        $tag = ($i === 0) ? 'th' : 'td';
                        $tabla .= "<$tag>" . htmlspecialchars($celda) . "</$tag>";
                    }
                    $tabla .= '</tr>';
                }
                $tabla .= '</table></div>';
                $hojas[] = $tabla;
            }
        } catch (Exception $e) {
            $mensaje = "❌ Error al procesar el archivo: " . $e->getMessage();
        }
    } else {
        $mensaje = "❌ Error al guardar el archivo.";
    }
}

// Procesar archivo existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archivo_existente'])) {
    $archivoNombre = basename($_POST['archivo_existente']);
    $rutaGuardada = $carpetaDestino . $archivoNombre;

    if (file_exists($rutaGuardada)) {
        try {
            $spreadsheet = IOFactory::load($rutaGuardada);
            $mensaje = "✅ Archivo existente <strong>$archivoNombre</strong> cargado con éxito.";

            foreach ($spreadsheet->getWorksheetIterator() as $index => $sheet) {
                $nombresHojas[] = $sheet->getTitle();
                $datos = $sheet->toArray();
                $tabla = '<div class="table-responsive"><table class="table table-bordered">';
                foreach ($datos as $i => $fila) {
                    $tabla .= '<tr>';
                    foreach ($fila as $celda) {
                        $tag = ($i === 0) ? 'th' : 'td';
                        $tabla .= "<$tag>" . htmlspecialchars($celda) . "</$tag>";
                    }
                    $tabla .= '</tr>';
                }
                $tabla .= '</table></div>';
                $hojas[] = $tabla;
            }
        } catch (Exception $e) {
            $mensaje = "❌ Error al procesar el archivo: " . $e->getMessage();
        }
    } else {
        $mensaje = "❌ El archivo seleccionado no existe.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Excel con Múltiples Hojas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container">
        <h2 class="mb-4">Subir archivo Excel (.xlsx, .xls)</h2>

        <!-- Formulario para subir nuevo archivo -->
        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <div class="mb-3">
                <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls" required>
            </div>
            <button type="submit" class="btn btn-primary">Subir y Mostrar</button>
        </form>

        <!-- Formulario para seleccionar archivo ya subido -->
        <?php
        $archivosDisponibles = glob($carpetaDestino . '*.xls*');
        if (count($archivosDisponibles) > 0):
        ?>
            <form method="POST" class="mb-4">
                <div class="mb-3">
                    <label for="archivo_existente" class="form-label">O seleccionar archivo ya subido:</label>
                    <select name="archivo_existente" id="archivo_existente" class="form-select" required>
                        <option value="" disabled selected>-- Selecciona un archivo --</option>
                        <?php foreach ($archivosDisponibles as $archivo): ?>
                            <option value="<?= basename($archivo) ?>"><?= basename($archivo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Mostrar archivo existente</button>
            </form>
        <?php endif; ?>

        <!-- Mensaje -->
        <?php if ($mensaje): ?>
            <div class="alert alert-info"><?= $mensaje ?></div>
        <?php endif; ?>

        <!-- Mostrar contenido de las hojas -->
        <?php if (count($hojas) > 0): ?>
            <ul class="nav nav-tabs" id="hojasTabs" role="tablist">
                <?php foreach ($nombresHojas as $i => $nombre): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" id="tab<?= $i ?>-tab" data-bs-toggle="tab" data-bs-target="#tab<?= $i ?>" type="button" role="tab"><?= htmlspecialchars($nombre) ?></button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content border border-top-0 p-3 bg-white" id="hojasTabsContent">
                <?php foreach ($hojas as $i => $contenido): ?>
                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="tab<?= $i ?>" role="tabpanel"
                         data-tab-content="<?= htmlspecialchars(json_encode($contenido), ENT_QUOTES, 'UTF-8') ?>"
                         data-loaded="<?= $i === 0 ? 'true' : 'false' ?>">
                        <?= $i === 0 ? $contenido : '' ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function () {
                const targetId = tab.getAttribute('data-bs-target');
                const targetPane = document.querySelector(targetId);
                if (targetPane && targetPane.dataset.loaded !== 'true') {
                    const content = targetPane.getAttribute('data-tab-content');
                    if (content) {
                        targetPane.innerHTML = JSON.parse(content);
                        targetPane.dataset.loaded = 'true';
                    }
                }
            });
        });
    });
    </script>
</body>
</html>
