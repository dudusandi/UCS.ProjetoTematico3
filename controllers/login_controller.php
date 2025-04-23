<?php
$basePath = realpath(dirname(__DIR__));
require_once "$basePath/config/database.php";
require_once "$basePath/model/cliente.php";
require_once "$basePath/model/endereco.php";
require_once "$basePath/dao/cliente_dao.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    // Validar entrada
    if (empty($email) || empty($senha)) {
        header('Location: ../view/login.php?erro=1');
        exit();
    }

    try {
        // Inicializar conexão com o banco
        $pdo = Database::getConnection();
        $clienteDAO = new ClienteDAO($pdo);

        // Buscar cliente por email e verificar senha
        $cliente = $clienteDAO->buscarPorEmailSenha($email, $senha);

        if ($cliente) {
            // Login bem-sucedido
            session_start();
            $_SESSION['usuario_id'] = $cliente->getId();
            $_SESSION['usuario_email'] = $cliente->getEmail();
            $_SESSION['usuario_nome'] = $cliente->getNome();
            header('Location: ../view/dashboard.php');
            exit();
        } else {
            // Credenciais inválidas
            header('Location: ../view/login.php?erro=1');
            exit();
        }
    } catch (Exception $e) {
        // Erro no servidor ou banco de dados
        header('Location: ../view/login.php?erro=2');
        exit();
    }
}
?>