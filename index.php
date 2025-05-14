<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header(header: "Location: ./view/login.php");
    exit();
}
header(header: "Location: ./view/dashboard.php");



?>

