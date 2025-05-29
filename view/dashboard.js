let currentProdutoId = null;
let isEditando = false;
let fornecedores = []; 

function mostrarDetalhes(id) {
    currentProdutoId = id;
    console.log("[mostrarDetalhes] ID do produto clicado:", id);
    console.log("[mostrarDetalhes] ID do usuário logado (window.usuarioLogadoId):", window.usuarioLogadoId);

    fetch(`../controllers/get_produto.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            const produto = data.produto;
            console.log("[mostrarDetalhes] Dados do produto recebidos:", produto);
            if (!produto) {
                alert('Falha ao carregar dados do produto.');
                return;
            }

            document.getElementById('produtoNome').textContent = produto.nome;
            document.getElementById('produtoDescricao').textContent = produto.descricao || 'Nenhuma';
            document.getElementById('produtoPreco').textContent = `R$ ${(produto.preco ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            document.getElementById('produtoAnuncianteNome').textContent = produto.proprietario_nome || 'Não informado';
            document.getElementById('produtoFoto').src = produto.foto ? `data:image/jpeg;base64,${produto.foto}` : 'https://via.placeholder.com/200';
            document.getElementById('produtoId').value = id; 
            document.getElementById('produtoNomeInput').value = produto.nome;
            document.getElementById('produtoDescricaoInput').value = produto.descricao || '';
            document.getElementById('produtoPrecoInput').value = produto.preco ?? 0;

            const btnConfirmarExclusao = document.getElementById('btnConfirmarExclusao');
            if (btnConfirmarExclusao) {
                btnConfirmarExclusao.href = `../controllers/excluir_produto.php?id=${id}`;
            }

            const btnEditar = document.getElementById('btnEditar');
            const btnExcluir = document.getElementById('btnExcluir');
            const btnSalvar = document.getElementById('btnSalvar');
            const btnTenhoInteresse = document.getElementById('btnTenhoInteresse');

            const btnEnviarMensagem = document.getElementById('btnEnviarMensagemVendedor');
            const proprietarioIdDoProduto = produto.usuario_id;

            console.log("[mostrarDetalhes] ID Proprietário do Produto:", proprietarioIdDoProduto, "(tipo:", typeof proprietarioIdDoProduto, ")");

            if (proprietarioIdDoProduto && window.usuarioLogadoId && parseInt(proprietarioIdDoProduto) !== parseInt(window.usuarioLogadoId)) {
                console.log("[mostrarDetalhes] Mostrando botão Enviar Mensagem.");
                btnEnviarMensagem.href = `../view/chat.php?usuario_id=${proprietarioIdDoProduto}`; 
                btnEnviarMensagem.classList.remove('d-none');
            } else {
                console.log("[mostrarDetalhes] Escondendo botão Enviar Mensagem (usuário é o dono ou não logado ou proprietário não definido).");
                btnEnviarMensagem.classList.add('d-none');
            }

            console.log("[mostrarDetalhes] Verificando condições de exibição dos botões:");
            console.log("[mostrarDetalhes] ID Usuário Logado:", window.usuarioLogadoId, "(tipo:", typeof window.usuarioLogadoId, ")");
            console.log("[mostrarDetalhes] ID Dono do Produto:", produto.usuario_id, "(tipo:", typeof produto.usuario_id, ")");
            console.log("[mostrarDetalhes] É Admin? (window.isAdmin):", window.isAdmin);

            const usuarioLogadoIdNum = Number(window.usuarioLogadoId);
            const produtoUsuarioIdNum = Number(produto.usuario_id);

            if (window.isAdmin || (usuarioLogadoIdNum && usuarioLogadoIdNum === produtoUsuarioIdNum) ) {
                console.log("[mostrarDetalhes] Condição: Admin ou Dono do produto.");
                if (btnEditar) btnEditar.classList.remove('d-none');
                if (btnExcluir) btnExcluir.classList.remove('d-none');
                if (btnSalvar) btnSalvar.classList.add('d-none'); 
                if (btnTenhoInteresse) btnTenhoInteresse.classList.add('d-none');
            } else if (usuarioLogadoIdNum && usuarioLogadoIdNum !== produtoUsuarioIdNum) {
                 console.log("[mostrarDetalhes] Condição: Usuário logado, NÃO é o dono.");
                if (btnEditar) btnEditar.classList.add('d-none');
                if (btnExcluir) btnExcluir.classList.add('d-none');
                if (btnSalvar) btnSalvar.classList.add('d-none');
                if (btnTenhoInteresse) btnTenhoInteresse.classList.remove('d-none');
            } else {
                console.log("[mostrarDetalhes] Condição: Não logado ou outra situação (esconder todos os botões de ação específica).");
                if (btnEditar) btnEditar.classList.add('d-none');
                if (btnExcluir) btnExcluir.classList.add('d-none');
                if (btnSalvar) btnSalvar.classList.add('d-none');
                if (btnTenhoInteresse) btnTenhoInteresse.classList.add('d-none');
            }

            isEditando = false;
            document.getElementById('visualizacao').classList.remove('d-none');
            document.getElementById('editarForm').classList.add('d-none');
            document.getElementById('produtoFotoInput').classList.add('d-none');
            document.getElementById('mensagemErro').style.display = 'none';
            document.getElementById('mensagemSucesso').style.display = 'none';

            const modal = new bootstrap.Modal(document.getElementById('produtoModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Erro ao carregar detalhes do produto:', error);
            alert('Erro ao carregar detalhes do produto: ' + error.message);
        });
}

async function registrarInteresseProduto() {
    if (!currentProdutoId) {
        alert('ID do produto não encontrado para registrar interesse.');
        return;
    }
    if (!window.usuarioLogadoId) {
        alert('Você precisa estar logado para demonstrar interesse.');
        return;
    }

    try {
        const response = await fetch('../controllers/registrar_interesse_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `produto_id=${currentProdutoId}`
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message || 'Interesse registrado com sucesso! O vendedor será notificado.');
            const btnTenhoInteresse = document.getElementById('btnTenhoInteresse');
            if (btnTenhoInteresse) {
                btnTenhoInteresse.disabled = true;
                btnTenhoInteresse.innerHTML = '<i class="bi bi-check-lg"></i> Interesse Enviado';
            }

        } else {
            alert('Erro ao registrar interesse: ' + (result.error || 'Ocorreu um problema.'));
        }
    } catch (error) {
        console.error('Erro no fetch ao registrar interesse:', error);
        alert('Erro de comunicação ao registrar interesse. Tente novamente.');
    }
}

function alternarEdicao() {
    isEditando = !isEditando;
    document.getElementById('visualizacao').classList.toggle('d-none');
    document.getElementById('editarForm').classList.toggle('d-none');
    document.getElementById('btnEditar').classList.toggle('d-none');
    document.getElementById('btnSalvar').classList.toggle('d-none');
    document.getElementById('produtoFotoInput').classList.toggle('d-none');
}

function salvarProduto() {
    const form = document.getElementById('editarForm');
    const formData = new FormData(form);
    if (document.getElementById('produtoFotoInput').files[0]) {
        formData.append('foto', document.getElementById('produtoFotoInput').files[0]);
    }

    fetch('../controllers/atualizar_produto.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('produtoModal'));
                modal.hide();
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                window.location.reload();
            } else {
                document.getElementById('mensagemErroTexto').textContent = data.error;
                document.getElementById('mensagemErro').style.display = 'block';
                document.getElementById('mensagemSucesso').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Erro ao salvar:', error);
            document.getElementById('mensagemErroTexto').textContent = 'Erro ao salvar o produto';
            document.getElementById('mensagemErro').style.display = 'block';
            document.getElementById('mensagemSucesso').style.display = 'none';
        });
}

function confirmarExclusao() {
    document.getElementById('confirmProdutoNome').textContent = document.getElementById('produtoNome').textContent;
    const detalhesModal = bootstrap.Modal.getInstance(document.getElementById('produtoModal'));
    detalhesModal.hide();
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    confirmModal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    const formCadastroDashboard = document.getElementById('formCadastroProduto');
    if (formCadastroDashboard) {
        formCadastroDashboard.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('../controllers/cadastrar_produto.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('cadastroProdutoModal')); 
                    if (modal) modal.hide();
                    formCadastroDashboard.reset();
                    window.location.href = 'dashboard.php?mensagem=' + encodeURIComponent(data.message || 'Produto cadastrado com sucesso!') + '&tipo_mensagem=sucesso';
                } else {
                    alert(data.error || 'Erro ao cadastrar produto');
                }
            })
            .catch(error => {
                console.error('Erro no cadastro de produto (Dashboard):', error);
                alert('Erro de comunicação ao cadastrar produto.');
            });
        });
    }

    if (typeof window.usuarioLogadoId !== 'undefined' && window.usuarioLogadoId) {
        console.log("[DOMContentLoaded] Usuário logado. Carregando notificações pela primeira vez e configurando intervalo.");
        carregarNotificacoes(); 
        setInterval(carregarNotificacoes, 60000); 
    } else {
        console.log("[DOMContentLoaded] Usuário não logado ou ID não definido. Notificações não serão carregadas automaticamente.");
    }
});

function exibirProduto(produto) {
    if (produto.foto) {
        const fotoBase64 = btoa(String.fromCharCode.apply(null, new Uint8Array(produto.foto)));
        document.getElementById('produtoFoto').src = `data:image/jpeg;base64,${fotoBase64}`;
    } else {
        document.getElementById('produtoFoto').src = 'https://via.placeholder.com/200';
    }
}


async function carregarNotificacoes() {
    console.log("[carregarNotificacoes] Verificando ID do usuário logado (window.usuarioLogadoId):", window.usuarioLogadoId);
    if (!window.usuarioLogadoId) {
        console.warn("[carregarNotificacoes] Usuário não logado, notificações não serão carregadas.");
        return;
    }

    try {
        const response = await fetch('../controllers/get_notificacoes_controller.php');
        if (!response.ok) {
            console.error('Erro HTTP ao buscar notificações:', response.status, response.statusText);
            document.getElementById('notificacaoItemLoading').textContent = 'Erro ao carregar.';
            return;
        }
        const data = await response.json();

        if (data.success) {
            renderizarNotificacoesSideNav(data.notificacoes, data.contadorNaoLidas);
        } else {
            console.error('Erro ao buscar notificações (API):', data.error);
            document.getElementById('notificacaoItemLoadingSideNav').textContent = 'Erro ao carregar (API).';
        }
    } catch (error) {
        console.error('Erro no fetch de notificações:', error);
        document.getElementById('notificacaoItemLoadingSideNav').textContent = 'Falha na comunicação.';
    }
}

function renderizarNotificacoesSideNav(notificacoes, contadorNaoLidas) {
    const listaDropdown = document.getElementById('listaNotificacoesSideNav');
    const contadorBadge = document.getElementById('contadorNotificacoesSideNav');
    const loadingItem = document.getElementById('notificacaoItemLoadingSideNav');
    const nenhumaItem = document.getElementById('notificacaoItemNenhumaSideNav');
    
    const marcarTodasLidasContainer = document.getElementById('marcarTodasLidasContainerSideNav'); 

    const itensAtuais = listaDropdown.querySelectorAll('li.notificacao-item');
    itensAtuais.forEach(item => item.remove());

    if (!loadingItem || !nenhumaItem || !listaDropdown || !marcarTodasLidasContainer) {
        console.error("[renderizarNotificacoesSideNav] Elementos essenciais da UI de notificações não encontrados. Abortando renderização.");
        if(loadingItem) loadingItem.textContent = 'Erro na UI.';
        return;
    }

    loadingItem.classList.add('d-none'); 

    if (contadorNaoLidas > 0) {
        if(contadorBadge) {
            contadorBadge.textContent = contadorNaoLidas > 9 ? '9+' : contadorNaoLidas;
            contadorBadge.classList.remove('d-none');
        }
        if (marcarTodasLidasContainer) {
            marcarTodasLidasContainer.classList.remove('d-none');
        }
    } else {
        if(contadorBadge) contadorBadge.classList.add('d-none');
        if (marcarTodasLidasContainer) {
            marcarTodasLidasContainer.classList.add('d-none');
        }
    }

    if (notificacoes && notificacoes.length > 0) {
        nenhumaItem.classList.add('d-none');

        notificacoes.forEach(notif => {
            const li = document.createElement('li');
            li.classList.add('notificacao-item'); 
            const a = document.createElement('a');
            a.classList.add('dropdown-item', 'd-flex', 'justify-content-between', 'align-items-start');
            if (!notif.lida) {
                a.classList.add('fw-bold'); 
            }
            a.href = notif.link || '#';
            a.onclick = (event) => {
                event.preventDefault(); 
                marcarNotificacaoLida(notif.id, notif.link, a);
            };

            const textoDiv = document.createElement('div');
            textoDiv.style.whiteSpace = 'normal'; 
            textoDiv.style.maxWidth = '280px'; 
            
            const msgSpan = document.createElement('span');
            msgSpan.textContent = notif.mensagem;
            textoDiv.appendChild(msgSpan);

            const dataSpan = document.createElement('small');
            dataSpan.classList.add('text-muted', 'd-block', 'mt-1');
            dataSpan.textContent = notif.data_formatada;
            textoDiv.appendChild(dataSpan);

            a.appendChild(textoDiv);

            const wrapperDireita = document.createElement('div');
            wrapperDireita.classList.add('d-flex', 'align-items-center', 'ms-auto');

            if (!notif.lida) {
                const dotSpan = document.createElement('span');
                dotSpan.classList.add('badge', 'bg-primary', 'rounded-pill', 'me-2');
                dotSpan.textContent = ' ';
                dotSpan.style.padding = '0.3em';
                dotSpan.style.lineHeight = '0.5';
                wrapperDireita.appendChild(dotSpan);
            }

            const btnDispensar = document.createElement('button');
            btnDispensar.classList.add('btn', 'btn-sm', 'p-0'); 
            btnDispensar.innerHTML = '<i class="bi bi-x-lg text-danger"></i>'; 
            btnDispensar.style.lineHeight = '1';
            btnDispensar.style.background = 'none';
            btnDispensar.style.border = 'none';
            btnDispensar.title = 'Dispensar notificação';
            btnDispensar.onclick = (event) => {
                event.stopPropagation();
                event.preventDefault();
                dispensarNotificacao(notif.id, li, !notif.lida); 
            };
            wrapperDireita.appendChild(btnDispensar);
            a.appendChild(wrapperDireita); 
            
            li.appendChild(a);

            listaDropdown.insertBefore(li, marcarTodasLidasContainer);
        });
    } else {
        nenhumaItem.classList.remove('d-none');
    }
}

async function dispensarNotificacao(notificacaoId, elementoLi, eraNaoLida) {


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
            if (elementoLi) {
                elementoLi.remove();
            }

            if (eraNaoLida) {
                const contadorBadge = document.getElementById('contadorNotificacoesSideNav');
                if (contadorBadge) {
                    let contagemAtual = parseInt(contadorBadge.textContent);
                    if (isNaN(contagemAtual) && contadorBadge.textContent.includes('+')) { // Ex: 9+
                        carregarNotificacoes(); 
                        return;
                    }
                    if (!isNaN(contagemAtual) && contagemAtual > 0) {
                        contagemAtual--;
                        contadorBadge.textContent = contagemAtual > 9 ? '9+' : (contagemAtual === 0 ? '' : contagemAtual);
                        if (contagemAtual === 0) {
                            contadorBadge.classList.add('d-none');
                            const marcarTodasLidasContainer = document.getElementById('marcarTodasLidasContainerSideNav');
                            if(marcarTodasLidasContainer) marcarTodasLidasContainer.classList.add('d-none');
                        }
                    } else {
                        carregarNotificacoes();
                    }
                }
            }
            const lista = document.getElementById('listaNotificacoesSideNav');
            const nenhumaItem = document.getElementById('notificacaoItemNenhumaSideNav');
            if (lista && nenhumaItem && lista.querySelectorAll('li.notificacao-item').length === 0) {
                nenhumaItem.classList.remove('d-none');
            }

        } else {
            alert('Erro ao remover notificação: ' + (result.error || 'Erro desconhecido.'));
        }
    } catch (error) {
        console.error('Erro no fetch ao dispensar notificação:', error);
        alert('Erro de comunicação ao remover notificação.');
    }
}

async function marcarNotificacaoLida(notificacaoId, linkNotificacao, elementoA) {
    try {
        const response = await fetch('../controllers/marcar_notificacao_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ acao: 'marcar_lida', notificacao_id: notificacaoId })
        });
        const result = await response.json();
        if (result.success) {
            if (elementoA) {
                elementoA.classList.remove('fw-bold');
                const dot = elementoA.querySelector('.badge.bg-primary.rounded-pill');
                if(dot) dot.remove();
            }
            carregarNotificacoes(); 
            
            if (linkNotificacao && linkNotificacao !== '#') {
                window.location.href = linkNotificacao;
            } else {
            }
        } else {
            console.error('Erro ao marcar notificação como lida:', result.error);
            alert('Erro ao marcar notificação como lida.');
        }
    } catch (error) {
        console.error('Erro no fetch ao marcar como lida:', error);
        alert('Erro de comunicação ao marcar notificação.');
    }
}

async function marcarTodasComoLidas(event) {
    event.preventDefault();
    if (!confirm("Tem certeza que deseja marcar todas as notificações como lidas?")) {
        return;
    }
    try {
        const response = await fetch('../controllers/marcar_notificacao_controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ acao: 'marcar_todas_lidas' })
        });
        const result = await response.json();
        if (result.success) {
            carregarNotificacoes(); 
        } else {
            alert('Erro ao marcar todas as notificações como lidas: ' + (result.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Erro ao marcar todas como lidas:', error);
        alert('Erro de comunicação.');
    }
}
