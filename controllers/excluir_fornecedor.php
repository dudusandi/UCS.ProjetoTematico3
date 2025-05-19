<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/fornecedor_dao.php';

try {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception('ID não fornecido');
    }

    $fornecedorDAO = new FornecedorDAO(Database::getConnection());
    $fornecedorDAO->removerFornecedor((int)$id);
    
    header('Location: ../view/listar_fornecedor.php?mensagem=Fornecedor excluído com sucesso&tipo_mensagem=success');
    exit;

} catch (Exception $e) {
    header('Location: ../view/listar_fornecedor.php?mensagem=Erro ao excluir: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    exit;
}
?>