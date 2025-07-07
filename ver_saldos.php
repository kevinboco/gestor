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
    $datos = new stdClass(); // Evita errores si no hay tabla
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Saldos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    td[contenteditable], th[contenteditable] {
      cursor: text;
    }
    td[contenteditable]:focus {
      outline: 2px solid #007bff;
      background-color: #e9f5ff;
    }
    .table-responsive {
      width: 100%;
    }
    .table {
      width: 100%;
      min-width: max-content;
    }
    .table th, .table td {
      padding: 0.3rem;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Gestor de Saldos</a>
    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbarTablas">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarTablas">
      <ul class="navbar-nav me-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Seleccionar tabla</a>
          <ul class="dropdown-menu">
            <?php foreach ($tablas as $t): ?>
              <li><a class="dropdown-item <?= $t === $tabla ? 'active' : '' ?>" href="?tabla=<?= urlencode($t) ?>"><?= htmlspecialchars($t) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </li>
      </ul>
      <a href="index.php" class="btn btn-outline-light ms-3">➕ Crear nueva tabla</a>
      <form method="POST" action="eliminar_tabla.php" onsubmit="return confirm('¿Eliminar la tabla <?= $tabla ?>?')" class="d-flex ms-3">
        <input type="hidden" name="tabla" value="<?= $tabla ?>">
        <button type="submit" class="btn btn-danger">🗑️ Eliminar tabla</button>
      </form>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <h2>Tabla: <?= htmlspecialchars($tabla) ?></h2>

  <div class="mb-3 text-end">
    <button class="btn btn-primary" onclick="abrirModalFacturas()">➕ Buscar cédula</button>
  </div>

  <table class="table table-bordered text-center">
    <thead>
      <tr>
        <?php foreach ($columnas as $col): if ($col === 'id') continue; ?>
          <th><?= htmlspecialchars($col) ?></th>
        <?php endforeach; ?>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $datos->fetch_assoc()): ?>
        <tr>
          <?php foreach ($columnas as $col): if ($col === 'id') continue; ?>
            <td data-columna="<?= $col ?>"><?= htmlspecialchars($row[$col]) ?></td>
          <?php endforeach; ?>
          <td><a href="eliminar_fila.php?tabla=<?= $tabla ?>&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Eliminar</a></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
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
function abrirModalFacturas() {
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

  // Eliminar filas anteriores insertadas desde el modal
  document.querySelectorAll("tr.factura-insertada").forEach(f => f.remove());

  fetch(`obtener_todas_facturas.php?cedula=${cedula}`)
    .then(res => res.json())
    .then(facturas => {
      const tbody = document.querySelector("tbody");

      facturas.forEach(factura => {
        const fila = document.createElement("tr");
        fila.classList.add("factura-insertada");
        fila.innerHTML = `
          <td>${factura.FECHA || ""}</td>
          <td><a href="archivos/${factura.COMPROBANTE}" target="_blank">${factura.COMPROBANTE}</a></td>
          <td>${factura.CUENTA_POR_PAGAR || 0}</td>
          <td>${factura.MONTO_PAGADO || 0}</td>
          <td>${(factura.CUENTA_POR_PAGAR || 0) - (factura.MONTO_PAGADO || 0)}</td>
          <td>${factura.DETALLES_DE_PAGO || ""}</td>
          <td>${factura.RETENCION_EN_LA_FUENTE || ""}</td>
          <td>${factura.RETENCION_PAGADA || ""}</td>
          <td>${factura.CEDULA}</td>
          <td>${factura.PRIMER_NOMBRE || ""}</td>
          <td>${factura.SEGUNDO_NOMBRE || ""}</td>
          <td>${factura.PRIMER_APELLIDO || ""}</td>
          <td>${factura.SEGUNDO_APELLIDO || ""}</td>
          <td><a href="#" class="btn btn-danger btn-sm">Eliminar</a></td>
        `;
        tbody.appendChild(fila);
      });
    });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
