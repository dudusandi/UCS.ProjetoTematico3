<?php
session_start();

require_once __DIR__ . '/../config/database.php'; // Para a conexão, se não for globalmente incluída
require_once __DIR__ . '/../dao/mensagem_dao.php';
require_once __DIR__ . '/../model/mensagem.php';
require_once __DIR__ . '/../dao/cliente_dao.php'; // Para verificar se o destinatário existe
require_once __DIR__ . '/../dao/NotificacaoDAO.php'; // Adicionado para notificações
require_once __DIR__ . '/../model/Notificacao.php';   // Adicionado para notificações

if (!isset($_SESSION['usuario_id'])) {
    // Idealmente, redirecionar para o login ou retornar um erro JSON se for uma API
    // Por agora, vamos apenas sair para evitar processamento não autorizado.
    // Em um cenário real, você pode querer enviar uma resposta de erro HTTP.
    http_response_code(403); // Forbidden
    echo "Acesso não autorizado.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conteudo = trim($_POST['conteudo'] ?? '');
    $destinatario_id = filter_input(INPUT_POST, 'destinatario_id', FILTER_VALIDATE_INT);
    $remetente_id_form = filter_input(INPUT_POST, 'remetente_id', FILTER_VALIDATE_INT);

    $usuario_logado_id = (int)$_SESSION['usuario_id'];

    // Validação básica
    if (empty($conteudo)) {
        $_SESSION['erro_mensagem'] = "O conteúdo da mensagem não pode estar vazio.";
        // Redireciona de volta para o chat com o destinatário original
        header('Location: ../view/chat.php?usuario_id=' . $destinatario_id);
        exit();
    }

    if (!$destinatario_id || !$remetente_id_form) {
        $_SESSION['erro_mensagem'] = "ID do destinatário ou remetente inválido.";
        // Não temos certeza para onde redirecionar se o destinatário_id for inválido, então vamos para a lista de mensagens
        header('Location: ../view/minhas_mensagens.php');
        exit();
    }

    // Segurança: Verificar se o remetente_id do formulário é o mesmo do usuário logado
    if ($remetente_id_form !== $usuario_logado_id) {
        $_SESSION['erro_mensagem'] = "Tentativa de envio de mensagem por usuário não autorizado.";
        header('Location: ../view/minhas_mensagens.php'); // Ou logout, ou log de segurança
        exit();
    }
    
    // Verificar se o destinatário existe
    $clienteDAO = new ClienteDAO();
    if (!$clienteDAO->buscarPorId($destinatario_id)) {
        $_SESSION['erro_mensagem'] = "Destinatário não encontrado.";
        header('Location: ../view/minhas_mensagens.php');
        exit();
    }

    $mensagem = new Mensagem();
    $mensagem->setRemetenteId($usuario_logado_id); // Remetente é o usuário logado
    $mensagem->setDestinatarioId($destinatario_id);
    $mensagem->setConteudo($conteudo);
    // data_envio e lida têm valores padrão no banco e no modelo, respectivamente

    $mensagemDAO = new MensagemDAO();
    if ($mensagemDAO->enviarMensagem($mensagem)) {
        // Mensagem enviada com sucesso
        $_SESSION['sucesso_mensagem'] = "Mensagem enviada!";

        // Criar notificação para o destinatário
        $nome_remetente = $_SESSION['usuario_nome'] ?? 'Alguém'; // Pega o nome da sessão se disponível
        $notificacaoDao = new NotificacaoDAO(Database::getConnection()); // Obter conexão PDO
        $tipo_notificacao = 'nova_mensagem_chat';
        $mensagem_notif = htmlspecialchars($nome_remetente) . " enviou uma nova mensagem para você.";
        $link_notif = "../view/chat.php?usuario_id=" . $usuario_logado_id; // Link para o chat com o remetente

        $novaNotificacao = new Notificacao(
            $destinatario_id,      // ID do usuário que receberá a notificação
            $tipo_notificacao,
            $mensagem_notif,
            $usuario_logado_id,    // ID do usuário que originou a ação (remetente da mensagem)
            null,                  // produto_id pode ser nulo para mensagens de chat diretas
            $link_notif
        );
        if (!$notificacaoDao->criar($novaNotificacao)) {
            error_log("Falha ao criar notificação para nova mensagem de chat. Destinatário: $destinatario_id, Remetente: $usuario_logado_id");
            // Não bloquear o fluxo principal por falha na notificação, mas logar.
        }

    } else {
        // Erro ao enviar
        $_SESSION['erro_mensagem'] = "Erro ao enviar a mensagem. Tente novamente.";
    }

    // Redireciona de volta para a página de chat com o destinatário
    // O destinatário da mensagem enviada é o 'outro_usuario_id' na URL do chat.php
    header('Location: ../view/chat.php?usuario_id=' . $destinatario_id);
    exit();

} else {
    // Se não for POST, redireciona para algum lugar ou mostra erro
    http_response_code(405); // Method Not Allowed
    echo "Método não permitido.";
    // Poderia redirecionar para minhas_mensagens.php ou para a página inicial
    // header('Location: ../view/minhas_mensagens.php');
    exit();
}
