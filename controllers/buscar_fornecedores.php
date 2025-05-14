<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/fornecedor_dao.php';

try {
    $pdo = Database::getConnection();
    $fornecedorDAO = new FornecedorDAO($pdo);

    $termo = $_GET['termo'] ?? '';
    $pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $itensPorPagina = 6;
    $offset = ($pagina - 1) * $itensPorPagina;

    // Buscar fornecedores
    if (!empty($termo)) {
        $fornecedores = $fornecedorDAO->buscarFornecedoresDinamicos($termo, $itensPorPagina, $offset);
        $total = $fornecedorDAO->contarFornecedoresBuscados($termo);
    } else {
        $fornecedores = $fornecedorDAO->listarTodos($itensPorPagina, $offset);
        $total = $fornecedorDAO->contarTodos();
    }

    // Formatar resposta
    $fornecedoresFormatados = array_map(function($fornecedor) {
        $endereco = $fornecedor->getEndereco();
        return [
            'id' => $fornecedor->getId(),
            'nome' => $fornecedor->getNome(),
            'descricao' => $fornecedor->getDescricao(),
            'telefone' => $fornecedor->getTelefone(),
            'email' => $fornecedor->getEmail(),
            'rua' => $endereco->getRua(),
            'numero' => $endereco->getNumero(),
            'bairro' => $endereco->getBairro(),
            'cidade' => $endereco->getCidade(),
            'estado' => $endereco->getEstado()
        ];
    }, $fornecedores);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'fornecedores' => $fornecedoresFormatados,
        'total' => $total
    ]);

} catch (Exception $e) {
    error_log("Erro ao buscar fornecedores: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao buscar fornecedores: ' . $e->getMessage()
    ]);
}