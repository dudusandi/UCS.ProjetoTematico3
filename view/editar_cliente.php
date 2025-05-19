<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';

$clienteDAO = new ClienteDAO(Database::getConnection());
$cliente = null;
$endereco = null;
$mensagem = '';
$tipoMensagem = '';

if (isset($_GET['id'])) {
    $cliente = $clienteDAO->buscarPorId($_GET['id']);
    if ($cliente) {
        $endereco = $cliente->getEndereco();
    }
}

// Carregar estados
$estados = json_decode(file_get_contents('http://servicodados.ibge.gov.br/api/v1/localidades/estados'), true);

// Carregar cidades do estado selecionado
$cidades = [];
if ($endereco && $endereco->getEstado()) {
    $cidades = json_decode(file_get_contents('http://servicodados.ibge.gov.br/api/v1/localidades/estados/' . $endereco->getEstado() . '/municipios'), true);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - ECOxchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="editar.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="endereco.js"></script>
</head>
<body>
    <div class="header">
        <div class="logo">ECO<span>xchange</span></div>
    </div>

    <div class="container">
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between mb-4">
            <h2>Editar Cliente</h2>
            <a href="listar_clientes.php" class="btn btn-secondary">Voltar</a>
        </div>

        <form id="formCliente" action="../controllers/atualizar_cliente.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="id" value="<?= $cliente ? $cliente->getId() : '' ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nome" class="form-label">Nome *</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?= $cliente ? $cliente->getNome() : '' ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="telefone" class="form-label">Telefone *</label>
                    <input type="text" class="form-control" id="telefone" name="telefone" value="<?= $cliente ? $cliente->getTelefone() : '' ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= $cliente ? $cliente->getEmail() : '' ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="cartao_credito" class="form-label">Cartão de Crédito *</label>
                    <input type="text" class="form-control" id="cartao_credito" name="cartao_credito" value="<?= $cliente ? $cliente->getCartaoCredito() : '' ?>" required>
                </div>
            </div>

            <h4 class="mt-4 mb-3">Endereço</h4>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="cep" class="form-label">CEP *</label>
                    <input type="text" class="form-control" id="cep" name="cep" value="<?= $endereco ? $endereco->getCep() : '' ?>" required>
                </div>
                
                <div class="col-md-8 mb-3">
                    <label for="rua" class="form-label">Rua *</label>
                    <input type="text" class="form-control" id="rua" name="rua" value="<?= $endereco ? $endereco->getRua() : '' ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="numero" class="form-label">Número *</label>
                    <input type="text" class="form-control" id="numero" name="numero" value="<?= $endereco ? $endereco->getNumero() : '' ?>" required>
                </div>
                
                <div class="col-md-8 mb-3">
                    <label for="complemento" class="form-label">Complemento</label>
                    <input type="text" class="form-control" id="complemento" name="complemento" value="<?= $endereco ? $endereco->getComplemento() : '' ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="bairro" class="form-label">Bairro *</label>
                    <input type="text" class="form-control" id="bairro" name="bairro" value="<?= $endereco ? $endereco->getBairro() : '' ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="estado" class="form-label">Estado *</label>
                    <select class="form-select" id="estado" name="estado" required>
                        <option value="">Selecione um estado</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado['sigla'] ?>" <?= $endereco && $endereco->getEstado() === $estado['sigla'] ? 'selected' : '' ?>>
                                <?= $estado['nome'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="cidade" class="form-label">Cidade *</label>
                    <select class="form-select" id="cidade" name="cidade" required>
                        <option value="">Selecione uma cidade</option>
                        <?php foreach ($cidades as $cidade): ?>
                            <option value="<?= $cidade['nome'] ?>" <?= $endereco && $endereco->getCidade() === $cidade['nome'] ? 'selected' : '' ?>>
                                <?= $cidade['nome'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>