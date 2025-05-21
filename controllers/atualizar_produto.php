<?php
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit;
}
$usuario_id = (int)$_SESSION['usuario_id'];

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';
// require_once __DIR__ . '/../dao/estoque_dao.php';
require_once __DIR__ . '/../model/produto.php';
// require_once __DIR__ . '/../model/estoque.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    // $estoqueDao = new EstoqueDAO($pdo);

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    $produtoExistente = $produtoDao->buscarPorId($id);
    if (!$produtoExistente) {
        echo json_encode(['error' => 'Produto não encontrado']);
        exit;
    }

    // Verificar se o produto pertence ao usuário logado
    if ($produtoExistente->getUsuarioId() !== $usuario_id) {
        http_response_code(403); // Forbidden
        echo json_encode(['error' => 'Você não tem permissão para editar este produto.']);
        exit;
    }

    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
    $descricao = trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'));
    $preco = (float)($_POST['preco'] ?? 0.0);

    if (empty($nome)) {
        echo json_encode(['error' => 'Nome é obrigatório']);
        exit;
    }
    if ($preco < 0) {
        echo json_encode(['error' => 'Preço inválido']);
        exit;
    }
    if ($produtoDao->nomeExiste($nome, $id)) {
        echo json_encode(['error' => 'Nome do produto já existe']);
        exit;
    }

    $foto = $produtoExistente->getFoto() ?? null;

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['error' => 'Foto inválida. Use JPEG, PNG ou GIF (máx. 16MB)']);
            exit;
        }

        $maxSize = 16 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            echo json_encode(['error' => 'Foto muito grande. Tamanho máximo: 16MB']);
            exit;
        }

        $foto = file_get_contents($file['tmp_name']);
    }

    // Usar o usuario_id original do produto, não pode ser alterado aqui.
    // A ordem correta dos parâmetros do construtor é: nome, descricao, imagem, preco, usuario_id
    $produtoAtualizado = new Produto(
        $nome, 
        $descricao, 
        $foto, 
        $preco, // preco
        $produtoExistente->getUsuarioId() // usuario_id original do produto
    );
    $produtoAtualizado->setId($id);

    if ($produtoDao->atualizarProduto($produtoAtualizado)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Erro ao atualizar o produto']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao processar: ' . $e->getMessage()]);
}
?>