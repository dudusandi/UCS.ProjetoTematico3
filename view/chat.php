<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/mensagem_dao.php';
require_once __DIR__ . '/../dao/cliente_dao.php'; // Usando ClienteDAO
require_once __DIR__ . '/../model/cliente.php';   // Usando Cliente model

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php'); // Ajuste para sua página de login
    exit();
}

if (!isset($_GET['usuario_id']) || !filter_var($_GET['usuario_id'], FILTER_VALIDATE_INT)) {
    // Se o ID do outro usuário não for fornecido ou não for um inteiro, redireciona ou mostra erro
    header('Location: minhas_mensagens.php'); // Volta para a lista de conversas
    exit();
}

$usuario_logado_id = (int)$_SESSION['usuario_id'];
$outro_usuario_id = (int)$_GET['usuario_id'];

if ($usuario_logado_id === $outro_usuario_id) {
    // Não permitir que o usuário converse consigo mesmo
    header('Location: minhas_mensagens.php');
    exit();
}

$mensagemDAO = new MensagemDAO();
$clienteDAO = new ClienteDAO();

// Buscar detalhes do usuário logado
$usuario_logado = $clienteDAO->buscarPorId($usuario_logado_id);
$nome_usuario_logado = ($usuario_logado && method_exists($usuario_logado, 'getNome')) ? $usuario_logado->getNome() : "Você";

// Buscar detalhes do outro usuário
$outro_usuario = $clienteDAO->buscarPorId($outro_usuario_id);
if (!$outro_usuario) {
    // Se o outro usuário não existir, volta para a lista de conversas
    // Ou pode mostrar uma mensagem de erro mais amigável
    $_SESSION['erro_chat'] = "Usuário não encontrado.";
    header('Location: minhas_mensagens.php');
    exit();
}
$nome_outro_usuario = ($outro_usuario && method_exists($outro_usuario, 'getNome')) ? $outro_usuario->getNome() : "Usuário #{$outro_usuario_id}";

// Marcar mensagens desta conversa como lidas (mensagens que o usuário logado recebeu do outro usuário)
$mensagemDAO->marcarMensagensComoLidas($usuario_logado_id, $outro_usuario_id);

// Buscar todas as mensagens da conversa
$mensagens_da_conversa = $mensagemDAO->buscarConversa($usuario_logado_id, $outro_usuario_id);

// Incluir seu cabeçalho padrão aqui, se houver
// Exemplo: include_once __DIR__ . '/layout/header.php';

// --- Lógica do dashboard para header e nav-bar (adaptada de minhas_mensagens.php) ---
// ProdutoDAO não é diretamente necessário para o chat em si, mas incluído por consistência se o header/nav o esperar
// require_once __DIR__ . '/../dao/produto_dao.php'; 

$nome_usuario_display_header = "Usuário"; // Default para o header
$contador_mensagens_nao_lidas_header = 0; // Para o ícone de sino no header

// $usuario_logado_id já está definido acima
if (isset($_SESSION['usuario_id'])) { // Redundante, mas mantém a estrutura original
    try {
        // ClienteDAO já instanciado como $clienteDAO_chat
        $cliente_logado_info = $clienteDAO->buscarPorId($usuario_logado_id);
        if ($cliente_logado_info) {
            if (!isset($_SESSION['usuario_nome'])) {
                 $_SESSION['usuario_nome'] = $cliente_logado_info->getNome();
            }
            $nome_usuario_display_header = $_SESSION['usuario_nome'];
        }

        // MensagemDAO já instanciado como $mensagemDAO_chat ou podemos usar $mensagemDAO_header de minhas_mensagens.php
        // Para evitar recriar, e se o escopo for o mesmo, poderíamos usar $mensagemDAO_chat.
        // Mas para manter a lógica do header separada, pode-se criar um novo se necessário ou passar a instância.
        // Aqui vamos assumir que o $contador_mensagens_nao_lidas_header é para o sino e é melhor recalcular ou ter uma instância separada.
        $mensagemDAO_header_specific = new MensagemDAO();
        $contador_mensagens_nao_lidas_header = $mensagemDAO_header_specific->contarMensagensNaoLidas($usuario_logado_id);

        // O contador para o botão "Minhas Mensagens" na nav-bar ($contador_mensagens_nao_lidas)
        // pode ser o mesmo $contador_mensagens_nao_lidas_header aqui, já que ambos são para o usuário logado.
        $contador_mensagens_nao_lidas_nav = $contador_mensagens_nao_lidas_header;

    } catch (Exception $e) {
        error_log("Erro ao buscar dados para o header em chat.php: " . $e->getMessage());
    }
}
// --- Fim da lógica do dashboard ---

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat com <?php echo htmlspecialchars($nome_outro_usuario); ?> - ECOxchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard.css"> <!-- CSS do Dashboard -->
    <link rel="stylesheet" href="estilo_mensagens.css"> <!-- CSS específico para mensagens e chat -->
    <style>
        /* Estilos para o botão ativo na nav-bar (se necessário aqui) */
        .nav-bar .btn.active { 
            background-color: #0dcaf0;
            color: white;
        }
        /* Ajustes para o layout do chat dentro do novo container */
        .products-section .chat-container-wrapper {
            /* background-color: #fff;  O chat-container interno já tem seu fundo */
            padding: 0; /* Remover padding se .products-section já tem */
            border-radius: 8px;
            /* box-shadow: 0 2px 4px rgba(0,0,0,0.1); */ /* Sombra já no chat-container interno */
            height: calc(100vh - 180px); /* Ajustar altura para caber header/nav + um pouco de margem */
            display: flex; /* Para o chat-container ocupar toda a altura */
            flex-direction: column;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ECO<span>xchange</span></div>
        <div class="search-bar">
            <form id="searchForm" class="d-flex" method="GET" action="dashboard.php">
                <input type="text" id="searchInput" name="termo" placeholder="Pesquisar produtos..." value="">
                <button type="submit" class="btn-search-custom"><i class="bi bi-search"></i></button>
            </form>
        </div>
        <div class="user-options">
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <div class="dropdown me-2" id="notificacoesDropdownContainer" style="display: inline-block;">
                    <button class="btn btn-outline-secondary position-relative" type="button" id="notificacoesDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <span id="contadorNotificacoes" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?php echo $contador_mensagens_nao_lidas_header > 0 ? '' : 'd-none'; ?>">
                            <?php echo $contador_mensagens_nao_lidas_header; ?>
                            <span class="visually-hidden">notificações não lidas</span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificacoesDropdownBtn" id="listaNotificacoesDropdown">
                        <li><h6 class="dropdown-header">Notificações</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="notificacaoItemLoading" class="dropdown-item text-muted">Carregando...</li>
                        <li id="notificacaoItemNenhuma" class="dropdown-item text-muted d-none">Nenhuma notificação nova.</li>
                        <li><hr class="dropdown-divider d-none" id="notificacoesDividerFinal"></li>
                        <li><a class="dropdown-item text-center d-none" href="#" id="verTodasNotificacoesLink">Ver todas</a></li> 
                        <li><a class="dropdown-item text-center d-none" href="#" id="marcarTodasLidasLink" onclick="marcarTodasComoLidasClientSide(event)">Marcar todas como lidas</a></li>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['usuario_nome'])): ?>
                <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
                <a href="../controllers/logout_controller.php">Sair</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">Login</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="nav-bar">
        <a href="minhas_mensagens.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left-circle"></i> Voltar para Conversas
        </a>
        <a href="dashboard.php" class="btn btn-outline-info ms-2">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <a href="../view/listar_clientes.php" class="btn btn-outline-primary ms-2">
                <i class="bi bi-people"></i> Editar Clientes
            </a> 
        <?php endif; ?>
    </div>

    <div class="products-section container"> 
        <div class="chat-container-wrapper"> <!-- Novo wrapper para controlar altura e layout -->
            <div class="chat-container"> <!-- chat-container original, agora filho do wrapper -->
                <div class="chat-header">
                    <!-- Título já definido em estilo_mensagens.css, aqui apenas o nome -->
                    <h5>Chat com <?php echo htmlspecialchars($nome_outro_usuario); ?></h5>
                </div>

                <div class="mensagens-box" id="chat-messages-area"> <!-- Usando .mensagens-box de estilo_mensagens.css -->
                    <?php if (empty($mensagens_da_conversa)): ?>
                        <p class="sem-mensagens">Nenhuma mensagem ainda. Seja o primeiro a enviar!</p> <!-- Usando .sem-mensagens -->
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

                <div class="area-envio-mensagem"> <!-- Usando .area-envio-mensagem de estilo_mensagens.css -->
                    <form action="../controllers/enviar_mensagem_controller.php" method="POST" style="display:flex; width:100%;">
                        <textarea name="conteudo" placeholder="Digite sua mensagem..." rows="2" required></textarea>
                        <input type="hidden" name="destinatario_id" value="<?php echo htmlspecialchars($outro_usuario_id); ?>">
                        <input type="hidden" name="remetente_id" value="<?php echo htmlspecialchars($usuario_logado_id); ?>">
                        <button type="submit" class="btn btn-primary">Enviar</button> <!-- Adicionando classe Bootstrap -->
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.usuarioLogadoId = <?php echo json_encode(isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null); ?>;
        window.isAdmin = <?php echo json_encode(isset($_SESSION['is_admin']) && $_SESSION['is_admin']); ?>;

        function marcarTodasComoLidasClientSide(event) {
            event.preventDefault();
            const contadorNotificacoes = document.getElementById('contadorNotificacoes');
            const listaNotificacoes = document.getElementById('listaNotificacoesDropdown');
            if (contadorNotificacoes) {
                contadorNotificacoes.classList.add('d-none');
                contadorNotificacoes.textContent = '0';
            }
            const itensNotificacao = listaNotificacoes.querySelectorAll('.notificacao-nao-lida');
            itensNotificacao.forEach(item => item.classList.remove('notificacao-nao-lida'));
            document.getElementById('notificacaoItemLoading').classList.add('d-none');
            document.getElementById('notificacaoItemNenhuma').classList.remove('d-none');
            const marcarLidasLink = document.getElementById('marcarTodasLidasLink');
            const verTodasLink = document.getElementById('verTodasNotificacoesLink');
            if(marcarLidasLink) marcarLidasLink.classList.add('d-none');
            if(verTodasLink) verTodasLink.classList.add('d-none');
        }

        const chatMessagesArea = document.getElementById('chat-messages-area');
        if(chatMessagesArea) {
            chatMessagesArea.scrollTop = chatMessagesArea.scrollHeight;
        }
        
        // Adaptação para o envio de formulário AJAX (opcional, mas melhora UX)
        const chatForm = document.querySelector('.area-envio-mensagem form');
        if (chatForm) {
            chatForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(chatForm);
                const textarea = chatForm.querySelector('textarea[name="conteudo"]');
                const conteudoMensagem = textarea.value.trim();

                if (!conteudoMensagem) return; // Não envia mensagem vazia

                // Adiciona a mensagem à UI instantaneamente (otimista)
                const agora = new Date();
                const dataFormatada = `${agora.getDate().toString().padStart(2, '0')}/${(agora.getMonth()+1).toString().padStart(2, '0')}/${agora.getFullYear()} ${agora.getHours().toString().padStart(2, '0')}:${agora.getMinutes().toString().padStart(2, '0')}`;
                
                const novaMensagemDiv = document.createElement('div');
                novaMensagemDiv.classList.add('message', 'sent'); // 'mensagem', 'enviada' conforme estilo_mensagens.css
                novaMensagemDiv.innerHTML = `<p style="margin:0;">${conteudoMensagem.replace(/\n/g, '<br>')}</p><span class="timestamp">${dataFormatada}</span>`;
                chatMessagesArea.appendChild(novaMensagemDiv);
                chatMessagesArea.scrollTop = chatMessagesArea.scrollHeight; // Rola para a nova mensagem
                textarea.value = ''; // Limpa o textarea
                textarea.focus();

                try {
                    const response = await fetch(chatForm.action, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json(); // Espera-se um JSON do controller
                    if (!result.success) {
                        console.error('Erro ao enviar mensagem:', result.error);
                        // Poderia remover a mensagem otimista ou marcar como erro
                        novaMensagemDiv.style.opacity = '0.5'; 
                        novaMensagemDiv.title = 'Erro ao enviar: ' + result.error;
                    } else {
                        // Mensagem enviada com sucesso, ID retornado pelo servidor
                        if(result.mensagem_id) {
                           novaMensagemDiv.dataset.id = result.mensagem_id; // Armazena o ID se necessário
                        }
                    }
                } catch (error) {
                    console.error('Falha na requisição de envio de mensagem:', error);
                    novaMensagemDiv.style.opacity = '0.5';
                    novaMensagemDiv.title = 'Falha ao enviar. Verifique sua conexão.';
                }
            });
        }
    </script>
</body>
</html> 