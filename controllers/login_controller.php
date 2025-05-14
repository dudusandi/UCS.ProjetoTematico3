<?php
$basePath = realpath(dirname(__DIR__));
require_once "$basePath/config/database.php";
require_once "$basePath/model/cliente.php";
require_once "$basePath/model/endereco.php";
require_once "$basePath/dao/cliente_dao.php";


// Controlador para login de cliente com adição de um admin para acesso a configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        header('Location: ../view/login.php?erro=1');
        exit();
    }

    try {
        $pdo = Database::getConnection();
        $clienteDAO = new ClienteDAO($pdo);

        $cliente = $clienteDAO->buscarPorEmailSenha($email, $senha);

        if ($cliente) {
            session_start();
            $_SESSION['usuario_id'] = $cliente->getId();
            $_SESSION['usuario_email'] = $cliente->getEmail();
            $_SESSION['usuario_nome'] = $cliente->getNome();
            $_SESSION['is_admin'] = ($cliente->getEmail() === 'dudaesouza@gmail.com' || 'admin@admin.com');
            header('Location: ../view/dashboard.php');
            exit();
        } else {
            header('Location: ../view/login.php?erro=1');
            exit();
        }
    } catch (Exception $e) {
        header('Location: ../view/login.php?erro=2');
        exit();
    }
}
?>