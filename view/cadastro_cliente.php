<?php


require_once '../config/database.php';
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';
require_once '../model/endereco.php';

$mensagem = $_GET['mensagem'] ?? '';
$tipoMensagem = $_GET['tipo_mensagem'] ?? '';
$campoErro = $_GET['campo'] ?? '';

try {
    $clienteDAO = new ClienteDAO(Database::getConnection());
} catch (Exception $e) {
    $mensagem = "Erro ao conectar ao banco de dados: " . $e->getMessage();
    $tipoMensagem = 'erro';
}

// Carregar estados
$estados = json_decode(file_get_contents('http://servicodados.ibge.gov.br/api/v1/localidades/estados'), true);

// Carregar cidades se houver estado selecionado
$cidades = [];
if (isset($_POST['estado'])) {
    $cidades = json_decode(file_get_contents('http://servicodados.ibge.gov.br/api/v1/localidades/estados/' . $_POST['estado'] . '/municipios'), true);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Cliente - ECOxChange</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="editar.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="endereco.js"></script>
</head>
<body>

<div class="header">
    <div class="logo">ECO<span>Exchange</span></div>
</div>

<div class="container">
    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> mt-3">
            <?= htmlspecialchars($mensagem) ?>
        </div>
    <?php endif; ?>

    <div class="form-section mt-4">
        <h2 class="mb-4">Cadastro de Cliente</h2>

        <form action="../controllers/cliente_controller.php" method="POST">
            <input type="hidden" name="acao" value="cadastrar">

            <div class="row mb-3">
                <div class="col">
                    <label for="nome" class="form-label">Nome *</label>
                    <input type="text" class="form-control <?= $campoErro === 'nome' ? 'is-invalid' : '' ?>" id="nome" name="nome" value="<?= $_POST['nome'] ?? '' ?>" required>
                </div>
                <div class="col">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control <?= $campoErro === 'email' ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= $_POST['email'] ?? '' ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label for="senha" class="form-label">Senha *</label>
                    <input type="password" class="form-control <?= $campoErro === 'senha' ? 'is-invalid' : '' ?>" id="senha" name="senha" required>
                </div>
                <div class="col">
                    <label for="telefone" class="form-label">Telefone *</label>
                    <input type="tel" class="form-control <?= $campoErro === 'telefone' ? 'is-invalid' : '' ?>" id="telefone" name="telefone" value="<?= $_POST['telefone'] ?? '' ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label for="cep" class="form-label">CEP *</label>
                    <input type="text" class="form-control <?= $campoErro === 'cep' ? 'is-invalid' : '' ?>" id="cep" name="cep" value="<?= $_POST['cep'] ?? '' ?>" required>
                </div>
                <div class="col">
                    <label for="bairro" class="form-label">Bairro *</label>
                    <input type="text" class="form-control <?= $campoErro === 'bairro' ? 'is-invalid' : '' ?>" id="bairro" name="bairro" value="<?= $_POST['bairro'] ?? '' ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col">
                    <label for="rua" class="form-label">Rua *</label>
                    <input type="text" class="form-control <?= $campoErro === 'rua' ? 'is-invalid' : '' ?>" id="rua" name="rua" value="<?= $_POST['rua'] ?? '' ?>" required>
                </div>
                <div class="col">
                    <label for="numero" class="form-label">Número *</label>
                    <input type="text" class="form-control <?= $campoErro === 'numero' ? 'is-invalid' : '' ?>" id="numero" name="numero" value="<?= $_POST['numero'] ?? '' ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="complemento" class="form-label">Complemento</label>
                <input type="text" class="form-control" id="complemento" name="complemento" value="<?= $_POST['complemento'] ?? '' ?>">
            </div>

            <div class="row mb-4">
                <div class="col">
                    <label for="estado" class="form-label">Estado *</label>
                    <select class="form-control <?= $campoErro === 'estado' ? 'is-invalid' : '' ?>" id="estado" name="estado" required>
                        <option value="">Selecione um estado</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado['sigla'] ?>" <?= isset($_POST['estado']) && $_POST['estado'] === $estado['sigla'] ? 'selected' : '' ?>>
                                <?= $estado['nome'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col">
                    <label for="cidade" class="form-label">Cidade *</label>
                    <select class="form-control <?= $campoErro === 'cidade' ? 'is-invalid' : '' ?>" id="cidade" name="cidade" required>
                        <option value="">Selecione uma cidade</option>
                        <?php foreach ($cidades as $cidade): ?>
                            <option value="<?= $cidade['nome'] ?>" <?= isset($_POST['cidade']) && $_POST['cidade'] === $cidade['nome'] ? 'selected' : '' ?>>
                                <?= $cidade['nome'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="submit" class="btn btn-primary">Cadastrar Cliente</button>
                <a href="listar_clientes.php" class="btn btn-secondary">Voltar</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
