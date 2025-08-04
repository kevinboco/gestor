<?php
include 'conexion.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['rango'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    $ini = $data['fecha_ini'];
    $fin = $data['fecha_fin'];
    $stmt = $conexion->prepare("
        SELECT fecha_pago, SUM(monto_pagado) AS total_pagado
        FROM ingresos_entidades
        WHERE fecha_pago BETWEEN ? AND ?
        GROUP BY fecha_pago
        ORDER BY fecha_pago ASC
    ");
    $stmt->bind_param("ss", $ini, $fin);
    $stmt->execute();
    $res = $stmt->get_result();
    $por_dia = [];
    while ($f = $res->fetch_assoc()) {
        $por_dia[$f['fecha_pago']] = (float)$f['total_pagado'];
    }
    header('Content-Type: application/json');
    echo json_encode($por_dia);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    foreach ($data as $fila) {
        $mt = str_replace(',', '', $fila['monto_total']);
        $mp = str_replace(',', '', $fila['monto_pagado']);
        if (!empty($fila['id'])) {
            $stmt = $conexion->prepare("
                UPDATE ingresos_entidades
                SET nombre_entidad=?, monto_total=?, monto_pagado=?, fecha_pago=?, observaciones=?
                WHERE id=?
            ");
            $stmt->bind_param("sddssi", $fila['nombre_entidad'], $mt, $mp, $fila['fecha_pago'], $fila['observaciones'], $fila['id']);
        } else {
            $stmt = $conexion->prepare("
                INSERT INTO ingresos_entidades
                (nombre_entidad, monto_total, monto_pagado, fecha_pago, observaciones)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sddss", $fila['nombre_entidad'], $mt, $mp, $fila['fecha_pago'], $fila['observaciones']);
        }
        $stmt->execute();
    }
    exit('guardado');
}

$result = $conexion->query("SELECT * FROM ingresos_entidades");
$datos = []; $total = $pagado = 0;
$por_entidad = [];
while ($r = $result->fetch_assoc()) {
    $r['diferencia'] = number_format($r['monto_total'] - $r['monto_pagado'], 2);
    $datos[] = $r;
    $total += $r['monto_total'];
    $pagado += $r['monto_pagado'];
    $por_entidad[$r['nombre_entidad']] = ($por_entidad[$r['nombre_entidad']] ?? 0) + $r['monto_pagado'];
}
$faltante = $total - $pagado;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ingresos por Entidad</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light p-4">
<div class="container">
  <h2 class="mb-4">Ingresos por Entidad</h2>

  <div class="mb-3" id="resumen">
    <strong>Total Esperado:</strong> $<?= number_format($total,2) ?> |
    <strong>Pagado:</strong> $<?= number_format($pagado,2) ?> |
    <strong>Faltante:</strong> $<?= number_format($faltante,2) ?>
  </div>

  <!-- Filtros -->
  <div class="mb-3">
    <strong>Filtrar por Entidad:</strong>
    <select id="filtro_entidad" class="form-select mb-2" style="max-width: 300px;">
      <option value="">Todas</option>
      <?php foreach(array_keys($por_entidad) as $ent): ?>
        <option value="<?= htmlspecialchars($ent) ?>"><?= htmlspecialchars($ent) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
      <label>Fecha inicial:</label>
      <input type="date" id="filtro_fecha_ini" class="form-control">
    </div>
    <div class="col-md-3">
      <label>Fecha final:</label>
      <input type="date" id="filtro_fecha_fin" class="form-control">
    </div>
    <div class="col-md-3">
      <button onclick="filtrarTabla()" class="btn btn-secondary mt-2">🔍 Filtrar</button>
    </div>
  </div>

  <div class="d-flex gap-2 mb-3">
    <button onclick="agregarFila()" class="btn btn-success">➕ Agregar Fila</button>
    <button onclick="guardarCambios()" class="btn btn-primary">💾 Guardar Cambios</button>
    <button class="btn btn-warning" onclick="abrirEstadisticas()">📊 Ver Estadísticas</button>
  </div>

  <!-- Lista para autocompletar -->
  <datalist id="lista_entidades">
    <?php foreach(array_keys($por_entidad) as $ent): ?>
      <option value="<?= htmlspecialchars($ent) ?>"></option>
    <?php endforeach; ?>
  </datalist>

  <!-- Tabla -->
  <div class="table-responsive">
    <table class="table table-bordered align-middle text-center bg-white">
      <thead class="table-light">
        <tr>
          <th>Entidad</th><th>Monto Total</th><th>Pagado</th><th>Diferencia</th>
          <th>Fecha de Pago</th><th>Observaciones</th><th>Eliminar</th>
        </tr>
      </thead>
      <tbody id="tabla">
        <?php foreach ($datos as $fila): ?>
        <tr>
          <td><input list="lista_entidades" value="<?= $fila['nombre_entidad'] ?>" data-id="<?= $fila['id'] ?>" data-campo="nombre_entidad" class="form-control"></td>
          <td><input type="text" value="<?= number_format($fila['monto_total'],0,',',',') ?>" data-id="<?= $fila['id'] ?>" data-campo="monto_total" class="form-control monto"></td>
          <td><input type="text" value="<?= number_format($fila['monto_pagado'],0,',',',') ?>" data-id="<?= $fila['id'] ?>" data-campo="monto_pagado" class="form-control monto"></td>
          <td>$<?= $fila['diferencia'] ?></td>
          <td><input type="date" value="<?= $fila['fecha_pago'] ?>" data-id="<?= $fila['id'] ?>" data-campo="fecha_pago" class="form-control"></td>
          <td><textarea data-id="<?= $fila['id'] ?>" data-campo="observaciones" class="form-control"><?= $fila['observaciones'] ?></textarea></td>
          <td><button onclick="eliminarFila(this)" class="btn btn-sm btn-danger">❌</button></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Gráficos -->
  <div id="estadisticas" class="d-none mt-5">
    <h4>📊 Elige gráfico:</h4>
    <div class="btn-group mb-3" role="group">
      <button class="btn btn-outline-primary" onclick="mostrarGrafico('total')">Total Pagado vs Faltante</button>
      <button class="btn btn-outline-primary" onclick="mostrarGrafico('entidad')">Monto por Entidad</button>
      <button class="btn btn-outline-primary" onclick="mostrarGrafico('rango')">Pagos por Rango de Fecha</button>
    </div>

    <div id="contenedor-graficos">
      <canvas id="graficoArea" class="mb-4"></canvas>
      <div id="filtro-rango" class="d-none mb-4">
        <div class="row g-2 align-items-end">
          <div class="col-md-3">
            <label>Inicio</label>
            <input type="date" id="fecha_ini" class="form-control">
          </div>
          <div class="col-md-3">
            <label>Fin</label>
            <input type="date" id="fecha_fin" class="form-control">
          </div>
          <div class="col-md-3">
            <button class="btn btn-info" onclick="verRango()">Ver rango</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let chart;
function abrirEstadisticas(){
  document.getElementById('estadisticas').classList.toggle('d-none');
  mostrarGrafico('total');
}

function mostrarGrafico(tipo){
  document.getElementById('filtro-rango').classList.add('d-none');
  const ctx = document.getElementById('graficoArea').getContext('2d');
  if(chart) chart.destroy();

  let labels = [], data = [], cfg;

  if(tipo === 'total'){
    labels = ['Pagado', 'Faltante'];
    data = [<?= $pagado ?>, <?= $faltante ?>];
    cfg = { type: 'pie', data: { labels: labels, datasets: [{ data: data, backgroundColor: ['#198754','#dc3545'] }] } };

  } else if(tipo === 'entidad'){
    labels = <?= json_encode(array_keys($por_entidad)) ?>;
    data = <?= json_encode(array_values($por_entidad)) ?>;
    cfg = { type: 'bar', data: { labels: labels, datasets: [{ label: 'Monto pagado x Entidad', data: data, backgroundColor: '#ffc107' }] } };

  } else if(tipo === 'rango'){
    document.getElementById('filtro-rango').classList.remove('d-none');
    return;
  }

  chart = new Chart(ctx, cfg);
}

function verRango(){
  const ini = document.getElementById('fecha_ini').value;
  const fin = document.getElementById('fecha_fin').value;
  if(!ini || !fin){ alert('Selecciona ambas fechas'); return; }
  fetch('?rango=1', {
    method: 'POST',
    headers: { 'Content-Type':'application/json' },
    body: JSON.stringify({ fecha_ini: ini, fecha_fin: fin })
  })
  .then(res => res.json())
  .then(data => {
    const days = Object.keys(data);
    const values = Object.values(data);
    if(days.length === 0){
      alert('No hay pagos en ese rango');
      return;
    }
    const ctx = document.getElementById('graficoArea').getContext('2d');
    if(chart) chart.destroy();
    chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: days,
        datasets: [{
          label: 'Pagos por día',
          data: values,
          borderColor: '#17a2b8',
          fill: false,
          tension: 0.1
        }]
      },
      options: {
        scales: {
          x: { title: { display: true, text: 'Fecha' } },
          y: { title: { display: true, text: 'Monto pagado' } }
        }
      }
    });
  });
}

function formatoInput(inp){
  inp.addEventListener('input', ()=> {
    let v = inp.value.replace(/,/g,'').replace(/\D/g,'');
    inp.value = v ? new Intl.NumberFormat('es-CO').format(v) : '';
  });
}

function setupFormato(){
  document.querySelectorAll('.monto').forEach(inp => {
    inp.oninput = null;
    formatoInput(inp);
  });
}

function agregarFila(){
  const fila = document.createElement('tr');
  fila.innerHTML = `
    <td><input list="lista_entidades" data-campo="nombre_entidad" class="form-control"></td>
    <td><input type="text" data-campo="monto_total" class="form-control monto"></td>
    <td><input type="text" data-campo="monto_pagado" class="form-control monto"></td>
    <td>$0</td>
    <td><input type="date" data-campo="fecha_pago" class="form-control"></td>
    <td><textarea data-campo="observaciones" class="form-control"></textarea></td>
    <td><button onclick="eliminarFila(this)" class="btn btn-sm btn-danger">❌</button></td>
  `;
  document.getElementById('tabla').appendChild(fila);
  setupFormato();
}

function eliminarFila(btn){
  btn.closest('tr').remove();
}

function guardarCambios(){
  const filas = document.querySelectorAll('#tabla tr');
  const data = [];

  filas.forEach(tr => {
    const obj = {};
    const inputs = tr.querySelectorAll('input, select, textarea');
    inputs.forEach(el => {
      const campo = el.dataset.campo;
      const id = el.dataset.id;
      if (campo) obj[campo] = el.value;
      if (id) obj.id = id;
    });
    if(obj['monto_total']) obj['monto_total'] = obj['monto_total'].replace(/,/g,'');
    if(obj['monto_pagado']) obj['monto_pagado'] = obj['monto_pagado'].replace(/,/g,'');
    data.push(obj);
  });

  fetch('', {
    method: 'POST',
    headers: { 'Content-Type':'application/json' },
    body: JSON.stringify(data)
  })
  .then(res => res.text())
  .then(txt => {
    alert('Guardado exitoso');
    location.reload();
  });
}

function filtrarTabla(){
  const nombre = document.getElementById('filtro_entidad').value;
  const ini = document.getElementById('filtro_fecha_ini').value;
  const fin = document.getElementById('filtro_fecha_fin').value;

  const filas = document.querySelectorAll('#tabla tr');
  let total = 0, pagado = 0;

  filas.forEach(tr => {
    const inpEntidad = tr.querySelector('input[data-campo="nombre_entidad"]');
    const inpFecha = tr.querySelector('input[data-campo="fecha_pago"]');
    if (!inpEntidad || !inpFecha) return;

    const entidad = inpEntidad.value.trim();
    const fecha = inpFecha.value;

    const coincideEntidad = (nombre === '' || entidad === nombre);
    const coincideFecha = (!ini || !fin || (fecha >= ini && fecha <= fin));

    const mostrar = coincideEntidad && coincideFecha;
    tr.style.display = mostrar ? '' : 'none';

    if (mostrar) {
      const mt = parseFloat(tr.querySelector('input[data-campo="monto_total"]').value.replace(/,/g, '')) || 0;
      const mp = parseFloat(tr.querySelector('input[data-campo="monto_pagado"]').value.replace(/,/g, '')) || 0;
      total += mt;
      pagado += mp;
    }
  });

  const faltante = total - pagado;
  document.getElementById('resumen').innerHTML = `
    <strong>Total Esperado:</strong> $${total.toLocaleString('es-CO')} |
    <strong>Pagado:</strong> $${pagado.toLocaleString('es-CO')} |
    <strong>Faltante:</strong> $${faltante.toLocaleString('es-CO')}
  `;
}

window.onload = setupFormato;
</script>
</body>
</html>
