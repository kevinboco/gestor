<?php
include 'conexion.php';

$tablas = $conexion->query("SHOW TABLES");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tablas Existentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0">📋 Tablas Existentes</h4>
                </div>
                <div class="card-body">
                    <?php if ($tablas->num_rows > 0): ?>
                        <ul class="list-group">
                            <?php while ($fila = $tablas->fetch_array()): 
                                $tabla = htmlspecialchars($fila[0]);
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= $tabla ?>
                                    <a href="ver_tabla.php?tabla=<?= urlencode($tabla) ?>" class="btn btn-sm btn-primary">Ver</a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No hay tablas creadas todavía.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-success">➕ Crear Nueva Tabla</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
