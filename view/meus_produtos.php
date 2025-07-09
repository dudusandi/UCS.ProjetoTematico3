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
        error_log("Cliente com ID $id_usuario_logado não encontrado em meus_produtos.php");
    }

    $mensagemDAO = new MensagemDAO();
    $contador_mensagens_nao_lidas = $mensagemDAO->contarMensagensNaoLidas($id_usuario_logado);
    
    $produtoDao = new ProdutoDAO(); 

} catch (Exception $e) {
    error_log("Erro na inicialização de dados em meus_produtos.php: " . $e->getMessage());
    if (!isset($produtoDao)) $produtoDao = null; 
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
        
        .main-content, .main-content > *, .products-section > * {
            box-sizing: border-box;
        }
        body {
            display: flex; 
            min-height: 100vh;
        }
        .main-content {
            flex-grow: 1; 
            padding: 0 !important; 
        }
        
        
        .main-content > .search-bar-container {
            margin-left: 20px !important;
            margin-right: 20px !important;
            padding: 20px 0 !important; 
            width: calc(100% - 40px); 
        }

        .main-content > .products-section {
            padding: 20px !important; 
             width: calc(100% - 40px); 
             margin-left: 20px !important; 
             margin-right: 20px !important; 
        }

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

            border-radius: 18px;
            background: #f6f9fb;
            box-shadow: 0 8px 32px 0 rgba(60, 80, 120, 0.10);
            border: none;
        }

            border-bottom: none;
            padding-bottom: 0;
            justify-content: center;
        }

            font-weight: 600;
            color: #3a3a4a;
            text-align: center;
            width: 100%;
        }

            padding-top: 0;
        }

            font-weight: 500;
            color: #4a4a5a;
        }

            border-radius: 8px;
            border: 1px solid #e0e6ed;
            background: #fff;
            box-shadow: 0 2px 8px rgba(60, 80, 120, 0.04);
            font-size: 1rem;
            margin-bottom: 8px;
        }

            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102,126,234,0.10);
        }

            text-align: center;
            margin-bottom: 20px;
            border: 2px dashed #dbeafe;
            padding: 18px;
            border-radius: 10px;
            background: #f0f4fa;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-color 0.2s;
        }

            border-color: #667eea;
        }

            max-width: 100%;
            max-height: 160px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(60, 80, 120, 0.08);
        }

            color: #7a7a8c;
            font-style: italic;
            font-size: 1rem;
        }

            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border: none;
            font-weight: 500;
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(102,126,234,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }

            background: linear-gradient(90deg, #764ba2 0%, #667eea 100%);
            box-shadow: 0 4px 16px rgba(102,126,234,0.12);
        }

            border-top: none;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../menu.php'; ?>

    <div class="main-content">
        <!-- Barra de Busca como filho direto de main-content -->
        <div class="search-bar-container"> <!-- Deve herdar padding: 20px 0; e margin-left/right: 20px de dashboard.css -->
            <div class="search-bar-md3">
                <form id="searchFormMeusProdutos" method="GET" action="meus_produtos.php" class="d-flex flex-grow-1">
                    <input type="text" id="searchInputMeusProdutos" name="termo" class="flex-grow-1" placeholder="Pesquisar em Meus Produtos..." value="<?= htmlspecialchars($_GET['termo'] ?? '') ?>">
                    <button type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="products-section"> <!-- Removida classe container-fluid -->
            
            <div class="text-center mb-4">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCadastroProduto">
                    <i class="bi bi-plus-circle"></i> Cadastrar Produto
                </button>
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
                                echo '    <div class="d-flex align-items-center me-3 mb-2 mb-md-0" style="flex-grow: 1; min-width: 200px; cursor:pointer;" onclick="abrirModalEdicao(' . $produto['id'] . ')">'; 
                                
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
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <label for="produtoModalNomeInput" class="form-label">Nome *</label>
                                        <input type="text" class="form-control" id="produtoModalNomeInput" name="nome" required>
                                        <div class="invalid-feedback">Por favor, informe o nome do produto.</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="produtoModalPrecoInput" class="form-label">Preço *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">R$</span>
                                            <input type="number" class="form-control" id="produtoModalPrecoInput" name="preco" min="0" step="0.01" required>
                                        </div>
                                        <div class="invalid-feedback">Por favor, informe o preço.</div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="produtoModalDescricaoInput" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="produtoModalDescricaoInput" name="descricao" rows="3"></textarea>
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

    <!-- Modal de Cadastro de Produto -->
    <div class="modal fade" id="modalCadastroProduto" tabindex="-1" aria-labelledby="modalCadastroProdutoLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title w-100 text-center" id="modalCadastroProdutoLabel">Cadastrar Novo Produto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <form id="formCadastroProdutoPagina" action="../controllers/cadastrar_produto.php" method="POST" enctype="multipart/form-data">
              <div class="mb-4">
                <label for="foto" class="form-label">Foto do Produto</label>
                <div class="image-preview-container text-center" id="imagePreviewContainer" style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; cursor: pointer;">
                  <img src="#" alt="Pré-visualização da Imagem" class="d-none mx-auto" id="imagePreview" style="max-width: 100%; max-height: 250px; object-fit: contain;">
                  <span class="image-preview-text" id="imagePreviewText">Clique ou arraste para adicionar uma imagem (JPG, PNG, GIF)</span>
                </div>
                <input type="file" class="form-control d-none" id="foto" name="foto" accept="image/jpeg,image/png,image/gif">
                <div class="form-text">Tamanho máximo: 2MB. Uma boa imagem ajuda a vender!</div>
              </div>
              <div class="row mb-3">
                <div class="col-md-8">
                  <label for="nome" class="form-label">Nome do Produto *</label>
                  <input type="text" class="form-control" id="nome" name="nome" required>
                  <div class="invalid-feedback">Por favor, informe o nome do produto.</div>
                </div>
                <div class="col-md-4">
                  <label for="preco" class="form-label">Preço *</label>
                  <div class="input-group">
                    <span class="input-group-text">R$</span>
                    <input type="number" class="form-control" id="preco" name="preco" min="0" step="0.01" required>
                  </div>
                  <div class="invalid-feedback">Por favor, informe o preço.</div>
                </div>
              </div>
              <div class="mb-3">
                <label for="descricao" class="form-label">Descrição Detalhada</label>
                <textarea class="form-control" id="descricao" name="descricao" rows="4" placeholder="Descreva o estado do produto, motivo da troca/venda, etc."></textarea>
              </div>
              <div class="mt-4 text-center">
                <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle"></i> Cadastrar Produto</button>
              </div>
            </form>
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

        
        (function() {
            const fotoInput = document.getElementById('foto');
            const imagePreviewContainer = document.getElementById('imagePreviewContainer');
            const imagePreview = document.getElementById('imagePreview');
            const imagePreviewText = document.getElementById('imagePreviewText');
            if (imagePreviewContainer && fotoInput) {
                imagePreviewContainer.addEventListener('click', function() {
                    fotoInput.click();
                });
                fotoInput.addEventListener('change', function (event) {
                    const file = event.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            imagePreview.src = e.target.result;
                            imagePreview.classList.remove('d-none');
                            imagePreviewText.classList.add('d-none');
                        }
                        reader.readAsDataURL(file);
                    } else {
                        imagePreview.src = '#';
                        imagePreview.classList.add('d-none');
                        imagePreviewText.classList.remove('d-none');
                    }
                });
            }
            
            const form = document.getElementById('formCadastroProdutoPagina');
            if (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            }
        })();

        
        document.getElementById('formCadastroProdutoPagina').addEventListener('submit', async function(event) {
            event.preventDefault(); 
            const form = event.target;
            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message || 'Produto cadastrado com sucesso!');
                    location.reload(); 
                } else {
                    alert('Erro ao cadastrar produto: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao cadastrar produto:', error);
                alert('Erro de comunicação ao cadastrar produto. Tente novamente.');
            }
        });
    </script>
</body>
</html> 