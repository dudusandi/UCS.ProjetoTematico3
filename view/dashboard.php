<?php
session_start(); // Adicionado para iniciar a sessão

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../model/produto.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    $mensagem = $_GET['mensagem'] ?? '';
    $tipoMensagem = $_GET['tipo_mensagem'] ?? '';
} catch (Exception $e) {
    error_log("Erro ao listar produtos: " . $e->getMessage());
    $mensagem = "Erro ao carregar produtos: " . $e->getMessage();
    $tipoMensagem = 'erro';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="header">
        <div class="logo">ECO<span>xchange</span></div>
        <div class="search-bar">
            <form id="searchForm" class="d-flex" method="GET">
                <input type="text" id="searchInput" name="termo" placeholder="Pesquisar produtos..." value="<?= htmlspecialchars($_GET['termo'] ?? '') ?>">
                <button type="submit" class="btn-search-custom">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
        <div class="user-options">
            <?php if (isset($_SESSION['usuario_id'])): // Verifica se o usuário está logado ?>
                <div class="dropdown me-2" id="notificacoesDropdownContainer" style="display: inline-block;">
                    <button class="btn btn-outline-secondary position-relative" type="button" id="notificacoesDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <span id="contadorNotificacoes" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                            0
                            <span class="visually-hidden">notificações não lidas</span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificacoesDropdownBtn" id="listaNotificacoesDropdown">
                        <li><h6 class="dropdown-header">Notificações</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <!-- Notificações serão inseridas aqui pelo JS -->
                        <li id="notificacaoItemLoading" class="dropdown-item text-muted">Carregando...</li>
                        <li id="notificacaoItemNenhuma" class="dropdown-item text-muted d-none">Nenhuma notificação nova.</li>
                        <li><hr class="dropdown-divider d-none" id="notificacoesDividerFinal"></li>
                        <li><a class="dropdown-item text-center d-none" href="#" id="verTodasNotificacoesLink">Ver todas</a></li> 
                        <li><a class="dropdown-item text-center d-none" href="#" id="marcarTodasLidasLink" onclick="marcarTodasComoLidas(event)">Marcar todas como lidas</a></li>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['usuario_nome'])): ?>
                <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
                <a href="../controllers/logout_controller.php">Sair</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-sm">Login</a>
            <?php endif; ?>

        </div>
    </div>
    <!-- Menu com visualização apenas para o admin -->
    <div class="nav-bar">
    <?php if (isset($_SESSION['usuario_id'])): // Verifica se o usuário está logado ?>
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#cadastroProdutoModal">
                <i class="bi bi-plus-circle"></i> Cadastrar Produto
            </button>
            <a href="meus_produtos.php" class="btn btn-outline-info ms-2">
                <i class="bi bi-archive"></i> Meus Produtos
            </a>
    <?php endif; ?>
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <a href="../view/listar_clientes.php" class="btn btn-outline-primary">
                <i class="bi bi-people"></i> Editar Clientes
            </a> 
        <?php endif; ?>
    </div>

    <!-- Seção de Produtos -->
    <div class="products-section container">
        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Listagem -->
        <div id="produtosContainer">
            <?php
            try {
                $itensPorPagina = 8;
                $paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
                if ($paginaAtual < 1) $paginaAtual = 1;
                $offset = ($paginaAtual - 1) * $itensPorPagina;

                $termo = $_GET['termo'] ?? '';
                
                $produtos = $produtoDao->buscarProdutos($termo, $itensPorPagina, $offset);
                $totalProdutos = $produtoDao->contarProdutosBuscados($termo);
                $totalPaginas = ceil($totalProdutos / $itensPorPagina);

                if (empty($produtos)) {
                    echo '<div class="empty-state">
                            <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                            <h3 class="mt-3">' . ($termo ? "Nenhum produto encontrado para \"$termo\"" : "Nenhum produto cadastrado") . '</h3>
                          </div>';
                } else {
                    echo '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">'; // Mantendo 4 colunas para layout responsivo, mas serão no máximo 8 itens.
                    foreach ($produtos as $produto) {
                        $fotoUrl = $produto['foto'] ? 'data:image/jpeg;base64,' . base64_encode($produto['foto']) : 'https://via.placeholder.com/200?text=Sem+Imagem';
                        $precoFormatado = number_format($produto['preco'], 2, ',', '.');

                        $onClickCard = 'onclick="mostrarDetalhes(' . $produto['id'] . ')"';

                        echo '<div class="col">
                                <div class="card h-100 produto-card" ' . $onClickCard . '>
                                    <div class="card-img-container">
                                        <img src="' . $fotoUrl . '" class="card-img-top" alt="Foto do produto">
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title text-truncate" title="' . htmlspecialchars($produto['nome']) . '">' . htmlspecialchars($produto['nome']) . '</h5>
                                        <p class="card-text">
                                            <span class="preco">R$ ' . $precoFormatado . '</span>
                                        </p>
                                    </div>
                                </div>
                            </div>';
                    }
                    echo '</div>';

                    // Renderizar controles de paginação
                    if ($totalPaginas > 1) {
                        echo '<nav aria-label="Paginação de produtos" class="mt-4">';
                        echo '<ul class="pagination justify-content-center">';

                        // Botão Anterior
                        if ($paginaAtual > 1) {
                            $linkAnterior = '?pagina=' . ($paginaAtual - 1) . ($termo ? '&termo=' . urlencode($termo) : '');
                            echo '<li class="page-item"><a class="page-link" href="' . $linkAnterior . '">Anterior</a></li>';
                        } else {
                            echo '<li class="page-item disabled"><span class="page-link">Anterior</span></li>';
                        }

                        // Links das páginas
                        for ($i = 1; $i <= $totalPaginas; $i++) {
                            $linkPagina = '?pagina=' . $i . ($termo ? '&termo=' . urlencode($termo) : '');
                            if ($i == $paginaAtual) {
                                echo '<li class="page-item active" aria-current="page"><span class="page-link">' . $i . '</span></li>';
                            } else {
                                echo '<li class="page-item"><a class="page-link" href="' . $linkPagina . '">' . $i . '</a></li>';
                            }
                        }

                        // Botão Próximo
                        if ($paginaAtual < $totalPaginas) {
                            $linkProximo = '?pagina=' . ($paginaAtual + 1) . ($termo ? '&termo=' . urlencode($termo) : '');
                            echo '<li class="page-item"><a class="page-link" href="' . $linkProximo . '">Próximo</a></li>';
                        } else {
                            echo '<li class="page-item disabled"><span class="page-link">Próximo</span></li>';
                        }

                        echo '</ul>';
                        echo '</nav>';
                    }
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Erro ao carregar produtos: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="produtoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="produtoNome"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="mensagemErro" class="alert alert-danger alert-dismissible fade show" role="alert">
                        <span id="mensagemErroTexto"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div id="mensagemSucesso" class="alert alert-success alert-dismissible fade show" role="alert">
                        Produto atualizado com sucesso!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <img id="produtoFoto" src="" class="img-fluid mb-3" alt="Foto do produto" style="max-height: 200px; object-fit: contain;">
                            <input type="file" id="produtoFotoInput" name="foto" class="form-control d-none" accept="image/jpeg,image/png,image/gif">
                        </div>
                        <div class="col-md-8">
                            <div id="visualizacao">
                                <p><strong>Descrição:</strong> <span id="produtoDescricao"></span></p>
                                <p><strong>Preço:</strong> <span id="produtoPreco"></span></p>
                                <p><strong>Anunciado por:</strong> <span id="produtoAnuncianteNome"></span></p>
                            </div>
                            <form id="editarForm" class="d-none">
                                <input type="hidden" id="produtoId" name="id">
                                <div class="mb-3">
                                    <label for="produtoNomeInput" class="form-label">Nome *</label>
                                    <input type="text" class="form-control" id="produtoNomeInput" name="nome" required>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoDescricaoInput" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="produtoDescricaoInput" name="descricao" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoPrecoInput" class="form-label">Preço *</label>
                                    <input type="number" step="0.01" class="form-control" id="produtoPrecoInput" name="preco" required>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    
                    <!-- Botão Tenho Interesse -->
                    <button id="btnTenhoInteresse" type="button" class="btn btn-success d-none" onclick="registrarInteresseProduto()">
                        <i class="bi bi-heart"></i> Tenho Interesse
                    </button>

                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                        <button id="btnEditar" class="btn btn-primary" onclick="alternarEdicao()">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <button id="btnSalvar" class="btn btn-primary d-none" onclick="salvarProduto()">
                            <i class="bi bi-save"></i> Salvar
                        </button>
                        <button id="btnExcluir" class="btn btn-danger" onclick="confirmarExclusao()">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o produto "<span id="confirmProdutoNome"></span>"?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a id="btnConfirmarExclusao" href="#" class="btn btn-danger">Excluir</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cadastro de Produto -->
    <div class="modal fade" id="cadastroProdutoModal" tabindex="-1" aria-labelledby="cadastroProdutoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cadastroProdutoModalLabel">Cadastro de Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <form id="formCadastroProduto" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome do Produto</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                                <div class="invalid-feedback">
                                    Por favor, informe o nome do produto.
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="preco" class="form-label">Preço</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" id="preco" name="preco" min="0" step="0.01" required>
                                </div>
                                <div class="invalid-feedback">
                                    Por favor, informe o preço.
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto do Produto</label>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="formCadastroProduto" class="btn btn-primary">Cadastrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    window.usuarioLogadoId = <?php echo json_encode(isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null); ?>;
    window.isAdmin = <?php echo json_encode(isset($_SESSION['is_admin']) && $_SESSION['is_admin']); ?>;

    // function verificarLogin() {
    //     if (!window.usuarioLogadoId) {
    //         if (confirm('Você precisa estar logado para adicionar produtos ao carrinho. Deseja fazer login agora?')) {
    //             // Salvar a URL atual para retornar após o login
    //             localStorage.setItem('returnUrl', window.location.href);
    //             window.location.href = 'login.php';
    //         }
    //         return false;
    //     }
    //     return true;
    // }

    // function adicionarProdutoDoModalAoCarrinho() {
    //     if (!verificarLogin()) {
    //         return;
    //     }
    //     const quantidade = parseInt(document.getElementById('quantidadeModalProduto').value) || 1;
    //     const produtoId = document.getElementById('produtoId').value;
    //     carrinho.adicionarItem(produtoId, quantidade);
    // }
    </script>
    <script src="./dashboard.js"></script>
    <!-- <script src="./carrinho.js"></script> Removido pois o arquivo não existe mais -->
    <script>
        function exibirProduto(produto) {
            if (produto.foto) {
                const fotoBase64 = btoa(String.fromCharCode.apply(null, new Uint8Array(produto.foto)));
                document.getElementById('produtoFoto').src = `data:image/jpeg;base64,${fotoBase64}`;
            } else {
                document.getElementById('produtoFoto').src = 'https://via.placeholder.com/200';
            }
        }

        // Debug dos formulários de adicionar ao carrinho
        // document.querySelectorAll('form[action="carrinho.php"]').forEach(form => {
        //     form.addEventListener('submit', function(e) {
        //         console.log('Formulário enviado:', {
        //             acao: this.querySelector('[name="acao"]').value,
        //             produto_id: this.querySelector('[name="produto_id"]').value,
        //             quantidade: this.querySelector('[name="quantidade"]').value,
        //             redirect: this.querySelector('[name="redirect"]').value
        //         });
        //     });
        // });
    </script>
</body>
</html>