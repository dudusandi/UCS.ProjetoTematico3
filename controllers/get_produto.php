<?php
ob_start();
session_start();
require_once '../config/database.php';
require_once '../dao/produto_dao.php';
require_once '../model/produto.php';
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';

ob_clean();

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID do produto não fornecido');
    }

    $id = (int)$_GET['id'];
    $produtoDAO = new ProdutoDAO();
    
    $produtoArray = $produtoDAO->buscarPorId($id);

    if (!$produtoArray) {
        throw new Exception('Produto não encontrado');
    }

    $response = [
        'success' => true,
        'produto' => [
            'id' => $produtoArray['id'],
            'nome' => $produtoArray['nome'],
            'descricao' => $produtoArray['descricao'],
            'foto' => $produtoArray['foto'] ? base64_encode(stream_get_contents($produtoArray['foto'])) : null,
            'preco' => $produtoArray['preco'],
            'usuario_id' => $produtoArray['usuario_id'],
            'proprietario_nome' => $produtoArray['proprietario_nome']
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