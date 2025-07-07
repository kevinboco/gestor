
<?php
include 'conexion.php';

// Obtener lista de tablas
$tablas = [];
$tablas_result = $conexion->query("SHOW TABLES");
while ($row = $tablas_result->fetch_array()) {
    $tablas[] = $row[0];
}

// Tabla actual
$tabla = $_GET['tabla'] ?? '';
if (!$tabla) die("Tabla no especificada.");

$columnas_result = $conexion->query("DESCRIBE `$tabla`");
$columnas = [];
while ($col = $columnas_result->fetch_assoc()) {
    $columnas[] = $col['Field'];
}

$datos = $conexion->query("SELECT * FROM `$tabla`");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Inputmask -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/inputmask.min.js"></script>
    <!-- Flatpickr (calendario moderno) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

  <meta charset="UTF-8">
  <title>Tabla Editable</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    td[contenteditable], th[contenteditable] {
      cursor: text;
    }
    td[contenteditable]:focus {
      outline: 2px solid #007bff;
      background-color: #e9f5ff;
    }
        /* Agrega esto en tu <style> o CSS */
    .table-responsive {
      width: 100%;
    }
    .table {
      width: 100%;
      min-width: max-content; /* Hace que las columnas no se colapsen */
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

<div class="container">
  <h2>Tabla: <?= htmlspecialchars($tabla) ?></h2>
  <div class="table-responsive">
    <table class="table table-bordered table-hover text-center" id="miTabla">
      <thead class="table-light">
        <tr>
          <?php foreach ($columnas as $col): ?>
            <?php if ($col === 'id') continue; ?>
            <th>
              <div class="d-flex flex-column align-items-center">
                <div contenteditable="true" onblur="renombrarColumna(this, '<?= $col ?>')"><?= htmlspecialchars($col) ?></div>
                <button class="btn btn-sm btn-danger mt-1" onclick="eliminarColumna('<?= $col ?>')">🗑️</button>
              </div>
            </th>
          <?php endforeach; ?>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $datos->fetch_assoc()): ?>
          <tr>
            <?php foreach ($columnas as $col): ?>
              <?php if ($col === 'id') continue; ?>
              <td data-columna="<?= $col ?>">
                <?php if ($tabla === 'facturas' && strtoupper($col) === 'CEDULA'): ?>
                   <span><?= htmlspecialchars($row[$col]) ?></span>
                   <button class="btn btn-sm btn-primary ms-2" onclick="abrirModalSeleccion(this.parentElement)">➕ Añadir persona</button>
                
                <?php elseif (strtoupper($col) === 'FECHA'): ?>
                    <input type="text" class="form-control form-control-sm fecha-input"
                    value="<?= htmlspecialchars($row[$col]) ?>"
                    onchange="actualizarCeldaInput(this, '<?= $col ?>', <?= $row['id'] ?>)">
                <?php elseif (strtoupper($col) === 'COMPROBANTE'): ?>
                    <?php if (!empty($row[$col])): ?>
                        <a class="btn btn-outline-primary btn-sm mb-1" href="archivos/<?= htmlspecialchars($row[$col]) ?>" target="_blank">📄 Ver</a><br>
                        <label class="btn btn-sm btn-warning mt-1">
                        🔄 Cambiar
                        <input type="file" hidden onchange="subirComprobante(this, <?= $row['id'] ?>)">
                        </label>
                    <?php else: ?>
                        <label class="btn btn-sm btn-success">
                        📤 Subir comprobante
                        <input type="file" hidden onchange="subirComprobante(this, <?= $row['id'] ?>)">
                        </label>
                    <?php endif; ?>

                <?php else: ?>
                    <div contenteditable="true"
                    onblur="actualizarCelda(this, '<?= $col ?>', <?= $row['id'] ?>)">
                    <?= htmlspecialchars($row[$col]) ?>
                    </div>
                <?php endif; ?>
              </td>


            <?php endforeach; ?>
            <td><a href="eliminar_fila.php?tabla=<?= $tabla ?>&id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Eliminar fila</a></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
  <button onclick="agregarFila()" class="btn btn-success mt-3">➕ Añadir Fila</button>
</div>

<!-- Modal para seleccionar persona -->
<div class="modal fade" id="modalPersonas" tabindex="-1" aria-labelledby="modalPersonasLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalPersonasLabel">Seleccionar Persona</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="tablaPersonas">Cargando personas...</div>
      </div>
    </div>
  </div>
</div>

<script>
const tablaActual = "<?= $tabla ?>";
let celdaSeleccionada = null;

function actualizarCelda(td, columna, id) {
  fetch('actualizar_celda.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
      tabla: tablaActual,
      columna: columna,
      valor: td.innerText.trim(),
      id: id
    })
  });
}

function renombrarColumna(th, anterior) {
  const nuevo = th.innerText.trim();
  if (nuevo !== anterior && nuevo !== "") {
    fetch('renombrar_columna.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({
        tabla: tablaActual,
        anterior: anterior,
        nuevo: nuevo
      })
    }).then(res => {
      if (res.ok) location.reload();
      else alert("Error al renombrar columna.");
    });
  }
}

function eliminarColumna(nombre) {
  if (!confirm(`¿Eliminar la columna "${nombre}"?`)) return;
  fetch('eliminar_columna.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ tabla: tablaActual, columna: nombre })
  }).then(res => {
    if (res.ok) location.reload();
    else alert("Error al eliminar columna.");
  });
}
function agregarFila() {
  const tbody = document.querySelector("#miTabla tbody");
  const nuevaFila = document.createElement("tr");
  const columnas = <?= json_encode($columnas) ?>;

  // Marcar la fila como nueva
  nuevaFila.setAttribute('data-nueva', '1');

  columnas.forEach(col => {
    if (col === "id") return;

    const celda = document.createElement("td");
    celda.setAttribute("data-columna", col);

    if (tablaActual === "facturas" && col.toUpperCase() === "CEDULA") {
      celda.innerHTML = '<button class="btn btn-sm btn-primary" onclick="abrirModalSeleccion(this.parentElement)">➕ Añadir persona</button>';
    } else if (col.toUpperCase() === "FECHA") {
      const input = document.createElement("input");
      input.type = "text";
      input.className = "form-control form-control-sm fecha-input";
      input.onchange = () => guardarFilaCompleta(celda.closest("tr"));
      celda.appendChild(input);
      setTimeout(() => flatpickr(input, { dateFormat: "d/m/Y", allowInput: true }), 0);
    } else {
      celda.contentEditable = true;
      // Solo para filas nuevas, usar guardarFilaCompleta
      celda.onblur = function () {
        if (nuevaFila.getAttribute('data-nueva') === '1') {
          guardarFilaCompleta(this.closest("tr"));
        }
      };
    }

    nuevaFila.appendChild(celda);
  });

  const acciones = document.createElement("td");
  acciones.innerHTML = '<span class="text-muted">Nueva fila</span>';
  nuevaFila.appendChild(acciones);

  tbody.appendChild(nuevaFila);
}

function guardarFilaCompleta(fila) {
  // Solo guardar si la fila es nueva
  if (fila.getAttribute('data-nueva') !== '1') return;

  const columnas = <?= json_encode($columnas) ?>;
  const datos = {};
  let vacia = true;

  columnas.forEach(col => {
    if (col === 'id') return;
    const celda = fila.querySelector(`[data-columna="${col}"]`);
    const valor = celda ? celda.innerText.trim() : '';
    datos[col] = valor;
    if (valor !== '') vacia = false;
  });

  if (vacia) return;
  datos.tabla = tablaActual;

  fetch('insertar_fila.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams(datos)
  }).then(res => {
    if (res.ok) {
      // Una vez guardada, recargar para mostrar la fila como existente
      location.reload();
    } else {
      alert("Error al guardar la fila.");
    }
  });
}

function abrirModalSeleccion(celda) {
  celdaSeleccionada = celda;
  const modal = new bootstrap.Modal(document.getElementById('modalPersonas'));
  fetch('obtener_personas.php')
    .then(res => res.text())
    .then(html => {
      document.getElementById('tablaPersonas').innerHTML = html;
      modal.show();
    });
}

// ...existing code...
function seleccionarPersona(persona) {
  if (!celdaSeleccionada) return;

  const fila = celdaSeleccionada.closest("tr");
  const campos = ["CEDULA", "PRIMER_NOMBRE", "SEGUNDO_NOMBRE", "PRIMER_APELLIDO", "SEGUNDO_APELLIDO", "CARGO"];
  const id = fila.querySelector('[data-columna]')?.closest('tr').querySelector('a.btn-danger')?.href.match(/id=(\d+)/)?.[1];

  campos.forEach(campo => {
    const celda = fila.querySelector(`[data-columna="${campo}"]`);
    if (celda) {
      celda.innerText = persona[campo] || "";
      // Si la fila ya existe (tiene id), actualizar en BD
      if (id) {
        actualizarCelda(celda, campo, id);
      }
    }
  });

  // Si es fila nueva, guardar toda la fila
  if (!id && fila.getAttribute('data-nueva') === '1') {
    guardarFilaCompleta(fila);
  }

  const modal = bootstrap.Modal.getInstance(document.getElementById('modalPersonas'));
  modal.hide();
}
// ...existing code...
document.addEventListener('DOMContentLoaded', () => {
  flatpickr(".fecha-input", {
    dateFormat: "d/m/Y",
    allowInput: true,
    locale: "es" // opcional
  });
});

function actualizarCeldaInput(input, columna, id) {
  fetch('actualizar_celda.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
      tabla: tablaActual,
      columna: columna,
      valor: input.value.trim(),
      id: id
    })
  });
}
function subirComprobante(input, id) {
  const archivo = input.files[0];
  if (!archivo) return;

  const formData = new FormData();
  formData.append('archivo', archivo);
  formData.append('tabla', tablaActual);
  formData.append('id', id);
  formData.append('columna', 'COMPROBANTE');

  fetch('subir_comprobante.php', {
    method: 'POST',
    body: formData
  }).then(res => {
    if (res.ok) location.reload();
    else alert("Error al subir el comprobante.");
  });
}


</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>  