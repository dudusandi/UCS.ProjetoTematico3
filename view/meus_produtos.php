<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php?erro=login_necessario");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../dao/mensagem_dao.php';
require_once __DIR__ . '/../dao/cliente_dao.php';

$id_usuario_logado = $_SESSION['usuario_id'];
$nome_usuario = "Usuário"; 
$contador_mensagens_nao_lidas = 0;

try {
    $pdo = Database::getConnection(); 

    $clienteDAO = new ClienteDAO();
    $cliente_logado_info = $clienteDAO->buscarPorId($id_usuario_logado);
    if ($cliente_logado_info) {
        $nome_usuario = $cliente_logado_info->getNome();
        if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_nome'] !== $nome_usuario) {
             $_SESSION['usuario_nome'] = $nome_usuario;
        }
    } else {
    }

    $mensagemDAO = new MensagemDAO();
    $contador_mensagens_nao_lidas = $mensagemDAO->contarMensagensNaoLidas($id_usuario_logado);
    
    $produtoDao = new ProdutoDAO(); 

} catch (Exception $e) {
    error_log("Erro na inicialização de dados em meus_produtos.php: " . $e->getMessage());
}

$mensagem_feedback = $_GET['mensagem'] ?? '';
$tipoMensagem_feedback = $_GET['tipo_mensagem'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Produtos - ECOxChange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="dashboard.css"> <!-- CSS unificado -->
    <style>
        .list-group-item img {
            width: 70px; 
            height: 70px; 
            object-fit: cover; 
            margin-right: 15px; 
            border-radius: 4px;
        }
        .img-placeholder-icon {
            width: 70px; 
            height: 70px; 
            background-color: #e9ecef; 
            margin-right: 15px; 
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
         .list-group-item .btn-group .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="side-nav-bar">
        <div class="logo-container">
            <div class="logo">ECO<span>Exchange</span></div>
        </div>

        <a href="dashboard.php">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        
        <?php if ($id_usuario_logado): ?>
            <a href="#" data-bs-toggle="modal" data-bs-target="#cadastroProdutoModalDashboard"> 
                <i class="bi bi-plus-circle"></i>
                <span>Cadastrar Produto</span>
            </a>
            <a href="meus_produtos.php" class="active"> 
                <i class="bi bi-archive"></i>
                <span>Meus Produtos</span>
            </a>
            <a href="minhas_mensagens.php" class="position-relative">
                <i class="bi bi-chat-left-dots"></i>
                <span>Minhas Mensagens</span>
                <?php if ($contador_mensagens_nao_lidas > 0): ?>
                    <span class="badge bg-danger position-absolute top-50 start-100 translate-middle-y ms-2" style="font-size: 0.65em; padding: 0.3em 0.5em;"><?php echo $contador_mensagens_nao_lidas; ?></span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
            <a href="../view/listar_clientes.php">
                <i class="bi bi-people"></i>
                <span>Gerenciar Clientes</span>
            </a> 
        <?php endif; ?>

        <?php if ($id_usuario_logado): ?>
        <div class="notifications-section-container">
            <div class="notifications-header">
                <i class="bi bi-bell"></i>
                <span>Notificações</span>
                <span id="contadorNotificacoesSideNav" class="badge bg-danger ms-2" style="font-size: 0.7em; padding: 0.3em 0.5em; <?php echo ($contador_mensagens_nao_lidas > 0 ? '' : 'display:none;'); ?>">
                    <?php echo $contador_mensagens_nao_lidas; ?> 
                </span>
            </div>
            <ul class="notifications-list" id="listaNotificacoesSideNav">
                <li id="notificacaoItemLoadingSideNav" class="dropdown-item text-muted">Carregando...</li>
                <li id="notificacaoItemNenhumaSideNav" class="dropdown-item text-muted d-none">Nenhuma notificação nova.</li>
                <li id="marcarTodasLidasContainerSideNav" class="d-none"><a class="dropdown-item text-center" href="#" id="marcarTodasLidasLinkSideNav" onclick="marcarTodasComoLidas(event)">Marcar todas como lidas</a></li> 
            </ul>
        </div>
        <?php endif; ?>

        <div class="user-info-nav">
            <?php if ($id_usuario_logado): ?>
                <span><i class="bi bi-person-circle"></i> <?= htmlspecialchars($nome_usuario) ?></span>
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

    <div class="main-content">
        <div class="products-section container-fluid mt-3" style="max-width: 70%; margin-left: auto; margin-right: auto;">
            
            <div class="search-bar-container mb-3">
                <div class="search-bar-md3">
                    <form id="searchFormMeusProdutos" method="GET" action="meus_produtos.php" class="d-flex flex-grow-1">
                        <input type="text" id="searchInputMeusProdutos" name="termo" class="form-control flex-grow-1" placeholder="Pesquisar em Meus Produtos..." value="<?= htmlspecialchars($_GET['termo'] ?? '') ?>">
                        <button type="submit" class="btn">
                            <i class="bi bi-search"></i>
                        </button>
                    </form>
                </div>
            </div>

            <?php if (!empty($mensagem_feedback)): ?>
                <div class="alert alert-<?= $tipoMensagem_feedback === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensagem_feedback, ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div id="meusProdutosContainer">
                <?php
                if ($produtoDao) { 
                    try {
                        $itensPorPagina = 8;
                        $paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
                        if ($paginaAtual < 1) $paginaAtual = 1;
                        $offset = ($paginaAtual - 1) * $itensPorPagina;
                        $termo_busca_meus_produtos = $_GET['termo'] ?? ''; 

                        $produtos = $produtoDao->buscarPorUsuarioId($id_usuario_logado, $termo_busca_meus_produtos, $itensPorPagina, $offset);
                        $totalProdutos = $produtoDao->contarProdutosPorUsuarioId($id_usuario_logado, $termo_busca_meus_produtos);
                        $totalPaginas = ceil($totalProdutos / $itensPorPagina);

                        if (empty($produtos)) {
                            echo '<div class="empty-state">';
                            echo '    <i class="bi bi-dropbox" style="font-size: 4rem;"></i>';
                            echo '    <h3 class="mt-3">' . ($termo_busca_meus_produtos ? "Nenhum produto seu encontrado para \"" . htmlspecialchars($termo_busca_meus_produtos) . "\"" : "Você ainda não cadastrou nenhum produto.") . '</h3>';
                            echo '    <p class="mt-2">Que tal <a href="#" data-bs-toggle="modal" data-bs-target="#cadastroProdutoModalDashboard">cadastrar seu primeiro produto</a> agora?</p>';
                            echo '</div>';
                        } else {
                            echo '<ul class="list-group">';
                            foreach ($produtos as $produto) {
                                $precoFormatado = number_format($produto['preco'] ?? 0.0, 2, ',', '.');

                                echo '<li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">';
                                echo '    <div class="d-flex align-items-center me-3 mb-2 mb-md-0" style="flex-grow: 1; min-width: 200px; cursor:pointer;" onclick="abrirModalEdicao(' . $produto['id'] . ')">'; // Adicionado flex-grow e min-width
                                
                                if (!empty($produto['foto'])) {
                                    $fotoUrl = 'data:image/jpeg;base64,' . base64_encode($produto['foto']);
                                    echo '        <img src="' . $fotoUrl . '" alt="' . htmlspecialchars($produto['nome']) . '">';
                                } else {
                                    echo '        <div class="img-placeholder-icon d-flex align-items-center justify-content-center">';
                                    echo '            <i class="bi bi-card-image" style="font-size: 2.5rem; color: #6c757d;"></i>';
                                    echo '        </div>';
                                }

                                echo '        <div>';
                                echo '            <h5 class="mb-1 text-truncate" title="' . htmlspecialchars($produto['nome']) . '">' . htmlspecialchars($produto['nome']) . '</h5>';
                                echo '            <p class="mb-0 text-muted">R$ ' . $precoFormatado . '</p>';
                                echo '        </div>';
                                echo '    </div>';
                                echo '    <div class="btn-group mt-2 mt-md-0" role="group" aria-label="Ações do produto">'; 
                                echo '        <button class="btn btn-sm btn-outline-primary" onclick="abrirModalEdicao(' . $produto['id'] . ')">';
                                echo '            <i class="bi bi-pencil-square"></i> Editar';
                                echo '        </button>';
                                echo '        <button class="btn btn-sm btn-outline-danger" onclick="confirmarExclusao(' . $produto['id'] . ', \'' . htmlspecialchars(addslashes($produto['nome']), ENT_QUOTES) . '\')">';
                                echo '            <i class="bi bi-trash"></i> Excluir';
                                echo '        </button>';
                                echo '    </div>';
                                echo '</li>';
                            }
                            echo '</ul>';

                            if ($totalPaginas > 1) {
                                echo '<nav aria-label="Paginação de Meus Produtos" class="mt-4">';
                                echo '<ul class="pagination justify-content-center">';
                                $queryString = $termo_busca_meus_produtos ? '&termo=' . urlencode($termo_busca_meus_produtos) : '';
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
                     echo '<div class="alert alert-danger">' . htmlspecialchars($mensagem_feedback) . '</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="produtoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="produtoModalNome">Editar Produto</h5>
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

    <div class="modal fade" id="cadastroProdutoModalDashboard" tabindex="-1" aria-labelledby="cadastroProdutoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cadastroProdutoModalLabel">Cadastrar Novo Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="mensagemErroCadastro" class="alert alert-danger d-none" role="alert"></div>
                    <div id="mensagemSucessoCadastro" class="alert alert-success d-none" role="alert"></div>
                    <form id="formCadastroProdutoDashboard" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cadastroNome" class="form-label">Nome do Produto *</label>
                                <input type="text" class="form-control" id="cadastroNome" name="nome" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cadastroPreco" class="form-label">Preço *</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" class="form-control" id="cadastroPreco" name="preco" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="cadastroDescricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="cadastroDescricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="cadastroFoto" class="form-label">Foto do Produto</label>
                            <input type="file" class="form-control" id="cadastroFoto" name="foto" accept="image/*">
                            <div class="form-text">Formatos aceitos: JPG, PNG, GIF.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnSalvarCadastroProdutoDashboard" class="btn btn-primary">Cadastrar Produto</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.usuarioLogadoId = <?= json_encode($_SESSION['usuario_id'] ?? null); ?>;
        window.isAdmin = <?= json_encode(isset($_SESSION['is_admin']) && $_SESSION['is_admin']); ?>; 

        const produtoModalElement = document.getElementById('produtoModal');
        const produtoModalInstance = new bootstrap.Modal(produtoModalElement);
        const confirmExcluirModalElement = document.getElementById('confirmExcluirModal');
        const confirmExcluirModalInstance = new bootstrap.Modal(confirmExcluirModalElement);
        let produtoIdParaExcluir = null;

        const cadastroProdutoModalDashboardElement = document.getElementById('cadastroProdutoModalDashboard');
        let cadastroProdutoModalDashboardInstance = null;
        if (cadastroProdutoModalDashboardElement) {
            cadastroProdutoModalDashboardInstance = new bootstrap.Modal(cadastroProdutoModalDashboardElement);
        }
        
        document.getElementById('btnSalvarCadastroProdutoDashboard')?.addEventListener('click', async function() {
            const form = document.getElementById('formCadastroProdutoDashboard');
            const formData = new FormData(form);
            const msgErroCadastro = document.getElementById('mensagemErroCadastro');
            const msgSucessoCadastro = document.getElementById('mensagemSucessoCadastro');

            msgErroCadastro.classList.add('d-none');
            msgSucessoCadastro.classList.add('d-none');

            try {
                const response = await fetch('../controllers/cadastrar_produto.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    msgSucessoCadastro.textContent = result.message || 'Produto cadastrado com sucesso!';
                    msgSucessoCadastro.classList.remove('d-none');
                    form.reset();
                    setTimeout(() => {
                        cadastroProdutoModalDashboardInstance.hide();
                        window.location.reload(); 
                    }, 1500);
                } else {
                    msgErroCadastro.textContent = result.error || 'Erro desconhecido ao cadastrar produto.';
                    msgErroCadastro.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Erro ao cadastrar produto:', error);
                msgErroCadastro.textContent = 'Erro de comunicação ao cadastrar. Tente novamente.';
                msgErroCadastro.classList.remove('d-none');
            }
        });


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
                    document.getElementById('produtoModalNome').textContent = 'Editar: ' + p.nome;
                    document.getElementById('produtoModalNomeInput').value = p.nome;
                    document.getElementById('produtoModalDescricaoInput').value = p.descricao;
                    document.getElementById('produtoModalPrecoInput').value = p.preco;
                    document.getElementById('produtoModalFoto').src = p.foto ? `data:image/jpeg;base64,${p.foto}` : 'https://via.placeholder.com/200?text=Sem+Imagem';
                    document.getElementById('produtoModalFotoInput').value = '';
                    produtoModalInstance.show();
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
                        produtoModalInstance.hide();
                        window.location.reload();
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
        
        function confirmarExclusao(id, nome) {
            document.getElementById('confirmExcluirNomeProduto').textContent = nome;
            produtoIdParaExcluir = id;
            confirmExcluirModalInstance.show();
        }

        document.getElementById('btnConfirmarExclusaoDefinitiva').addEventListener('click', async function() {
            if (!produtoIdParaExcluir) return;
            try {
                const response = await fetch('../controllers/excluir_produto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
                    body: `id=${produtoIdParaExcluir}`
                });
                const result = await response.json();
                if (result.success) {
                    confirmExcluirModalInstance.hide();
                    window.location.href = 'meus_produtos.php?mensagem=Produto excluído com sucesso&tipo_mensagem=sucesso';
                } else {
                    alert('Erro ao excluir produto: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao excluir produto:', error);
                alert('Erro de comunicação ao excluir produto.');
            }
        });

        document.getElementById('produtoModalFotoInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) { document.getElementById('produtoModalFoto').src = e.target.result; }
                reader.readAsDataURL(file);
            }
        });

        function marcarTodasComoLidasClientSide(event, prefix = '') {
            event.preventDefault();
            const contadorNotificacoes = document.getElementById('contadorNotificacoesSideNav'); 
            const listaNotificacoes = document.getElementById('listaNotificacoesDropdown' + prefix); 
            
            if (contadorNotificacoes) {
                contadorNotificacoes.style.display = 'none';
            }
            
            if(listaNotificacoes){
                const itensNotificacao = listaNotificacoes.querySelectorAll('.notificacao-nao-lida'); 
                itensNotificacao.forEach(item => item.classList.remove('notificacao-nao-lida'));

                const loadingItem = listaNotificacoes.querySelector('#notificacaoItemLoading' + prefix);
                const nenhumaItem = listaNotificacoes.querySelector('#notificacaoItemNenhuma' + prefix);
                const dividerFinal = listaNotificacoes.querySelector('#notificacoesDividerFinal' + prefix);
                const verTodasLink = listaNotificacoes.querySelector('#verTodasNotificacoesLink' + prefix);
                const marcarLidasLink = listaNotificacoes.querySelector('#marcarTodasLidasLink' + prefix);

                if(loadingItem) loadingItem.classList.add('d-none');
                if(nenhumaItem) nenhumaItem.classList.remove('d-none');
                if(dividerFinal) dividerFinal.classList.add('d-none');
                if(verTodasLink) verTodasLink.classList.add('d-none');
                if(marcarLidasLink) marcarLidasLink.classList.add('d-none');
            }
        }
    </script>
</body>
</html> 