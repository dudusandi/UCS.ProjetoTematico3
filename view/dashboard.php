<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: view/login.php');
    exit;
}
?>

<h1>Bem-vindo, <?= $_SESSION['usuario_nome'] ?>!</h1>
<a href="/web2/controllers/logout_controller.php">Sair</a>
