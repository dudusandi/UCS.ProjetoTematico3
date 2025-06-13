<?php
error_reporting(E_ALL);



register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode([
            'erro_fatal_shutdown' => 'Ocorreu um erro crítico no servidor.',
            'tipo_erro' => $error['type'],
            'mensagem_erro' => $error['message'],
            'arquivo_erro' => $error['file'],
            'linha_erro' => $error['line']
        ]);
    }
});

header('Content-Type: application/json');






session_start();


try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../dao/mensagem_dao.php';
    require_once __DIR__ . '/../dao/cliente_dao.php';
} catch (Throwable $e) { 
    echo json_encode(["erro_include" => "Falha ao incluir arquivo: " . $e->getMessage(), "trace" => $e->getTraceAsString()]);
    exit;
}




$id_usuario_logado = $_SESSION['usuario_id'] ?? null;
$notificacoes_formatadas = [];
$status_debug = "Iniciando busca de notificações.";

if ($id_usuario_logado) {
    try {
        $status_debug = "Tentando obter conexão PDO.";
        $pdo = Database::getConnection(); 
        $status_debug = "Conexão PDO obtida. Instanciando DAOs.";
        $mensagemDAO = new MensagemDAO(); 
        $clienteDAO = new ClienteDAO();   
        $status_debug = "DAOs instanciados. Buscando últimas mensagens.";

        $ultimas_mensagens = $mensagemDAO->buscarUltimasConversas($id_usuario_logado);
        $status_debug = "Busca de últimas mensagens concluída. Total de conversas encontradas: " . count($ultimas_mensagens);
        
        
        
        

        $contador_notificacoes_reais = 0;
        $status_debug = "Iniciando loop foreach sobre " . count($ultimas_mensagens) . " conversas.";

        foreach ($ultimas_mensagens as $index => $mensagem) {
            $status_debug = "Processando conversa index $index.";
            if (!is_object($mensagem) || !method_exists($mensagem, 'getDestinatarioId') || !method_exists($mensagem, 'isLida') || !method_exists($mensagem, 'getRemetenteId') || !method_exists($mensagem, 'getConteudo')) {
                error_log("[api/notificacoes.php] Item na coleção ultimas_mensagens não é um objeto Mensagem válido no índice $index.");
                
                continue; 
            }

            $status_debug = "Verificando mensagem ID: (não temos ID direto no objeto Mensagem, usando index $index) - Destinatário: " . $mensagem->getDestinatarioId() . " vs Logado: " . $id_usuario_logado . " - Lida: " . ($mensagem->isLida() ? 'Sim' : 'Não');

            if ($mensagem->getDestinatarioId() == $id_usuario_logado && !$mensagem->isLida()) {
                $status_debug = "Mensagem não lida para o usuário. Buscando remetente ID: " . $mensagem->getRemetenteId();
                $remetente = $clienteDAO->buscarPorId($mensagem->getRemetenteId());
                $nome_remetente = $remetente ? htmlspecialchars($remetente->getNome()) : 'Desconhecido';
                $status_debug = "Nome do remetente: " . $nome_remetente;
                
                $conteudo_original = $mensagem->getConteudo();
                $conteudo_curto = htmlspecialchars(substr($conteudo_original, 0, 50));
                if (strlen($conteudo_original) > 50) {
                    $conteudo_curto .= '...';
                }
                $conteudo_notificacao = 'Nova mensagem de ' . $nome_remetente . ': ' . $conteudo_curto;

                $notificacoes_formatadas[] = [
                    "mensagem" => $conteudo_notificacao,
                    "prioridade" => "Alta"
                ];
                $contador_notificacoes_reais++;
                $status_debug = "Notificação adicionada. Total agora: $contador_notificacoes_reais";
            }
        }
        $status_debug = "Loop foreach concluído. Total de notificações formatadas: " . count($notificacoes_formatadas);

    } catch (PDOException $e) {
        error_log("Erro de PDO em api/notificacoes.php: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        $notificacoes_formatadas = [["erro_interno_servidor" => "Erro de Banco de Dados ao buscar notificações.", "detalhe_dev" => $e->getMessage()]]; 
    } catch (Throwable $e) { 
        error_log("Erro geral em api/notificacoes.php: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        $notificacoes_formatadas = [["erro_interno_servidor" => "Erro Geral ao buscar notificações.", "detalhe_dev" => $e->getMessage()]]; 
    }
} else {
    $status_debug = "Usuário não logado.";
    $notificacoes_formatadas = [["mensagem" => "Usuário não logado.", "prioridade" => "Info"]];
}



if ($id_usuario_logado && empty($notificacoes_formatadas) && !isset($e)) { 
    $status_debug .= " Nenhuma notificação encontrada para o usuário.";
}


if (!isset($e) && !str_contains(json_encode($notificacoes_formatadas), 'erro_')) { 
    if (empty($notificacoes_formatadas) && $id_usuario_logado) {
         
         
         
    }
    
    
    
}

echo json_encode($notificacoes_formatadas);
?> 