<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/mensagem_dao.php';
require_once __DIR__ . '/../dao/cliente_dao.php';
require_once __DIR__ . '/../model/cliente.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['usuario_id']) || !filter_var($_GET['usuario_id'], FILTER_VALIDATE_INT)) {
    header('Location: minhas_mensagens.php');
    exit();
}

$usuario_logado_id = (int)$_SESSION['usuario_id'];
$outro_usuario_id = (int)$_GET['usuario_id'];

if ($usuario_logado_id === $outro_usuario_id) {
    header('Location: minhas_mensagens.php');
    exit();
}

$mensagemDAO = new MensagemDAO();
$clienteDAO = new ClienteDAO();

$usuario_logado = $clienteDAO->buscarPorId($usuario_logado_id);
$nome_usuario_logado = ($usuario_logado && method_exists($usuario_logado, 'getNome')) ? $usuario_logado->getNome() : "Você";

$outro_usuario = $clienteDAO->buscarPorId($outro_usuario_id);
if (!$outro_usuario) {
    $_SESSION['erro_chat'] = "Usuário não encontrado.";
    header('Location: minhas_mensagens.php');
    exit();
}
$nome_outro_usuario = ($outro_usuario && method_exists($outro_usuario, 'getNome')) ? $outro_usuario->getNome() : "Usuário #{$outro_usuario_id}";

$mensagemDAO->marcarMensagensComoLidas($usuario_logado_id, $outro_usuario_id);

$mensagens_da_conversa = $mensagemDAO->buscarConversa($usuario_logado_id, $outro_usuario_id);

$nome_usuario_sidenav = "Usuário"; 
$contador_mensagens_nao_lidas_geral = 0;
$id_usuario_logado_s = $_SESSION['usuario_id'] ?? null;

if ($id_usuario_logado_s) {
    try {
        $pdo_s = Database::getConnection(); 
        $clienteDAO_s = new ClienteDAO();
        $cliente_s = $clienteDAO_s->buscarPorId($id_usuario_logado_s);
        if ($cliente_s) {
            $nome_usuario_sidenav = $cliente_s->getNome();
            if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_nome'] !== $nome_usuario_sidenav) {
                 $_SESSION['usuario_nome'] = $nome_usuario_sidenav;
            }
        }

        $mensagemDAO_s = new MensagemDAO(); 
        $contador_mensagens_nao_lidas_geral = $mensagemDAO_s->contarMensagensNaoLidas($id_usuario_logado_s);

    } catch (Exception $e) {
        error_log("Erro ao buscar dados para side-nav em chat.php: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat com <?php echo htmlspecialchars($nome_outro_usuario); ?> - ECOxChange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="dashboard.css"> 
    <link rel="stylesheet" href="estilo_mensagens.css"> 
    <style>
        .main-content .products-section {
            padding-top: 10px; 
        }
        .chat-container-wrapper {
            height: calc(100vh - 70px); 
            display: flex; 
            flex-direction: column;
        }
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
        
        <?php if ($id_usuario_logado_s): ?>
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
                <?php if ($contador_mensagens_nao_lidas_geral > 0): ?>
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

        <?php if ($id_usuario_logado_s): ?>
        <div class="notifications-section-container">
            <div class="notifications-header">
                <i class="bi bi-bell"></i>
                <span>Notificações</span>
                <span id="contadorNotificacoesSideNav" class="badge bg-danger ms-2" style="font-size: 0.7em; padding: 0.3em 0.5em; <?php echo ($contador_mensagens_nao_lidas_geral > 0 ? '' : 'display:none;'); ?>">
                    <?php echo $contador_mensagens_nao_lidas_geral; ?> 
                </span>
            </div>
            <ul class="notifications-list" id="listaNotificacoesSideNav">
                <li id="notificacaoItemLoadingSideNav" class="dropdown-item text-muted">Carregando...</li>
                <li id="notificacaoItemNenhumaSideNav" class="dropdown-item text-muted d-none">Nenhuma notificação nova.</li>
                <li id="marcarTodasLidasContainerSideNav" class="d-none"><a class="dropdown-item text-center" href="#" id="marcarTodasLidasLinkSideNav" onclick="marcarTodasComoLidas(event)">Marcar todas como lidas</a></li> 
            </ul>
        </div>
        <?php endif; ?>

        <div class="user-info-nav">
            <?php if ($id_usuario_logado_s): ?>
                <span><i class="bi bi-person-circle"></i> <?= htmlspecialchars($nome_usuario_sidenav) ?></span>
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
        <div class="products-section container-fluid mt-0" style="max-width: 70%; margin-left: auto; margin-right: auto; padding-top: 15px;"> 
            <div class="chat-container-wrapper"> 
                <div class="chat-container"> 
                    <div class="chat-header">
                        <h5>Chat com <?php echo htmlspecialchars($nome_outro_usuario); ?></h5>
                    </div>

                    <div class="mensagens-box" id="chat-messages-area">
                        <?php if (empty($mensagens_da_conversa)): ?>
                            <p class="sem-mensagens">Nenhuma mensagem ainda. Seja o primeiro a enviar!</p>
                        <?php else: ?>
                            <?php foreach ($mensagens_da_conversa as $msg): ?>
                                <?php 
                                $classe_css = ($msg->getRemetenteId() == $usuario_logado_id) ? 'enviada' : 'recebida';
                                $data_formatada = date("d/m/Y H:i", strtotime($msg->getDataEnvio()));
                                ?>
                                <div class="mensagem <?php echo $classe_css; ?>">
                                    <p style="margin:0;"><?php echo nl2br(htmlspecialchars($msg->getConteudo())); ?></p>
                                    <span class="timestamp"><?php echo $data_formatada; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="area-envio-mensagem">
                        <form id="chatForm" action="../controllers/enviar_mensagem_controller.php" method="POST" style="display:flex; width:100%;">
                            <textarea name="conteudo" placeholder="Digite sua mensagem..." rows="2" required></textarea>
                            <input type="hidden" name="destinatario_id" value="<?php echo htmlspecialchars($outro_usuario_id); ?>">
                            <input type="hidden" name="remetente_id" value="<?php echo htmlspecialchars($usuario_logado_id); ?>">
                            <button type="submit" class="btn btn-primary">Enviar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cadastroProdutoModalDashboard" tabindex="-1" aria-labelledby="cadastroProdutoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cadastroProdutoModalLabel">Cadastrar Novo Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="mensagemErroCadastroChat" class="alert alert-danger d-none" role="alert"></div>
                    <div id="mensagemSucessoCadastroChat" class="alert alert-success d-none" role="alert"></div>
                    <form id="formCadastroProdutoChat" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cadastroNomeChat" class="form-label">Nome do Produto *</label>
                                <input type="text" class="form-control" id="cadastroNomeChat" name="nome" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cadastroPrecoChat" class="form-label">Preço *</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" id="cadastroPrecoChat" name="preco" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cadastroDescricaoChat" class="form-label">Descrição</label>
                            <textarea class="form-control" id="cadastroDescricaoChat" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="cadastroFotoChat" class="form-label">Foto do Produto</label>
                            <input type="file" class="form-control" id="cadastroFotoChat" name="foto" accept="image/*">
                            <div class="form-text">Formatos aceitos: JPG, PNG, GIF.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnSalvarCadastroProdutoChat" class="btn btn-primary">Cadastrar Produto</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.usuarioLogadoId = <?php echo json_encode($usuario_logado_id); ?>; 
        window.isAdmin = <?php echo json_encode(isset($_SESSION['is_admin']) && $_SESSION['is_admin']); ?>;
        const outroUsuarioId = <?php echo json_encode($outro_usuario_id); ?>;

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

        const chatMessagesArea = document.getElementById('chat-messages-area');
        if(chatMessagesArea) {
            chatMessagesArea.scrollTop = chatMessagesArea.scrollHeight;
        }
        
        const chatForm = document.getElementById('chatForm');
        if (chatForm) {
            chatForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(chatForm);
                const textarea = chatForm.querySelector('textarea[name="conteudo"]');
                const conteudoMensagem = textarea.value.trim();

                if (!conteudoMensagem) return;

                const tempId = 'temp_' + Date.now();
                const dataAtual = new Date().toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'});

                const mensagemDiv = document.createElement('div');
                mensagemDiv.classList.add('mensagem', 'enviada');
                mensagemDiv.id = tempId;
                mensagemDiv.innerHTML = `<p style="margin:0;">${conteudoMensagem.replace(/\n/g, '<br>')}</p><span class="timestamp">Enviando... (${dataAtual})</span>`;
                chatMessagesArea.appendChild(mensagemDiv);
                textarea.value = '';
                chatMessagesArea.scrollTop = chatMessagesArea.scrollHeight;
                
                try {
                    const response = await fetch('../controllers/enviar_mensagem_controller.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    const msgElement = document.getElementById(tempId);

                    if (result.success && msgElement) {
                        msgElement.querySelector('.timestamp').textContent = dataAtual;
                    } else if (msgElement) {
                        msgElement.querySelector('.timestamp').textContent = `Falha ao enviar (${dataAtual})`;
                        msgElement.classList.add('mensagem-falha'); 
                    }
                } catch (error) {
                    console.error('Erro ao enviar mensagem:', error);
                    const msgElement = document.getElementById(tempId);
                    if (msgElement) {
                         msgElement.querySelector('.timestamp').textContent = `Erro de rede (${dataAtual})`;
                         msgElement.classList.add('mensagem-falha');
                    }
                }
            });
        }

        const btnSalvarCadastroProdutoChat = document.getElementById('btnSalvarCadastroProdutoChat');
        if(btnSalvarCadastroProdutoChat) {
            btnSalvarCadastroProdutoChat.addEventListener('click', async function() {
                const form = document.getElementById('formCadastroProdutoChat');
                const formData = new FormData(form);
                const msgErroCadastro = document.getElementById('mensagemErroCadastroChat');
                const msgSucessoCadastro = document.getElementById('mensagemSucessoCadastroChat');

                msgErroCadastro.classList.add('d-none');
                msgSucessoCadastro.classList.add('d-none');

                try {
                    const response = await fetch('../controllers/cadastrar_produto.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        msgSucessoCadastro.textContent = result.message || 'Produto cadastrado com sucesso!';
                        msgSucessoCadastro.classList.remove('d-none');
                        form.reset();
                    } else {
                        msgErroCadastro.textContent = result.error || 'Erro desconhecido ao cadastrar produto.';
                        msgErroCadastro.classList.remove('d-none');
                    }
                } catch (error) {
                    console.error('Erro ao cadastrar produto via chat page:', error);
                    msgErroCadastro.textContent = 'Erro de comunicação ao cadastrar. Tente novamente.';
                    msgErroCadastro.classList.remove('d-none');
                }
            });
        }
    </script>
</body>
</html> 