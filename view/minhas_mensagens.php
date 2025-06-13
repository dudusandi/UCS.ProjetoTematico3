<?php 
require_once __DIR__ . '/../controllers/mensagens_controller.php'; 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/cliente_dao.php';
require_once __DIR__ . '/../dao/mensagem_dao.php'; 

$nome_usuario = "Usuário"; 
$contador_mensagens_nao_lidas = 0; 
$id_usuario_logado = $_SESSION['usuario_id'] ?? null;

if ($id_usuario_logado) {
    try {
        $clienteDAO = new ClienteDAO();
        $cliente = $clienteDAO->buscarPorId($id_usuario_logado);
        if ($cliente) {
            $nome_usuario = $cliente->getNome();
            if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_nome'] !== $nome_usuario) {
                 $_SESSION['usuario_nome'] = $nome_usuario;
            }
        }

        $mensagemDAO_nav = new MensagemDAO(); 
        $contador_mensagens_nao_lidas = $mensagemDAO_nav->contarMensagensNaoLidas($id_usuario_logado);

    } catch (Exception $e) {
        error_log("Erro ao buscar dados para side-nav em minhas_mensagens.php: " . $e->getMessage());
    }
} else {
    
    
    
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Mensagens - ECOxChange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="dashboard.css"> 
    <link rel="stylesheet" href="estilo_mensagens.css"> 
    <style>
        body {
            display: flex; 
            min-height: 100vh;
        }
        .main-content {
            flex-grow: 1; 
            padding: 0; 
        }
        
        .main-content > .search-bar-container,
        .main-content > .info-banner-messages,
        .main-content > .messages-section-container {
             margin-left: 20px;
             margin-right: 20px;
        }
        .main-content > .messages-section-container {
            padding-top: 20px; 
            padding-bottom: 20px;
        }

        
        .search-bar-container {
            padding: 20px 0; 
             
            display: flex;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 0; 
            margin-bottom: 15px; 
        }
        .search-bar-messages-style { 
            display: flex;
            align-items: center;
            width: 50%; 
            max-width: 600px; 
            background-color: #e8eaed; 
            border-radius: 28px; 
            padding: 8px 12px 8px 20px; 
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.1), 0 1px 4px 0 rgba(0,0,0,0.08);
        }
        .search-bar-messages-style input[type="text"] {
            flex-grow: 1;
            border: none;
            background: transparent;
            outline: none;
            font-size: 16px;
            color: #3c4043; 
            margin-right: 8px;
        }
        .search-bar-messages-style input[type="text"]::placeholder {
            color: #5f6368; 
        }
        .search-bar-messages-style button {
            border: none;
            background: transparent;
            color: #5f6368; 
            padding: 8px;
            border-radius: 50%; 
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .search-bar-messages-style button i {
            font-size: 20px;
        }
        .search-bar-messages-style button:hover {
            background-color: rgba(0,0,0,0.05); 
        }

        .info-banner-messages { 
             
            padding: 15px 20px;
            border-radius: 8px; 
            margin-bottom: 20px;
            text-align: center;
            border: none; 
        }
        .info-banner-messages p {
            margin-bottom: 0;
            font-size: 0.9rem; 
            color: #c6c9cb;    
            line-height: 1.4;  
        }

        .card-conversa {
            cursor: pointer;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border-radius: 15px; 
            overflow: hidden; 
             
            
            
        }
        .card-conversa:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        .card-conversa.nao-lida {
            
        }
        .card-conversa .card-body {
            display: flex;
            flex-direction: column;
            align-items: center; 
            text-align: center;
            padding: 1.25rem 1rem; 
        }
        .card-conversa .icon-conversa {
            font-size: 3rem; 
            margin-bottom: 0.75rem;
            color: var(--bs-secondary); 
        }
        .card-title-conversa {
             white-space: nowrap;
             overflow: hidden;
             text-overflow: ellipsis;
             font-weight: 600; 
             margin-bottom: 0.5rem; 
        }
        .ultima-msg-texto.small {
            display: inline-block; 
            max-width: 95%;     
            background-color: #f1f1f1; 
            color: #333; 
            padding: 8px 12px; 
            border-radius: 10px; 
            font-size: 0.85em; 
            line-height: 1.4; 
            margin-top: 0.75rem; 
            text-align: left; 
            word-wrap: break-word; 
            
            
        }

        .card-conversa .badge.bg-danger {
            display: none;
        }

            max-width: 800px;
            height: 85vh; 
        }

            display: flex;
            flex-direction: column;
            height: 100%; 
        }

            padding: 0; 
            flex-grow: 1; 
            min-height: 0; 
            overflow: hidden; 
        }

            width: 100%;
            height: 100%; 
            border: none;
            box-sizing: border-box; 
        }

        #chatModal .modal-dialog {
            margin: 0;
            width: 100vw;
            max-width: 100vw;
        }

        #chatModal .modal-dialog.modal-lg-chat {
            width: 100vw;
        }

        #chatModal .modal-content {
            height: 90vh;
            border: none;
            border-radius: 0;
            box-shadow: none;
            padding: 0;
        }

        #chatModal .modal-header {
            border-bottom: 1px solid #e9ecef;
        }

        #chatModal iframe {
            background: #ffffff;
        }

        #chatIframe {
            width: 100%;
            height: 100%;
        }

        #chatModal .modal-body {
            padding: 0 !important;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../menu.php'; ?>

    <div class="main-content">

        <div class="search-bar-container">
            <div class="search-bar-messages-style"> <!-- Wrapper adicionado -->
                <form id="searchConversasForm" onsubmit="return false;" style="display: flex; flex-grow: 1;">
                    <input type="text" id="searchConversasInput" class="form-control" placeholder="Buscar conversa por nome...">
                    <button type="button" id="searchConversasButton" class="btn">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="info-banner-messages">
            <p>Mantenha uma comunicação clara e respeitosa. Boas conversas fortalecem nossa comunidade e facilitam trocas bem-sucedidas!</p>
        </div>
        
        <div class="messages-section-container container-fluid"> 
            <h3 class="mb-4 visually-hidden">Minhas Mensagens</h3> <!-- Título pode ser oculto se a busca e banner já contextualizam -->
            
            <div class="container-mensagens-cards"> <!-- Renomeado para evitar conflito com estilo de lista antigo -->
                <?php if (!empty($conversas_formatadas)): ?>
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4" id="listaConversasCards"> 
                        <?php foreach ($conversas_formatadas as $conversa): ?>
                            <div class="col card-conversa-col">
                                <div class="card h-100 card-conversa <?php echo $conversa['nao_lida'] ? 'nao-lida' : ''; ?>" 
                                     data-usuario-id="<?php echo htmlspecialchars($conversa['outro_usuario_id']); ?>"
                                     data-usuario-nome="<?php echo htmlspecialchars($conversa['nome_outro_usuario']); ?>">
                                    <div class="card-body">
                                        <i class="bi bi-person-circle icon-conversa"></i>
                                        <h5 class="card-title mb-0 card-title-conversa" title="<?php echo htmlspecialchars($conversa['nome_outro_usuario']); ?>">
                                            <?php echo htmlspecialchars($conversa['nome_outro_usuario']); ?>
                                        </h5>
                                        <p class="card-text ultima-msg-texto small">
                                            <?php 
                                            $ultima_msg = $conversa['ultima_mensagem'];
                                            
                                            if (function_exists('mb_strimwidth')) {
                                                echo htmlspecialchars(mb_strimwidth($ultima_msg, 0, 100, "..."));
                                            } else {
                                                echo htmlspecialchars(substr($ultima_msg, 0, 97) . (strlen($ultima_msg) > 100 ? "..." : ""));
                                            }
                                            ?>
                                        </p>
                                        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="excluirConversa('<?php echo $conversa['outro_usuario_id']; ?>', event)">Excluir</button>
                                        <?php if ($conversa['nao_lida']): ?>
                                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle p-2 border border-light rounded-circle" title="Nova mensagem!">
                                                <span class="visually-hidden">Nova mensagem!</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                     <div id="nenhumaConversaEncontrada" class="alert alert-info mt-4 d-none" role="alert">
                        Nenhuma conversa encontrada com esse nome.
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

    <!-- Modal para o Chat -->
    <div class="modal fade" id="chatModal" tabindex="-1" aria-labelledby="chatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-lg-chat">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="chatModalLabel">Chat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe id="chatIframe" src="" title="Chat"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir esta conversa?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnConfirmDeleteConversa">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.usuarioLogadoId = <?php echo json_encode($id_usuario_logado); ?>;

        
        let conversaParaExcluirId = null;

        
        function excluirConversa(usuarioId, event) {
            event.stopPropagation();
            event.preventDefault();
            conversaParaExcluirId = usuarioId;
            const modalDel = bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmDeleteModal'));
            modalDel.show();
        }
        
        window.excluirConversa = excluirConversa;

        document.addEventListener('DOMContentLoaded', () => {
            const chatModal = document.getElementById('chatModal');
            const chatIframe = document.getElementById('chatIframe');
            const modalTitle = document.getElementById('chatModalLabel');

            if (chatModal) {
                chatModal.addEventListener('show.bs.modal', function (event) {
                    const card = event.relatedTarget;
                    const usuarioId = card.getAttribute('data-usuario-id');
                    const usuarioNome = card.getAttribute('data-usuario-nome');

                    if (modalTitle) {
                        modalTitle.textContent = 'Chat com ' + usuarioNome;
                    }
                    if (chatIframe) {
                        chatIframe.src = `chat.php?usuario_id=${usuarioId}&nome_usuario=${encodeURIComponent(usuarioNome)}&is_modal=true&scroll_to_bottom=true`;
                    }
                });

                chatModal.addEventListener('hidden.bs.modal', function () {
                    if (chatIframe) {
                        chatIframe.src = 'about:blank'; 
                    }
                    
                    
                });
            }

            
            const searchInput = document.getElementById('searchConversasInput');
            const searchButton = document.getElementById('searchConversasButton'); 
            const conversasContainer = document.getElementById('listaConversasCards');
            const nenhumaConversaMsg = document.getElementById('nenhumaConversaEncontrada');

            function filtrarConversas() {
                if (!conversasContainer) return; 
                
                const termoBusca = searchInput.value.toLowerCase().trim();
                const cardsConversa = conversasContainer.querySelectorAll('.card-conversa-col');
                let encontradas = 0;

                cardsConversa.forEach(cardCol => {
                    const card = cardCol.querySelector('.card-conversa');
                    const nomeUsuario = card.getAttribute('data-usuario-nome').toLowerCase();
                    
                    if (nomeUsuario.includes(termoBusca)) {
                        cardCol.style.display = ''; 
                        encontradas++;
                    } else {
                        cardCol.style.display = 'none'; 
                    }
                });

                if (nenhumaConversaMsg) {
                    if (encontradas === 0 && termoBusca !== '') {
                        nenhumaConversaMsg.classList.remove('d-none');
                    } else {
                        nenhumaConversaMsg.classList.add('d-none');
                    }
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', filtrarConversas);
            }
            
            
            
            

            
            console.log('[Minhas Mensagens] Verificando se precisa abrir modal via URL...');
            const params = new URLSearchParams(window.location.search);
            const conversaIdParaAbrir = params.get('abrir_conversa_id');
            console.log('[Minhas Mensagens] ID da conversa para abrir via URL:', conversaIdParaAbrir);

            if (conversaIdParaAbrir) {
                console.log('[Minhas Mensagens] Elementos do Modal: chatModal:', !!chatModal, 'chatIframe:', !!chatIframe, 'modalTitle:', !!modalTitle);
                if (chatModal && chatIframe && modalTitle) {
                    const cardParaAbrir = document.querySelector(`.card-conversa[data-usuario-id="${conversaIdParaAbrir}"]`);
                    console.log('[Minhas Mensagens] Card encontrado para o ID:', conversaIdParaAbrir, 'foi:', cardParaAbrir);

                    if (cardParaAbrir) {
                        const usuarioNome = cardParaAbrir.getAttribute('data-usuario-nome');
                        console.log('[Minhas Mensagens] Nome do usuário do card:', usuarioNome);
                        
                        modalTitle.textContent = 'Chat com ' + usuarioNome;
                        chatIframe.src = `chat.php?usuario_id=${conversaIdParaAbrir}&nome_usuario=${encodeURIComponent(usuarioNome)}&is_modal=true&scroll_to_bottom=true`;
                        console.log('[Minhas Mensagens] Iframe src definido para:', chatIframe.src);

                        try {
                            const modalInstance = new bootstrap.Modal(chatModal);
                            console.log('[Minhas Mensagens] Instância do modal criada. Tentando mostrar...');
                            modalInstance.show();
                            console.log('[Minhas Mensagens] Comando modalInstance.show() executado.');
                        } catch (e) {
                            console.error('[Minhas Mensagens] Erro ao tentar mostrar o modal:', e);
                        }
                    } else {
                        console.warn('[Minhas Mensagens] Card para abrir conversa via notificação NÃO encontrado. ID procurado:', conversaIdParaAbrir);
                    }
                } else {
                    console.warn('[Minhas Mensagens] Um ou mais elementos do modal (chatModal, chatIframe, modalTitle) não foram encontrados no DOM.');
                }
            } else {
                console.log('[Minhas Mensagens] Nenhum ID de conversa encontrado na URL para abrir automaticamente.');
            }

            
            document.querySelectorAll('.card-conversa').forEach(card => {
                card.addEventListener('click', function(e) {
                    
                    if (e.target.closest('.btn-danger')) {
                        return;
                    }

                    const usuarioId = this.getAttribute('data-usuario-id');
                    const usuarioNome = this.getAttribute('data-usuario-nome');

                    if (!usuarioId || !usuarioNome) return;

                    const chatModalEl = document.getElementById('chatModal');
                    const chatIframe = document.getElementById('chatIframe');
                    const modalTitle = document.getElementById('chatModalLabel');
                    if (!chatModalEl || !chatIframe || !modalTitle) return;

                    modalTitle.textContent = 'Chat com ' + usuarioNome;
                    chatIframe.src = `chat.php?usuario_id=${usuarioId}&nome_usuario=${encodeURIComponent(usuarioNome)}&is_modal=true&scroll_to_bottom=true`;

                    const modalInstance = bootstrap.Modal.getOrCreateInstance(chatModalEl);
                    modalInstance.show();
                });
            });

            const btnConfirmDelete = document.getElementById('btnConfirmDeleteConversa');
            if (btnConfirmDelete) {
                btnConfirmDelete.addEventListener('click', () => {
                    if (!conversaParaExcluirId) return;
                    fetch('../controllers/excluir_conversa_controller.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ usuario_id: conversaParaExcluirId })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            
                            const modalDelEl = document.getElementById('confirmDeleteModal');
                            const modalDelInst = bootstrap.Modal.getInstance(modalDelEl);
                            if (modalDelInst) modalDelInst.hide();

                            window.location.href = 'minhas_mensagens.php';
                        } else {
                            alert('Erro ao excluir conversa: ' + (result.error || 'Erro desconhecido'));
                        }
                    })
                    .catch(error => {
                        alert('Erro de comunicação ao excluir conversa.');
                    });
                });
            }
        });
    </script>
</body>
</html> 