<?php

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function responderErro($mensagem) {
    echo json_encode([
        'success' => false,
        'mensagem' => $mensagem
    ]);
    exit;
}

function responderSucesso($pedido) {
    echo json_encode([
        'success' => true,
        'mensagem' => 'Pedido finalizado com sucesso!',
        'pedido' => $pedido
    ]);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    responderErro('Você precisa estar logado para finalizar o pedido');
}

try {
    require_once '../config/database.php';
    require_once '../dao/produto_dao.php';
    require_once '../dao/pedido_dao.php';
    define('CARRINHO_LOGIC_ONLY', true);
    require_once '../controllers/carrinho.php';
    $produtosCarrinho = obterProdutosCarrinho();
    
    if (empty($produtosCarrinho)) {
        responderErro('Seu carrinho está vazio');
    }

    $pdo = Database::getConnection();
    $pedidoDAO = new PedidoDAO($pdo);
    $clienteId = $_SESSION['usuario_id'];
    $resultado = $pedidoDAO->criarPedido($clienteId, $produtosCarrinho);

    limparCarrinho();
    
    responderSucesso([
        'id' => $resultado['id'],
        'numero' => $resultado['numero'],
        'valor_total' => number_format($resultado['valor_total'], 2, ',', '.')
    ]);
    
} catch (Exception $e) {
    responderErro('Erro ao finalizar o pedido: ' . $e->getMessage());
}
?> 