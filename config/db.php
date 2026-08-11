<?php
$host = "localhost";
$dbname = "biblioteca";
$user = "root";
$pass = ""; // ajusta la contraseña según corresponda

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>