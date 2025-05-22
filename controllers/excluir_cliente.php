<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/cliente_dao.php';

if (!isset($_GET['id'])) {
    header('Location: listar_clientes.php?mensagem=ID do cliente não fornecido&tipo_mensagem=erro');
    exit;
}

$id = (int)$_GET['id'];

try {
    $clienteDAO = new ClienteDAO();
    $clienteDAO->removerCliente($id);
    header('Location: ../view/listar_clientes.php?mensagem=Cliente excluído com sucesso&tipo_mensagem=sucesso');
} catch (Exception $e) {
    header('Location: ../view/listar_clientes.php?mensagem=Erro ao excluir cliente: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
}
exit; 