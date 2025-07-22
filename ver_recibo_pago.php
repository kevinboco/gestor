<?php
include 'conexion.php';

// Obtener lista de tablas
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

// Leer la plantilla si existe
$plantilla = [];
if (file_exists('plantilla_factura.json')) {
    $plantilla = json_decode(file_get_contents('plantilla_factura.json'), true);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Recibo de Pago</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    td[contenteditable], th[contenteditable] {
      cursor: text;
    }
    td[contenteditable]:focus {
      outline: 2px solid #007bff;
      background-color: #e9f5ff;
    }
    .table-responsive { width: 100%; }
    .table { width: 100%; min-width: max-content; }
    .table th, .table td { padding: 0.3rem; font-size: 0.95rem; }
  </style>
</head>
<body>
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
              <li><a class="dropdown-item <?= $t === $tabla ? 'active' : '' ?>" href="ver_tabla.php?tabla=<?= urlencode($t) ?>"><?= htmlspecialchars($t) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
      </ul>
      <a href="index.php" class="btn btn-outline-light ms-3">➕ Crear nueva tabla</a>
      <a href="subir_excel.php" class="btn btn-outline-light ms-3">➕ Subir Excel</a>
      <form method="POST" action="eliminar_tabla.php" onsubmit="return confirm('¿Eliminar la tabla <?= $tabla ?>?')" class="d-flex ms-3">
        <input type="hidden" name="tabla" value="<?= $tabla ?>">
        <button type="submit" class="btn btn-danger">🗑️ Eliminar tabla</button>
      </form>
    </div>
  </div>
</nav>

<div class="container border p-4 my-4" style="border: 2px dashed #000; max-width: 800px;" id="facturaEditable">
  <h4 class="text-center mb-3" contenteditable="true">FACTURA / COMPROBANTE DE PAGO</h4>
  <table class="table table-bordered">
    <tbody>
      <?php
      $etiquetas = [
        ["Nombre", "", "Cédula", ""],
        ["Concepto", "", "", ""],
        ["Fecha", "", "Monto Pagado", ""],
        ["Forma de Pago", "", "", ""],
        ["Comprobante", "", "Saldo", ""],
        ["Observaciones", "", "", ""],
        ["Firma ______________________________", "", "", ""]
      ];

      $i = 0;
      foreach ($etiquetas as $fila) {
          echo "<tr>";
          foreach ($fila as $columna) {
              $valor = $plantilla["campo$i"] ?? htmlspecialchars($columna);
              $colspan = ($columna === "" && count(array_filter($fila)) === 1) ? 3 : 1;
              echo "<td contenteditable='true' colspan='$colspan'>" . $valor . "</td>";
              $i++;
              if ($colspan === 3) break;
          }
          echo "</tr>";
      }
      ?>
      <tr>
        <td colspan="4">
          <table class="table table-bordered mt-3">
            <thead>
              <tr>
                <th>Fecha</th>
                <th>Comprobante</th>
                <th>Monto Pagado</th>
                <th>detalle del pago</th>
                <th>Cédula</th>
              </tr>
            </thead>
            <tbody id="detalleFacturas"></tbody>
          </table>
        </td>
      </tr>
    </tbody>
  </table>
  <div class="text-center mt-3">
    <button class="btn btn-outline-primary" onclick="imprimirFactura()">🖨️ Imprimir</button>
    <button class="btn btn-success mt-2" onclick="guardarPlantilla()">💾 Guardar</button>
  </div>
</div>

<div class="mb-3 text-end">
  <button class="btn btn-primary" onclick="abrirModalFacturas(null)">➕ Buscar cédula</button>
</div>

<!-- Modal -->
<div class="modal fade" id="modalFacturas" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Seleccionar desde Facturas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="tablaFacturas">Cargando datos...</div>
      </div>
    </div>
  </div>
</div>

<script>
function guardarPlantilla() {
  const factura = document.getElementById("facturaEditable");
  const celdas = factura.querySelectorAll("td[contenteditable], th[contenteditable]");
  const datos = {};
  let index = 0;
  celdas.forEach(celda => {
    datos[`campo${index++}`] = celda.innerText.trim();
  });

  fetch("guardar_plantilla.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(datos)
  })
  .then(res => res.text())
  .then(respuesta => alert("✅ Factura guardada"))
  .catch(err => alert("❌ Error al guardar"));
}

function abrirModalFacturas(celda) {
  const modal = new bootstrap.Modal(document.getElementById('modalFacturas'));
  fetch('obtener_personas_factura.php')
    .then(res => res.text())
    .then(html => {
      document.getElementById('tablaFacturas').innerHTML = html;
      modal.show();
    });
}

function seleccionarDesdeFacturas(cedula) {
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalFacturas'));
  modal.hide();
  document.querySelectorAll("tr.factura-insertada").forEach(f => f.remove());

  fetch(`obtener_todas_facturas.php?cedula=${cedula}`)
    .then(res => res.json())
    .then(facturas => {
      const detalleBody = document.getElementById("detalleFacturas");
      detalleBody.innerHTML = '';
      facturas.forEach(factura => {
        const fila = document.createElement("tr");
        fila.classList.add("factura-insertada");
        fila.innerHTML = `
          <td>${factura.FECHA || ""}</td>
          <td><a href="informacion/${factura.COMPROBANTE}" target="_blank">${factura.COMPROBANTE}</a></td>
          <td>${factura.CUENTA_POR_PAGAR || 0}</td>
          <td>${factura.DETALLES_DE_PAGO || ""}</td>
          <td>${factura.CEDULA}</td>
        `;
        detalleBody.appendChild(fila);
      });
    });
}

function imprimirFactura() {
  const original = document.body.innerHTML;
  const factura = document.getElementById('facturaEditable').outerHTML;
  document.body.innerHTML = factura;
  window.print();
  document.body.innerHTML = original;
  location.reload();
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
