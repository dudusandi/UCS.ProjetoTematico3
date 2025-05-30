<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/mensagem_dao.php';
require_once __DIR__ . '/../dao/cliente_dao.php';
require_once __DIR__ . '/../model/cliente.php';

$is_modal_view = isset($_GET['is_modal']) && $_GET['is_modal'] === 'true';

if (!$is_modal_view && !isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
} elseif ($is_modal_view && !isset($_SESSION['usuario_id'])) {
    echo "<p>Sessão expirada. Por favor, recarregue a página principal e tente novamente.</p>";
    exit();
}

if (!isset($_GET['usuario_id']) || !filter_var($_GET['usuario_id'], FILTER_VALIDATE_INT)) {
    if (!$is_modal_view) header('Location: minhas_mensagens.php');
    else echo "<p>ID de usuário inválido.</p>";
    exit();
}

$id_usuario_logado = (int)$_SESSION['usuario_id'];
$outro_usuario_id = (int)$_GET['usuario_id'];
$nome_outro_usuario_param = $_GET['nome_usuario'] ?? null;

if ($id_usuario_logado === $outro_usuario_id) {
    if (!$is_modal_view) header('Location: minhas_mensagens.php');
    else echo "<p>Não é possível conversar consigo mesmo.</p>";
    exit();
}

$mensagemDAO = new MensagemDAO();
$clienteDAO = new ClienteDAO();

$usuario_logado_obj = $clienteDAO->buscarPorId($id_usuario_logado);

if ($is_modal_view && $nome_outro_usuario_param) {
    $nome_outro_usuario = htmlspecialchars(urldecode($nome_outro_usuario_param));
} else {
    $outro_usuario = $clienteDAO->buscarPorId($outro_usuario_id);
    if (!$outro_usuario) {
        if (!$is_modal_view) {
            $_SESSION['erro_chat'] = "Usuário não encontrado.";
            header('Location: minhas_mensagens.php');
        } else {
            echo "<p>Usuário não encontrado.</p>";
        }
        exit();
    }
    $nome_outro_usuario = ($outro_usuario && method_exists($outro_usuario, 'getNome')) ? $outro_usuario->getNome() : "Usuário #{$outro_usuario_id}";
}

$mensagemDAO->marcarMensagensComoLidas($id_usuario_logado, $outro_usuario_id);
$mensagens_da_conversa = $mensagemDAO->buscarConversa($id_usuario_logado, $outro_usuario_id);

if (!$is_modal_view) {
    $nome_usuario = $_SESSION['usuario_nome'] ?? 'Usuário';
    $contador_mensagens_nao_lidas = 0;
    if ($id_usuario_logado) {
        try {
            $cliente_menu = $clienteDAO->buscarPorId($id_usuario_logado);
            if ($cliente_menu) {
                if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_nome'] !== $cliente_menu->getNome()) {
                     $_SESSION['usuario_nome'] = $cliente_menu->getNome();
                     $nome_usuario = $cliente_menu->getNome();
                }
            }
            $contador_mensagens_nao_lidas = $mensagemDAO->contarMensagensNaoLidas($id_usuario_logado);
        } catch (Exception $e) {
            error_log("Erro ao buscar dados para side-nav em chat.php: " . $e->getMessage());
        }
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
    <?php if (!$is_modal_view): ?>
    <link rel="stylesheet" href="dashboard.css">
    <?php endif; ?>
    <link rel="stylesheet" href="estilo_mensagens.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            <?php if ($is_modal_view): ?>
            overflow: hidden; /* Evitar scrollbars no próprio iframe se o conteúdo couber */
            <?php endif; ?>
        }
        body {
            display: flex;
            flex-direction: column;
            <?php if ($is_modal_view): ?>
            background-color: #f8f9fa; /* Cor de fundo suave para o chat no modal */
            <?php else: ?>
            min-height: 100vh; /* Para view normal, garantir altura mínima */
            <?php endif; ?>
        }
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 0; /* Essencial para flex children com overflow/scroll */
            <?php if (!$is_modal_view): ?>
            /* padding-top: 10px; // Estilo específico para view normal, se necessário */
            <?php else: ?>
            padding-top: 0;
            <?php endif; ?>
        }
        .products-section { /* Container do chat ou da página */
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            <?php if ($is_modal_view): ?>
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            <?php else: ?>
            /* Estilos para view normal, mantendo o que estava antes se aplicável */
            max-width: 70%; 
            margin-left: auto; 
            margin-right: auto; 
            padding-top: 15px;
            <?php endif; ?>
        }
        .chat-container-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            <?php if (!$is_modal_view): ?>
            height: calc(100vh - 70px); /* Altura para visualização normal */
            <?php endif; ?>
            /* Se for modal, a altura é determinada pelo flex-grow */
        }
        .chat-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden; /* CRUCIAL: impede que este container role ou se expanda além do seu espaço flexível */
            min-height: 0;   /* Permite encolher */
            background-color: #fff;
        }
        .chat-header {
            flex-shrink: 0; /* Não encolher */
            <?php if ($is_modal_view): ?>
            /* O header do chat não é mostrado no modal, o título do modal já serve. */
            /* Se fosse mostrado, teria padding menor: padding: 8px 12px; */
            /* font-size: 1rem; */
            <?php else: ?>
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            <?php endif; ?>
        }
        .mensagens-box {
            flex-grow: 1;
            overflow-y: auto;
            min-height: 0;
            word-break: break-word;
            padding: 10px 12px;
        }
        .area-envio-mensagem {
            flex-shrink: 0;
            border-top: 1px solid #dee2e6;
            <?php if ($is_modal_view): ?>
            padding: 8px 12px;
            background-color: #f8f9fa;
            <?php else: ?>
            padding: 10px 15px;
            /* background-color: #f1f1f1; // Exemplo de cor para view normal */
            <?php endif; ?>
        }
        .area-envio-mensagem form {
            display: flex; /* Mantém o layout flexível para textarea e botão */
            align-items: center; /* Alinha verticalmente os itens no centro */
            width: 100%; /* FAZ O FORMULÁRIO OCUPAR TODA A LARGURA DISPONÍVEL */
        }
        .area-envio-mensagem textarea {
            flex-grow: 1;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <?php if (!$is_modal_view): ?>
        <?php include __DIR__ . '/../menu.php'; ?>
    <?php endif; ?>

    <div class="main-content">
        <div class="products-section">
            <div class="chat-container-wrapper">
                <div class="chat-container">
                    <?php if (!$is_modal_view): ?>
                    <div class="chat-header">
                        <h5>Chat com <?php echo htmlspecialchars($nome_outro_usuario); ?></h5>
                    </div>
                    <?php endif; ?>

                    <div class="mensagens-box" id="chat-messages-area">
                        <?php if (empty($mensagens_da_conversa)): ?>
                            <p class="sem-mensagens">Nenhuma mensagem ainda. Seja o primeiro a enviar!</p>
                        <?php else: ?>
                            <?php foreach ($mensagens_da_conversa as $msg): ?>
                                <?php
                                $classe_css = ($msg->getRemetenteId() == $id_usuario_logado) ? 'enviada' : 'recebida';
                                $data_formatada = date("d/m/Y H:i", strtotime($msg->getDataEnvio()));
                                ?>
                                <div class="mensagem <?php echo $classe_css; ?>" data-mensagem-id="<?php echo $msg->getId(); /* Ou $msg['id'] se for array */ ?>">
                                    <p style="margin:0;"><?php echo nl2br(htmlspecialchars($msg->getConteudo())); ?></p>
                                    <span class="timestamp"><?php echo $data_formatada; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="area-envio-mensagem">
                        <form id="chatForm" action="../controllers/enviar_mensagem_controller.php" method="POST">
                            <textarea name="conteudo" placeholder="Digite sua mensagem..." rows="2" required></textarea>
                            <input type="hidden" name="destinatario_id" value="<?php echo htmlspecialchars($outro_usuario_id); ?>">
                            <input type="hidden" name="remetente_id" value="<?php echo htmlspecialchars($id_usuario_logado); ?>">
                            <button type="submit" class="btn btn-primary">Enviar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.usuarioLogadoId = <?php echo json_encode($id_usuario_logado); ?>;
        window.isAdmin = <?php echo json_encode(isset($_SESSION['is_admin']) && $_SESSION['is_admin']); ?>;
        const outroUsuarioId = <?php echo json_encode($outro_usuario_id); ?>;
        const isModalView = <?php echo json_encode($is_modal_view); ?>;
        let ultimaMensagemIdConhecida = 0;
        const chatMessagesArea = document.getElementById('chat-messages-area');

        window.onload = function() {
            const params = new URLSearchParams(window.location.search);
            const scrollToBottomParam = params.get('scroll_to_bottom');

            // Pega o ID da última mensagem renderizada inicialmente
            const todasAsMensagens = chatMessagesArea ? chatMessagesArea.querySelectorAll('.mensagem[data-mensagem-id]') : [];
            if (todasAsMensagens.length > 0) {
                ultimaMensagemIdConhecida = parseInt(todasAsMensagens[todasAsMensagens.length - 1].getAttribute('data-mensagem-id'));
            }
            console.log('[Chat.php] ID da última mensagem conhecida inicialmente:', ultimaMensagemIdConhecida);

            function doScroll() {
                if(chatMessagesArea) {
                    chatMessagesArea.scrollTop = chatMessagesArea.scrollHeight;
                    console.log('[Chat.php] Tentativa de rolagem para o final. ScrollTop:', chatMessagesArea.scrollTop, 'ScrollHeight:', chatMessagesArea.scrollHeight);
                } else {
                    console.warn('[Chat.php] Área de mensagens (chat-messages-area) não encontrada para rolagem.');
                }
            }

            if (scrollToBottomParam === 'true') {
                // Tenta rolar imediatamente e depois com um pequeno delay para garantir
                doScroll(); 
                setTimeout(doScroll, 100); // Delay de 100ms
                setTimeout(doScroll, 300); // Delay adicional para casos mais lentos
            } else {
                 // Comportamento padrão (que já rola para o final)
                doScroll();
            }
        };

        const chatForm = document.getElementById('chatForm');
        if (chatForm) {
            chatForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(chatForm);
                const textarea = chatForm.querySelector('textarea[name="conteudo"]');
                const conteudoMensagem = textarea.value.trim();

                if (!conteudoMensagem || !chatMessagesArea) return;

                const tempId = 'temp_' + Date.now();
                const dataAtual = new Date().toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'});

                const mensagemDiv = document.createElement('div');
                mensagemDiv.classList.add('mensagem', 'enviada');
                mensagemDiv.id = tempId;
                // Usar textContent para segurança e nl2br manual para quebra de linha
                const p = document.createElement('p');
                p.style.margin = '0';
                p.innerHTML = conteudoMensagem.replace(/\n/g, '<br>'); // nl2br via JS
                mensagemDiv.appendChild(p);

                const span = document.createElement('span');
                span.classList.add('timestamp');
                span.textContent = `Enviando... (${dataAtual})`;
                mensagemDiv.appendChild(span);
                
                chatMessagesArea.appendChild(mensagemDiv);
                textarea.value = '';
                chatMessagesArea.scrollTop = chatMessagesArea.scrollHeight;

                try {
                    const response = await fetch('../controllers/enviar_mensagem_controller.php', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    const result = await response.json();
                    const msgElement = document.getElementById(tempId);

                    if (result.success && msgElement) {
                        if (result.mensagem_id) { // Verificar se o ID da mensagem foi retornado
                            msgElement.setAttribute('data-mensagem-id', result.mensagem_id);
                            msgElement.querySelector('.timestamp').textContent = dataAtual; // Manter o timestamp local por enquanto
                            msgElement.classList.remove('mensagem-falha');
                            // msgElement.classList.add('mensagem-sucesso'); // Opcional

                            const novoId = parseInt(result.mensagem_id);
                            if (novoId > ultimaMensagemIdConhecida) {
                                ultimaMensagemIdConhecida = novoId;
                                console.log(`[Chat.php] Mensagem enviada ID ${novoId}. ultimaMensagemIdConhecida atualizado para: ${ultimaMensagemIdConhecida}`);
                            }
                        } else {
                            console.warn('[Chat.php] Mensagem enviada com sucesso, mas o ID da mensagem não foi retornado pelo controller. A mensagem pode ser duplicada pelo polling.');
                            msgElement.querySelector('.timestamp').textContent = dataAtual + " (Enviada)";
                            msgElement.classList.remove('mensagem-falha');
                        }
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

        // Auto-ajuste da altura da textarea
        const textarea = document.querySelector('.area-envio-mensagem textarea');
        if (textarea) {
            textarea.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Enviar mensagem com Enter (Shift+Enter para nova linha)
            textarea.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault(); // Previne nova linha no textarea
                    // Dispara o evento submit do formulário
                    if (chatForm) {
                        // Verifica se o conteúdo não é apenas espaços em branco antes de enviar
                        if (this.value.trim() !== '') {
                            chatForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                        } else {
                            // Opcional: limpar o textarea se só tiver espaços e o usuário tentar enviar
                            this.value = ''; 
                        }
                    }
                }
            });
        }

        // Função para buscar e renderizar novas mensagens
        async function buscarEAdicionarNovasMensagens() {
            if (!window.usuarioLogadoId || !outroUsuarioId) return;

            console.log(`[Chat.php] Polling: Buscando novas. Último ID conhecido: ${ultimaMensagemIdConhecida}, Outro Usuário: ${outroUsuarioId}`);
            try {
                const response = await fetch(`../controllers/get_novas_mensagens_controller.php?outro_usuario_id=${outroUsuarioId}&ultimo_id_conhecido=${ultimaMensagemIdConhecida}`);
                
                const responseText = await response.clone().text(); 
                console.log('[Chat.php] Polling: Resposta bruta:', responseText);

                const data = await response.json();
                console.log('[Chat.php] Polling: Dados JSON parseados:', data);

                if (data.success && data.mensagens && data.mensagens.length > 0) {
                    console.log(`[Chat.php] Polling: ${data.mensagens.length} nova(s) mensagem(ns) recebida(s).`);
                    const chatMessagesArea = document.getElementById('chat-messages-area');
                    let novasMensagensAdicionadas = false;

                    data.mensagens.forEach(msg => {
                        if (document.querySelector(`.mensagem[data-mensagem-id="${msg.id}"]`)) return;
                        console.log('[Chat.php] Polling: Adicionando mensagem ID:', msg.id, 'Conteúdo:', msg.conteudo);

                        const mensagemDiv = document.createElement('div');
                        mensagemDiv.classList.add('mensagem');
                        mensagemDiv.classList.add(parseInt(msg.remetente_id) === parseInt(window.usuarioLogadoId) ? 'enviada' : 'recebida');
                        mensagemDiv.setAttribute('data-mensagem-id', msg.id);
                        
                        const p = document.createElement('p');
                        p.style.margin = '0';
                        p.innerHTML = msg.conteudo.replace(/\n/g, '<br>');
                        mensagemDiv.appendChild(p);

                        const span = document.createElement('span');
                        span.classList.add('timestamp');
                        span.textContent = msg.data_formatada;
                        mensagemDiv.appendChild(span);
                        
                        chatMessagesArea.appendChild(mensagemDiv);
                        novasMensagensAdicionadas = true;
                        ultimaMensagemIdConhecida = Math.max(ultimaMensagemIdConhecida, parseInt(msg.id));
                    });

                    if (novasMensagensAdicionadas) {
                        chatMessagesArea.scrollTop = chatMessagesArea.scrollHeight;
                        console.log('[Chat.php] Polling: Novas mensagens adicionadas. Último ID agora:', ultimaMensagemIdConhecida);
                        
                        if (typeof window.parent.carregarNotificacoesAPI === 'function') {
                            window.parent.carregarNotificacoesAPI();
                        }
                    }
                } else if (data.success && data.mensagens && data.mensagens.length === 0) {
                    // console.log('[Chat.php] Polling: Nenhuma mensagem nova.'); // Opcional
                } else if (!data.success && data.error) {
                    console.warn('[Chat.php] Polling: Erro da API ao buscar novas mensagens:', data.error);
                }
            } catch (error) {
                console.error('[Chat.php] Polling: Erro de rede ou JSON.parse ao buscar novas mensagens:', error);
            }
        }

        // Iniciar o polling para novas mensagens
        if (window.usuarioLogadoId && outroUsuarioId) {
            setInterval(buscarEAdicionarNovasMensagens, 3000); // Verificar a cada 3 segundos
            console.log('[Chat.php] Polling para novas mensagens iniciado.');
        }

    </script>
</body>
</html> 