<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}
$usuario_id_logado = (int)$_SESSION['usuario_id'];

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Method Not Allowed
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
        exit;
    }

    $id = $_POST['id'] ?? null;
    if (!$id || !is_numeric($id)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'ID do produto inválido ou não fornecido']);
        exit;
    }
    $id = (int)$id;

    $produto = $produtoDao->buscarPorId($id);

    if (!$produto) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'error' => 'Produto não encontrado']);
        exit;
    }

    // Verificar se o produto pertence ao usuário logado
    if ($produto['usuario_id'] !== $usuario_id_logado) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'error' => 'Você não tem permissão para excluir este produto.']);
        exit;
    }

    // A remoção do produto em si não precisa de transação aqui se for uma única operação DELETE.
    // O DAO pode ou não usar transações internamente se fizer múltiplas coisas.
    if ($produtoDao->removerProduto($id)) {
        echo json_encode(['success' => true, 'message' => 'Produto excluído com sucesso']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'error' => 'Erro ao remover o produto do banco de dados']);
    }

} catch (Exception $e) {
    error_log("Erro ao excluir produto: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor ao tentar excluir o produto: ' . $e->getMessage()]);
}
?>