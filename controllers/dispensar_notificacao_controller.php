<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../dao/NotificacaoDAO.php';

$response = ['success' => false, 'error' => ''];

if (!isset($_SESSION['usuario_id'])) {
    $response['error'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (empty($input['notificacao_id'])) {
    $response['error'] = 'ID da notificação não fornecido.';
    echo json_encode($response);
    exit;
}

$notificacao_id = filter_var($input['notificacao_id'], FILTER_VALIDATE_INT);
$usuario_id_logado = $_SESSION['usuario_id'];

if ($notificacao_id === false || $notificacao_id <= 0) {
    $response['error'] = 'ID da notificação inválido.';
    echo json_encode($response);
    exit;
}

try {
    $db = Database::getConnection();
    $notificacaoDAO = new NotificacaoDAO($db);

    if ($notificacaoDAO->deletarNotificacao($notificacao_id, $usuario_id_logado)) {
        $response['success'] = true;
    } else {
        $response['error'] = 'Não foi possível remover a notificação ou acesso negado.';
    }
} catch (Exception $e) {
    error_log("Erro em dispensar_notificacao_controller: " . $e->getMessage());
    $response['error'] = 'Erro interno do servidor ao tentar remover notificação.';
}

echo json_encode($response);
?> 