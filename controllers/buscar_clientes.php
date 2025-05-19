<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/cliente_dao.php';

try {
    $pdo = Database::getConnection();
    $clienteDAO = new ClienteDAO($pdo);

    $termo = $_GET['termo'] ?? '';
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $itensPorPagina = 6;
    $offset = ($pagina - 1) * $itensPorPagina;

    if (!empty($termo)) {
        $clientes = $clienteDAO->buscarClientesDinamicos($termo, $itensPorPagina, $offset);
        $total = $clienteDAO->contarClientesBuscados($termo);
    } else {
        $clientes = $clienteDAO->listarTodos($itensPorPagina, $offset);
        $total = $clienteDAO->contarTodos();
    }
    $clientesFormatados = array_map(function($cliente) {
        $endereco = $cliente->getEndereco();
        return [
            'id' => $cliente->getId(),
            'nome' => $cliente->getNome(),
            'telefone' => $cliente->getTelefone(),
            'email' => $cliente->getEmail(),
            'rua' => $endereco->getRua(),
            'numero' => $endereco->getNumero(),
            'bairro' => $endereco->getBairro(),
            'cidade' => $endereco->getCidade(),
            'estado' => $endereco->getEstado()
        ];
    }, $clientes);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'clientes' => $clientesFormatados,
        'total' => $total
    ]);

} catch (Exception $e) {
    error_log("Erro ao buscar clientes: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar clientes: ' . $e->getMessage()
    ]);
} 