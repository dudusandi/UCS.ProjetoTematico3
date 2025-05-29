<?php
session_start();

require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../dao/mensagem_dao.php';
require_once __DIR__ . '/../model/mensagem.php';
require_once __DIR__ . '/../dao/cliente_dao.php'; 
require_once __DIR__ . '/../dao/NotificacaoDAO.php'; 
require_once __DIR__ . '/../model/Notificacao.php';  

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403); 
    echo "Acesso não autorizado.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conteudo = trim($_POST['conteudo'] ?? '');
    $destinatario_id = filter_input(INPUT_POST, 'destinatario_id', FILTER_VALIDATE_INT);
    $remetente_id_form = filter_input(INPUT_POST, 'remetente_id', FILTER_VALIDATE_INT);

    $usuario_logado_id = (int)$_SESSION['usuario_id'];

    if (empty($conteudo)) {
        $_SESSION['erro_mensagem'] = "O conteúdo da mensagem não pode estar vazio.";
        header('Location: ../view/chat.php?usuario_id=' . $destinatario_id);
        exit();
    }

    if (!$destinatario_id || !$remetente_id_form) {
        $_SESSION['erro_mensagem'] = "ID do destinatário ou remetente inválido.";
        header('Location: ../view/minhas_mensagens.php');
        exit();
    }

    if ($remetente_id_form !== $usuario_logado_id) {
        $_SESSION['erro_mensagem'] = "Tentativa de envio de mensagem por usuário não autorizado.";
        header('Location: ../view/minhas_mensagens.php'); 
        exit();
    }
    
    $clienteDAO = new ClienteDAO();
    if (!$clienteDAO->buscarPorId($destinatario_id)) {
        $_SESSION['erro_mensagem'] = "Destinatário não encontrado.";
        header('Location: ../view/minhas_mensagens.php');
        exit();
    }

    $mensagem = new Mensagem();
    $mensagem->setRemetenteId($usuario_logado_id); 
    $mensagem->setDestinatarioId($destinatario_id);
    $mensagem->setConteudo($conteudo);

    $mensagemDAO = new MensagemDAO();
    if ($mensagemDAO->enviarMensagem($mensagem)) {
        $_SESSION['sucesso_mensagem'] = "Mensagem enviada!";

        $nome_remetente = $_SESSION['usuario_nome'] ?? 'Alguém'; 
        $notificacaoDao = new NotificacaoDAO(Database::getConnection()); 
        $tipo_notificacao = 'nova_mensagem_chat';
        $mensagem_notif = htmlspecialchars($nome_remetente) . " enviou uma nova mensagem para você.";
        $link_notif = "../view/chat.php?usuario_id=" . $usuario_logado_id; 

        $novaNotificacao = new Notificacao(
            $destinatario_id,     
            $tipo_notificacao,
            $mensagem_notif,
            $usuario_logado_id,   
            null,                
            $link_notif
        );
        if (!$notificacaoDao->criar($novaNotificacao)) {
            error_log("Falha ao criar notificação para nova mensagem de chat. Destinatário: $destinatario_id, Remetente: $usuario_logado_id");
        }

    } else {
        $_SESSION['erro_mensagem'] = "Erro ao enviar a mensagem. Tente novamente.";
    }

    header('Location: ../view/chat.php?usuario_id=' . $destinatario_id);
    exit();

} else {
    http_response_code(405); 
    echo "Método não permitido.";
    exit();
}
