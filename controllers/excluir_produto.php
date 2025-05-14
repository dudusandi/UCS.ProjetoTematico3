<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../dao/estoque_dao.php'; 

    //Exluir Produto

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    $estoqueDao = new EstoqueDAO($pdo); 

    $id = $_GET['id'] ?? null;

    if ($id) {
        $pdo->beginTransaction();

        // Busca o produto para obter o estoque_id
        $produto = $produtoDao->buscarPorId((int)$id);
        if ($produto) {
            $estoqueId = $produto->getEstoqueId();

            $produtoDao->excluir((int)$id);

            if ($estoqueId) {
                $contagemReferencias = $pdo->query("SELECT COUNT(*) FROM produtos WHERE estoque_id = $estoqueId")->fetchColumn();
                if ($contagemReferencias == 0) {
                    $estoqueDao->excluir((int)$estoqueId); // Exclui o estoque apenas se não houver mais referências
                }
            }

            $pdo->commit();
            header('Location: ../view/dashboard.php?mensagem=Produto+excluído+com+sucesso&tipo_mensagem=success');
            exit;
        } else {
            throw new Exception('Produto não encontrado');
        }
    } else {
        throw new Exception('ID não fornecido');
    }
} catch (Exception $e) {
    $pdo->rollBack();
    error_log(date('[Y-m-d H:i:s] ') . "Erro ao excluir produto: " . $e->getMessage() . PHP_EOL);
    header('Location: ../view/dashboard.php?mensagem=Erro+ao+excluir:+' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    exit;
}
?>