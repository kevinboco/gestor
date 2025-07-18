<?php
require 'vendor/autoload.php';
include 'conexion.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$tablas = [];
$tablas_result = $conexion->query("SHOW TABLES");
while ($row = $tablas_result->fetch_array()) {
    $tablas[] = $row[0];
}

$tabla = $_GET['tabla'] ?? '';
if ($tabla) {
    $columnas_result = $conexion->query("DESCRIBE `$tabla`");
    $columnas = [];
    while ($col = $columnas_result->fetch_assoc()) {
        $columnas[] = $col['Field'];
    }
    $datos = $conexion->query("SELECT * FROM `$tabla`");
} else {
    $columnas = [];
    $datos = new stdClass();
}
$mensaje = '';
$hojas = [];
$nombresHojas = [];

$carpetaDestino = __DIR__ . '/archivos_excel/';
if (!is_dir($carpetaDestino)) mkdir($carpetaDestino, 0777, true);

function limpiarFilasVacias(array $datos): array {
    while (!empty($datos)) {
        $ultimaFila = end($datos);
        if (array_filter($ultimaFila, fn($valor) => trim($valor) !== '')) break;
        array_pop($datos);
    }
    return $datos;
}

$archivoNombre = '';

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
                $datosCrudos = $sheet->toArray();
                $datos = limpiarFilasVacias($datosCrudos);

                $tabla = '<div class="table-responsive"><table class="table table-bordered">';
                
                // Agregar filas fusionadas de encabezado tipo Excel
                if (count($datos) > 2) {
                    $encabezado1 = array_shift($datos);
                    $encabezado2 = array_shift($datos);
                    $tabla .= '<tr><td class="excel-header" colspan="' . count($encabezado1) . '">' . implode(' ', array_filter($encabezado1)) . '</td></tr>';
                    $tabla .= '<tr><td class="excel-header" colspan="' . count($encabezado2) . '">' . implode(' ', array_filter($encabezado2)) . '</td></tr>';
                }

                foreach ($datos as $i => $fila) {
                    $tabla .= '<tr>';
                    foreach ($fila as $celda) {
                        if ($i === 0) {
                            $tabla .= "<th>" . htmlspecialchars($celda) . "</th>";
                        } else {
                            $tabla .= "<td contenteditable='true'>" . htmlspecialchars($celda) . "</td>";
                        }
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archivo_existente'])) {
    $archivoNombre = basename($_POST['archivo_existente']);
    $rutaGuardada = $carpetaDestino . $archivoNombre;

    if (file_exists($rutaGuardada)) {
        try {
            $spreadsheet = IOFactory::load($rutaGuardada);
            $mensaje = "✅ Archivo existente <strong>$archivoNombre</strong> cargado con éxito.";
            foreach ($spreadsheet->getWorksheetIterator() as $index => $sheet) {
                $nombresHojas[] = $sheet->getTitle();
                $datosCrudos = $sheet->toArray();
                $datos = limpiarFilasVacias($datosCrudos);

                $tabla = '<div class="table-responsive"><table class="table table-bordered">';
                
                // Agregar filas fusionadas de encabezado tipo Excel
                if (count($datos) > 2) {
                    $encabezado1 = array_shift($datos);
                    $encabezado2 = array_shift($datos);
                    $tabla .= '<tr><td class="excel-header" colspan="' . count($encabezado1) . '">' . implode(' ', array_filter($encabezado1)) . '</td></tr>';
                    $tabla .= '<tr><td class="excel-header" colspan="' . count($encabezado2) . '">' . implode(' ', array_filter($encabezado2)) . '</td></tr>';
                }

                foreach ($datos as $i => $fila) {
                    $tabla .= '<tr>';
                    foreach ($fila as $celda) {
                        if ($i === 0) {
                            $tabla .= "<th>" . htmlspecialchars($celda) . "</th>";
                        } else {
                            $tabla .= "<td contenteditable='true'>" . htmlspecialchars($celda) . "</td>";
                        }
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
    <title>Editar Excel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Estilos tipo Excel -->
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: 'Segoe UI', sans-serif;
            font-size: 14px;
        }

        th, td {
            border: 1px solid #ddd;
            text-align: center;
            padding: 8px;
            vertical-align: middle;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            white-space: nowrap;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table-responsive {
            max-height: 600px;
            overflow-y: auto;
        }

        .excel-header {
            background-color: #cfe2ff;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body class="bg-light p-4">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Gestor de Tablas</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarTablas">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarTablas">
      <ul class="navbar-nav me-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Seleccionar tabla</a>
          <ul class="dropdown-menu">
            <?php foreach ($tablas as $t): ?>
              <li>
                <a class="dropdown-item <?= $t === $tabla ? 'active' : '' ?>" href="ver_tabla.php?tabla=<?= urlencode($t) ?>">
                    <?= htmlspecialchars($t) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>
      </ul>
      <a href="index.php" class="btn btn-outline-light ms-3">➕ Crear nueva tabla</a>
    </div>
  </div>
</nav>

<div class="container">
    <h2 class="mb-4">Subir o Editar Excel</h2>

    <form method="POST" enctype="multipart/form-data" class="mb-4">
        <div class="mb-3">
            <input type="file" name="archivo" class="form-control" accept=".xlsx,.xls" required>
        </div>
        <button type="submit" class="btn btn-primary">Subir y Mostrar</button>
    </form>

    <?php
    $archivosDisponibles = glob($carpetaDestino . '*.xls*');
    if (count($archivosDisponibles) > 0):
    ?>
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="archivo_existente" class="form-label">Seleccionar archivo existente:</label>
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

    <?php if ($mensaje): ?>
        <div class="alert alert-info"><?= $mensaje ?></div>
    <?php endif; ?>

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
                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="tab<?= $i ?>" role="tabpanel"><?= $contenido ?></div>
            <?php endforeach; ?>
        </div>

        <button id="guardarCambios" class="btn btn-success mt-4">💾 Guardar Cambios</button>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('guardarCambios')?.addEventListener('click', function () {
    const hojas = [];
    document.querySelectorAll('.tab-pane').forEach(pane => {
        const filas = [];
        pane.querySelectorAll('tr').forEach(tr => {
            const fila = [];
            tr.querySelectorAll('th, td').forEach(cell => {
                fila.push(cell.innerText.trim());
            });
            filas.push(fila);
        });
        hojas.push(filas);
    });

    fetch('guardar_excel.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            hojas,
            archivo: '<?= $archivoNombre ?>'
        })
    })
    .then(res => res.json())
    .then(res => alert(res.mensaje))
    .catch(err => alert("❌ Error al guardar: " + err));
});
</script>
</body>
</html>
