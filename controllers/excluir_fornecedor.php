<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/fornecedor_dao.php';


    // Excluir Fornecedor

try {
    $fornecedorDAO = new FornecedorDAO(Database::getConnection());
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception('ID não fornecido');
    }

    $fornecedor = $fornecedorDAO->buscarPorId($id);
    if (!$fornecedor) {
        throw new Exception('Fornecedor não encontrado');
    }
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM produtos WHERE fornecedor_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        throw new Exception('Não é possível excluir o fornecedor porque ele está associado a produtos');
    }

    $fornecedorDAO->excluir($id);
    header('Location: ../view/listar_fornecedor.php?mensagem=Fornecedor excluído com sucesso&tipo_mensagem=success');
    exit;
    } catch (Exception $e) {
    header('Location: ../view/listar_fornecedor.php?mensagem=Erro ao excluir: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    exit;
}
?>