<?php
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tabla'])) {
    $tabla = $_POST['tabla'];

    $sql = "DROP TABLE `$tabla`";
    if ($conexion->query($sql) === TRUE) {
        header("Location: ver_tablas.php?eliminado=1");
        exit;
    } else {
        echo "Error al eliminar tabla: " . $conexion->error;
    }
} else {
    echo "Solicitud inválida.";
}
?>
