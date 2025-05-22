<?php
$basePath = realpath(dirname(__DIR__));
require_once "$basePath/config/database.php";
require_once "$basePath/model/cliente.php";
require_once "$basePath/model/endereco.php";
require_once "$basePath/dao/cliente_dao.php";

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        header('Location: ../view/login.php?erro=1');
        exit();
    }

    try {
        $pdo = Database::getConnection();
        $clienteDAO = new ClienteDAO();

        $cliente = $clienteDAO->buscarPorEmailSenha($email, $senha);

        if ($cliente) {
            $carrinhos = isset($_SESSION['carrinhos']) ? $_SESSION['carrinhos'] : [];
            $_SESSION = [
                'carrinhos' => $carrinhos,
                'usuario_id' => $cliente->getId(),
                'usuario_email' => $cliente->getEmail(),
                'usuario_nome' => $cliente->getNome(),
                'is_admin' => ($cliente->getEmail() === 'admin@admin.com')
            ];
            
            echo "<script>
                var returnUrl = localStorage.getItem('returnUrl');
                localStorage.removeItem('returnUrl');
                window.location.href = returnUrl || '../view/dashboard.php';
            </script>";
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