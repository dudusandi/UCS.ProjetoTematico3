// Variáveis globais
let currentPage = 1;
let searchTerm = '';
let isLoading = false;
let allLoaded = false;
let debounceTimer;

// Função para carregar clientes
function carregarClientes(termo = '', pagina = 1, append = false) {
    if (isLoading || allLoaded) return;
    
    isLoading = true;
    const clientesContainer = document.getElementById('clientesContainer');
    const loading = document.getElementById('loading');
    
    loading.classList.remove('d-none');

    fetch(`../controllers/buscar_clientes.php?termo=${encodeURIComponent(termo)}&pagina=${pagina}`)
        .then(response => response.json())
        .then(data => {
            isLoading = false;
            loading.classList.add('d-none');

            if (!data.success) {
                mostrarMensagemErro(data.error || 'Erro ao carregar clientes');
                return;
            }

            if (data.total === 0) {
                mostrarMensagemVazia(termo);
                return;
            }

            const clientesHtml = data.clientes.map(cliente => criarCardCliente(cliente)).join('');
            
            if (!append) {
                clientesContainer.innerHTML = `
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        ${clientesHtml}
                    </div>
                `;
            } else {
                const row = clientesContainer.querySelector('.row');
                row.insertAdjacentHTML('beforeend', clientesHtml);
            }

            atualizarEstadoPaginacao(data.total, pagina);
        })
        .catch(error => {
            isLoading = false;
            loading.classList.add('d-none');
            mostrarMensagemErro('Erro ao carregar clientes: ' + error.message);
        });
}

// Função para criar o card do cliente
function criarCardCliente(cliente) {
    return `
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">${cliente.nome}</h5>
                    <p class="card-text text-muted">
                        <strong>Telefone:</strong> ${cliente.telefone}<br>
                        <strong>Email:</strong> ${cliente.email}<br>
                        <strong>Endereço:</strong> 
                        ${cliente.rua}, ${cliente.numero}, ${cliente.bairro}, ${cliente.cidade} - ${cliente.estado}
                    </p>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="editar_cliente.php?id=${cliente.id}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <a href="../controllers/excluir_cliente.php?id=${cliente.id}" class="btn btn-sm btn-danger" 
                       onclick="return confirm('Tem certeza que deseja excluir o cliente ${cliente.nome}?')">
                        <i class="bi bi-trash"></i> Excluir
                    </a>
                </div>
            </div>
        </div>
    `;
}

// Função para mostrar mensagem de erro
function mostrarMensagemErro(mensagem) {
    document.getElementById('clientesContainer').innerHTML = `
        <div class="empty-state">
            <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
            <h3 class="mt-3">${mensagem}</h3>
        </div>
    `;
    allLoaded = true;
    document.getElementById('sentinela').style.display = 'none';
}

// Função para mostrar mensagem quando não há clientes
function mostrarMensagemVazia(termo) {
    const mensagem = termo 
        ? `Nenhum cliente encontrado para "${termo}"`
        : 'Nenhum cliente cadastrado';
    
    document.getElementById('clientesContainer').innerHTML = `
        <div class="empty-state">
            <i class="bi bi-person" style="font-size: 3rem;"></i>
            <h3 class="mt-3">${mensagem}</h3>
        </div>
    `;
    allLoaded = true;
    document.getElementById('sentinela').style.display = 'none';
}

// Função para atualizar estado da paginação
function atualizarEstadoPaginacao(total, pagina) {
    allLoaded = total <= pagina * 6;
    document.getElementById('sentinela').style.display = allLoaded ? 'none' : 'block';
    currentPage = pagina;
}

// Configurar Intersection Observer para o carregamento infinito
document.addEventListener('DOMContentLoaded', () => {
    const sentinela = document.getElementById('sentinela');
    if (!sentinela) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !isLoading && !allLoaded) {
                setTimeout(() => carregarClientes(searchTerm, currentPage + 1, true), 100);
            }
        });
    }, {
        root: document.querySelector('.container'),
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
                    searchTerm = termo;
                    currentPage = 1;
                    allLoaded = false;
                    document.getElementById('sentinela').style.display = 'block';
                    carregarClientes(termo, 1, false);
                }
            }, 300);
        });
    }

    // Carregar clientes iniciais
    carregarClientes('', 1, false);
}); 