<?php
session_start();

// Verificar se o usuário é administrador e se a requisição é POST
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../view/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../view/admin_listar_pedidos.php?mensagem=Acesso inválido&tipo_mensagem=erro');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/pedido_dao.php';

$pedidoId = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
$novaSituacao = trim($_POST['nova_situacao'] ?? '');

$statusPermitidos = ['NOVO', 'EM_PREPARACAO', 'ENVIADO', 'ENTREGUE', 'CANCELADO'];

if (!$pedidoId) {
    header('Location: ../view/admin_listar_pedidos.php?mensagem=ID do pedido inválido.&tipo_mensagem=erro');
    exit;
}

if (empty($novaSituacao) || !in_array($novaSituacao, $statusPermitidos)) {
    header('Location: ../view/admin_detalhes_pedido.php?id=' . $pedidoId . '&mensagem=Situação inválida selecionada.&tipo_mensagem=erro');
    exit;
}

try {
    $pdo = Database::getConnection();
    $pedidoDao = new PedidoDAO($pdo);
    
    $sucesso = $pedidoDao->atualizarStatusPedido($pedidoId, $novaSituacao);
    
    if ($sucesso) {
        header('Location: ../view/admin_detalhes_pedido.php?id=' . $pedidoId . '&mensagem=Status do pedido atualizado com sucesso!&tipo_mensagem=sucesso');
    } else {
        header('Location: ../view/admin_detalhes_pedido.php?id=' . $pedidoId . '&mensagem=Falha ao atualizar o status do pedido.&tipo_mensagem=erro');
    }
    exit;

} catch (Exception $e) {
    error_log("Erro ao atualizar status do pedido (Controller): " . $e->getMessage());
    header('Location: ../view/admin_detalhes_pedido.php?id=' . $pedidoId . '&mensagem=Erro crítico ao atualizar status: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    exit;
}
?> 