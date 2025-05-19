<?php
session_start();
// Verificar se o usuário é administrador
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../view/login.php'); // Redireciona para login se não for admin
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/pedido_dao.php';

$mensagem = $_GET['mensagem'] ?? '';
$tipoMensagem = $_GET['tipo_mensagem'] ?? '';

$paginaAtual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$itensPorPagina = 15; // Pode ajustar conforme necessário
$termoBusca = $_GET['busca'] ?? ''; // Novo: Captura o termo de busca

$pdo = null;
try {
    $pdo = Database::getConnection();
    $pedidoDao = new PedidoDAO($pdo);
    // Modificado: Passar termo de busca para o DAO
    $resultado = $pedidoDao->listarTodosPedidos($paginaAtual, $itensPorPagina, $termoBusca);
    $pedidos = $resultado['pedidos'];
    $totalPedidos = $resultado['total']; // Este total agora reflete a busca
    $totalPaginas = ceil($totalPedidos / $itensPorPagina);
} catch (Exception $e) {
    error_log("Erro ao buscar todos os pedidos (admin): " . $e->getMessage());
    $mensagem = "Erro ao carregar pedidos: " . $e->getMessage();
    $tipoMensagem = 'erro';
    $pedidos = [];
    $totalPedidos = 0;
    $totalPaginas = 0;
}

function formatarData($data) {
    if (empty($data)) return '-';
    try {
        $dt = new DateTime($data);
        return $dt->format('d/m/Y H:i:s');
    } catch (Exception $e) {
        return $data; // Retorna a data original se houver erro na formatação
    }
}

function formatarValor($valor) {
    return 'R$ ' . number_format($valor ?? 0, 2, ',', '.');
}

function badgeSituacao($situacao) {
    $badgeClass = 'bg-secondary';
    switch ($situacao) {
        case 'NOVO': $badgeClass = 'bg-primary'; break;
        case 'EM_PREPARACAO': $badgeClass = 'bg-warning text-dark'; break;
        case 'ENVIADO': $badgeClass = 'bg-info text-dark'; break;
        case 'ENTREGUE': $badgeClass = 'bg-success'; break;
        case 'CANCELADO': $badgeClass = 'bg-danger'; break;
    }
    return "<span class='badge {$badgeClass}'>" . htmlspecialchars($situacao) . "</span>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração de Pedidos - ECOxchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard.css"> <!-- Pode reutilizar ou criar um CSS específico -->
    <style>
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ECO<span>xchange</span> - Admin</div>
        <div class="user-options">
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
            <a href="../controllers/logout_controller.php">Sair</a>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i> Voltar ao Dashboard
            </a>
        </div>
    </div>

    <div class="container mt-4">
        <h2>Todos os Pedidos (<?= $totalPedidos ?>)</h2>

        <!-- Formulário de Busca Adicionado -->
        <form method="GET" action="admin_listar_pedidos.php" class="mb-3">
            <div class="input-group">
                <input type="text" name="busca" class="form-control" placeholder="Buscar por Nº Pedido ou Nome do Cliente" value="<?= htmlspecialchars($termoBusca) ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
                 <?php if (!empty($termoBusca)): ?>
                    <a href="admin_listar_pedidos.php" class="btn btn-outline-secondary" title="Limpar busca"><i class="bi bi-x-lg"></i> Limpar</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($pedidos) && $tipoMensagem !== 'erro'): ?>
            <div class="alert alert-info">Nenhum pedido encontrado.</div>
        <?php elseif (!empty($pedidos)): ?>
            <!-- Container para atualização AJAX -->
            <div id="pedidos-list-container">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Nº Pedido</th>
                                <th>Data</th>
                                <th>Cliente</th>
                                <th>Valor Total</th>
                                <th>Situação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $index => $pedido): ?>
                                <tr>
                                    <td><?= ($paginaAtual - 1) * $itensPorPagina + $index + 1 ?></td>
                                    <td><?= htmlspecialchars($pedido['numero']) ?></td>
                                    <td><?= formatarData($pedido['data_pedido']) ?></td>
                                    <td>
                                        <?= htmlspecialchars($pedido['nome_cliente']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($pedido['email_cliente']) ?></small>
                                    </td>
                                    <td><?= formatarValor($pedido['valor_total']) ?></td>
                                    <td><?= badgeSituacao($pedido['situacao']) ?></td>
                                    <td>
                                        <a href="admin_detalhes_pedido.php?id=<?= $pedido['id'] ?>" class="btn btn-info btn-sm" title="Ver Detalhes">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <?php if ($totalPaginas > 1): ?>
                    <nav aria-label="Paginação de pedidos">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <li class="page-item <?= ($i == $paginaAtual) ? 'active' : '' ?>">
                                    <a class="page-link" href="?pagina=<?= $i ?>&busca=<?= urlencode($termoBusca) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div> <!-- Fim de #pedidos-list-container -->
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pedidosListContainer = document.getElementById('pedidos-list-container');
            const campoBusca = document.querySelector('input[name="busca"]');

            function carregarPedidos() {
                // Pega o termo de busca atual do input
                const termoBuscaAtual = campoBusca ? campoBusca.value : '';
                
                // Pega a página atual da URL da página principal
                const urlParams = new URLSearchParams(window.location.search);
                const paginaAtual = urlParams.get('pagina') || '1';

                // Constrói a URL para a requisição AJAX
                const url = `ajax_carregar_pedidos.php?busca=${encodeURIComponent(termoBuscaAtual)}&pagina=${paginaAtual}`;

                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Falha na requisição de rede: ' + response.statusText);
                        }
                        return response.text(); // Espera HTML como texto
                    })
                    .then(html => {
                        if (pedidosListContainer) {
                            pedidosListContainer.innerHTML = html;
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao carregar lista de pedidos via AJAX:', error);
                        // Opcional: exibir uma mensagem de erro no container
                        if (pedidosListContainer) {
                            // pedidosListContainer.innerHTML = '<div class="alert alert-danger">Erro ao atualizar a lista. Tente recarregar a página.</div>';
                        }
                    });
            }

            // Carrega os pedidos a cada 5 segundos
            setInterval(carregarPedidos, 5000);
        });
    </script>
</body>
</html> 