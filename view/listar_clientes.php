<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';
require_once '../dao/mensagem_dao.php';

try {
    $clienteDAO = new ClienteDAO(Database::getConnection());
    $itensPorPagina = 6;
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $offset = ($pagina - 1) * $itensPorPagina;
    
    $clientes = $clienteDAO->listarTodos($itensPorPagina, $offset);
    $totalClientes = $clienteDAO->contarTodos();
    $totalPaginas = ceil($totalClientes / $itensPorPagina);

    $id_usuario_logado = $_SESSION['usuario_id'];
    $nome_usuario = $_SESSION['usuario_nome'] ?? "Usuário";
    $mensagemDAO = new MensagemDAO();
    $contador_mensagens_nao_lidas = $mensagemDAO->contarMensagensNaoLidas($id_usuario_logado);

} catch (Exception $e) {
    $clientes = [];
    $mensagem_erro_bloco = "Erro ao listar clientes: " . $e->getMessage();
    $tipoMensagem_erro_bloco = 'erro';
    
    $id_usuario_logado = $_SESSION['usuario_id'] ?? null;
    $nome_usuario = $_SESSION['usuario_nome'] ?? "Usuário";
    $contador_mensagens_nao_lidas = 0;
    if ($id_usuario_logado) {
        try {
            $mensagemDAO = new MensagemDAO();
            $contador_mensagens_nao_lidas = $mensagemDAO->contarMensagensNaoLidas($id_usuario_logado);
        } catch (Exception $eMsg) {
            error_log("Erro ao buscar contador de mensagens em listar_clientes: " . $eMsg->getMessage());
        }
    }
}

$mensagem_feedback_get = $_GET['mensagem'] ?? '';
$tipoMensagem_feedback_get = $_GET['tipo_mensagem'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes - ECOxChange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="dashboard.css"> 
    <link rel="stylesheet" href="listar.css"> 
</head>
<body>
    <div class="side-nav-bar">
        <div class="logo-container">
            <div class="logo">ECO<span>Exchange</span></div>
        </div>

        <a href="dashboard.php">
            <i class="bi bi-speedometer2"></i>
            <span>Pagina Inicial</span>
        </a>
        
        <?php if ($id_usuario_logado): ?>
            <a href="#" data-bs-toggle="modal" data-bs-target="#cadastroProdutoModal">
                <i class="bi bi-plus-circle"></i>
                <span>Cadastrar Produto</span>
            </a>
            <a href="meus_produtos.php">
                <i class="bi bi-archive"></i>
                <span>Meus Produtos</span>
            </a>
            <a href="minhas_mensagens.php" class="position-relative">
                <i class="bi bi-chat-left-dots"></i>
                <span>Minhas Mensagens</span>
                <?php if ($contador_mensagens_nao_lidas > 0): ?>
                    <span class="badge bg-danger position-absolute top-50 start-100 translate-middle-y ms-2" style="font-size: 0.65em; padding: 0.3em 0.5em;"><?php echo $contador_mensagens_nao_lidas; ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <a href="../view/listar_clientes.php" class="active">
                <i class="bi bi-people"></i>
                <span>Gerenciar Clientes</span>
            </a> 
        <?php endif; ?>
        
        <?php if ($id_usuario_logado): ?>
        <div class="notifications-section-container">
            <div class="notifications-header">
                <i class="bi bi-bell"></i>
                <span>Notificações</span>
                <span id="contadorNotificacoesSideNav" class="badge bg-danger ms-2" style="font-size: 0.7em; padding: 0.3em 0.5em; <?php echo ($contador_mensagens_nao_lidas > 0 ? '' : 'display:none;'); ?>">
                    <?php echo $contador_mensagens_nao_lidas; ?> 
                </span>
            </div>
            <ul class="notifications-list" id="listaNotificacoesSideNav">
                <li id="notificacaoItemLoadingSideNav" class="dropdown-item text-muted">Carregando...</li>
                <li id="notificacaoItemNenhumaSideNav" class="dropdown-item text-muted d-none">Nenhuma notificação nova.</li>
                <li id="marcarTodasLidasContainerSideNav" class="d-none"><a class="dropdown-item text-center" href="#" id="marcarTodasLidasLinkSideNav">Marcar todas como lidas</a></li>
            </ul>
        </div>
        <?php endif; ?>

        <div class="user-info-nav">
            <?php if ($id_usuario_logado): ?>
                <span><i class="bi bi-person-circle"></i> <?= htmlspecialchars($nome_usuario) ?></span>
                <a href="../controllers/logout_controller.php">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Sair</span>
                </a>
            <?php else: ?>
                <a href="login.php">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Login</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content">
        <div class="search-bar-container"> 
            <div class="search-bar-md3"> 
                <input type="text" id="searchInput" class="form-control flex-grow-1" placeholder="Pesquisar clientes..." autocomplete="off">
            </div>
        </div>

        <div class="container-fluid px-4"> 
            <?php if (!empty($mensagem_feedback_get)): ?>
                <div class="alert alert-<?= $tipoMensagem_feedback_get === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show mt-3" role="alert">
                    <?= htmlspecialchars($mensagem_feedback_get) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($mensagem_erro_bloco) && !empty($mensagem_erro_bloco) && isset($tipoMensagem_erro_bloco)): ?>
                <div class="alert alert-<?= $tipoMensagem_erro_bloco === 'erro' ? 'danger' : 'warning' ?> alert-dismissible fade show mt-3" role="alert">
                    <?= htmlspecialchars($mensagem_erro_bloco) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center my-4">
                <h2>Gerenciar Clientes</h2>
                <div>
                    <a href="cadastro_cliente.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Cadastrar Novo Cliente
                    </a>
                </div>
            </div>

            <div id="clientesContainer" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            </div>
            <div id="sentinela" style="height: 20px;"></div>
            <div id="loading" class="text-center my-3 d-none">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="listar_clientes.js"></script>
    <script>
        function carregarNotificacoesSideNav() {
            const listaNotificacoes = document.getElementById('listaNotificacoesSideNav');
            const loadingItem = document.getElementById('notificacaoItemLoadingSideNav');
            const nenhumaItem = document.getElementById('notificacaoItemNenhumaSideNav');
            const marcarTodasContainer = document.getElementById('marcarTodasLidasContainerSideNav');
            const contadorBadge = document.getElementById('contadorNotificacoesSideNav');

            if (!listaNotificacoes || !loadingItem || !nenhumaItem || !marcarTodasContainer || !contadorBadge) return;

            loadingItem.classList.remove('d-none');
            nenhumaItem.classList.add('d-none');
            marcarTodasContainer.classList.add('d-none');
            
            setTimeout(() => {
                loadingItem.classList.add('d-none');
                const currentBadgeCount = parseInt(contadorBadge.textContent);
                 if (isNaN(currentBadgeCount) || currentBadgeCount === 0) {
                     contadorBadge.style.display = 'none';
                     nenhumaItem.classList.remove('d-none');
                 } else {
                     marcarTodasContainer.classList.remove('d-none');
                     nenhumaItem.classList.add('d-none');
                 }
            }, 1000); 
        }
        
        document.addEventListener('DOMContentLoaded', function () {
            if (<?php echo json_encode($id_usuario_logado ? true : false); ?>) {
                carregarNotificacoesSideNav();
                
                const marcarLidasLink = document.getElementById('marcarTodasLidasLinkSideNav');
                if(marcarLidasLink && marcarLidasLink.getAttribute('onclick')){
                } else if (marcarLidasLink) {
                }
            }
        });
    </script>
</body>
</html>