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

$id_usuario_logado = $_SESSION['usuario_id'];
$nome_usuario = $_SESSION['usuario_nome'] ?? "Usuário";
$contador_mensagens_nao_lidas = 0; // Default, será atualizado

try {
    $clienteDAO = new ClienteDAO(Database::getConnection()); // Instanciação mantida pois é usada pela página
    // $itensPorPagina = 6; // Lógica de paginação da própria página, não do menu
    // $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    // $offset = ($pagina - 1) * $itensPorPagina;
    // $clientes = $clienteDAO->listarTodos($itensPorPagina, $offset);
    // $totalClientes = $clienteDAO->contarTodos();
    // $totalPaginas = ceil($totalClientes / $itensPorPagina);

    // A busca de nome e contador de mensagens para o menu
    $cliente_info_menu = $clienteDAO->buscarPorId($id_usuario_logado); // Usar uma nova variável para não conflitar
    if ($cliente_info_menu) {
        $nome_usuario = $cliente_info_menu->getNome();
        if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_nome'] !== $nome_usuario) {
            $_SESSION['usuario_nome'] = $nome_usuario;
        }
    }
    $mensagemDAO = new MensagemDAO();
    $contador_mensagens_nao_lidas = $mensagemDAO->contarMensagensNaoLidas($id_usuario_logado);

} catch (Exception $e) {
    // $clientes = []; // Lógica da página
    $mensagem_erro_bloco = "Erro ao inicializar dados para listar clientes ou menu: " . $e->getMessage();
    $tipoMensagem_erro_bloco = 'erro';
    // Garante que as variáveis do menu tenham um valor padrão em caso de erro grave
    if (!isset($nome_usuario)) $nome_usuario = "Usuário";
    if (!isset($contador_mensagens_nao_lidas)) $contador_mensagens_nao_lidas = 0;
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
    <style>
        body {
            display: flex; /* Para o menu e conteúdo ficarem lado a lado */
            min-height: 100vh;
        }
        .main-content {
            flex-grow: 1; /* Ocupa o espaço restante */
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../menu.php'; // Inclui o menu lateral padronizado ?>

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
        // Funções carregarNotificacoesSideNav e marcarTodasComoLidasClientSide REMOVIDAS
        // A lógica de notificações é tratada pelo script em menu.php
        
        document.addEventListener('DOMContentLoaded', function () {
            // O script de listar_clientes.js já lida com a inicialização dos clientes.
            // Qualquer lógica específica de notificação que estava aqui foi removida.
            // if (<?php echo json_encode($id_usuario_logado ? true : false); ?>) {
            //     // carregarNotificacoesSideNav(); // Removido
            // }
        });
    </script>
</body>
</html>