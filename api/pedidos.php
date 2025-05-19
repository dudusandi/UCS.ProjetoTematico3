<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); // Forbidden
    echo json_encode([
        'success' => false, 
        'error' => 'Acesso não autorizado. Requer privilégios de administrador.'
    ]);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/pedido_dao.php';

$termoBusca = $_GET['busca'] ?? '';

$paginaAtual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$itensPorPagina = isset($_GET['limite']) ? max(1, (int)$_GET['limite']) : 15; // Mesmo padrão da página admin

$response = [];

try {
    $pdo = Database::getConnection();
    $pedidoDao = new PedidoDAO($pdo);

    $resultado = $pedidoDao->listarTodosPedidos($paginaAtual, $itensPorPagina, $termoBusca);

    if ($resultado && isset($resultado['pedidos'])) {
        $response = [
            'success' => true,
            'data' => [
                'pedidos' => $resultado['pedidos'],
                'totalRegistrosFiltrados' => (int)$resultado['total'],
                'paginaAtual' => (int)$resultado['pagina'],
                'itensPorPagina' => (int)$resultado['itensPorPagina'],
                'totalPaginas' => ceil($resultado['total'] / $resultado['itensPorPagina'])
            ]
        ];
    } else {
        $response = [
            'success' => true, 
            'data' => [
                'pedidos' => [], 
                'totalRegistrosFiltrados' => 0, 
                'paginaAtual' => $paginaAtual, 
                'itensPorPagina' => $itensPorPagina, 
                'totalPaginas' => 0
            ],
            'message' => 'Nenhum pedido encontrado para os critérios fornecidos.'
        ];
    }

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Erro na API de Pedidos (api/pedidos.php): " . $e->getMessage());
    $response = [
        'success' => false, 
        'error' => 'Erro interno ao processar a requisição de pedidos.',
    ];
}

echo json_encode($response);
exit;
?> 