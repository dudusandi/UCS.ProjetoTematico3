<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';

// Busca todos os clientes (inicialmente)
try {
    $clienteDAO = new ClienteDAO(Database::getConnection());
    $itensPorPagina = 6;
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $offset = ($pagina - 1) * $itensPorPagina;
    
    $clientes = $clienteDAO->listarTodos($itensPorPagina, $offset);
    $totalClientes = $clienteDAO->contarTodos();
    $totalPaginas = ceil($totalClientes / $itensPorPagina);
} catch (Exception $e) {
    $clientes = [];
    $mensagem = "Erro ao listar clientes: " . $e->getMessage();
    $tipoMensagem = 'erro';
}

// Mensagens
$mensagem = $_GET['mensagem'] ?? '';
$tipoMensagem = $_GET['tipo_mensagem'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - ECOxChange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="listar.css">
</head>
<body>
    <!-- Cabeçalho -->
    <div class="header">
        <a href="dashboard.php" class="logo">ECO<span>Exchange</span></a>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Pesquisar clientes..." autocomplete="off">
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

        <!-- Botões de Navegação -->
        <div class="d-flex justify-content-between mb-4">
            <h2>Gerenciar Clientes</h2>
            <div>
                <a href="cadastro_cliente.php" class="btn btn-primary me-2">
                    <i class="bi bi-plus"></i> Cadastrar Novo
                </a>
            </div>
        </div>

        <!-- Listagem de Clientes -->
        <div id="clientesContainer">
            <!-- Clientes serão carregados dinamicamente -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="listar_clientes.js"></script>
</body>
</html>