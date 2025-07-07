<?php
include 'conexion.php';

// Traer solo una fila por cada CEDULA (agrupado)
$result = $conexion->query("
    SELECT CEDULA, CONCAT_WS(' ', PRIMER_NOMBRE, PRIMER_APELLIDO) AS NOMBRE 
    FROM facturas 
    GROUP BY CEDULA
");

echo "<table class='table table-bordered'>";
echo "<thead><tr><th>Cédula</th><th>Nombre</th><th>Acción</th></tr></thead><tbody>";

while ($row = $result->fetch_assoc()) {
    $cedula = htmlspecialchars($row['CEDULA']);
    $nombre = htmlspecialchars($row['NOMBRE']);
    echo "<tr>";
    echo "<td>$cedula</td>";
    echo "<td>$nombre</td>";
    echo "<td><button class='btn btn-primary' onclick='seleccionarDesdeFacturas(\"$cedula\")'>Seleccionar</button></td>";
    echo "</tr>";
}

echo "</tbody></table>";
?>
