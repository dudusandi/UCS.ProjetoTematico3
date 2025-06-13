let currentPage = 1;
let searchTerm = '';
let isLoading = false;
let allLoaded = false;
let debounceTimer;

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
                    <ul class="list-group list-group-flush">
                        ${clientesHtml}
                    </ul>
                `;
            } else {
                const ul = clientesContainer.querySelector('ul.list-group');
                ul.insertAdjacentHTML('beforeend', clientesHtml);
            }

            atualizarEstadoPaginacao(data.total, pagina);
        })
        .catch(error => {
            isLoading = false;
            loading.classList.add('d-none');
            mostrarMensagemErro('Erro ao carregar clientes: ' + error.message);
        });
}

function criarCardCliente(cliente) {
    return `
        <li class="list-group-item d-flex justify-content-between align-items-start py-3">
            <div class="ms-2 me-auto">
                <div class="fw-semibold">${cliente.nome}</div>
                <small class="text-muted d-block"><i class="bi bi-telephone"></i> ${cliente.telefone}</small>
                <small class="text-muted d-block"><i class="bi bi-envelope"></i> ${cliente.email}</small>
                <small class="text-muted d-block"><i class="bi bi-geo-alt"></i> ${cliente.rua}, ${cliente.numero}, ${cliente.bairro}, ${cliente.cidade} - ${cliente.estado}</small>
            </div>
            <div class="btn-group btn-group-sm">
                <a href="editar_cliente.php?id=${cliente.id}" class="btn btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                <a href="../controllers/excluir_cliente.php?id=${cliente.id}" class="btn btn-outline-danger" title="Excluir" onclick="return confirm('Excluir ${cliente.nome}?')"><i class="bi bi-trash"></i></a>
            </div>
        </li>
    `;
}

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

function atualizarEstadoPaginacao(total, pagina) {
    allLoaded = total <= pagina * 6;
    document.getElementById('sentinela').style.display = allLoaded ? 'none' : 'block';
    currentPage = pagina;
}

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

    carregarClientes('', 1, false);
}); 