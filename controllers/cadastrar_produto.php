<?php
session_start();
require_once '../config/database.php';
require_once '../dao/produto_dao.php';
require_once '../models/produto.php';

header('Content-Type: application/json');

try {
    // Validar dados recebidos
    if (!isset($_POST['nome']) || empty($_POST['nome'])) {
        throw new Exception('Nome do produto é obrigatório');
    }

    if (!isset($_POST['fornecedor_id']) || empty($_POST['fornecedor_id'])) {
        throw new Exception('Fornecedor é obrigatório');
    }

    if (!isset($_POST['quantidade']) || !is_numeric($_POST['quantidade']) || $_POST['quantidade'] < 0) {
        throw new Exception('Quantidade inválida');
    }

    if (!isset($_POST['preco']) || !is_numeric($_POST['preco']) || $_POST['preco'] < 0) {
        throw new Exception('Preço inválido');
    }

    // Processar upload da foto
    $foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['foto']['type'], $allowedTypes)) {
            throw new Exception('Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.');
        }

        if ($_FILES['foto']['size'] > $maxSize) {
            throw new Exception('Arquivo muito grande. Tamanho máximo: 2MB');
        }

        $uploadDir = '../public/uploads/imagens/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto = uniqid() . '.' . $extension;
        $uploadFile = $uploadDir . $foto;

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $uploadFile)) {
            throw new Exception('Erro ao fazer upload da imagem');
        }
    }

    // Criar objeto Produto
    $produto = new Produto(
        $_POST['nome'],
        $_POST['descricao'] ?? null,
        $foto,
        $_POST['fornecedor_id'],
        $_SESSION['usuario_id']
    );

    // Definir quantidade e preço
    $produto->setQuantidade($_POST['quantidade']);
    $produto->setPreco($_POST['preco']);

    // Inserir no banco de dados
    $pdo = Database::getConnection();
    $produtoDAO = new ProdutoDAO($pdo);
    $produtoDAO->cadastrarProduto($produto, $_POST['quantidade'], $_POST['preco']);

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
?>