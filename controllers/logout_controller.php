<?php
session_start();

$_SESSION = array();

// Função para explodir tudo (Cuidado)
session_destroy();

header("Location: ../view/login.php");
exit;
?>