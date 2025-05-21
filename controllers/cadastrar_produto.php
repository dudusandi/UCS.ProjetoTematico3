<?php
ob_start();
ob_clean();

session_start();
require_once '../config/database.php';
require_once '../dao/produto_dao.php';
require_once '../model/produto.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        throw new Exception('Usuário não autenticado. Faça login para cadastrar um produto.');
    }

    if (!isset($_POST['nome']) || empty($_POST['nome'])) {
        throw new Exception('Nome do produto é obrigatório');
    }

    if (!isset($_POST['preco']) || !is_numeric($_POST['preco']) || $_POST['preco'] < 0) {
        throw new Exception('Preço inválido');
    }

    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 16 * 1024 * 1024; 

        if (!in_array($_FILES['foto']['type'], $allowedTypes)) {
            throw new Exception('Tipo de arquivo não permitido');
        }

        if ($_FILES['foto']['size'] > $maxSize) {
            throw new Exception('Tamanho máximo: 16MB');
        }

        $foto = file_get_contents($_FILES['foto']['tmp_name']);
    }

    $produto = new Produto(
        $_POST['nome'],
        $_POST['descricao'] ?? null,
        $foto,
        (float)($_POST['preco'] ?? 0.0),
        $_SESSION['usuario_id']
    );

    $pdo = Database::getConnection();
    $produtoDAO = new ProdutoDAO($pdo);
    $produtoDAO->cadastrarProduto($produto);

    echo json_encode([
        'success' => true,
        'message' => 'Produto cadastrado com sucesso'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}