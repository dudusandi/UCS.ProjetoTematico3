<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../view/login.php');
    exit;
}

require_once '../config/database.php';
require_once '../dao/cliente_dao.php';
require_once '../model/cliente.php';
require_once '../model/endereco.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $clienteDAO = new ClienteDAO(Database::getConnection());
        
        // Criar objeto Endereco usando o construtor
        $endereco = new Endereco(
            $_POST['rua'],
            $_POST['numero'],
            $_POST['bairro'],
            $_POST['cep'],
            $_POST['cidade'],
            $_POST['estado'],
            $_POST['complemento'] ?? null
        );

        // Criar objeto Cliente usando o construtor
        $cliente = new Cliente(
            $_POST['nome'],
            $_POST['telefone'],
            $_POST['email'],
            $_POST['cartao_credito'],
            $endereco
        );
        $cliente->setId($_POST['id']);

        // Atualizar cliente
        $clienteDAO->atualizarCliente($cliente);
        
        header('Location: ../view/listar_clientes.php?mensagem=Cliente atualizado com sucesso&tipo_mensagem=sucesso');
    } catch (Exception $e) {
        header('Location: ../view/editar_cliente.php?id=' . $_POST['id'] . '&mensagem=Erro ao atualizar cliente: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
    }
} else {
    header('Location: ../view/listar_clientes.php');
} 