<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Tabla</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">➕ Crear Nueva Tabla</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="crear_tabla.php">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la tabla:</label>
                            <input type="text" name="nombre_tabla" class="form-control" placeholder="Ej: productos" required>
                        </div>

                        <div id="campos">
                            <div class="row mb-2 align-items-center">
                                <div class="col-md-6">
                                    <input type="text" name="campos[]" class="form-control" placeholder="Nombre del campo" required>
                                </div>
                                <div class="col-md-6">
                                    <select name="tipos[]" class="form-select">
                                        <option value="VARCHAR(255)">Texto</option>
                                        <option value="INT">Número</option>
                                        <option value="DATE">Fecha</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary" onclick="agregarCampo()">➕ Agregar Campo</button>
                            <button type="submit" class="btn btn-primary">✅ Crear Tabla</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="ver_tablas.php" class="btn btn-link">🔙 Ver Tablas Existentes</a>
            </div>
        </div>
    </div>
</div>

<script>
function agregarCampo() {
    const campos = document.getElementById('campos');
    const div = document.createElement('div');
    div.className = 'row mb-2 align-items-center';
    div.innerHTML = `
        <div class="col-md-6">
            <input type="text" name="campos[]" class="form-control" placeholder="Nombre del campo" required>
        </div>
        <div class="col-md-6">
            <select name="tipos[]" class="form-select">
                <option value="VARCHAR(255)">Texto</option>
                <option value="INT">Número</option>
                <option value="DATE">Fecha</option>
            </select>
        </div>
    `;
    campos.appendChild(div);
}
</script>

</body>
</html>

