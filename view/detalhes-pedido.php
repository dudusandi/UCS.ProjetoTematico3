<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: meus-pedidos.php');
    exit;
}

$pedidoId = (int)$_GET['id'];

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/pedido_dao.php';
require_once __DIR__ . '/../model/pedido.php';
require_once __DIR__ . '/../model/item_pedido.php';
require_once __DIR__ . '/../model/produto.php';

try {
    $pdo = Database::getConnection();
    $pedidoDao = new PedidoDAO($pdo);
    
    $pedido = $pedidoDao->buscarPorId($pedidoId);
    
    if (!$pedido || $pedido->getcliente()->getid() != $_SESSION['usuario_id']) {
        header('Location: meus-pedidos.php');
        exit;
    }
    
    $itensPedido = $pedido->getitenspedido();
    
} catch (Exception $e) {
    error_log("Erro ao buscar detalhes do pedido: " . $e->getMessage());
    $mensagem = "Erro ao carregar detalhes do pedido: " . $e->getMessage();
    $tipoMensagem = 'erro';
    $itensPedido = [];
    $pedido = null;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido - UcsExpress</title>
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
            <a href="meus-pedidos.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="container mt-4">
        <?php if ($pedido): ?>
            <h2>Detalhes do Pedido #<?= htmlspecialchars($pedido->getnumero()) ?></h2>
        <?php else: ?>
            <h2>Detalhes do Pedido</h2>
        <?php endif; ?>
        
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($pedido): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Informações do Pedido</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Número:</strong> <?= htmlspecialchars($pedido->getnumero()) ?></p>
                            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido->getdatapedido())) ?></p>
                            <p>
                                <strong>Situação:</strong>
                                <span class="badge
                                    <?php
                                    // Este switch define a cor do badge
                                    switch($pedido->getsituacao()) {
                                        case 'NOVO': echo 'bg-primary'; break;
                                        case 'EM_PREPARACAO': echo 'bg-warning'; break; // Classe para EM_PREPARACAO
                                        case 'ENVIADO': echo 'bg-info'; break;
                                        case 'ENTREGUE': echo 'bg-success'; break;
                                        case 'CANCELADO': echo 'bg-danger'; break;
                                        default: echo 'bg-secondary';
                                    }
                                    ?>">
                                    <?php // Este é o bloco para o TEXTO do badge
                                    $statusBruto = $pedido->getsituacao();
                                    $statusExibicao = '';
                                    switch ($statusBruto) {
                                        case 'NOVO':
                                            $statusExibicao = 'Novo';
                                            break;
                                        case 'EM_PREPARACAO': // Case para o texto EM_PREPARACAO
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
                            </p>
                        </div>
                        <div class="col-md-6">
                            <?php if ($pedido->getdataentrega()): ?>
                                <p><strong>Data de Entrega:</strong> <?= date('d/m/Y H:i', strtotime($pedido->getdataentrega())) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Itens do Pedido</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($itensPedido)): ?>
                        <p>Nenhum item encontrado.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Quantidade</th>
                                        <th>Preço Unitário</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalGeralPedido = 0;
                                    foreach ($itensPedido as $item): 
                                        $produto = $item->getproduto();
                                        
                                        $nomeProduto = 'Produto não disponível';
                                        $fotoBase64 = null;
                                        $precoUnitarioItem = 0;

                                        if ($produto) {
                                            $nomeProduto = $produto->getnome();
                                            if (method_exists($produto, 'getfoto')) {
                                                $fotoData = $produto->getfoto();
                                                if ($fotoData) {
                                                    if (is_resource($fotoData)) { 
                                                        $fotoData = stream_get_contents($fotoData);
                                                    }
                                                    if (strpos($fotoData, 'data:image') !== 0) {
                                                        $fotoBase64 = 'data:image/jpeg;base64,' . base64_encode($fotoData);
                                                    } else {
                                                        $fotoBase64 = $fotoData;
                                                    }
                                                }
                                            }
                                            $precoUnitarioItem = $item->getprecounitario();
                                        }
                                        
                                        $subtotalItem = $item->getsubtotal();
                                        $totalGeralPedido += $subtotalItem;
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ($fotoBase64): ?>
                                                    <img src="<?= $fotoBase64 ?>" alt="<?= htmlspecialchars($nomeProduto) ?>" style="width: 70px; height: 70px; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="bi bi-card-image" style="font-size: 2rem; color: #ccc;"></i>
                                                <?php endif; ?>
                                                <br><?= htmlspecialchars($nomeProduto) ?>
                                            </td>
                                            <td><?= $item->getquantidade() ?></td>
                                            <td>R$ <?= number_format($precoUnitarioItem, 2, ',', '.') ?></td>
                                            <td>R$ <?= number_format($subtotalItem, 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <?php if ($pedido->getvalortotal()): ?>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total do Pedido:</strong></td>
                                        <td><strong>R$ <?= number_format($pedido->getvalortotal(), 2, ',', '.') ?></strong></td>
                                    </tr>
                                    <?php endif; ?>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                Pedido não encontrado ou detalhes não puderam ser carregados.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 