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

// Inicialização de variáveis para mensagens
$mensagem = $_GET['mensagem'] ?? '';
$tipoMensagem = $_GET['tipo_mensagem'] ?? '';
$campoErro = $_GET['campo'] ?? '';

try {
    $fornecedorDAO = new FornecedorDAO(Database::getConnection());
} catch (Exception $e) {
    $mensagem = "Erro ao conectar ao banco de dados: " . $e->getMessage();
    $tipoMensagem = 'erro';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Fornecedor - ECOxchange</title>
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
            <div class="alert alert-<?php echo $tipoMensagem === 'erro' ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="form-section mt-4">
            <h2 class="mb-4">Cadastro de Fornecedor</h2>

            <form action="../controllers/fornecedor_controller.php" method="POST" id="formFornecedor">
                <input type="hidden" name="acao" value="cadastrar">

                <div class="row mb-3">
                    <div class="col">
                        <label for="nome" class="form-label">Nome *</label>
                        <input type="text" class="form-control <?= $campoErro === 'nome' ? 'is-invalid' : '' ?>" id="nome" name="nome" value="" required>
                        <?php if ($campoErro === 'nome'): ?>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="4"></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col">
                        <label for="telefone" class="form-label">Telefone *</label>
                        <input type="tel" class="form-control <?= $campoErro === 'telefone' ? 'is-invalid' : '' ?>" id="telefone" name="telefone" value="" required>
                        <?php if ($campoErro === 'telefone'): ?>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                    <div class="col">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control <?= $campoErro === 'email' ? 'is-invalid' : '' ?>" id="email" name="email" value="" required>
                        <?php if ($campoErro === 'email'): ?>
                            <div class="invalid-feedback">Campo obrigatório ou email inválido</div>
                        <?php endif; ?>
                    </div>
                </div>

                <h5 class="mt-4 mb-3">Endereço</h5>
                <div class="row mb-3">
                    <div class="col">
                        <label for="rua" class="form-label">Rua *</label>
                        <input type="text" class="form-control <?= $campoErro === 'rua' ? 'is-invalid' : '' ?>" id="rua" name="rua" value="" required>
                        <?php if ($campoErro === 'rua'): ?>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                    <div class="col">
                        <label for="numero" class="form-label">Número *</label>
                        <input type="text" class="form-control <?= $campoErro === 'numero' ? 'is-invalid' : '' ?>" id="numero" name="numero" value="" required>
                        <?php if ($campoErro === 'numero'): ?>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col">
                        <label for="complemento" class="form-label">Complemento</label>
                        <input type="text" class="form-control" id="complemento" name="complemento" value="">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col">
                        <label for="bairro" class="form-label">Bairro *</label>
                        <input type="text" class="form-control <?= $campoErro === 'bairro' ? 'is-invalid' : '' ?>" id="bairro" name="bairro" value="" required>
                        <?php if ($campoErro === 'bairro'): ?>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                    <div class="col">
                        <label for="cep" class="form-label">CEP *</label>
                        <input type="text" class="form-control <?= $campoErro === 'cep' ? 'is-invalid' : '' ?>" id="cep" name="cep" value="" required>
                        <small class="text-muted">Digite o CEP para preencher automaticamente</small>
                        <?php if ($campoErro === 'cep'): ?>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col">
                        <label for="estado" class="form-label">Estado *</label>
                        <select class="form-control <?= $campoErro === 'estado' ? 'is-invalid' : '' ?>" id="estado" name="estado" required>
                            <option value="">Selecione um estado</option>
                        </select>
                        <?php if ($campoErro === 'estado'): ?>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                    <div class="col">
                        <label for="cidade" class="form-label">Cidade *</label>
                        <select class="form-control <?= $campoErro === 'cidade' ? 'is-invalid' : '' ?>" id="cidade" name="cidade" required disabled>
                            <option value="">Selecione um estado primeiro</option>
                        </select>
                        <?php if ($campoErro === 'cidade'): ?>
                            <div class="invalid-feedback">Campo obrigatório</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Cadastrar Fornecedor</button>
                    <a href="listar_fornecedor.php" class="btn btn-secondary">Voltar</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>