<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/mensagem_dao.php';

$response = ['success' => false, 'error' => ''];

if (!isset($_SESSION['usuario_id'])) {
    $response['error'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (empty($input['usuario_id'])) {
    $response['error'] = 'ID do usuário não fornecido.';
    echo json_encode($response);
    exit;
}

$usuario_id = filter_var($input['usuario_id'], FILTER_VALIDATE_INT);
$usuario_id_logado = $_SESSION['usuario_id'];

if ($usuario_id === false || $usuario_id <= 0) {
    $response['error'] = 'ID do usuário inválido.';
    echo json_encode($response);
    exit;
}

try {
    $db = Database::getConnection();
    $mensagemDAO = new MensagemDAO($db);

    if ($mensagemDAO->excluirConversa($usuario_id, $usuario_id_logado)) {
        $response['success'] = true;
    } else {
        $response['error'] = 'Não foi possível excluir a conversa ou acesso negado.';
    }
} catch (Exception $e) {
    error_log("Erro em excluir_conversa_controller: " . $e->getMessage());
    $response['error'] = 'Erro interno do servidor ao tentar excluir conversa.';
}

echo json_encode($response); 