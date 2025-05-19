<?php
ob_start();
session_start();
require_once '../config/database.php';
require_once '../dao/produto_dao.php';
require_once '../model/produto.php';

ob_clean();

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID do produto não fornecido');
    }

    $id = (int)$_GET['id'];
    $pdo = Database::getConnection();
    $produtoDAO = new ProdutoDAO($pdo);
    $produto = $produtoDAO->buscarPorId($id);

    if (!$produto) {
        throw new Exception('Produto não encontrado');
    }

    $response = [
        'success' => true,
        'produto' => [
            'id' => $produto->getId(),
            'nome' => $produto->getNome(),
            'descricao' => $produto->getDescricao(),
            'foto' => $produto->getFoto() ? base64_encode(stream_get_contents($produto->getFoto())) : null,
            'fornecedor_id' => $produto->getFornecedorId(),
            'quantidade' => $produto->getQuantidade(),
            'preco' => $produto->getPreco(),
            'fornecedor_nome' => $produto->getFornecedorNome()
        ]
    ];

    ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

ob_end_flush();
?>