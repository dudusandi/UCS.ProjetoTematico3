<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/fornecedor_dao.php';
require_once '../model/fornecedor.php';
require_once '../model/endereco.php';

// Verifica se o ID 
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: listar_fornecedor.php?mensagem=ID do fornecedor não fornecido');
    exit;
}

// Busca o fornecedor pelo ID
try {
    $fornecedorDAO = new FornecedorDAO(Database::getConnection());
    $fornecedor = $fornecedorDAO->buscarPorId($id);
    if (!$fornecedor) {
        header('Location: listar_fornecedor.php?mensagem=Fornecedor não encontrado');
        exit;
    }
} catch (Exception $e) {
    header('Location: listar_fornecedor.php?mensagem=Erro ao buscar fornecedor: ' . urlencode($e->getMessage()));
    exit;
}

// Dados do fornecedor e do endereço
$endereco = $fornecedor->getEndereco();
$mensagem = $_GET['mensagem'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Fornecedor - ECOxchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="editar.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="endereco.js"></script>
</head>
<body>
    <!-- Cabeçalho -->
    <div class="header">
        <div class="logo">ECO<span>xchange</span></div>
    </div>

    <div class="container">
        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo strpos($mensagem, 'Erro') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário de Edição -->
        <div class="form-section">
            <h2>Editar Fornecedor</h2>
            <form id="formFornecedor" action="../controllers/fornecedor_controller.php" method="POST">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" value="<?= $fornecedor->getId() ?>">

                <!-- Dados do Fornecedor -->
                <div class="mb-3">
                    <label for="nome" class="form-label">Nome *</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($fornecedor->getNome(), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($fornecedor->getDescricao() ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="telefone" class="form-label">Telefone *</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" value="<?= htmlspecialchars($fornecedor->getTelefone(), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($fornecedor->getEmail(), ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <!-- Endereço -->
                <h5 class="mt-4">Endereço</h5>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="rua" class="form-label">Rua *</label>
                        <input type="text" class="form-control" id="rua" name="rua" value="<?= htmlspecialchars($endereco->getRua(), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="numero" class="form-label">Número *</label>
                        <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($endereco->getNumero(), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="complemento" class="form-label">Complemento</label>
                    <input type="text" class="form-control" id="complemento" name="complemento" value="<?= htmlspecialchars($endereco->getComplemento() ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="bairro" class="form-label">Bairro *</label>
                        <input type="text" class="form-control" id="bairro" name="bairro" value="<?= htmlspecialchars($endereco->getBairro(), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cep" class="form-label">CEP *</label>
                        <input type="text" class="form-control" id="cep" name="cep" value="<?= htmlspecialchars($endereco->getCep(), ENT_QUOTES, 'UTF-8') ?>" required>
                        <small class="text-muted">Digite o CEP para preencher automaticamente</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="estado" class="form-label">Estado *</label>
                        <select class="form-control" id="estado" name="estado" required data-selected="<?= htmlspecialchars($endereco->getEstado(), ENT_QUOTES, 'UTF-8') ?>">
                            <option value="">Selecione um estado</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="cidade" class="form-label">Cidade *</label>
                        <select class="form-control" id="cidade" name="cidade" required disabled data-selected="<?= htmlspecialchars($endereco->getCidade(), ENT_QUOTES, 'UTF-8') ?>">
                            <option value="">Selecione um estado primeiro</option>
                        </select>
                    </div>
                </div>

                <!-- Botões -->
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    <a href="listar_fornecedor.php" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>