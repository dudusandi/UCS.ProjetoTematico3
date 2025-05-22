<?php 
// Inclui o controller que busca os dados das mensagens
require_once __DIR__ . '/../controllers/mensagens_controller.php'; 

// Requerimentos comuns
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/cliente_dao.php';
require_once __DIR__ . '/../dao/mensagem_dao.php'; // Necessário para o contador na side-nav
// require_once __DIR__ . '/../dao/produto_dao.php'; // Não usado diretamente nesta página, mas pode ser mantido se houver dependências indiretas futuras.
// MensagemDAO é instanciado em mensagens_controller.php e também abaixo para o header, se necessário.

// Lógica unificada para dados do usuário e contador de mensagens (para side-nav e header)
$nome_usuario = "Usuário"; // Default
$contador_mensagens_nao_lidas_geral = 0; 
$id_usuario_logado = $_SESSION['usuario_id'] ?? null;

if ($id_usuario_logado) {
    try {
        $pdo = Database::getConnection(); // Garante que o PDO esteja disponível.
        $clienteDAO = new ClienteDAO();
        $cliente = $clienteDAO->buscarPorId($id_usuario_logado);
        if ($cliente) {
            $nome_usuario = $cliente->getNome();
            if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_nome'] !== $nome_usuario) {
                 $_SESSION['usuario_nome'] = $nome_usuario;
            }
        }

        // $contador_mensagens_nao_lidas é definido em mensagens_controller.php para o conteúdo da página.
        // Usaremos $contador_mensagens_nao_lidas_geral para o ícone de sino e badge da side-nav.
        $mensagemDAO_nav = new MensagemDAO(); 
        $contador_mensagens_nao_lidas_geral = $mensagemDAO_nav->contarMensagensNaoLidas($id_usuario_logado);

    } catch (Exception $e) {
        error_log("Erro ao buscar dados para side-nav em minhas_mensagens.php: " . $e->getMessage());
    }
} else {
    // Idealmente, redirecionar para login se não estiver logado e tentar acessar esta página.
    // header("Location: login.php?erro=acesso_restrito");
    // exit;
}
// Fim da lógica unificada
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Mensagens - ECOxchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="dashboard.css"> <!-- CSS Unificado -->
    <link rel="stylesheet" href="estilo_mensagens.css"> <!-- CSS específico para mensagens -->
    <style>
        /* Estilos que eram da antiga nav-bar podem ser removidos se não mais aplicáveis à side-nav */
    </style>
</head>
<body>
    <div class="side-nav-bar">
        <div class="logo-container">
            <div class="logo">ECO<span>xchange</span></div>
        </div>

        <a href="dashboard.php">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        
        <?php if ($id_usuario_logado): ?>
            <a href="#" data-bs-toggle="modal" data-bs-target="#cadastroProdutoModalDashboard"> 
                <i class="bi bi-plus-circle"></i>
                <span>Cadastrar Produto</span>
            </a>
            <a href="meus_produtos.php">
                <i class="bi bi-archive"></i>
                <span>Meus Produtos</span>
            </a>
            <a href="minhas_mensagens.php" class="active position-relative">
                <i class="bi bi-chat-left-dots"></i>
                <span>Minhas Mensagens</span>
                <?php 
                // Usar o contador_mensagens_nao_lidas_geral para o badge do link de mensagens
                if ($contador_mensagens_nao_lidas_geral > 0): 
                ?>
                    <span class="badge bg-danger position-absolute top-50 start-100 translate-middle-y ms-2" style="font-size: 0.65em; padding: 0.3em 0.5em;"><?php echo $contador_mensagens_nao_lidas_geral; ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <a href="../view/listar_clientes.php">
                <i class="bi bi-people"></i>
                <span>Gerenciar Clientes</span>
            </a> 
        <?php endif; ?>

        <!-- Notificações na Side Nav -->
        <?php if ($id_usuario_logado): ?>
        <div class="nav-item-notificacao dropdown">
            <button class="btn-notificacao dropdown-toggle" type="button" id="notificacoesDropdownBtnSideNav" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell"></i>
                <span>Notificações</span>
                 <span id="contadorNotificacoesSideNav" class="badge bg-danger position-absolute top-50 start-100 translate-middle-y ms-2" style="font-size: 0.65em; padding: 0.3em 0.5em; <?php echo ($contador_mensagens_nao_lidas_geral > 0 ? '' : 'display:none;'); ?>">
                    <?php echo $contador_mensagens_nao_lidas_geral; ?> 
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="notificacoesDropdownBtnSideNav" id="listaNotificacoesDropdownMensagens">
                <li><h6 class="dropdown-header">Notificações</h6></li>
                <li><hr class="dropdown-divider"></li>
                <li id="notificacaoItemLoadingMensagens" class="dropdown-item text-muted">Carregando...</li>
                <li id="notificacaoItemNenhumaMensagens" class="dropdown-item text-muted d-none">Nenhuma notificação nova.</li>
                <li><hr class="dropdown-divider d-none" id="notificacoesDividerFinalMensagens"></li>
                <li><a class="dropdown-item text-center d-none" href="#" id="verTodasNotificacoesLinkMensagens">Ver todas</a></li> 
                <li><a class="dropdown-item text-center d-none" href="#" id="marcarTodasLidasLinkMensagens" onclick="marcarTodasComoLidasClientSide(event, 'Mensagens')">Marcar todas como lidas</a></li>
            </ul>
        </div>
        <?php endif; ?>
        <!-- Fim Notificações na Side Nav -->

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
        <!-- HEADER ROXO REMOVIDO -->

        <!-- Conteúdo específico da página de mensagens -->
        <div class="messages-section container-fluid mt-3"> <!-- Usando a classe genérica e container-fluid -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                 <h2>Minhas Conversas</h2>
            </div>

            <div class="container-mensagens" style="background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);"> <!-- Mantendo esta div para estilos específicos de `estilo_mensagens.css` -->
                <?php if (!empty($conversas_formatadas)): ?>
                    <div class="list-group lista-conversas"> <!-- Usando list-group do Bootstrap para melhor estilização base -->
                        <?php foreach ($conversas_formatadas as $conversa): ?>
                            <a href="chat.php?usuario_id=<?php echo htmlspecialchars($conversa['outro_usuario_id']); ?>" 
                               class="list-group-item list-group-item-action conversa-item <?php echo $conversa['nao_lida'] ? 'nao-lida' : ''; ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1 nome-usuario"><?php echo htmlspecialchars($conversa['nome_outro_usuario']); ?></h5>
                                    <small class="data-msg"><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($conversa['data_ultima_mensagem']))); ?></small>
                                </div>
                                <p class="mb-1 ultima-msg-texto">
                                    <?php 
                                    $ultima_msg = $conversa['ultima_mensagem'];
                                    if (function_exists('mb_strimwidth')) {
                                        echo htmlspecialchars(mb_strimwidth($ultima_msg, 0, 70, "...")); // Aumentei um pouco o limite
                                    } else {
                                        echo htmlspecialchars(substr($ultima_msg, 0, 67) . (strlen($ultima_msg) > 70 ? "..." : ""));
                                    }
                                    ?>
                                </p>
                                <?php if ($conversa['nao_lida']): ?>
                                    <small class="text-danger fw-bold">Nova mensagem!</small>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-chat-square-dots" style="font-size: 3rem; color: #6c757d;"></i>
                        <p class="mt-3 lead text-muted">Você ainda não possui nenhuma conversa.</p>
                        <p>Quando você iniciar ou receber uma mensagem sobre um produto, ela aparecerá aqui.</p>
                        <a href="dashboard.php" class="btn btn-primary mt-3">Ver Produtos</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.usuarioLogadoId = <?php echo json_encode($id_usuario_logado); ?>;

        function marcarTodasComoLidasClientSide(event, prefix = '') {
            event.preventDefault();
            const contadorNotificacoes = document.getElementById('contadorNotificacoesSideNav'); 
            const listaNotificacoes = document.getElementById('listaNotificacoesDropdown' + prefix);
            
            if (contadorNotificacoes) {
                contadorNotificacoes.style.display = 'none';
            }
            
            if(listaNotificacoes){
                const itensNotificacao = listaNotificacoes.querySelectorAll('.notificacao-nao-lida'); 
                itensNotificacao.forEach(item => item.classList.remove('notificacao-nao-lida'));

                const loadingItem = listaNotificacoes.querySelector('#notificacaoItemLoading' + prefix);
                const nenhumaItem = listaNotificacoes.querySelector('#notificacaoItemNenhuma' + prefix);
                const dividerFinal = listaNotificacoes.querySelector('#notificacoesDividerFinal' + prefix);
                const verTodasLink = listaNotificacoes.querySelector('#verTodasNotificacoesLink' + prefix);
                const marcarLidasLink = listaNotificacoes.querySelector('#marcarTodasLidasLink' + prefix);

                if(loadingItem) loadingItem.classList.add('d-none');
                if(nenhumaItem) nenhumaItem.classList.remove('d-none');
                if(dividerFinal) dividerFinal.classList.add('d-none');
                if(verTodasLink) verTodasLink.classList.add('d-none');
                if(marcarLidasLink) marcarLidasLink.classList.add('d-none');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const cadastroProdutoModal = document.getElementById('cadastroProdutoModalDashboard');
            if (!cadastroProdutoModal) {
                // console.warn("Modal #cadastroProdutoModalDashboard não encontrado nesta página.");
            }
        });
    </script>
    <!-- Se houver um dashboard.js global que trata de inicializações como o dropdown de notificações, 
         e ele for incluído em todas as páginas, não precisa de scripts duplicados. -->
    <!-- Exemplo: <script src="./js/dashboard_global.js"></script> --> 

</body>
</html> 