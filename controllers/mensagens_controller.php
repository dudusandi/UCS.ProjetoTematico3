<?php
session_start();

require_once __DIR__ . '/../config/database.php'; 
require_once __DIR__ . '/../dao/mensagem_dao.php';
require_once __DIR__ . '/../dao/cliente_dao.php'; 
require_once __DIR__ . '/../model/cliente.php';   
require_once __DIR__ . '/../model/mensagem.php';

if (!isset($_SESSION['usuario_id'])) { 
    header('Location: ../view/login.php'); 
    exit();
}

$usuario_logado_id = $_SESSION['usuario_id']; 
$mensagemDAO = new MensagemDAO(); 
$clienteDAO = new ClienteDAO(); 


$destinatario_forcar_id = filter_input(INPUT_GET, 'abrir_conversa_id', FILTER_VALIDATE_INT);
if ($destinatario_forcar_id && $destinatario_forcar_id !== $usuario_logado_id) {
    
    $destinatario_existe = $clienteDAO->buscarPorId($destinatario_forcar_id);
    if ($destinatario_existe) {
        $conversaExistente = $mensagemDAO->buscarConversa($usuario_logado_id, $destinatario_forcar_id);
        
        if (empty($conversaExistente)) {
            $mensagemInicial = new Mensagem(null, $usuario_logado_id, $destinatario_forcar_id, "Início da conversa.");
            $mensagemDAO->enviarMensagem($mensagemInicial);
        }
    }
}


$ultimas_conversas_raw = $mensagemDAO->buscarUltimasConversas($usuario_logado_id);

$conversas_formatadas = [];

foreach ($ultimas_conversas_raw as $mensagem) {
    $outro_usuario_id = ($mensagem->getRemetenteId() == $usuario_logado_id)
                        ? $mensagem->getDestinatarioId()
                        : $mensagem->getRemetenteId();

    $outro_cliente = $clienteDAO->buscarPorId($outro_usuario_id);
    
    $nome_outro_usuario = "Cliente Desconhecido"; 
    if ($outro_cliente && method_exists($outro_cliente, 'getNome')) { 
        $nome_outro_usuario = $outro_cliente->getNome(); 
    } else {
        $nome_outro_usuario = "Cliente #{$outro_usuario_id}";
    }

    $nao_lida_nesta_conversa = !$mensagem->isLida() && $mensagem->getDestinatarioId() == $usuario_logado_id;

    $conversas_formatadas[] = [
        'outro_usuario_id' => $outro_usuario_id,
        'nome_outro_usuario' => $nome_outro_usuario,
        'ultima_mensagem' => $mensagem->getConteudo(),
        'data_ultima_mensagem' => $mensagem->getDataEnvio(),
        'nao_lida' => $nao_lida_nesta_conversa,
        'id_ultima_mensagem' => $mensagem->getId()
    ];
}

?> 