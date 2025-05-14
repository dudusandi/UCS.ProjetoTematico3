<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../model/produto.php';
require_once __DIR__ . '/../dao/fornecedor_dao.php'; 

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    $fornecedorDao = new FornecedorDAO($pdo); 

    // Busca lista de fornecedores
    $fornecedores = [];
    try {
        $fornecedores = $fornecedorDao->listarFornecedores();
    } catch (Exception $e) {
        error_log("Erro ao listar fornecedores: " . $e->getMessage());
        $mensagem = "Erro ao carregar fornecedores: " . $e->getMessage();
        $tipoMensagem = 'erro';
    }

    // Mensagens
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
    <title>UcsExpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="header">
        <div class="logo">UCS<span>express</span></div>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Pesquisar produtos..." autocomplete="off">
        </div>
        <div class="user-options">
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
            <a href="../controllers/logout_controller.php">Sair</a>
            <div class="cart">
                <span>0</span>
                🛒
            </div>
        </div>
    </div>
    <!-- Menu com visualização apenas para o admin -->
    <div class="nav-bar">
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cadastroProdutoModal">
                <i class="bi bi-plus-circle"></i> Cadastrar Produto
            </button>
            <a href="../view/listar_fornecedor.php">Editar Fornecedores</a> 
            <a href="../view/listar_clientes.php">Editar Clientes</a> 
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
            <!-- Produtos serão carregados dinamicamente -->
        </div>
        <!-- Sentinela para carregamento infinito -->
        <div id="sentinela" style="height: 20px;"></div>
        <!-- Indicador de carregamento -->
        <div id="loading" class="text-center my-3 d-none">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div class="modal fade" id="detalhesModal" tabindex="-1" aria-hidden="true">
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
                                <p><strong>Fornecedor:</strong> <span id="produtoFornecedor"></span></p>
                                <p><strong>Estoque:</strong> <span id="produtoEstoque"></span></p>
                                <p><strong>Preço:</strong> <span id="produtoPreco"></span></p>
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
                                    <label for="produtoFornecedorInput" class="form-label">Fornecedor *</label>
                                    <select class="form-control" id="produtoFornecedorInput" name="fornecedor" required></select>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoEstoqueInput" class="form-label">Estoque *</label>
                                    <input type="number" class="form-control" id="produtoEstoqueInput" name="estoque" min="0" required>
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
                    <button id="btnEditar" class="btn btn-primary d-none" onclick="alternarEdicao()">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                    <button id="btnSalvar" class="btn btn-primary d-none" onclick="salvarProduto()">
                        <i class="bi bi-save"></i> Salvar
                    </button>
                    <button id="btnExcluir" class="btn btn-danger" onclick="confirmarExclusao()">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
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
                                <label for="fornecedor" class="form-label">Fornecedor</label>
                                <select class="form-select" id="fornecedor" name="fornecedor_id" required>
                                    <option value="">Selecione um fornecedor</option>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor, selecione um fornecedor.
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantidade" class="form-label">Quantidade</label>
                                <input type="number" class="form-control" id="quantidade" name="quantidade" min="0" required>
                                <div class="invalid-feedback">
                                    Por favor, informe a quantidade.
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
    window.fornecedores = <?php echo json_encode($fornecedores); ?>;
    window.usuarioLogadoId = <?php echo json_encode($_SESSION['usuario_id']); ?>;
    window.isAdmin = <?php echo json_encode(isset($_SESSION['is_admin']) && $_SESSION['is_admin']); ?>;
    </script>
    <script src="./dashboard.js"></script>
    <script>
        // Preencher select de fornecedores
        document.addEventListener('DOMContentLoaded', function() {
            const fornecedorSelect = document.getElementById('fornecedor');
            if (window.fornecedores && window.fornecedores.length > 0) {
                window.fornecedores.forEach(fornecedor => {
                    const option = document.createElement('option');
                    option.value = fornecedor.id;
                    option.text = fornecedor.nome;
                    fornecedorSelect.appendChild(option);
                });
            }
        });
    </script>
</body>
</html>
