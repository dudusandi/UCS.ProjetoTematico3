<?php
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>
<div class="side-nav-bar">
    <div class="logo-container">
        <div class="logo">ECO<span>Exchange</span></div>
    </div>

    <a href="dashboard.php" class="<?php echo ($paginaAtual == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2"></i>
        <span>Pagina Inicial</span>
    </a>
    
    <?php if ($id_usuario_logado): ?>
        <a href="meus_produtos.php" class="<?php echo ($paginaAtual == 'meus_produtos.php') ? 'active' : ''; ?>">
            <i class="bi bi-archive"></i>
            <span>Meus Produtos</span>
        </a>
        <a href="minhas_mensagens.php" class="position-relative <?php echo ($paginaAtual == 'minhas_mensagens.php' || $paginaAtual == 'chat.php') ? 'active' : ''; ?>">
            <i class="bi bi-chat-left-dots"></i>
            <span>Minhas Mensagens</span>
            <?php if (($id_usuario_logado ?? null) && ($contador_mensagens_nao_lidas ?? 0) > 0): ?>
                <span class="badge bg-danger position-absolute top-50 start-100 translate-middle-y ms-2" style="font-size: 0.65em; padding: 0.3em 0.5em;"><?php echo $contador_mensagens_nao_lidas; ?></span>
            <?php endif; ?>
        </a>
    <?php endif; ?>

    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
        <a href="../view/listar_clientes.php" class="<?php echo ($paginaAtual == 'listar_clientes.php') ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            <span>Gerenciar Clientes</span>
        </a> 
    <?php endif; ?>

    <?php 
    if (isset($id_usuario_logado) && $id_usuario_logado): 
    ?>
    <div class="notifications-section-container">
        <div class="notifications-header">
            <i class="bi bi-bell-fill"></i> <!-- Ícone atualizado para API -->
            <span>Notificações</span> 
            <span id="contadorNotificacoesAPISideNav" class="badge bg-primary ms-2" style="font-size: 0.7em; padding: 0.3em 0.5em; display:none;">
                0 
            </span>
        </div>
        <ul class="notifications-list" id="listaNotificacoesAPISideNav">
            <li id="notificacaoItemLoadingAPISideNav" class="dropdown-item text-muted">Carregando notificações...</li>
            <li id="notificacaoItemNenhumaAPISideNav" class="dropdown-item text-muted" style="display:none;">Nenhuma notificação nova.</li>
        </ul>
    </div>
    <?php else: ?>
        <?php // echo "<!-- DEBUG menu.php: Bloco de notificações NÃO renderizado pois id_usuario_logado é falso ou não definido. Valor: "; var_dump($id_usuario_logado ?? "INDEFINIDO NO ELSE NOTIF"); echo " -->"; ?>
    <?php endif; ?>

    <div class="user-info-nav">
        <?php if ($id_usuario_logado): ?>
            <span><i class="bi bi-person-circle"></i> <?= htmlspecialchars($nome_usuario ?? 'Usuário') ?></span>
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

<style>
.side-nav-bar {
    width: 250px; 
    background-color: #f8f9fa;
    padding: 15px;
    height: 100vh;
    display: flex;
    flex-direction: column;
    border-right: 1px solid #dee2e6;
    flex-shrink: 0;
}

.logo-container {
    text-align: center;
    margin-bottom: 30px; /* Aumentar um pouco a margem inferior */
    padding: 10px 0; /* Adicionar padding vertical */
}

.logo {
    font-size: 28px; /* Consistente com o antigo dashboard.css */
    font-weight: bold;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: inline-block; /* Importante para o efeito de gradiente no span */
}

.logo span {
    background: repeating-linear-gradient(
        90deg,
        #f093fb 0%,
        #f5576c 25%,
        #764ba2 50%,
        #667eea 75%,
        #f093fb 100%
    );
    background-size: 200% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    animation: gradientFlow 2s linear infinite;
}

@keyframes gradientFlow {
    0% { background-position: 0% 0%; }
    100% { background-position: 200% 0%; }
}

.side-nav-bar a, .side-nav-bar .notifications-header, .side-nav-bar .user-info-nav > span {
    color: #333;
    padding: 10px 15px;
    text-decoration: none;
    display: flex;
    align-items: center;
    border-radius: 5px;
    margin-bottom: 5px;
    transition: background-color 0.3s ease;
}

.side-nav-bar a i, .side-nav-bar .notifications-header i, .side-nav-bar .user-info-nav i {
    margin-right: 10px;
    font-size: 1.2em;
}

.side-nav-bar a:hover, .side-nav-bar a.active {
    background-color: #e9ecef;
    color: #007bff;
}
.side-nav-bar a.active {
    font-weight: bold;
}

.notifications-section-container {
    margin-top: 15px;
    padding-top: 10px;
    border-top: 1px solid #ddd;
}
.notifications-header {
    font-weight: bold;
}

.notifications-list {
    list-style: none;
    padding-left: 0;
    margin-right: 5px; /* Espaço para a scrollbar não colar */
    font-size: 0.9em; 
    height: 500px; /* Altura fixa para a lista de notificações */
    overflow-y: auto; /* Adiciona scroll vertical quando necessário */
    padding-right: 10px; /* Ajuste para não colar na scrollbar */
}

/* Estilo para cada item de notificação */
.notifications-list > li.dropdown-item { 
    background-color:rgb(249, 249, 249);
    border: 1px solid #e0e0e0;
    border-radius: 20px;
    padding: 10px 12px;
    margin-bottom: 8px;
    line-height: 1.4;
    font-size: 0.9em; /* Tamanho da fonte do item */
    white-space: normal !important; /* Permite a quebra de linha normal, sobrescrevendo Bootstrap */
    /* border-bottom removido, pois agora tem borda completa */
    overflow: visible;
    position: relative;
}

/* Destaque para notificações não lidas */
.notifications-list > li.dropdown-item.fw-bold {
    border-left: 4px solid #0d6efd; /* Borda esquerda azul mais espessa */
    background-color: #f8f9fa; /* Fundo levemente diferente */
}

/* Estilo para o link dentro da notificação, se houver */
.notifications-list > li.dropdown-item a {
    text-decoration: none;
    color: inherit;
    display: block; /* Para o link ocupar todo o espaço do li */
    padding: 0;
}

/* Classe para o container da mensagem da notificação */
.notifications-list .notification-message {
    display: block; 

    margin-bottom: 5px; /* Espaço entre mensagem e data */
}

/* Classe para a data da notificação */
.notifications-list .notification-date {
    display: block; 
    font-size: 0.85em; 
    color: #6c757d; 
    text-align: left; /* Alinha a data à esquerda */
}

.user-info-nav {
    margin-top: auto; /* Empurra para o final */
    padding-top: 15px;
    border-top: 1px solid #ddd;
}
.user-info-nav > span {
    font-weight: bold;
}

/* Botão de remover notificação customizado */
.notificacao-remover-container {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: absolute;
    left: 0;
    bottom: 0;
    z-index: 2;
    pointer-events: none;
}

/* Aura sutil na base da notificação (dentro do card) */
.notificacao-aura-suave {
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 8px;
    border-radius: 0 0 20px 20px;
    background: linear-gradient(to bottom, rgba(255,255,255,0) 0%, rgba(255,255,255,0.01) 30%, rgba(102,126,234,0.18) 60%, rgba(118,75,162,0.22) 80%, rgba(240,147,251,0.18) 90%, rgba(245,87,108,0.18) 100%),
                linear-gradient(90deg, rgba(102,126,234,0.18) 0%, rgba(118,75,162,0.22) 40%, rgba(240,147,251,0.18) 70%, rgba(245,87,108,0.18) 100%);
    background-size: 100% 100%, 200% 100%;
    background-repeat: no-repeat;
    animation: gradienteAura 20s linear infinite;
    box-shadow: 0 8px 24px 0 rgba(120, 75, 162, 0.10);
    pointer-events: none;
    z-index: 1;
    opacity: 0.3;
    transition: opacity 0.4s;
}

.notifications-list > li.dropdown-item:hover .notificacao-aura-suave {
    opacity: 1;
}

.notificacao-remover-btn {
    background: radial-gradient(circle, #ff4d4f 60%, #fff0 100%);
    border: none;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.3em;
    box-shadow: 0 2px 8px rgba(255,77,79,0.15);
    cursor: pointer;
    position: absolute;
    left: 50%;
    bottom: -22px;
    transform: translate(-50%, 10px); /* Movido um pouco para baixo para efeito de surgir */
    transition: box-shadow 0.2s, background 0.2s, opacity 0.3s ease, transform 0.3s ease; /* Transições atualizadas */
    pointer-events: none; /* Inicialmente não interativo */
    outline: none;
    z-index: 2;
    opacity: 0; /* Inicialmente invisível */
}

@keyframes gradienteAura {
    0% { background-position: 0% 50%; }
    100% { background-position: 200% 50%; }
}

.notificacao-remover-btn .icon {
    z-index: 2;
    pointer-events: none;
    transition: opacity 0.2s;
}

.notificacao-remover-btn .texto-excluir {
    position: absolute;
    left: 50%;
    top: -30px;
    transform: translateX(-50%);
    background: #764ba2;
    color: #fff;
    padding: 2px 10px;
    border-radius: 8px;
    font-size: 0.85em;
    opacity: 0;
    pointer-events: none;
    white-space: nowrap;
    transition: opacity 0.2s, top 0.2s;
    box-shadow: 0 2px 8px rgba(120, 75, 162, 0.15);
}

.notificacao-remover-btn:focus .texto-excluir {
    opacity: 1;
    top: -38px;
}

/* Mostrar o botão de remover no hover do item da notificação */
.notifications-list > li.dropdown-item:hover .notificacao-remover-btn {
    opacity: 1;
    transform: translate(-50%, 0); /* Retorna à posição original */
    pointer-events: auto; /* Torna-se interativo */
}

.notificacao-remover-btn:hover,
.notificacao-remover-btn:focus {
    box-shadow: 0 4px 16px rgba(120, 75, 162, 0.25);
}

.notifications-list > li.dropdown-item {
    overflow: visible;
    position: relative;
}

.notifications-list > li.dropdown-item::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 16px;
    border-radius: 0 0 20px 20px;
    pointer-events: none;
    z-index: 1;
    opacity: 0.2;
    background: none;
    background-size: 200% 100%;
    background-position: 0% 0%;
    filter: none;
    transition: none;
    animation: none;
}

@keyframes gradienteAura {
    0% { background-position: 0% 0%; }
    100% { background-position: 200% 0%; }
}

.notifications-list > li.dropdown-item a:hover,
.notifications-list > li.dropdown-item a:focus {
    background: transparent !important;
    opacity: 1 !important;
    color: inherit;
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variáveis de sessão e usuário (serão definidas pelo PHP que inclui este arquivo)
    // Certifique-se de que $id_usuario_logado e $nome_usuario estão disponíveis no escopo do include
    // Como este script está no menu.php, ele terá acesso às variáveis PHP definidas ANTES do include.

    // Função para buscar e exibir notificações da API
    function carregarNotificacoesAPI() {
        const listaNotificacoes = document.getElementById('listaNotificacoesAPISideNav');
        const loadingItem = document.getElementById('notificacaoItemLoadingAPISideNav');
        const nenhumaItem = document.getElementById('notificacaoItemNenhumaAPISideNav');
        const contadorBadge = document.getElementById('contadorNotificacoesAPISideNav');

        // Logs de debug removidos
        // console.log('[Menu Notificações] Tentando encontrar elementos: listaNotificacoesAPISideNav, notificacaoItemLoadingAPISideNav, notificacaoItemNenhumaAPISideNav, contadorNotificacoesAPISideNav');
        // if (!listaNotificacoes) console.error('[Menu Notificações Debug] listaNotificacoesAPISideNav NÃO encontrado!');
        // if (!loadingItem) console.error('[Menu Notificações Debug] notificacaoItemLoadingAPISideNav NÃO encontrado!');
        // if (!nenhumaItem) console.error('[Menu Notificações Debug] notificacaoItemNenhumaAPISideNav NÃO encontrado!');
        // if (!contadorBadge) console.error('[Menu Notificações Debug] contadorNotificacoesAPISideNav NÃO encontrado!');
        
        if (!listaNotificacoes || !loadingItem || !nenhumaItem || !contadorBadge) {
          // Mantemos este log, pois é útil para saber se os elementos essenciais não foram encontrados
          console.warn('[Menu Notificações] Um ou mais elementos de notificação da API NÃO foram encontrados. Notificações da API não serão carregadas.');
          return; 
        }

        // console.log('[Menu Notificações] Iniciando fetch para ../controllers/get_notificacoes_controller.php');
        fetch('../controllers/get_notificacoes_controller.php') 
            .then(response => {
                // console.log('[Menu Notificações] Resposta do fetch recebida:', response);
                if (!response.ok) {
                    console.error('[Menu Notificações] Erro na resposta da rede:', response.status, response.statusText); // Manter este erro crítico
                    throw new Error('Erro na rede ou servidor: ' + response.statusText + ' ao buscar get_notificacoes_controller.php');
                }
                return response.json(); 
            })
            .then(data => {
                // console.log('[Menu Notificações] Dados JSON recebidos e parseados:', data);
                loadingItem.classList.add('d-none');
                
                if (data.success && data.notificacoes) {
                    // console.log('[Menu Notificações] Sucesso na API. Notificações recebidas:', data.notificacoes, 'ContadorNaoLidas:', data.contadorNaoLidas);
                    const notificacoesRecebidas = data.notificacoes;
                    // let contadorRenderizadas = 0; // Não usado, pode ser removido

                    // Limpa notificações antigas ANTES de adicionar novas
                    const itensAtuais = listaNotificacoes.querySelectorAll('li:not(#notificacaoItemLoadingAPISideNav):not(#notificacaoItemNenhumaAPISideNav)');
                    itensAtuais.forEach(item => item.remove());

                    if (notificacoesRecebidas.length > 0) {
                        // console.log('[Menu Notificações] Renderizando ' + notificacoesRecebidas.length + ' notificações.');
                        notificacoesRecebidas.forEach((notif, index) => {
                            // console.log('[Menu Notificações] Renderizando notificação #' + index + ':', notif);
                            const listItem = document.createElement('li');
                            listItem.classList.add('dropdown-item'); 
                            listItem.style.position = 'relative'; // Para posicionar o botão
                            
                            let mensagemOriginal = notif.mensagem; 
                            let mensagemFormatada = '<span class="notification-message">' + htmlspecialchars(mensagemOriginal) + '</span>';
                            let dataFormatada = '<span class="notification-date">' + (notif.data_formatada || '') + '</span>';

                            let conteudoItem = mensagemFormatada + dataFormatada;
                            let linkFinal = notif.link; // Link padrão
                            console.log('[Menu Notificações] Processando notificação:', JSON.stringify(notif)); // Log da notificação inteira

                            // Verifica se a notificação é para uma conversa e ajusta o link
                            if (notif.link && typeof notif.link === 'string' && notif.link.includes('chat.php?')) {
                                console.log('[Menu Notificações] Link original da notificação de chat:', notif.link);
                                try {
                                    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
                                    const url = new URL(notif.link, baseUrl);
                                    const params = url.searchParams;
                                    console.log('[Menu Notificações] Parâmetros da URL do link original:', params.toString());
                                    
                                    let conversaId = params.get('conversa_id') || params.get('id_conversa') || params.get('id_destinatario') || params.get('chat_id') || params.get('usuario_id');
                                    console.log('[Menu Notificações] ID da conversa extraído (conversa_id, id_conversa, id_destinatario, chat_id, usuario_id):', conversaId);

                                    if (conversaId) {
                                        linkFinal = `minhas_mensagens.php?abrir_conversa_id=${conversaId}`;
                                    } else {
                                        console.warn('[Menu Notificações] Não foi possível extrair um ID de conversa reconhecível do link:', notif.link, '. Redirecionando para minhas_mensagens.php sem ID.');
                                        linkFinal = 'minhas_mensagens.php';
                                    }
                                } catch (e) {
                                    console.warn('[Menu Notificações] Erro ao processar link da notificação:', notif.link, e);
                                    linkFinal = notif.link || '#'; 
                                }
                            } else {
                                console.log('[Menu Notificações] Link da notificação não parece ser de chat ou está ausente/inválido. Link original:', notif.link);
                            }
                            console.log('[Menu Notificações] Link final gerado para a notificação:', linkFinal);

                            if (linkFinal && linkFinal !== '#') {
                                // A classe notification-link-item não é estritamente necessária se o CSS já cobre bem
                                // os links <a> dentro de li.dropdown-item
                                listItem.innerHTML = `<a href="${linkFinal}">${conteudoItem}</a>`;
                            } else {
                                listItem.innerHTML = conteudoItem;
                            }

                            // Botão de remover customizado
                            let btnContainer = document.createElement('div');
                            btnContainer.className = 'notificacao-remover-container';

                            let btnRemover = document.createElement('button');
                            btnRemover.type = 'button';
                            btnRemover.className = 'notificacao-remover-btn';
                            btnRemover.innerHTML = '<span class="icon">&#128465;</span><span class="texto-excluir">Excluir</span>';
                            btnRemover.title = 'Excluir notificação';
                            btnRemover.addEventListener('click', function(e) {
                                e.stopPropagation();
                                e.preventDefault();
                                removerNotificacao(notif.id, listItem);
                            });
                            btnContainer.appendChild(btnRemover);
                            listItem.appendChild(btnContainer);

                            if (nenhumaItem && nenhumaItem.parentNode === listaNotificacoes) {
                                listaNotificacoes.insertBefore(listItem, nenhumaItem);
                            } else {
                                listaNotificacoes.appendChild(listItem);
                            }
                        });
                        nenhumaItem.classList.add('d-none');
                    } else {
                        // console.log('[Menu Notificações] Nenhuma notificação para renderizar (array vazio).');
                        nenhumaItem.classList.remove('d-none');
                    }

                    // Atualiza o contador geral (badge)
                    if (data.contadorNaoLidas > 0) {
                        contadorBadge.textContent = data.contadorNaoLidas > 9 ? '9+' : data.contadorNaoLidas;
                        contadorBadge.style.display = ''; 
                    } else {
                        contadorBadge.style.display = 'none';
                    }

                } else if (data.error) {
                    console.error('[Menu Notificações] Erro lógico retornado pela API:', data.error); // Manter este erro crítico
                    nenhumaItem.innerHTML = '<span class="text-danger">Erro: ' + htmlspecialchars(data.error) + '</span>';
                    nenhumaItem.classList.remove('d-none');
                    if (contadorBadge) contadorBadge.style.display = 'none';
                } else {
                    console.error('[Menu Notificações] Formato de dados inesperado da API:', data); // Manter este erro crítico
                    nenhumaItem.innerHTML = '<span class="text-danger">Resposta inesperada.</span>';
                    nenhumaItem.classList.remove('d-none');
                    if (contadorBadge) contadorBadge.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('[Menu Notificações] Erro no catch do fetch ou JSON.parse:', error); // Manter este erro crítico
                loadingItem.classList.add('d-none');
                nenhumaItem.classList.remove('d-none');
                nenhumaItem.innerHTML = '<span class="text-danger">Erro ao carregar.</span>';
                if (contadorBadge) contadorBadge.style.display = 'none';
            });
    }

    // Verifica se os elementos do DOM existem antes de chamar a função
    const listaAPINotificacoes = document.getElementById('listaNotificacoesAPISideNav');
    if (listaAPINotificacoes) {
        // console.log('[Menu Notificações] Elemento listaNotificacoesAPISideNav encontrado. Chamando carregarNotificacoesAPI().');
        carregarNotificacoesAPI(); // Carrega as notificações uma vez ao iniciar
        setInterval(carregarNotificacoesAPI, 5000); // Atualiza a cada 5 segundos
    } else {
        // Mantemos este log, pois é útil para saber se os elementos essenciais não foram encontrados
        console.warn('[Menu Notificações] Elemento listaNotificacoesAPISideNav NÃO encontrado. Notificações da API não serão carregadas.');
    }

});

// Adicionar função utilitária simples para escapar HTML no JS, se não existir uma global
function htmlspecialchars(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/[&<>'"\/]/g, function (s) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;'
        }[s];
    });
}

async function removerNotificacao(notificacaoId, listItem) {
    try {
        const response = await fetch('../controllers/dispensar_notificacao_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notificacao_id: notificacaoId })
        });
        const result = await response.json();
        if (result.success) {
            if (listItem && listItem.parentNode) {
                listItem.parentNode.removeChild(listItem);
            }
            
            if (typeof carregarNotificacoesAPI === 'function') {
                carregarNotificacoesAPI();
            }
        } else {
            alert('Erro ao remover notificação: ' + (result.error || 'Erro desconhecido'));
        }
    } catch (error) {
        alert('Erro de comunicação ao remover notificação.');
    }
}
</script> 