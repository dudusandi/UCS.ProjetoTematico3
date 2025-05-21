<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?erro=login_necessario");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

$usuario_id = $_SESSION['usuario_id'];
$mensagem = $_GET['mensagem'] ?? '';
$tipoMensagem = $_GET['tipo_mensagem'] ?? '';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
} catch (Exception $e) {
    error_log("Erro ao conectar ao banco de dados: " . $e->getMessage());
    $mensagem = "Erro crítico ao carregar a página. Tente novamente mais tarde.";
    $tipoMensagem = 'erro';
    // Não prosseguir se não conseguir conectar ao DB
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Produtos - ECOxchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .produto-card .card-footer {
            background-color: transparent;
            border-top: none;
        }
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo"><a href="dashboard.php" style="text-decoration: none; color: inherit;">ECO<span>xchange</span></a></div>
        <div class="search-bar">
            <form id="searchFormMeusProdutos" class="d-flex" method="GET" action="meus_produtos.php">
                <input type="text" id="searchInputMeusProdutos" name="termo" class="form-control me-2" placeholder="Pesquisar nos meus produtos..." value="<?= htmlspecialchars($_GET['termo'] ?? '') ?>">
                <button type="submit" class="btn btn-outline-success">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
        <div class="user-options">
            <?php if (isset($_SESSION['usuario_nome'])): ?>
                <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
                <a href="../controllers/logout_controller.php" class="btn btn-outline-danger ms-2">Sair</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">Login</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="nav-bar">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar ao Início</a>
    </div>

    <div class="container mt-4">
        <h2>Meus Produtos Anunciados</h2>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div id="meusProdutosContainer">
            <?php
            if (isset($produtoDao)) { // Só prossegue se o DAO foi inicializado
                try {
                    $itensPorPagina = 8;
                    $paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
                    if ($paginaAtual < 1) $paginaAtual = 1;
                    $offset = ($paginaAtual - 1) * $itensPorPagina;
                    $termo = $_GET['termo'] ?? '';

                    $produtos = $produtoDao->buscarPorUsuarioId($usuario_id, $termo, $itensPorPagina, $offset);
                    $totalProdutos = $produtoDao->contarProdutosPorUsuarioId($usuario_id, $termo);
                    $totalPaginas = ceil($totalProdutos / $itensPorPagina);

                    if (empty($produtos)) {
                        echo '<div class="empty-state">
                                <i class="bi bi-dropbox" style="font-size: 4rem;"></i>
                                <h3 class="mt-3">' . ($termo ? "Nenhum produto seu encontrado para \"" . htmlspecialchars($termo) . "\"" : "Você ainda não cadastrou nenhum produto.") . '</h3>
                                <!-- <p class="mt-2">Que tal <a href="#" data-bs-toggle="modal" data-bs-target="#cadastroProdutoModal">cadastrar seu primeiro produto</a> agora?</p> -->
                              </div>';
                    } else {
                        echo '<ul class="list-group">'; // Início da lista
                        foreach ($produtos as $produto) {
                            $fotoUrl = $produto['foto'] ? 'data:image/jpeg;base64,' . base64_encode($produto['foto']) : 'https://via.placeholder.com/80?text=Sem+Imagem';
                            $precoFormatado = number_format($produto['preco'] ?? 0.0, 2, ',', '.');

                            echo '<li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">';
                            echo '    <div class="d-flex align-items-center me-3 mb-2 mb-md-0" style="cursor:pointer;" onclick="abrirModalEdicao(' . $produto['id'] . ')">';
                            echo '        <img src="' . $fotoUrl . '" alt="' . htmlspecialchars($produto['nome']) . '" style="width: 80px; height: 80px; object-fit: cover; margin-right: 15px; border-radius: 4px;">';
                            echo '        <div>';
                            echo '            <h5 class="mb-1 text-truncate" title="' . htmlspecialchars($produto['nome']) . '">' . htmlspecialchars($produto['nome']) . '</h5>';
                            echo '            <p class="mb-0 text-muted">R$ ' . $precoFormatado . '</p>';
                            echo '        </div>';
                            echo '    </div>';
                            echo '    <div class="btn-group" role="group" aria-label="Ações do produto">';
                            echo '        <button class="btn btn-sm btn-outline-primary" onclick="abrirModalEdicao(' . $produto['id'] . ')">';
                            echo '            <i class="bi bi-pencil-square"></i> Editar';
                            echo '        </button>';
                            echo '        <button class="btn btn-sm btn-outline-danger" onclick="confirmarExclusao(' . $produto['id'] . ', \'' . htmlspecialchars(addslashes($produto['nome']), ENT_QUOTES) . '\')">';
                            echo '            <i class="bi bi-trash"></i> Excluir';
                            echo '        </button>';
                            echo '    </div>';
                            echo '</li>';
                        }
                        echo '</ul>'; // Fim da lista

                        if ($totalPaginas > 1) {
                            echo '<nav aria-label="Paginação de Meus Produtos" class="mt-4">';
                            echo '<ul class="pagination justify-content-center">';
                            $queryString = $termo ? '&termo=' . urlencode($termo) : '';
                            if ($paginaAtual > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?pagina=' . ($paginaAtual - 1) . $queryString . '">Anterior</a></li>';
                            } else {
                                echo '<li class="page-item disabled"><span class="page-link">Anterior</span></li>';
                            }
                            for ($i = 1; $i <= $totalPaginas; $i++) {
                                echo '<li class="page-item ' . ($i == $paginaAtual ? 'active' : '') . '"><a class="page-link" href="?pagina=' . $i . $queryString . '">' . $i . '</a></li>';
                            }
                            if ($paginaAtual < $totalPaginas) {
                                echo '<li class="page-item"><a class="page-link" href="?pagina=' . ($paginaAtual + 1) . $queryString . '">Próximo</a></li>';
                            } else {
                                echo '<li class="page-item disabled"><span class="page-link">Próximo</span></li>';
                            }
                            echo '</ul></nav>';
                        }
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">Erro ao carregar seus produtos: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            } else {
                 echo '<div class="alert alert-danger">' . htmlspecialchars($mensagem) . '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Modal de Detalhes/Edição (similar ao dashboard.php, mas adaptado) -->
    <div class="modal fade" id="produtoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="produtoModalNome">Detalhes do Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                     <div id="mensagemErroModal" class="alert alert-danger d-none" role="alert"></div>
                     <div id="mensagemSucessoModal" class="alert alert-success d-none" role="alert"></div>
                    <div class="row">
                        <div class="col-md-4">
                            <img id="produtoModalFoto" src="" class="img-fluid mb-3" alt="Foto do produto" style="max-height: 200px; object-fit: contain; cursor: pointer;" onclick="document.getElementById('produtoModalFotoInput').click();">
                            <input type="file" id="produtoModalFotoInput" name="foto" class="form-control d-none" accept="image/jpeg,image/png,image/gif">
                            <small class="form-text text-muted">Clique na imagem para alterar.</small>
                        </div>
                        <div class="col-md-8">
                            <form id="editarProdutoForm">
                                <input type="hidden" id="produtoModalId" name="id">
                                <div class="mb-3">
                                    <label for="produtoModalNomeInput" class="form-label">Nome *</label>
                                    <input type="text" class="form-control" id="produtoModalNomeInput" name="nome" required>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoModalDescricaoInput" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="produtoModalDescricaoInput" name="descricao" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoModalPrecoInput" class="form-label">Preço *</label>
                                    <input type="number" step="0.01" class="form-control" id="produtoModalPrecoInput" name="preco" required>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button id="btnSalvarEdicao" type="button" class="btn btn-primary" onclick="salvarEdicaoProduto()">
                        <i class="bi bi-save"></i> Salvar Alterações
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmExcluirModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o produto "<span id="confirmExcluirNomeProduto"></span>"? Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnConfirmarExclusaoDefinitiva" class="btn btn-danger">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Passar dados do PHP para o JavaScript
        window.usuarioLogadoId = <?= json_encode($_SESSION['usuario_id'] ?? null); ?>;
        
        const produtoModalElement = document.getElementById('produtoModal');
        const produtoModal = new bootstrap.Modal(produtoModalElement);
        const confirmExcluirModalElement = document.getElementById('confirmExcluirModal');
        const confirmExcluirModal = new bootstrap.Modal(confirmExcluirModalElement);
        let produtoIdParaExcluir = null;

        // Funções para o modal de edição
        async function abrirModalEdicao(produtoId) {
            document.getElementById('mensagemErroModal').classList.add('d-none');
            document.getElementById('mensagemSucessoModal').classList.add('d-none');
            try {
                const response = await fetch(`../controllers/get_produto.php?id=${produtoId}`);
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ error: 'Erro ao buscar dados do produto.' }));
                    throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                if (data.success && data.produto) {
                    const p = data.produto;
                    document.getElementById('produtoModalId').value = p.id;
                    document.getElementById('produtoModalNome').textContent = p.nome; // Título do Modal
                    document.getElementById('produtoModalNomeInput').value = p.nome;
                    document.getElementById('produtoModalDescricaoInput').value = p.descricao;
                    document.getElementById('produtoModalPrecoInput').value = p.preco;
                    document.getElementById('produtoModalFoto').src = p.foto ? `data:image/jpeg;base64,${p.foto}` : 'https://via.placeholder.com/200?text=Sem+Imagem';
                    document.getElementById('produtoModalFotoInput').value = ''; // Limpar input de foto
                    produtoModal.show();
                } else {
                    throw new Error(data.error || 'Produto não encontrado ou falha ao carregar.');
                }
            } catch (error) {
                console.error('Erro ao carregar produto para edição:', error);
                alert('Não foi possível carregar os dados do produto para edição: ' + error.message);
            }
        }

        async function salvarEdicaoProduto() {
            const form = document.getElementById('editarProdutoForm');
            const formData = new FormData(form);
            const fotoInput = document.getElementById('produtoModalFotoInput');
            if (fotoInput.files.length > 0) {
                formData.append('foto', fotoInput.files[0]);
            }
            
            // Adicionar ID ao FormData, pois o input está hidden e pode não ser pego por new FormData(form) em alguns casos.
            formData.append('id', document.getElementById('produtoModalId').value);


            document.getElementById('mensagemErroModal').classList.add('d-none');
            document.getElementById('mensagemSucessoModal').classList.add('d-none');

            try {
                const response = await fetch('../controllers/atualizar_produto.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    document.getElementById('mensagemSucessoModal').textContent = result.message || 'Produto atualizado com sucesso!';
                    document.getElementById('mensagemSucessoModal').classList.remove('d-none');
                    setTimeout(() => {
                        produtoModal.hide();
                        window.location.reload(); // Recarregar a página para ver as alterações
                    }, 1500);
                } else {
                    document.getElementById('mensagemErroModal').textContent = result.error || 'Erro desconhecido ao atualizar o produto.';
                    document.getElementById('mensagemErroModal').classList.remove('d-none');
                }
            } catch (error) {
                console.error('Erro ao salvar produto:', error);
                document.getElementById('mensagemErroModal').textContent = 'Erro de comunicação ao salvar. Tente novamente.';
                document.getElementById('mensagemErroModal').classList.remove('d-none');
            }
        }
        
        // Funções para exclusão
        function confirmarExclusao(id, nome) {
            document.getElementById('confirmExcluirNomeProduto').textContent = nome;
            produtoIdParaExcluir = id;
            confirmExcluirModal.show();
        }

        document.getElementById('btnConfirmarExclusaoDefinitiva').addEventListener('click', async function() {
            if (!produtoIdParaExcluir) return;

            try {
                const response = await fetch('../controllers/excluir_produto.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${produtoIdParaExcluir}`
                });
                const result = await response.json();
                if (result.success) {
                    confirmExcluirModal.hide();
                    // Idealmente, remover o card do DOM ou recarregar.
                    // Para simplicidade, recarregando a página:
                    window.location.href = 'meus_produtos.php?mensagem=Produto excluído com sucesso&tipo_mensagem=sucesso';
                } else {
                    alert('Erro ao excluir produto: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao excluir produto:', error);
                alert('Erro de comunicação ao excluir produto.');
            }
        });

        // Para o modal de detalhes (se quiser manter a funcionalidade de apenas visualizar)
        // A função mostrarDetalhes(id) do dashboard.js precisaria ser adaptada ou copiada
        // Por ora, o card já tem o botão Editar que abre um modal focado na edição.
        // Se o clique no card for para visualização, e o botão Editar para edição,
        // o modal precisaria de dois estados (visualização/edição) como no dashboard.
        // Para simplificar, o clique no card levará direto para edição nesta página.
        function mostrarDetalhes(produtoId) {
            // Nesta página, o "mostrarDetalhes" vai direto para edição.
            abrirModalEdicao(produtoId);
        }

        // Listener para o input de foto no modal de edição
        document.getElementById('produtoModalFotoInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('produtoModalFoto').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 