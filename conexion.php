<?php
$host = "mysql.hostinger.com";
$user =  "u648222299_keboco4";    
$pass = "Bucaramanga3011";
$db = "u648222299_proyecto"; 

$conexion = new mysqli($host, $user, $pass, $db);

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>