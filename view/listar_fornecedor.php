<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/fornecedor_dao.php';
require_once '../model/fornecedor.php';

// Mensagens de feedback
$mensagem = $_GET['mensagem'] ?? '';
$tipoMensagem = $_GET['tipo_mensagem'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Fornecedores - UcsExpress</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="listar.css">
</head>
<body>
    <!-- Cabeçalho -->
    <div class="header">
        <a href="dashboard.php" class="logo">UCS<span>express</span></a>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Pesquisar fornecedores..." autocomplete="off">
        </div>
    </div>

    <div class="container">
        <!-- Mensagens -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= $tipoMensagem === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Cabeçalho da Página -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gerenciar Fornecedores</h2>
            <div class="d-flex gap-2">
                <a href="cadastro_fornecedor.php" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Adicionar Fornecedor
                </a>
            </div>
        </div>

        <!-- Listagem de Fornecedores -->
        <div id="fornecedoresContainer"></div>

        <!-- Indicador de Carregamento -->
        <div id="loadingIndicator" class="text-center mt-4 d-none">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando mais...</span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="listar_fornecedores.js"></script>
</body>
</html>