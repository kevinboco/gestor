<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "proyecto_dinamico";

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>