<?php
ob_start(); 
session_start();

require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../dao/mensagem_dao.php'; 
require_once __DIR__ . '/../model/mensagem.php';  

ob_clean(); 
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não logado.']);
    exit();
}

try {
    $id_usuario_logado = (int)$_SESSION['usuario_id'];
    $outro_usuario_id = filter_input(INPUT_GET, 'outro_usuario_id', FILTER_VALIDATE_INT);
    $ultimo_id_conhecido = filter_input(INPUT_GET, 'ultimo_id_conhecido', FILTER_VALIDATE_INT);

    if (!$outro_usuario_id || $ultimo_id_conhecido === false || $ultimo_id_conhecido < 0) {
        echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
        exit();
    }
    $mensagemDAO = new MensagemDAO();
    $novas_mensagens_obj = $mensagemDAO->buscarNovasMensagens($id_usuario_logado, $outro_usuario_id, $ultimo_id_conhecido);

    $mensagens_para_enviar = [];
    if (!empty($novas_mensagens_obj)) {
        foreach ($novas_mensagens_obj as $msg) {
            if ($msg->getDestinatarioId() == $id_usuario_logado && !$msg->isLida()) {
                $mensagemDAO->marcarMensagensComoLidas($id_usuario_logado, $msg->getRemetenteId()); 
            }

            $mensagens_para_enviar[] = [
                'id' => $msg->getId(),
                'remetente_id' => $msg->getRemetenteId(),
                'destinatario_id' => $msg->getDestinatarioId(),
                'conteudo' => $msg->getConteudo(),
                'data_envio_raw' => $msg->getDataEnvio(),
                'data_formatada' => date("d/m/Y H:i", strtotime($msg->getDataEnvio())),
                'lida' => $msg->isLida()
            ];
        }
    }

    echo json_encode(['success' => true, 'mensagens' => $mensagens_para_enviar]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: ' . $e->getMessage()]);
}
?>