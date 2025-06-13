<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../dao/mensagem_dao.php';
require_once __DIR__ . '/../model/mensagem.php';
require_once __DIR__ . '/../dao/cliente_dao.php';
require_once __DIR__ . '/../dao/notificacaodao.php';
require_once __DIR__ . '/../model/notificacao.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'error' => 'Não foi possível processar a sua solicitação.'];

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    http_response_code(401); 
    $response['error'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit;
}

$id_remetente = (int)$_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); 
    $response['error'] = 'Método não permitido.';
    echo json_encode($response);
    exit;
}

$id_produto_oferecido = filter_input(INPUT_POST, 'id_produto_oferecido', FILTER_VALIDATE_INT);
$id_produto_desejado = filter_input(INPUT_POST, 'id_produto_desejado', FILTER_VALIDATE_INT);
$id_destinatario = filter_input(INPUT_POST, 'id_destinatario', FILTER_VALIDATE_INT);

if (!$id_produto_oferecido || !$id_produto_desejado || !$id_destinatario) {
    http_response_code(400); 
    $response['error'] = 'Dados inválidos para a proposta de troca.';
    echo json_encode($response);
    exit;
}

if ($id_remetente === $id_destinatario) {
    http_response_code(400);
    $response['error'] = 'Você não pode propor uma troca para si mesmo.';
    echo json_encode($response);
    exit;
}

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO();
    $mensagemDao = new MensagemDAO();
    $clienteDao = new ClienteDAO();
    $notificacaodao = new notificacaodao($pdo); 

    $produtoOferecido = $produtoDao->buscarPorId($id_produto_oferecido);
    $produtoDesejado = $produtoDao->buscarPorId($id_produto_desejado);

    if (!$produtoOferecido || !$produtoDesejado) {
        http_response_code(404); 
        $response['error'] = 'Um ou ambos os produtos não foram encontrados.';
        echo json_encode($response);
        exit;
    }

    
    if ((int)$produtoOferecido['usuario_id'] !== $id_remetente) {
        http_response_code(403); 
        $response['error'] = 'Você não tem permissão para oferecer este produto.';
        echo json_encode($response);
        exit;
    }

    
    if ((int)$produtoDesejado['usuario_id'] !== $id_destinatario) {
        http_response_code(400);
        $response['error'] = 'O destinatário informado não é o proprietário do produto desejado.';
        echo json_encode($response);
        exit;
    }

    $remetenteInfo = $clienteDao->buscarPorId($id_remetente);
    $destinatarioInfo = $clienteDao->buscarPorId($id_destinatario);

    $nomeRemetente = $remetenteInfo ? $remetenteInfo->getNome() : "Usuário #$id_remetente";
    $nomeDestinatario = $destinatarioInfo ? $destinatarioInfo->getNome() : "Usuário #$id_destinatario";

    $conteudoMensagem = sprintf(
        "Olá %s! %s mostrou interesse no seu produto '%s' e gostaria de oferecer o produto '%s' em troca. Você pode visualizar o produto ofertado na lista de produtos de %s ou entrar em contato diretamente pelo chat para mais detalhes.",
        htmlspecialchars($nomeDestinatario),
        htmlspecialchars($nomeRemetente),
        htmlspecialchars($produtoDesejado['nome']),
        htmlspecialchars($produtoOferecido['nome']),
        htmlspecialchars($nomeRemetente)
    );

    $mensagem = new Mensagem(null, $id_remetente, $id_destinatario, $conteudoMensagem);
    
    if ($mensagemDao->enviarMensagem($mensagem)) {
        
        $tipo_notificacao = 'proposta_troca';
        
        $mensagem_notif = sprintf(
            "%s propôs uma troca! Ofereceu '%s' pelo seu produto '%s'.",
            htmlspecialchars($nomeRemetente),
            htmlspecialchars($produtoOferecido['nome']),
            htmlspecialchars($produtoDesejado['nome'])
        );
        $link_notif = "../view/chat.php?usuario_id=" . $id_remetente; 

        $novanotificacao = new notificacao(
            $id_destinatario,      
            $tipo_notificacao,
            $mensagem_notif,
            $id_remetente,         
            $id_produto_desejado,  
            $link_notif
        );

        if (!$notificacaodao->criar($novanotificacao)) {
            error_log("Falha ao criar notificação para proposta de troca. Destinatário: $id_destinatario, Remetente: $id_remetente");
            
        }

        $response['success'] = true;
        $response['message'] = 'Proposta de troca enviada com sucesso!';
    } else {
        http_response_code(500);
        $response['error'] = 'Erro ao enviar a mensagem de proposta.';
    }

} catch (Exception $e) {
    error_log("Erro em enviar_proposta_troca_chat_controller.php: " . $e->getMessage());
    http_response_code(500);
    $response['error'] = 'Erro interno do servidor: ' . $e->getMessage();
}

echo json_encode($response);
?> 