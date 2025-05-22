<?php
session_start();

require_once __DIR__ . '/../config/database.php'; // Para a conexão, se não for globalmente incluída
require_once __DIR__ . '/../dao/mensagem_dao.php';
require_once __DIR__ . '/../dao/cliente_dao.php'; // Alterado de usuario_dao.php
require_once __DIR__ . '/../model/cliente.php';   // Alterado de usuario.php

if (!isset($_SESSION['usuario_id'])) { // Mantendo 'usuario_id' na sessão por enquanto, ou seria 'cliente_id'?
    // Redireciona para a página de login se não estiver logado
    header('Location: ../view/login.php'); // Ajuste o caminho conforme necessário
    exit();
}

// Assumindo que $_SESSION['usuario_id'] ainda é a chave correta para o ID do cliente logado.
// Se for $_SESSION['cliente_id'], ajuste abaixo.
$usuario_logado_id = $_SESSION['usuario_id']; 
$mensagemDAO = new MensagemDAO(); 
$clienteDAO = new ClienteDAO(); // Alterado de UsuarioDAO

// Buscar as últimas conversas (uma mensagem por conversa, a mais recente)
$ultimas_conversas_raw = $mensagemDAO->buscarUltimasConversas($usuario_logado_id);

$conversas_formatadas = [];

foreach ($ultimas_conversas_raw as $mensagem) {
    $outro_usuario_id = ($mensagem->getRemetenteId() == $usuario_logado_id)
                        ? $mensagem->getDestinatarioId()
                        : $mensagem->getRemetenteId();

    // Buscar detalhes do outro cliente
    $outro_cliente = $clienteDAO->buscarPorId($outro_usuario_id); // Alterado de $usuarioDAO
    
    $nome_outro_usuario = "Cliente Desconhecido"; // Valor padrão
    if ($outro_cliente && method_exists($outro_cliente, 'getNome')) { // Verifica se o método existe
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

// A variável $conversas_formatadas estará disponível para a view
// O script que incluir este controller (ex: minhas_mensagens.php) terá acesso a ela.

?> 