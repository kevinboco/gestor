<?php
include 'conexion.php';

$tabla = $_POST['tabla'];
$columna = $_POST['columna'];
$id = (int)$_POST['id'];
$valor = $conexion->real_escape_string($_POST['valor']);

$sql = "UPDATE `$tabla` SET `$columna` = '$valor' WHERE id = $id";
$conexion->query($sql);
?>