<?php
session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../view/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/pedido_dao.php';
require_once __DIR__ . '/../model/pedido.php'; // Para type hinting e instanceof
require_once __DIR__ . '/../model/item_pedido.php';
require_once __DIR__ . '/../model/produto.php';

$pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$mensagem = '';
$tipoMensagem = '';
$pedido = null;

if (!$pedido_id) {
    header('Location: admin_listar_pedidos.php?mensagem=ID do pedido inválido&tipo_mensagem=erro');
    exit;
}

try {
    $pdo = Database::getConnection();
    $pedidoDao = new PedidoDAO($pdo);
    $pedido = $pedidoDao->buscarPorId($pedido_id);

    if (!$pedido) {
        header('Location: admin_listar_pedidos.php?mensagem=Pedido não encontrado&tipo_mensagem=erro');
        exit;
    }
} catch (Exception $e) {
    error_log("Erro ao buscar detalhes do pedido (admin): " . $e->getMessage());
    $mensagem = "Erro ao carregar detalhes do pedido: " . $e->getMessage();
    $tipoMensagem = 'erro';
}

function formatarDataDetalhes($data) {
    if (empty($data)) return 'Não definida';
    try {
        $dt = new DateTime($data);
        return $dt->format('d/m/Y H:i:s');
    } catch (Exception $e) {
        return $data;
    }
}

function formatarValorDetalhes($valor) {
    return 'R$ ' . number_format($valor ?? 0, 2, ',', '.');
}

function exibirBadgeSituacao($situacao) {
    $badgeClass = 'bg-secondary';
    $textoCor = 'text-white';
    switch (strtoupper($situacao)) {
        case 'NOVO': $badgeClass = 'bg-primary'; break;
        case 'EM_PREPARACAO': $badgeClass = 'bg-warning'; $textoCor = 'text-dark'; break;
        case 'ENVIADO': $badgeClass = 'bg-info'; $textoCor = 'text-dark'; break;
        case 'ENTREGUE': $badgeClass = 'bg-success'; break;
        case 'CANCELADO': $badgeClass = 'bg-danger'; break;
    }
    return "<span class='badge {$badgeClass} {$textoCor}'>" . htmlspecialchars(str_replace('_', ' ', $situacao)) . "</span>";
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Pedido - ECOxchange Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .card-header { font-size: 1.2rem; }
        .item-pedido-foto { max-width: 100px; max-height: 100px; object-fit: cover; }
        .total-pedido { font-size: 1.3rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ECO<span>xchange</span> - Admin</div>
        <div class="user-options">
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
            <a href="../controllers/logout_controller.php">Sair</a>
            <a href="admin_listar_pedidos.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i> Voltar para Lista de Pedidos
            </a>
        </div>
    </div>

    <div class="container mt-4">
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?>" role="alert">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if ($pedido && $pedido instanceof Pedido): ?>
            <h2>Detalhes do Pedido: <?= htmlspecialchars($pedido->getNumero()) ?></h2>
            <hr>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">Informações do Pedido</div>
                        <div class="card-body">
                            <p><strong>Número:</strong> <?= htmlspecialchars($pedido->getNumero()) ?></p>
                            <p><strong>Data do Pedido:</strong> <?= formatarDataDetalhes($pedido->getDataPedido()) ?></p>
                            <p><strong>Situação Atual:</strong> <?= exibirBadgeSituacao($pedido->getSituacao()) ?></p>
                            
                            <?php if ($pedido->getDataEnvio()): ?>
                                <p><strong>Data de Envio:</strong> <?= formatarDataDetalhes($pedido->getDataEnvio()) ?></p>
                            <?php endif; ?>
                            <?php if ($pedido->getDataEntrega()): ?>
                                <p><strong>Data da Entrega:</strong> <?= formatarDataDetalhes($pedido->getDataEntrega()) ?></p>
                            <?php endif; ?>
                            <?php if ($pedido->getDataCancelamento()): ?>
                                <p><strong>Data de Cancelamento:</strong> <?= formatarDataDetalhes($pedido->getDataCancelamento()) ?></p>
                            <?php endif; ?>

                            <p><strong>Valor Total:</strong> <span class="total-pedido"><?= formatarValorDetalhes($pedido->getValorTotal()) ?></span></p>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">Alterar Situação do Pedido</div>
                        <div class="card-body">
                            <form action="../controllers/admin_atualizar_status_pedido.php" method="POST">
                                <input type="hidden" name="pedido_id" value="<?= $pedido->getId() // Corrigido para usar o ID numérico ?>">
                                <div class="mb-3">
                                    <label for="nova_situacao" class="form-label">Nova Situação:</label>
                                    <select name="nova_situacao" id="nova_situacao" class="form-select">
                                        <?php 
                                        $statusDisponiveis = ['NOVO', 'EM_PREPARACAO', 'ENVIADO', 'ENTREGUE', 'CANCELADO'];
                                        foreach ($statusDisponiveis as $status) {
                                            $selected = ($pedido->getSituacao() === $status) ? 'selected' : '';
                                            echo "<option value=\"$status\" $selected>" . htmlspecialchars(str_replace('_', ' ', $status)) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Atualizar Situação</button>
                            </form>
                        </div>
                    </div>

                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">Informações do Cliente</div>
                        <div class="card-body">
                            <?php $cliente = $pedido->getCliente(); ?>
                            <?php if ($cliente): ?>
                                <p><strong>Nome:</strong> <?= htmlspecialchars($cliente->getNome()) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($cliente->getEmail()) ?></p>
                                <?php /* Você pode adicionar mais detalhes do cliente se o objeto Cliente tiver, 
                                          ex: $cliente->getTelefone(), $cliente->getEndereco()->getRuaCompleta() */ ?>
                            <?php else: ?>
                                <p class="text-muted">Cliente não disponível.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Itens do Pedido</div>
                <div class="card-body">
                    <?php
                    $itens = $pedido->getItensPedido();
                    if (is_array($itens) && !empty($itens)):
                    ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Foto</th>
                                        <th>Produto</th>
                                        <th>Quantidade</th>
                                        <th>Preço Unit.</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itens as $item): ?>
                                        <?php
                                        $produtoDoItem = $item->getProduto();
                                        $fotoBase64 = null;
                                        if ($produtoDoItem && method_exists($produtoDoItem, 'getFoto')) {
                                            $fotoData = $produtoDoItem->getFoto();
                                            if ($fotoData) {
                                                if (is_resource($fotoData)) { // Para PostgreSQL bytea
                                                    $fotoData = stream_get_contents($fotoData);
                                                }
                                                // Se já não for base64 (ex: se o DAO já tratar isso), não precisa re-encodar.
                                                // Assumindo que getFoto() retorna os dados binários ou string base64.
                                                // Se for binário, precisa de base64_encode. Se já for string base64, não.
                                                // Para simplificar, vamos assumir que precisamos encodar se não for uma string já formatada.
                                                if (strpos($fotoData, 'data:image') !== 0) {
                                                    $fotoBase64 = 'data:image/jpeg;base64,' . base64_encode($fotoData);
                                                } else {
                                                    $fotoBase64 = $fotoData; // Já está em formato data URL
                                                }
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if ($fotoBase64): ?>
                                                    <img src="<?= $fotoBase64 ?>" alt="<?= htmlspecialchars($produtoDoItem ? $produtoDoItem->getNome() : 'Produto') ?>" class="item-pedido-foto img-thumbnail">
                                                <?php else: ?>
                                                    <i class="bi bi-card-image" style="font-size: 2rem; color: #ccc;"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($produtoDoItem ? $produtoDoItem->getNome() : 'Produto não disponível') ?></td>
                                            <td><?= $item->getQuantidade() ?></td>
                                            <td><?= formatarValorDetalhes($item->getPrecoUnitario()) ?></td>
                                            <td><?= formatarValorDetalhes($item->getSubtotal()) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Nenhum item encontrado para este pedido.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif (empty($mensagem)): // Se $pedido for null e não houver mensagem de erro já definida ?>
            <div class="alert alert-warning" role="alert">
                Não foi possível carregar os detalhes do pedido.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 