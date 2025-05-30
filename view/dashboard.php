<?php
session_start(); 
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../model/produto.php';
require_once __DIR__ . '/../dao/mensagem_dao.php';
require_once __DIR__ . '/../dao/cliente_dao.php';

$nome_usuario = "Usuário"; 
$contador_mensagens_nao_lidas = 0;
$id_usuario_logado = $_SESSION['usuario_id'] ?? null;

try {
    if ($id_usuario_logado) {
        $pdo = Database::getConnection();
        $clienteDAO = new ClienteDAO();
        $cliente = $clienteDAO->buscarPorId($id_usuario_logado);
        if ($cliente) {
            $nome_usuario = $cliente->getNome();
            if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_nome'] !== $nome_usuario) {
                 $_SESSION['usuario_nome'] = $nome_usuario;
            }
        }

        $mensagemDAO = new MensagemDAO(); 
        $contador_mensagens_nao_lidas = $mensagemDAO->contarMensagensNaoLidas($id_usuario_logado);
    }
    $mensagem_feedback = $_GET['mensagem'] ?? ''; 
    $tipoMensagem_feedback = $_GET['tipo_mensagem'] ?? ''; 

} catch (Exception $e) {
    error_log("Erro na inicialização do dashboard (dados de usuário/mensagens): " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ECOxChange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="side-nav-bar">
        <div class="logo-container">
            <div class="logo">ECO<span>Exchange</span></div>
        </div>

        <a href="dashboard.php" class="active">
            <i class="bi bi-speedometer2"></i>
            <span>Pagina Inicial</span>
        </a>
        
        <?php if ($id_usuario_logado): ?>
            <a href="#" data-bs-toggle="modal" data-bs-target="#cadastroProdutoModal">
                <i class="bi bi-plus-circle"></i>
                <span>Cadastrar Produto</span>
            </a>
            <a href="meus_produtos.php">
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
        <div class="search-bar-container">
            <div class="search-bar-md3">
                <form id="searchFormGlobal" method="GET" action="dashboard.php" class="d-flex flex-grow-1">
                    <input type="text" id="searchInputGlobal" name="termo" class="form-control flex-grow-1" placeholder="O que você está procurando hoje?" value="<?= htmlspecialchars($_GET['termo'] ?? '') ?>">
                    <button type="submit" class="btn">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="ecological-info-banner">
            <p>Prolongar a vida útil dos produtos é um passo essencial para um futuro mais verde. Ao dar uma nova chance a itens usados, você contribui ativamente para a redução do desperdício e promove a sustentabilidade. Juntos, podemos fazer a diferença!</p>
        </div>

        <div class="products-section container-fluid">
            <?php if (!empty($mensagem_feedback)): ?>
                <div class="alert alert-<?= $tipoMensagem_feedback === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensagem_feedback, ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div id="produtosContainer">
                <?php 
                try {
                    if (!isset($pdo)) $pdo = Database::getConnection(); 
                    $produtoDao = new ProdutoDAO();

                    $itensPorPagina = 8;
                    $paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
                    if ($paginaAtual < 1) $paginaAtual = 1;
                    $offset = ($paginaAtual - 1) * $itensPorPagina;
                    $termo_busca = $_GET['termo'] ?? '';
                    
                    $produtos = $produtoDao->buscarProdutos($termo_busca, $itensPorPagina, $offset);
                    $totalProdutos = $produtoDao->contarProdutosBuscados($termo_busca);
                    $totalPaginas = ceil($totalProdutos / $itensPorPagina);

                    if (empty($produtos)) {
                        echo '<div class="empty-state">
                                <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                                <h3 class="mt-3">' . ($termo_busca ? "Nenhum produto encontrado para \"" . htmlspecialchars($termo_busca) . "\"" : "Nenhum produto cadastrado") . '</h3>
                              </div>';
                    } else {
                        echo '<div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">'; 
                        foreach ($produtos as $produto) {
                            $fotoUrl = $produto['foto'] ? 'data:image/jpeg;base64,' . base64_encode($produto['foto']) : 'https://via.placeholder.com/200?text=Sem+Imagem';
                            $precoFormatado = number_format($produto['preco'], 2, ',', '.');

                            $onClickCard = 'onclick="mostrarDetalhes(' . $produto['id'] . ')"';

                            echo '<div class="col">
                                    <div class="card h-100 produto-card" ' . $onClickCard . '>
                                        <div class="card-img-half-circle-wrapper">
                                            <div class="card-img-container">';
                            if (!empty($produto['foto'])) {
                                $fotoDataUri = 'data:image/jpeg;base64,' . base64_encode($produto['foto']);
                                echo '<img src="' . $fotoDataUri . '" class="card-img-top" alt="Foto de ' . htmlspecialchars($produto['nome']) . '">';
                            } else {
                                echo '<i class="bi bi-image-alt card-img-placeholder-icon"></i>'; 
                            }
                            echo '            </div>
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title text-truncate" title="' . htmlspecialchars($produto['nome']) . '">' . (function_exists('mb_strimwidth') ? htmlspecialchars(mb_strimwidth($produto['nome'], 0, 20, "...")) : htmlspecialchars(substr($produto['nome'], 0, 18) . (strlen($produto['nome']) > 20 ? "..." : ""))) . '</h5>
                                            <p class="card-text">
                                                <span class="preco">R$ ' . $precoFormatado . '</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>';
                        }
                        echo '</div>';

                        if ($totalPaginas > 1) {
                            echo '<nav aria-label="Paginação de produtos" class="mt-4">';
                            echo '<ul class="pagination justify-content-center">';

                            if ($paginaAtual > 1) {
                                $linkAnterior = '?pagina=' . ($paginaAtual - 1) . ($termo_busca ? '&termo=' . urlencode($termo_busca) : '');
                                echo '<li class="page-item"><a class="page-link" href="' . $linkAnterior . '">Anterior</a></li>';
                            } else {
                                echo '<li class="page-item disabled"><span class="page-link">Anterior</span></li>';
                            }

                            for ($i = 1; $i <= $totalPaginas; $i++) {
                                $linkPagina = '?pagina=' . $i . ($termo_busca ? '&termo=' . urlencode($termo_busca) : '');
                                if ($i == $paginaAtual) {
                                    echo '<li class="page-item active" aria-current="page"><span class="page-link">' . $i . '</span></li>';
                                } else {
                                    echo '<li class="page-item"><a class="page-link" href="' . $linkPagina . '">' . $i . '</a></li>';
                                }
                            }

                            if ($paginaAtual < $totalPaginas) {
                                $linkProximo = '?pagina=' . ($paginaAtual + 1) . ($termo_busca ? '&termo=' . urlencode($termo_busca) : '');
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
    </div>

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
                    
                    <button id="btnTenhoInteresse" type="button" class="btn btn-success d-none" onclick="registrarInteresseProduto()">
                        <i class="bi bi-heart"></i> Tenho Interesse
                    </button>

                    <button id="btnProporTroca" type="button" class="btn btn-info d-none" onclick="abrirModalPropostaTroca()">
                        <i class="bi bi-arrow-repeat"></i> Propor Troca
                    </button>

                    <a href="#" id="btnEnviarMensagemVendedor" class="btn btn-primary d-none">
                        <i class="bi bi-send"></i> Enviar Mensagem ao Vendedor
                    </a>

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

    <!-- Modal para Proposta de Troca -->
    <div class="modal fade" id="propostaTrocaModal" tabindex="-1" aria-labelledby="propostaTrocaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="propostaTrocaModalLabel">Selecione um produto para propor a troca</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="listaProdutosUsuarioParaTroca" class="list-group">
                        <!-- Produtos do usuário serão listados aqui -->
                    </div>
                    <div id="nenhumProdutoParaTroca" class="alert alert-info d-none" role="alert">
                        Você não possui produtos cadastrados para propor uma troca.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <!-- Botão para confirmar a seleção do produto e enviar a proposta será adicionado dinamicamente ou ter uma lógica específica -->
                </div>
            </div>
        </div>
    </div>

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


    </script>
    <script src="./dashboard.js"></script>
    <script>
        function exibirProduto(produto) {
            if (produto.foto) {
                const fotoBase64 = btoa(String.fromCharCode.apply(null, new Uint8Array(produto.foto)));
                document.getElementById('produtoFoto').src = `data:image/jpeg;base64,${fotoBase64}`;
            } else {
                document.getElementById('produtoFoto').src = 'https://via.placeholder.com/200';
            }
        }

    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>