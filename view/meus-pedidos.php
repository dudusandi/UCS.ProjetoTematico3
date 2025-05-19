<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/pedido_dao.php';

try {
    $pdo = Database::getConnection();
    $pedidoDao = new PedidoDAO($pdo);
    $clienteId = $_SESSION['usuario_id'];
    
    // Buscar pedidos do cliente
    $pedidos = $pedidoDao->listarPedidosCliente($clienteId);
    
} catch (Exception $e) {
    error_log("Erro ao buscar pedidos: " . $e->getMessage());
    $mensagem = "Erro ao carregar pedidos: " . $e->getMessage();
    $tipoMensagem = 'erro';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos - UcsExpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="header">
        <div class="logo">UCS<span>express</span></div>
        <div class="user-options">
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
            <a href="../controllers/logout_controller.php">Sair</a>
            <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="container mt-4">
        <h2>Meus Pedidos</h2>
        
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($pedidos)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bag-x" style="font-size: 4rem;"></i>
                <h3 class="mt-3">Você ainda não fez nenhum pedido</h3>
                <a href="dashboard.php" class="btn btn-primary mt-3">
                    <i class="bi bi-shop"></i> Ir às Compras
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Data</th>
                            <th>Situação</th>
                            <th>Valor Total</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td><?= htmlspecialchars($pedido['numero']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                                <td>
                                    <span class="badge 
                                        <?php 
                                        switch($pedido['situacao']) {
                                            case 'NOVO': echo 'bg-primary'; break;
                                            case 'EM_PREPARACAO': echo 'bg-warning'; break;
                                            case 'ENVIADO': echo 'bg-info'; break;
                                            case 'ENTREGUE': echo 'bg-success'; break;
                                            case 'CANCELADO': echo 'bg-danger'; break;
                                            default: echo 'bg-secondary';
                                        }
                                        ?>">
                                            
                                        <?php 
                                        $statusBruto = $pedido['situacao'];
                                        $statusExibicao = '';
                                        switch ($statusBruto) {
                                            case 'NOVO':
                                                $statusExibicao = 'Novo';
                                                break;
                                            case 'EM_PREPARACAO':
                                                $statusExibicao = 'Em Preparação';
                                                break;
                                            case 'ENVIADO':
                                                $statusExibicao = 'Enviado';
                                                break;
                                            case 'ENTREGUE':
                                                $statusExibicao = 'Entregue';
                                                break;
                                            case 'CANCELADO':
                                                $statusExibicao = 'Cancelado';
                                                break;
                                            default:
                                                $statusExibicao = ucwords(strtolower(str_replace('_', ' ', $statusBruto)));
                                        }
                                        echo htmlspecialchars($statusExibicao);
                                    ?>
                                    </span>
                                </td>
                                <td>R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></td>
                                <td>
                                    <a href="detalhes-pedido.php?id=<?= $pedido['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Detalhes
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 