<?php
include 'conexion.php';

$resultado = $conexion->query("SELECT * FROM `base de datos` ORDER BY PRIMER_NOMBRE ASC");
?>

<input type="text" class="form-control mb-3" id="filtroPersonas" placeholder="Buscar por cédula, nombre, apellido o cargo...">

<?php if ($resultado->num_rows > 0): ?>
    <table class="table table-bordered table-hover text-center" id="tablaFiltroPersonas">
        <thead class="table-light">
            <tr>
                <th>Cédula</th>
                <th>Primer Nombre</th>
                <th>Primer Apellido</th>
                <th>CARGO</th>
                <th>Seleccionar</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $resultado->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['CEDULA']) ?></td>
                    <td><?= htmlspecialchars($row['PRIMER_NOMBRE']) ?></td>
                    <td><?= htmlspecialchars($row['PRIMER_APELLIDO']) ?></td>
                    <td><?= htmlspecialchars($row['CARGO']) ?></td>
                    <td>
                        <button class="btn btn-success btn-sm" onclick='seleccionarPersona(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                            ✅ Seleccionar
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Script para filtrar filas -->
    <script>
    document.getElementById('filtroPersonas').addEventListener('input', function () {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('#tablaFiltroPersonas tbody tr');

        filas.forEach(fila => {
            const textoFila = fila.innerText.toLowerCase();
            fila.style.display = textoFila.includes(filtro) ? '' : 'none';
        });
    });
    </script>

<?php else: ?>
    <p>No hay personas registradas.</p>
<?php endif; ?>

