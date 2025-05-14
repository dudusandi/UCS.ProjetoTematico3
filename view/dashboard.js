// dashboard.js
let currentProdutoId = null;
let isEditando = false;
let fornecedores = []; // Will be set by dashboard.php
const itensPorPagina = 12;
let paginaAtual = 1;
let termoBusca = '';
let isCarregando = false;
let todosCarregados = false;
let debounceTimer;

// Função para carregar produtos
function carregarProdutos(termo = '', pagina = 1, append = false) {
    if (isCarregando || todosCarregados) return;
    
    isCarregando = true;
    const produtosContainer = document.getElementById('produtosContainer');
    const loading = document.getElementById('loading');
    
    loading.classList.remove('d-none');

    fetch(`../controllers/buscar_produtos.php?termo=${encodeURIComponent(termo)}&pagina=${pagina}`)
        .then(response => response.json())
        .then(data => {
            isCarregando = false;
            loading.classList.add('d-none');

            if (!data.success) {
                mostrarMensagemErro(data.error || 'Erro ao carregar produtos');
                return;
            }

            if (data.total === 0) {
                mostrarMensagemVazia(termo);
                return;
            }

            const produtosHtml = data.produtos.map(produto => criarCardProduto(produto)).join('');
            
            if (!append) {
                produtosContainer.innerHTML = `
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
                        ${produtosHtml}
                    </div>
                `;
            } else {
                const row = produtosContainer.querySelector('.row');
                row.insertAdjacentHTML('beforeend', produtosHtml);
            }

            atualizarEstadoPaginacao(data.total, pagina);
        })
        .catch(error => {
            isCarregando = false;
            loading.classList.add('d-none');
            mostrarMensagemErro('Erro ao carregar produtos: ' + error.message);
        });
}

// Função para criar o card do produto
function criarCardProduto(produto) {
    const fotoUrl = produto.foto ? `../public/uploads/imagens/${produto.foto}` : 'https://via.placeholder.com/200?text=Sem+Imagem';
    const precoFormatado = (produto.preco ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    return `
        <div class="col">
            <div class="card h-100 produto-card" onclick="mostrarDetalhes(${produto.id})">
                <div class="card-img-container">
                    <img src="${fotoUrl}" class="card-img-top" alt="Foto do produto" loading="lazy">
                </div>
                <div class="card-body">
                    <h5 class="card-title text-truncate" title="${produto.nome}">${produto.nome}</h5>
                    <p class="card-text">
                        <span class="preco">R$ ${precoFormatado}</span>
                        <span class="estoque ${produto.quantidade <= 5 ? 'estoque-baixo' : ''}">
                            Estoque: ${produto.quantidade ?? 0}
                        </span>
                    </p>
                    <p class="card-text fornecedor text-truncate" title="${produto.fornecedor_nome || 'Sem fornecedor'}">
                        ${produto.fornecedor_nome || 'Sem fornecedor'}
                    </p>
                </div>
            </div>
        </div>
    `;
}

// Função para mostrar mensagem de erro
function mostrarMensagemErro(mensagem) {
    document.getElementById('produtosContainer').innerHTML = `
        <div class="empty-state">
            <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
            <h3 class="mt-3">${mensagem}</h3>
        </div>
    `;
    todosCarregados = true;
    document.getElementById('sentinela').style.display = 'none';
}

// Função para mostrar mensagem quando não há produtos
function mostrarMensagemVazia(termo) {
    const mensagem = termo 
        ? `Nenhum produto encontrado para "${termo}"`
        : 'Nenhum produto cadastrado';
    
    document.getElementById('produtosContainer').innerHTML = `
        <div class="empty-state">
            <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
            <h3 class="mt-3">${mensagem}</h3>
        </div>
    `;
    todosCarregados = true;
    document.getElementById('sentinela').style.display = 'none';
}

// Função para atualizar estado da paginação
function atualizarEstadoPaginacao(total, pagina) {
    todosCarregados = total <= pagina * itensPorPagina;
    document.getElementById('sentinela').style.display = todosCarregados ? 'none' : 'block';
    paginaAtual = pagina;
}

// Configurar Intersection Observer para o carregamento infinito
document.addEventListener('DOMContentLoaded', () => {
    const sentinela = document.getElementById('sentinela');
    if (!sentinela) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !isCarregando && !todosCarregados) {
                setTimeout(() => carregarProdutos(termoBusca, paginaAtual + 1, true), 100);
            }
        });
    }, {
        root: document.querySelector('.products-section'),
        threshold: 0.1,
        rootMargin: '300px'
    });
    observer.observe(sentinela);

    // Evento de busca com debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const termo = e.target.value.trim();
                if (termo.length >= 2 || termo === '') {
                    termoBusca = termo;
                    paginaAtual = 1;
                    todosCarregados = false;
                    document.getElementById('sentinela').style.display = 'block';
                    carregarProdutos(termo, 1, false);
                }
            }, 300);
        });
    }

    // Carregar produtos iniciais
    carregarProdutos('', 1, false);
});

// Função para mostrar detalhes do produto
function mostrarDetalhes(id) {
    currentProdutoId = id;
    fetch(`../controllers/get_produto.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            // Preencher dados do produto
            document.getElementById('produtoNome').textContent = data.nome;
            document.getElementById('produtoDescricao').textContent = data.descricao || 'Nenhuma';
            document.getElementById('produtoFornecedor').textContent = data.fornecedor_nome || 'Sem fornecedor';
            document.getElementById('produtoEstoque').textContent = data.estoque;
            document.getElementById('produtoPreco').textContent = `R$ ${(data.preco ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            document.getElementById('produtoFoto').src = data.foto ? `../public/uploads/imagens/${data.foto}` : 'https://via.placeholder.com/200';
            document.getElementById('btnConfirmarExclusao').href = `../controllers/excluir_produto.php?id=${id}`;

            // Preencher formulário de edição
            document.getElementById('produtoId').value = id;
            document.getElementById('produtoNomeInput').value = data.nome;
            document.getElementById('produtoDescricaoInput').value = data.descricao || '';
            document.getElementById('produtoEstoqueInput').value = data.estoque;
            document.getElementById('produtoPrecoInput').value = data.preco ?? 0;

            // Preencher select de fornecedores
            const fornecedorSelect = document.getElementById('produtoFornecedorInput');
            fornecedorSelect.innerHTML = '<option value="">Selecione um fornecedor</option>';
            
            if (window.fornecedores && window.fornecedores.length > 0) {
                window.fornecedores.forEach(fornecedor => {
                    const option = document.createElement('option');
                    option.value = fornecedor.id;
                    option.text = fornecedor.nome;
                    if (fornecedor.id == data.fornecedor) {
                        option.selected = true;
                    }
                    fornecedorSelect.appendChild(option);
                });
            }

            // Mostrar botões de ação
            document.getElementById('btnEditar').classList.remove('d-none');
            document.getElementById('btnExcluir').classList.remove('d-none');

            // Resetar estado do modal
            isEditando = false;
            document.getElementById('visualizacao').classList.remove('d-none');
            document.getElementById('editarForm').classList.add('d-none');
            document.getElementById('btnSalvar').classList.add('d-none');
            document.getElementById('produtoFotoInput').classList.add('d-none');
            document.getElementById('mensagemErro').style.display = 'none';
            document.getElementById('mensagemSucesso').style.display = 'none';

            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('detalhesModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Erro ao buscar detalhes:', error);
            alert('Erro ao carregar detalhes do produto');
        });
}

// Função para alternar modo de edição
function alternarEdicao() {
    isEditando = !isEditando;
    document.getElementById('visualizacao').classList.toggle('d-none');
    document.getElementById('editarForm').classList.toggle('d-none');
    document.getElementById('btnEditar').classList.toggle('d-none');
    document.getElementById('btnSalvar').classList.toggle('d-none');
    document.getElementById('produtoFotoInput').classList.toggle('d-none');
}

// Função para salvar produto
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
                // Fechar o modal e remover o backdrop
                const modal = bootstrap.Modal.getInstance(document.getElementById('detalhesModal'));
                modal.hide();
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                
                // Recarregar a dashboard
                termoBusca = '';
                paginaAtual = 1;
                todosCarregados = false;
                document.getElementById('sentinela').style.display = 'block';
                carregarProdutos('', 1, false);
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

// Função para confirmar exclusão
function confirmarExclusao() {
    document.getElementById('confirmProdutoNome').textContent = document.getElementById('produtoNome').textContent;
    const detalhesModal = bootstrap.Modal.getInstance(document.getElementById('detalhesModal'));
    detalhesModal.hide();
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    confirmModal.show();
}

// Função para mostrar detalhes do produto
function mostrarDetalhes(id) {
    currentProdutoId = id;
    fetch(`../controllers/get_produto.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            // Preencher dados do produto
            document.getElementById('produtoNome').textContent = data.nome;
            document.getElementById('produtoDescricao').textContent = data.descricao || 'Nenhuma';
            document.getElementById('produtoFornecedor').textContent = data.fornecedor_nome || 'Sem fornecedor';
            document.getElementById('produtoEstoque').textContent = data.estoque;
            document.getElementById('produtoPreco').textContent = `R$ ${(data.preco ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            document.getElementById('produtoFoto').src = data.foto ? `../public/uploads/imagens/${data.foto}` : 'https://via.placeholder.com/200';
            document.getElementById('btnConfirmarExclusao').href = `../controllers/excluir_produto.php?id=${id}`;

            // Preencher formulário de edição
            document.getElementById('produtoId').value = id;
            document.getElementById('produtoNomeInput').value = data.nome;
            document.getElementById('produtoDescricaoInput').value = data.descricao || '';
            document.getElementById('produtoEstoqueInput').value = data.estoque;
            document.getElementById('produtoPrecoInput').value = data.preco ?? 0;

            // Preencher select de fornecedores
            const fornecedorSelect = document.getElementById('produtoFornecedorInput');
            fornecedorSelect.innerHTML = '<option value="">Selecione um fornecedor</option>';
            
            if (window.fornecedores && window.fornecedores.length > 0) {
                window.fornecedores.forEach(fornecedor => {
                    const option = document.createElement('option');
                    option.value = fornecedor.id;
                    option.text = fornecedor.nome;
                    if (fornecedor.id == data.fornecedor) {
                        option.selected = true;
                    }
                    fornecedorSelect.appendChild(option);
                });
            }

            // Mostrar botões de ação
            document.getElementById('btnEditar').classList.remove('d-none');
            document.getElementById('btnExcluir').classList.remove('d-none');

            // Resetar estado do modal
            isEditando = false;
            document.getElementById('visualizacao').classList.remove('d-none');
            document.getElementById('editarForm').classList.add('d-none');
            document.getElementById('btnSalvar').classList.add('d-none');
            document.getElementById('produtoFotoInput').classList.add('d-none');
            document.getElementById('mensagemErro').style.display = 'none';
            document.getElementById('mensagemSucesso').style.display = 'none';

            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('detalhesModal'));
            modal.show();
        })
        .catch(error => {
            console.error('Erro ao buscar detalhes:', error);
            alert('Erro ao carregar detalhes do produto');
        });
}

// Função para confirmar exclusão
function confirmarExclusao(id, nome) {
    document.getElementById('confirmProdutoNome').textContent = nome;
    document.getElementById('btnConfirmarExclusao').href = `../controllers/excluir_produto.php?id=${id}`;
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

// Configurar eventos
document.addEventListener('DOMContentLoaded', () => {
    // Carregar produtos iniciais
    carregarProdutos();

    // Configurar busca
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            termoBusca = searchInput.value.trim();
            paginaAtual = 1;
            carregarProdutos(termoBusca, 1, false);
        }, 500);
    });

    // Configurar scroll infinito
    const sentinela = document.getElementById('sentinela');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !isCarregando && !todosCarregados) {
                carregarProdutos(termoBusca, paginaAtual + 1, true);
                paginaAtual++;
            }
        });
    });
    observer.observe(sentinela);

    // Configurar formulário de cadastro
    const formCadastro = document.getElementById('formCadastroProduto');
    formCadastro.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('../controllers/cadastrar_produto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fechar modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('cadastroProdutoModal'));
                modal.hide();
                
                // Limpar formulário
                formCadastro.reset();
                
                // Recarregar produtos
                carregarProdutos(termoBusca, 1, false);
                
                // Mostrar mensagem de sucesso
                alert('Produto cadastrado com sucesso!');
            } else {
                alert(data.error || 'Erro ao cadastrar produto');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao cadastrar produto');
        });
    });
});