<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

header('Content-Type: application/json');

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception('ID não fornecido');
    }

    $produto = $produtoDao->buscarPorId((int)$id);
    if (!$produto) {
        throw new Exception('Produto não encontrado');
    }

    $response = [
        'id' => $produto->getId(),
        'nome' => $produto->getNome(),
        'descricao' => $produto->getDescricao(),
        'fornecedor' => (int)$produto->getFornecedorId(),
        'fornecedor_nome' => $produto->fornecedor_nome ?? 'Sem fornecedor',
        'estoque' => $produto->getQuantidade() ?? 0,
        'preco' => $produto->getPreco() ?? 0,
        'foto' => $produto->getFoto(),
        'usuario_id' => $produto->getUsuarioId(),
        'estoque_baixo' => ($produto->getQuantidade() ?? 0) <= 5
    ];

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>