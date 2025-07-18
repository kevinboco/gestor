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
<script>
// Diccionario de tipos de documento y conceptos
const diccionario = {
  tipos_documento: {
    "11": "Registro civil de nacimiento",
    "12": "Tarjeta de identidad",
    "13": "Cédula de ciudadanía",
    "21": "Tarjeta de extranjería",
    "22": "Cédula de extranjería",
    "31": "NIT",
    "41": "Pasaporte",
    "42": "Tipo de documento Extranjero",
    "43": "Sin identificación del exterior o para uso definido por la DIAN",
    "47": "Permiso Especial de Permanencia",
    "48": "Permiso por Protección Temporal"
  },
  conceptos: {
  "5002": "Honorarios",
  "5003": "Comisiones",
  "5004": "Servicios",
  "5005": "Arrendamientos",
  "5006": "Intereses y rendimientos financieros causados",
  "5007": "Compra de activos movibles (E.T.Art 60)",
  "5008": "Compra de Activos Fijos (E.T. Art 60)",
  "5010": "Pagos o Abonos en cuenta por concepto de aportes parafiscales",
  "5011": "Pagos a EPS y Riesgos Laborales",
  "5012": "Aportes obligatorios para pensiones",
  "5013": "Donaciones en dinero a entidades",
  "5014": "Donaciones en activos a entidades",
  "5015": "Valor de los impuestos como deducción",
  "5016": "Otros costos y deducciones",
  "5018": "Primas de reaseguros",
  "5019": "Amortizaciones realizadas",
  "5020": "Compra de activos fijos reales productivos",
  "5023": "Pagos al exterior por asistencia técnica",
  "5024": "Pagos al exterior por marcas",
  "5025": "Pagos al exterior por patentes",
  "5026": "Pagos al exterior por regalías",
  "5027": "Pagos al exterior por servicios técnicos",
  "5028": "Devolución de pagos años anteriores",
  "5029": "Gastos anticipados por Compras",
  "5030": "Gastos anticipados por Honorarios",
  "5031": "Gastos anticipados por Comisiones",
  "5032": "Gastos anticipados por Servicios",
  "5033": "Gastos anticipados por Arrendamientos",
  "5034": "Gastos anticipados por intereses",
  "5035": "Gastos anticipados por otros conceptos",
  "5044": "Pago por loterías y apuestas",
  "5045": "Retención sobre ingresos de tarjetas",
  "5046": "Enajenación de activos en oficinas de tránsito",
  "5047": "Siniestros por lucro cesante",
  "5048": "Siniestros por daño emergente",
  "5053": "Retenciones a título de timbre",
  "5054": "Devolución de retenciones timbre",
  "5055": "Viáticos",
  "5056": "Gastos de representación",
  "5058": "Aportes y tasas como deducción",
  "5059": "Pagos a fondo de revalorización de aportes",
  "5060": "Redención de inversiones",
  "5061": "Utilidades a beneficiarios distintos",
  "5063": "Intereses efectivamente pagados",
  "5064": "Devoluciones aportes pensionales",
  "5065": "Excedentes pensionales pagados",
  "5066": "Impuesto nacional al consumo",
  "5067": "Pagos al exterior por consultoría",
  "5068": "Dividendos exigibles 2016 y anteriores",
  "5069": "Dividendos exigibles 2016 y anteriores (otra)",
  "5070": "Dividendos exigibles 2017 y siguientes",
  "5071": "Dividendos exigibles 2017 y siguientes (otra)",
  "5072": "Siniestros por seguros de vida",
  "5073": "Desembolsos depósitos judiciales",
  "5074": "Reintegros depósitos judiciales",
  "5075": "Regalías y propiedad intelectual",
  "5076": "Utilidades por diferimiento de ingresos",
  "5079": "Intereses por deuda subcapitalizada",
  "5080": "Servicios audiovisuales digitales",
  "5081": "Servicios en plataformas digitales",
  "5082": "Publicidad online",
  "5083": "Enseñanza a distancia",
  "5084": "Pagos por intangibles",
  "5085": "Otros servicios digitales",
  "5086": "Retención por dividendos",
  "5087": "Puntos premio redimidos",
  "5088": "Costos por diferencia en cambio",
  "5089": "Compra de acciones no cotizadas",
  "5090": "Donación de acciones no cotizadas",
  "5091": "Cesión de acciones no cotizadas",
  "5092": "Ingreso en especie",
  "5093": "Donaciones de bebidas ultraprocesadas",
  "5094": "Donaciones de comestibles ultraprocesados",
  "5095": "Regalías por hidrocarburos",
  "5096": "Regalías por gas",
  "5097": "Regalías por carbón",
  "5098": "Regalías por minerales",
  "5099": "Regalías por sal y materiales",
  "5100": "Prima en colocación de acciones",

  // Nuevos - Retenciones
  "1301": "Retenciones por salarios y demás pagos laborales",
  "1302": "Retenciones por ventas",
  "1303": "Retenciones por Servicios",
  "1304": "Retenciones por Honorarios",
  "1305": "Retenciones por Comisiones",
  "1306": "Retenciones por Intereses y Rendimientos Financieros",
  "1307": "Retenciones por Arrendamientos",
  "1308": "Retención por Otros conceptos",
  "1309": "Retención en la fuente en el Impuesto a las ventas",
  "1310": "Retención por dividendos y participaciones",
  "1311": "Retención por enajenación de activos fijos de personas naturales ante oficinas de tránsito y otras entidades autorizadas",
  "1312": "Retención por ingresos de tarjetas débito y crédito",
  "1313": "Retención por loterías, rifas, apuestas y similares",
  "1314": "Retención por Impuesto de Timbre",
  "1320": "Retención por dividendos y participaciones recibidas por sociedades nacionales art. 242-1 del E.T.",
  "1315": "Saldo de las cuentas por cobrar a clientes",
  "1316": "Saldo de las cuentas por cobrar a accionistas, socios, comuneros, cooperados y compañías vinculadas",
  "1317": "Otras Cuentas por Cobrar",
  "1318": "Valor total del saldo fiscal del deterioro de cartera, identificándolo con el NIT del deudor",

  // Nuevos - Descuentos tributarios (8300s)
  "8303": "Descuento tributario por impuestos pagados en el exterior. E.T., art. 254",
  "8305": "Descuento tributario empresas de servicios públicos domiciliarios. L. 788/2002, art. 104",
  "8316": "Descuento tributario por donaciones a programas de becas. E.T. Art. 256, Parágrafo 2, num. i)",
  "8317": "Descuento por inversión en I+D+i. E.T. art. 158-1 y 256",
  "8318": "Descuento por donaciones a ESAL del régimen especial. E.T., art. 257",
  "8319": "Descuento por donaciones a ESAL no contribuyentes. E.T., art. 257",
  "8320": "Descuento por inversión en medio ambiente. E.T., art. 255",
  "8321": "Descuento por donación a bibliotecas públicas. E.T., art. 257",
  "8322": "Descuento por donación a fondo para víctimas. L. 1448 de 2011",
  "8323": "Descuento por impuestos pagados por ECE. E.T., art. 892",
  "8324": "Descuento por donación a fundaciones de derechos humanos. E.T., art. 126-2",
  "8325": "Descuento por donación a deporte aficionado. E.T., art. 126-2",
  "8326": "Descuento por donación a cultura y deporte. E.T., art. 126-2",
  "8327": "Descuento por apadrinamiento de parques. E.T., art. 126-5",
  "8328": "Descuento por aportes pensionales régimen SIMPLE. E.T., art. 903",
  "8329": "Descuento por ventas electrónicas. E.T., art. 912",
  "8330": "Descuento por ICA. E.T., art. 115",
  "8331": "Descuento por IVA en activos fijos. E.T., art. 258-1",
  "8332": "Descuento por becas a deportistas. E.T., art. 257-1",
  "8333": "Descuento por inversión ambiental turística. E.T., art. 255 par.2",
  "8334": "Descuento por donación a iNNpulsa. E.T., art. 256 par.2",
  "8336": "Descuento por donación a Fondo Caldas. E.T., art. 158-1",
  "8337": "Descuento por contratación de doctores. E.T., art. 158-1",
  "8338": "Descuento por donación al Icetex para becas. E.T., art. 58-1",

  // Nuevos - Ingresos brutos (4000s)
  "4001": "Ingresos Brutos de Actividades Ordinarias",
  "4002": "Otros Ingresos Brutos",
  "4003": "Ingresos por intereses y rendimientos financieros",
  "4004": "Ingresos por intereses de Créditos Hipotecarios",
  "4005": "Ingresos por Consorcios o Uniones Temporales",
  "4006": "Ingresos por Mandato o Administración Delegada",
  "4007": "Ingresos por Exploración y Explotación",
  "4008": "Ingresos por Fiducia",
  "4009": "Ingresos por Terceros (Beneficiario)",
  "4011": "Ingresos por joint venture",
  "4012": "Ingresos por Cuentas en Participación",
  "4013": "Ingresos por Cooperación con Entidades Públicas",
  "4014": "Ventas con puntos premio redimidos",
  "4015": "Puntos premio vencidos no reclamados",
  "4016": "Ventas por puntos premio redimidos de años anteriores",
  "4017": "Recuperación de costos/deducciones como renta líquida",
  "4018": "Ingresos por diferencia en cambio",
  "4019": "Ingresos por ganancia ocasional",
  "4020": "Ingresos por venta de acciones no cotizadas - renta",
  "4021": "Ingresos por venta de acciones no cotizadas - ganancia ocasional",

  // Nuevos - Pasivos (2200s)
  "2201": "Saldo de pasivos con proveedores",
  "2202": "Pasivos con compañías vinculadas, accionistas y socios",
  "2203": "Obligaciones financieras",
  "2204": "Pasivos por impuestos, gravámenes y tasas",
  "2206": "Otros pasivos",
  "2207": "Pasivos por cálculo actuarial",
  "2208": "Pasivos respaldados en documento con fecha cierta",
  "2209": "Pasivos exclusivos de compañías de seguros",
  "2211": "Pasivos por depósitos judiciales",
  "2212": "Pasivo por puntos premio otorgados",
  "2213": "Pasivo por ingresos diferidos art. 23-1 E.T.",
  "2214": "Pasivos por aportes parafiscales, salud, pensión y cesantías",
  "2215": "Pasivos laborales consolidados (sin cesantías)",
  "2216": "Pasivos financieros sin identificación de acreedor nacional",
  "2217": "Pasivos financieros sin identificación de acreedor extranjero",

  // Nuevos conceptos agregados
  "1110": "Saldo a 31 de Diciembre de las cuentas corrientes y/o ahorro que posea en el país",
  "1115": "Valor total del saldo de las cuentas corrientes y/o ahorro poseídas en el exterior",
  "1200": "Valor Patrimonial de los Bonos",
  "1201": "Valor Patrimonial de los Certificados de Depósito",
  "1202": "Valor Patrimonial de los Títulos",
  "1203": "Valor Patrimonial de los Derechos Fiduciarios",
  "1204": "Valor Patrimonial de las Demás Inversiones Poseídas",
  "1205": "Valor Patrimonial de las Acciones o Aportes poseidos a 31 de Diciembre",
  "1206": "Valor patrimonial de los criptoactivos",

  // Ingresos no constitutivos (8000s)
  "8001": "Ingresos no constitutivos por dividendos y participaciones. E.T., art. 48",
  "8002": "Ingresos no constitutivos por componente inflacionario de los rendimientos financieros. E.T. art. 38 al 40",
  "8005": "Ingresos no constitutivos por la utilidad en enajenación de acciones. E.T., art. 36-1",
  "8006": "Ingresos no constitutivos por utilidades de negociación de derivados. E.T., art. 36-1",
  "8008": "Ingresos no constitutivos por indemnizaciones en seguros de daño. E.T., art. 45",
  "8009": "Ingresos no constitutivos por destrucción o renovación de cultivos. E.T., art. 46-1",
  "8010": "Ingresos no constitutivos por aportes al transporte masivo. E.T., art. 53",
  "8011": "Ingresos no constitutivos por la Comisión Nacional de Televisión. L. 488/98",
  "8013": "Ingresos no constitutivos por liberación de reserva Art. 290 E.T.",
  "8014": "Ingresos no constitutivos por Incentivo a la Capitalización Rural (ICR)",
  "8016": "Ingresos no constitutivos por retribución como recompensa. E.T., art. 42",
  "8017": "Ingresos no constitutivos por enajenación de bienes expropiados. L. 388/97",
  "8019": "Ingresos no constitutivos por aportes al sistema general de pensiones",
  "8025": "Ingresos no constitutivos por liquidación de sociedades limitadas",
  "8026": "Ingresos no constitutivos por donaciones a campañas políticas",
  "8028": "Ingresos no constitutivos por capitalización de utilidades",
  "8029": "Ingresos no constitutivos para proyectos de ciencia e inversión",
  "8030": "Ingresos no constitutivos administrados por Fogafín",
  "8032": "Ingresos no constitutivos por gananciales",
  "8033": "Ingresos no constitutivos por ajustes por inflación",
  "8034": "Ingresos no constitutivos por trabajos científicos o tecnológicos",
  "8035": "Ingresos no constitutivos por capital semilla",
  "8036": "Ingresos no constitutivos por aportes de la nación",
  "8037": "Ingresos no constitutivos por titularización de cartera hipotecaria",
  "8041": "Ingresos no constitutivos por enajenación de inmuebles",
  "8042": "Ingresos no constitutivos por dividendos de ECE",
  "8043": "Ingresos no constitutivos por ganancias en ECE",
  "8044": "Ingresos no constitutivos por Certificados de Incentivo Forestal",
  "8045": "Ingresos no constitutivos por aportes al sistema de salud",
  "8046": "Ingresos no constitutivos por Premio Fiscal. E.T., art. 618-1",
  "8047": "Ingresos no constitutivos por producción cinematográfica",
  "8048": "Ingresos no constitutivos por donaciones Protocolo Montreal",
  "8049": "Ingresos no constitutivos por apoyos estatales públicos",
  "8050": "Ingresos no constitutivos por reparto de acciones a trabajadores (BIC)",
  "8051": "Ingresos no constitutivos por convenios de doble tributación",
  "8052": "Ingresos no gravados por inversiones en régimen de Mega-inversiones"
 }

};

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('td').forEach(td => {
    const valor = td.innerText.trim();
    if (diccionario.tipos_documento[valor]) {
      td.setAttribute('title', diccionario.tipos_documento[valor]);
      td.classList.add('tooltip-diccionario');
    } else if (diccionario.conceptos[valor]) {
      td.setAttribute('title', diccionario.conceptos[valor]);
      td.classList.add('tooltip-diccionario');
    }
  });
});
</script>

<style>
.tooltip-diccionario {
  background-color: #fff3cd; /* amarillo suave */
  
}
</style>

</body>
</html>
