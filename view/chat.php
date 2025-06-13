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
        html,body{height:100%;margin:0;overflow:hidden;}
        .container-fluid {
            height: 100%;
            margin: 0;
            padding: 0;
            <?php if ($is_modal_view): ?>
            background-color: #f8f9fa; 
            <?php else: ?>
            min-height: 100vh; 
            <?php endif; ?>
        }
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            <?php if ($is_modal_view): ?>
            height: 100%;
            margin: 0;
            padding: 0;
            <?php endif; ?>
        }
        .products-section { 
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            <?php if ($is_modal_view): ?>
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            <?php else: ?>
            
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
            height: calc(100vh - 70px);
            <?php else: ?>
            height: 100%;
            <?php endif; ?>
            border:none !important;
        }
        .chat-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden; 
            min-height: 0;   
            background-color: transparent;
            border:none !important;
        }
        .chat-header {
            flex-shrink: 0; 
            <?php if ($is_modal_view): ?>
            
            
            
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
            padding: 4px 16px;
            background: transparent;
        }
        .area-envio-mensagem {
            flex-shrink: 0;
            border-top: 1px solid #dee2e6;
            <?php if ($is_modal_view): ?>
            padding: 12px 16px;
            background-color: #ffffff;
            <?php else: ?>
            padding: 10px 15px;
            
            <?php endif; ?>
        }
        .area-envio-mensagem form {
            display: flex; 
            align-items: center; 
            width: 100%; 
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
                                <div class="mensagem <?php echo $classe_css; ?>" data-mensagem-id="<?php echo $msg->getId();  ?>">
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
                
                doScroll(); 
                setTimeout(doScroll, 100); 
                setTimeout(doScroll, 300); 
            } else {
                 
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
                
                const p = document.createElement('p');
                p.style.margin = '0';
                p.innerHTML = conteudoMensagem.replace(/\n/g, '<br>'); 
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
                        if (result.mensagem_id) { 
                            msgElement.setAttribute('data-mensagem-id', result.mensagem_id);
                            msgElement.querySelector('.timestamp').textContent = dataAtual; 
                            msgElement.classList.remove('mensagem-falha');
                            

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

        
        const textarea = document.querySelector('.area-envio-mensagem textarea');
        if (textarea) {
            textarea.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            
            textarea.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault(); 
                    
                    if (chatForm) {
                        
                        if (this.value.trim() !== '') {
                            chatForm.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                        } else {
                            
                            this.value = ''; 
                        }
                    }
                }
            });
        }

        
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
                    
                } else if (!data.success && data.error) {
                    console.warn('[Chat.php] Polling: Erro da API ao buscar novas mensagens:', data.error);
                }
            } catch (error) {
                console.error('[Chat.php] Polling: Erro de rede ou JSON.parse ao buscar novas mensagens:', error);
            }
        }

        
        if (window.usuarioLogadoId && outroUsuarioId) {
            setInterval(buscarEAdicionarNovasMensagens, 3000); 
            console.log('[Chat.php] Polling para novas mensagens iniciado.');
        }

    </script>
</body>
</html> 