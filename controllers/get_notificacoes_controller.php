<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/NotificacaoDAO.php';
require_once __DIR__ . '/../model/Notificacao.php'; // Necessário se NotificacaoDAO retornar objetos Notificacao

if (!isset($_SESSION['usuario_id'])) {
    ob_clean(); // Limpar buffer antes do JSON
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$usuario_id_destino = (int)$_SESSION['usuario_id'];
$limite_dropdown = 5; // Quantas notificações mostrar no dropdown

try {
    $pdo = Database::getConnection();
    $notificacaoDao = new NotificacaoDAO($pdo);

    $contadorNaoLidas = $notificacaoDao->contarNaoLidas($usuario_id_destino);
    
    // Buscar as últimas N notificações (incluindo lidas e não lidas para popular o dropdown)
    // O método buscarPorUsuarioIdDestino já ordena por data_criacao DESC
    $notificacoesRecentesObjs = $notificacaoDao->buscarPorUsuarioIdDestino($usuario_id_destino, false, $limite_dropdown);

    $notificacoesFormatadas = [];
    foreach ($notificacoesRecentesObjs as $notifObj) {
        // Formatar data para exibição amigável (exemplo)
        $dataCriacao = new DateTime($notifObj->getDataCriacao());
        $agora = new DateTime();
        $intervalo = $agora->diff($dataCriacao);
        $dataFormatada = '';

        if ($intervalo->y > 0) {
            $dataFormatada = $intervalo->format('%y anos atrás');
        } elseif ($intervalo->m > 0) {
            $dataFormatada = $intervalo->format('%m meses atrás');
        } elseif ($intervalo->d > 0) {
            if ($intervalo->d == 1) $dataFormatada = 'Ontem';
            else $dataFormatada = $intervalo->format('%d dias atrás');
        } elseif ($intervalo->h > 0) {
            $dataFormatada = $intervalo->format('%h h atrás');
        } elseif ($intervalo->i > 0) {
            $dataFormatada = $intervalo->format('%i min atrás');
        } else {
            $dataFormatada = 'Agora mesmo';
        }

        $notificacoesFormatadas[] = [
            'id' => $notifObj->getId(),
            'mensagem' => htmlspecialchars($notifObj->getMensagem()),
            'link' => $notifObj->getLink() ? htmlspecialchars($notifObj->getLink()) : '#',
            'lida' => $notifObj->isLida(),
            'data_formatada' => $dataFormatada,
            'tipo' => $notifObj->getTipoNotificacao() // Para possível estilização ou ícones no futuro
        ];
    }
    ob_clean(); // Limpar buffer antes do JSON
    echo json_encode([
        'success' => true,
        'contadorNaoLidas' => $contadorNaoLidas,
        'notificacoes' => $notificacoesFormatadas
    ]);

} catch (Exception $e) {
    error_log("Erro ao buscar notificações: " . $e->getMessage());
    ob_clean(); // Limpar buffer antes do JSON de erro
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor ao buscar notificações.']);
}