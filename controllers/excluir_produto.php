<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);

    $id = $_GET['id'] ?? null;

    if ($id) {
        $pdo->beginTransaction();
        
        if ($produtoDao->removerProduto((int)$id)) {
            $pdo->commit();
            header('Location: ../view/dashboard.php?mensagem=Produto+excluído+com+sucesso&tipo_mensagem=success');
            exit;
        } else {
            $pdo->rollBack();
            throw new Exception('Erro ao remover o produto.'); 
        }
    } else {
        throw new Exception('ID não fornecido');
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../view/dashboard.php?mensagem=Erro+ao+excluir:+' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    exit;
}
?>