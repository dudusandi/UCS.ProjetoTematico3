<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';
require_once '../dao/mensagem_dao.php';

$id_usuario_logado = $_SESSION['usuario_id'];
$nome_usuario = $_SESSION['usuario_nome'] ?? "Usuário";
$contador_mensagens_nao_lidas = 0; 

try {
    $clienteDAO = new ClienteDAO(Database::getConnection()); 
    $cliente_info_menu = $clienteDAO->buscarPorId($id_usuario_logado); 
    if ($cliente_info_menu) {
        $nome_usuario = $cliente_info_menu->getNome();
        if (!isset($_SESSION['usuario_nome']) || $_SESSION['usuario_nome'] !== $nome_usuario) {
            $_SESSION['usuario_nome'] = $nome_usuario;
        }
    }
    $mensagemDAO = new MensagemDAO();
    $contador_mensagens_nao_lidas = $mensagemDAO->contarMensagensNaoLidas($id_usuario_logado);

} catch (Exception $e) {
    
    $mensagem_erro_bloco = "Erro ao inicializar dados para listar clientes ou menu: " . $e->getMessage();
    $tipoMensagem_erro_bloco = 'erro';
    
    if (!isset($nome_usuario)) $nome_usuario = "Usuário";
    if (!isset($contador_mensagens_nao_lidas)) $contador_mensagens_nao_lidas = 0;
}

$mensagem_feedback_get = $_GET['mensagem'] ?? '';
$tipoMensagem_feedback_get = $_GET['tipo_mensagem'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Clientes - ECOxChange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="dashboard.css"> 
    <link rel="stylesheet" href="listar.css"> 
    <style>
        body {
            display: flex; 
            min-height: 100vh;
        }
        .main-content {
            flex-grow: 1; 
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../menu.php'; ?>
    
    <div class="main-content">
        <div class="search-bar-container"> 
            <div class="search-bar-md3"> 
                <input type="text" id="searchInput" class="form-control flex-grow-1" placeholder="Pesquisar clientes..." autocomplete="off">
            </div>
        </div>

        <div class="container-fluid px-4"> 
            <?php if (!empty($mensagem_feedback_get)): ?>
                <div class="alert alert-<?= $tipoMensagem_feedback_get === 'erro' ? 'danger' : 'success' ?> alert-dismissible fade show mt-3" role="alert">
                    <?= htmlspecialchars($mensagem_feedback_get) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($mensagem_erro_bloco) && !empty($mensagem_erro_bloco) && isset($tipoMensagem_erro_bloco)): ?>
                <div class="alert alert-<?= $tipoMensagem_erro_bloco === 'erro' ? 'danger' : 'warning' ?> alert-dismissible fade show mt-3" role="alert">
                    <?= htmlspecialchars($mensagem_erro_bloco) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center my-4">
                <h2>Gerenciar Clientes</h2>
                <div>
                    <a href="cadastro_cliente.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Cadastrar Novo Cliente
                    </a>
                </div>
            </div>

            <div id="clientesContainer" class="clientes-list-container">
            </div>
            <div id="sentinela" style="height: 20px;"></div>
            <div id="loading" class="text-center my-3 d-none">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="listar_clientes.js"></script>
    <script>
        
        
        
        document.addEventListener('DOMContentLoaded', function () {
            
            
            
            
            
        });
    </script>
</body>
</html>