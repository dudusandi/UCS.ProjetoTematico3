<?php
session_start(); 

if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403); 
    echo '<div class="alert alert-danger">Acesso não autorizado.</div>';
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/pedido_dao.php';


if (!function_exists('formatarData')) {
    function formatarData($data) {
        if (empty($data)) return '-';
        try {
            $dt = new DateTime($data);
            return $dt->format('d/m/Y H:i:s');
        } catch (Exception $e) {
            return $data;
        }
    }
}

if (!function_exists('formatarValor')) {
    function formatarValor($valor) {
        return 'R$ ' . number_format($valor ?? 0, 2, ',', '.');
    }
}

if (!function_exists('badgeSituacao')) {
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
}

$paginaAtual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$itensPorPagina = 15; // Deve ser o mesmo que na página principal
$termoBusca = $_GET['busca'] ?? '';

$pedidos = [];
$totalPedidos = 0;
$totalPaginas = 0;

try {
    $pdo = Database::getConnection();
    $pedidoDao = new PedidoDAO($pdo);
    $resultado = $pedidoDao->listarTodosPedidos($paginaAtual, $itensPorPagina, $termoBusca);
    $pedidos = $resultado['pedidos'];
    $totalPedidos = $resultado['total'];
    $totalPaginas = ceil($totalPedidos / $itensPorPagina);
} catch (Exception $e) {
    error_log("Erro ao buscar pedidos para AJAX: " . $e->getMessage());
    echo '<div class="alert alert-danger">Erro ao carregar dados dos pedidos.</div>';

}

if (empty($pedidos)):
?>
    <div class="alert alert-info mt-3">Nenhum pedido encontrado para "<?= htmlspecialchars($termoBusca) ?>".</div>
<?php else: ?>
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
<?php endif; ?> 