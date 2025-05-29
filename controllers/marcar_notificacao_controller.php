<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/NotificacaoDAO.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$acao = $input['acao'] ?? null;
$notificacao_id = isset($input['notificacao_id']) ? (int)$input['notificacao_id'] : null;

try {
    $pdo = Database::getConnection();
    $notificacaoDao = new NotificacaoDAO($pdo);
    $sucesso = false;
    $mensagem = '';

    if ($acao === 'marcar_lida' && $notificacao_id) {
        if ($notificacaoDao->marcarComoLida($notificacao_id, $usuario_id)) {
            $sucesso = true;
            $mensagem = 'Notificação marcada como lida.';
        } else {
            $mensagem = 'Erro ao marcar notificação como lida ou notificação não encontrada/não pertence ao usuário.';
        }
    } elseif ($acao === 'marcar_todas_lidas') {
        if ($notificacaoDao->marcarTodasComoLidas($usuario_id)) {
            $sucesso = true;
            $mensagem = 'Todas as notificações foram marcadas como lidas.';
        } else {
            $mensagem = 'Erro ao marcar todas as notificações como lidas.';
        }
    } else {
        http_response_code(400); // Bad Request
        $mensagem = 'Ação inválida ou ID da notificação não fornecido.';
    }

    if ($sucesso) {
        echo json_encode(['success' => true, 'message' => $mensagem]);
    } else {
        if (http_response_code() === 200) http_response_code(500);
        echo json_encode(['success' => false, 'error' => $mensagem]);
    }

} catch (Exception $e) {
    error_log("Erro ao marcar notificação(ões) como lida(s): " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor.']);
} 