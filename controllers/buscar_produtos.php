<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

header('Content-Type: application/json');

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    
    $termo = $_GET['termo'] ?? '';
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $itensPorPagina = 12;
    $offset = ($pagina - 1) * $itensPorPagina;

    if (empty($termo)) {
        $produtos = $produtoDao->listarTodosProdutos($itensPorPagina, $offset);
        $total = $produtoDao->contarTodosProdutos();
    } else {
        $produtos = $produtoDao->buscarProdutosDinamicos($termo, $itensPorPagina, $offset);
        $total = $produtoDao->contarProdutosBuscados($termo);
    }

    $response = [
        'success' => true,
        'produtos' => array_map(function($produto) {
            return [
                'id' => $produto->getId(),
                'nome' => htmlspecialchars($produto->getNome(), ENT_QUOTES, 'UTF-8'),
                'descricao' => htmlspecialchars($produto->getDescricao() ?? '', ENT_QUOTES, 'UTF-8'),
                'foto' => $produto->getFoto(),
                'quantidade' => $produto->getQuantidade() ?? 0,
                'preco' => $produto->getPreco() ?? 0,
                'fornecedor_nome' => htmlspecialchars($produto->fornecedor_nome ?? 'Sem fornecedor', ENT_QUOTES, 'UTF-8'),
                'usuario_id' => $produto->getUsuarioId()
            ];
        }, $produtos),
        'total' => $total,
        'pagina' => $pagina,
        'total_paginas' => ceil($total / $itensPorPagina)
    ];

    echo json_encode($response);
} catch (Exception $e) {
    error_log("Erro em buscar_produtos.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar produtos: ' . $e->getMessage()
    ]);
} 