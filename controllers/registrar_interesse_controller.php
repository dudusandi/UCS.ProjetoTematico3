<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/Notificacao.php';
require_once __DIR__ . '/../dao/cliente_dao.php'; // Para buscar nome do interessado

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$usuario_id_origem = (int)$_SESSION['usuario_id'];
$nome_usuario_logado = $_SESSION['usuario_nome'] ?? 'Um usuário'; // Pega o nome da sessão se disponível

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
    exit;
}

$produto_id = $_POST['produto_id'] ?? null;

if (!$produto_id || !is_numeric($produto_id)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'ID do produto inválido ou não fornecido.']);
    exit;
}
$produto_id = (int)$produto_id;

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO();
    $notificacaoDao = new NotificacaoDAO($pdo);
    // $clienteDao = new ClienteDAO($pdo); // Já instanciado se precisar do nome do interessado de forma mais robusta

    $produto = $produtoDao->buscarPorId($produto_id);

    if (!$produto) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'error' => 'Produto não encontrado.']);
        exit;
    }

    // Acessar como array, pois buscarPorId agora retorna array
    $usuario_id_destino = $produto['usuario_id']; 

    if ($usuario_id_origem === $usuario_id_destino) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Você não pode demonstrar interesse no seu próprio produto.']);
        exit;
    }

    // Criar a notificação
    $tipo_notificacao = 'interesse_compra';
    // Usar o nome do usuário logado na mensagem
    // Acessar como array
    $mensagem = htmlspecialchars($nome_usuario_logado) . " demonstrou interesse no seu produto: " . htmlspecialchars($produto['nome']);
    $link = "../view/chat.php?usuario_id=" . $usuario_id_origem; // Link para o chat com o interessado

    $notificacao = new Notificacao(
        $usuario_id_destino,
        $tipo_notificacao,
        $mensagem,
        $usuario_id_origem,
        $produto_id,
        $link
    );

    if ($notificacaoDao->criar($notificacao)) {
        echo json_encode(['success' => true, 'message' => 'Interesse registrado com sucesso! O vendedor foi notificado.']);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar a notificação no banco de dados.']);
    }

} catch (Exception $e) {
    error_log("Erro ao registrar interesse: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?> 