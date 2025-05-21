<?php
session_start();
$carrinhos = isset($_SESSION['carrinhos']) ? $_SESSION['carrinhos'] : [];

unset($_SESSION['usuario_id']);
unset($_SESSION['usuario_email']);
unset($_SESSION['usuario_nome']);
unset($_SESSION['is_admin']);

$_SESSION['carrinhos'] = $carrinhos;

header("Location: ../view/dashboard.php");
exit;