<?php
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

session_start();
require_once '../config/database.php';
require_once '../dao/produto_dao.php';
require_once '../model/produto.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (isset($_GET['ids']) && !isset($_SESSION['usuario_id'])) {
        ob_clean(); 
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Acesso não autorizado. Por favor, faça login.'
        ]);
        ob_end_flush();
        exit;
    }

    $pdo = Database::getConnection();
    $produtoDAO = new ProdutoDAO();
    
    if (isset($_GET['ids'])) {
        $ids = array_map('intval', explode(',', $_GET['ids']));
        $produtos = $produtoDAO->buscarProdutosPorIds($ids);
    } else {
        $termo = $_GET['termo'] ?? '';
        $produtos = $produtoDAO->buscarProdutos($termo);
    }

    $produtosArray = [];
    if (isset($_GET['ids'])) {
        foreach ($produtos as $produto) { 
            try {
                $foto = $produto->getFoto();
                if ($foto && is_resource($foto)) {
                    $foto = stream_get_contents($foto);
                }

                $produtosArray[] = [
                    'id' => $produto->getId(),
                    'nome' => $produto->getNome(),
                    'descricao' => $produto->getDescricao(),
                    'foto' => $foto ? base64_encode($foto) : null,
                    'fornecedor_id' => $produto->getFornecedorId(),
                    'quantidade' => $produto->getQuantidade(),
                    'preco' => $produto->getPreco(),
                    'fornecedor_nome' => $produto->fornecedor_nome 
                ];
            } catch (Exception $e) {
                continue;
            }
        }
    } else {
        foreach ($produtos as $produtoData) { 
            try {
                $produtosArray[] = [
                    'id' => $produtoData['id'],
                    'nome' => $produtoData['nome'],
                    'descricao' => $produtoData['descricao'],
                    'foto' => $produtoData['foto'] ? base64_encode($produtoData['foto']) : null,
                    'fornecedor_id' => $produtoData['fornecedor_id'],
                    'quantidade' => $produtoData['quantidade'],
                    'preco' => $produtoData['preco'],
                    'fornecedor_nome' => $produtoData['fornecedor_nome']
                ];
            } catch (Exception $e) {
                continue;
            }
        }
    }

    $response = [
        'success' => true,
        'produtos' => $produtosArray
    ];

    ob_clean();
    echo json_encode($response);

} catch (Exception $e) {
        ob_clean();
    
    if ($e->getMessage() === 'Acesso não autorizado. Por favor, faça login.') {
        http_response_code(401);
    } else {
        http_response_code(500); 
    }
    
    $response = [
        'success' => false,
        'error' => 'Erro ao buscar produtos: ' . $e->getMessage()
    ];
    
    echo json_encode($response);
}

ob_end_flush(); 