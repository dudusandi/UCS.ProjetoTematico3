let paginaAtual = 1;
let carregando = false;
let termoBusca = '';
let observer;

// Função para criar o card do fornecedor
function criarCardFornecedor(fornecedor) {
    return `
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">${fornecedor.nome}</h5>
                    <p class="card-text text-muted">
                        <strong>Descrição:</strong> ${fornecedor.descricao || 'Nenhuma'}<br>
                        <strong>Telefone:</strong> ${fornecedor.telefone}<br>
                        <strong>Email:</strong> ${fornecedor.email}<br>
                        <strong>Endereço:</strong> 
                        ${fornecedor.rua}, ${fornecedor.numero}, ${fornecedor.bairro}, ${fornecedor.cidade} - ${fornecedor.estado}
                    </p>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="editar_fornecedor.php?id=${fornecedor.id}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <a href="../controllers/excluir_fornecedor.php?id=${fornecedor.id}" 
                       class="btn btn-sm btn-danger" 
                       onclick="return confirm('Tem certeza que deseja excluir o fornecedor ${fornecedor.nome}?')">
                        <i class="bi bi-trash"></i> Excluir
                    </a>
                </div>
            </div>
        </div>
    `;
}

// Função para carregar fornecedores
async function carregarFornecedores(resetar = false) {
    if (carregando) return;
    carregando = true;

    if (resetar) {
        paginaAtual = 1;
        document.getElementById('fornecedoresContainer').innerHTML = '';
    }

    const loadingIndicator = document.getElementById('loadingIndicator');
    loadingIndicator.classList.remove('d-none');

    try {
        const response = await fetch(`../controllers/buscar_fornecedores.php?termo=${encodeURIComponent(termoBusca)}&pagina=${paginaAtual}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar fornecedores');
        }

        const container = document.getElementById('fornecedoresContainer');
        
        if (data.fornecedores.length === 0 && paginaAtual === 1) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-building" style="font-size: 3rem;"></i>
                    <h3 class="mt-3">Nenhum fornecedor encontrado</h3>
                </div>
            `;
        } else {
            const row = document.createElement('div');
            row.className = 'row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4';
            row.innerHTML = data.fornecedores.map(criarCardFornecedor).join('');
            container.appendChild(row);
        }

        if (data.fornecedores.length < 6) {
            observer.disconnect();
        }

        paginaAtual++;
    } catch (error) {
        console.error('Erro:', error);
        document.getElementById('fornecedoresContainer').innerHTML = `
            <div class="alert alert-danger" role="alert">
                ${error.message}
            </div>
        `;
    } finally {
        carregando = false;
        loadingIndicator.classList.add('d-none');
    }
}

// Configurar o Intersection Observer
function configurarObserver() {
    const options = {
        root: null,
        rootMargin: '0px',
        threshold: 1.0
    };

    observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                carregarFornecedores();
            }
        });
    }, options);

    observer.observe(document.getElementById('loadingIndicator'));
}

// Função de debounce para a busca
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Configurar eventos
document.addEventListener('DOMContentLoaded', () => {
    configurarObserver();
    carregarFornecedores();

    const searchInput = document.getElementById('searchInput');
    
    const realizarBusca = () => {
        termoBusca = searchInput.value.trim();
        carregarFornecedores(true);
    };

    searchInput.addEventListener('input', debounce(realizarBusca, 500));
}); 