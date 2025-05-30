<?php
error_reporting(E_ALL);
// ini_set('display_errors', 1); // Removido temporariamente para evitar conflito com header JSON

// Função para lidar com erros fatais e garantir saída JSON
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
        // Se um header já foi enviado, não podemos enviar outro header JSON
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        // Limpa qualquer output que possa ter ocorrido antes do erro fatal
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

// Teste básico ANTERIORMENTE AQUI 
// $teste = ["status" => "API de teste está funcionando", "timestamp" => date('Y-m-d H:i:s')];
// echo json_encode($teste);
// exit; 

session_start();

// Tentativa de incluir arquivos
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../dao/mensagem_dao.php';
    require_once __DIR__ . '/../dao/cliente_dao.php';
} catch (Throwable $e) { // Throwable captura Errors e Exceptions no PHP 7+
    echo json_encode(["erro_include" => "Falha ao incluir arquivo: " . $e->getMessage(), "trace" => $e->getTraceAsString()]);
    exit;
}

// echo json_encode(["status" => "Includes e sessão OK", "session_id" => session_id(), "usuario_id_sessao" => ($_SESSION['usuario_id'] ?? 'Nao definido na sessao') ]);
// exit; // Ponto de parada anterior

$id_usuario_logado = $_SESSION['usuario_id'] ?? null;
$notificacoes_formatadas = [];
$status_debug = "Iniciando busca de notificações.";

if ($id_usuario_logado) {
    try {
        $status_debug = "Tentando obter conexão PDO.";
        $pdo = Database::getConnection(); // Pode lançar exceção
        $status_debug = "Conexão PDO obtida. Instanciando DAOs.";
        $mensagemDAO = new MensagemDAO(); // Construtor usa $this->pdo
        $clienteDAO = new ClienteDAO();   // Construtor usa $this->pdo
        $status_debug = "DAOs instanciados. Buscando últimas mensagens.";

        $ultimas_mensagens = $mensagemDAO->buscarUltimasConversas($id_usuario_logado);
        $status_debug = "Busca de últimas mensagens concluída. Total de conversas encontradas: " . count($ultimas_mensagens);
        
        // Teste: Vamos retornar o que foi encontrado até aqui e sair
        // echo json_encode(["status" => "DAOs e busca inicial OK", "debug" => $status_debug, "total_conversas" => count($ultimas_mensagens), "primeira_conversa_exemplo" => !empty($ultimas_mensagens) ? $ultimas_mensagens[0] : null]);
        // exit; // Ponto de parada anterior

        $contador_notificacoes_reais = 0;
        $status_debug = "Iniciando loop foreach sobre " . count($ultimas_mensagens) . " conversas.";

        foreach ($ultimas_mensagens as $index => $mensagem) {
            $status_debug = "Processando conversa index $index.";
            if (!is_object($mensagem) || !method_exists($mensagem, 'getDestinatarioId') || !method_exists($mensagem, 'isLida') || !method_exists($mensagem, 'getRemetenteId') || !method_exists($mensagem, 'getConteudo')) {
                error_log("[api/notificacoes.php] Item na coleção ultimas_mensagens não é um objeto Mensagem válido no índice $index.");
                // $notificacoes_formatadas[] = ["mensagem" => "Erro: Dado de mensagem inválido.", "prioridade" => "Erro"];
                continue; // Pula para a próxima iteração
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
    } catch (Throwable $e) { // Captura Exception e Error (PHP 7+)
        error_log("Erro geral em api/notificacoes.php: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        $notificacoes_formatadas = [["erro_interno_servidor" => "Erro Geral ao buscar notificações.", "detalhe_dev" => $e->getMessage()]]; 
    }
} else {
    $status_debug = "Usuário não logado.";
    $notificacoes_formatadas = [["mensagem" => "Usuário não logado.", "prioridade" => "Info"]];
}

// Se chegamos aqui sem um exit() nos blocos catch ou if/else, significa que o processamento principal terminou.
// Se $notificacoes_formatadas estiver vazio e o usuário estiver logado, é porque não havia notificações que atendessem aos critérios.
if ($id_usuario_logado && empty($notificacoes_formatadas) && !isset($e)) { // $e não está definido se não houve exceção
    $status_debug .= " Nenhuma notificação encontrada para o usuário.";
}

// Adicionar o status_debug final ao output JSON se não houver erro já formatado
if (!isset($e) && !str_contains(json_encode($notificacoes_formatadas), 'erro_')) { // Evita duplicar se já há erro
    if (empty($notificacoes_formatadas) && $id_usuario_logado) {
         // Não envia o array $notificacoes_formatadas se estiver vazio e o usuário logado,
         // envia apenas o status_debug para não confundir o frontend com um array vazio que pode ser interpretado como "sem notificacoes"
         // mas o script JS do menu.php espera um array, então vamos manter.
    }
    // Para depuração, podemos adicionar o $status_debug aos dados, ou como um campo separado
    // Se $notificacoes_formatadas for o que queremos retornar ao final, não o modificamos aqui
    // a menos que queiramos explicitamente adicionar o debug.
}

echo json_encode($notificacoes_formatadas);
?> 