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
require_once __DIR__ . '/../dao/estoque_dao.php';
require_once __DIR__ . '/../model/produto.php';
require_once __DIR__ . '/../model/estoque.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    $estoqueDao = new EstoqueDAO($pdo);

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

    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
    $descricao = trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'));
    $fornecedor = trim(htmlspecialchars($_POST['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'));
    $estoque = (int)($_POST['estoque'] ?? 0);
    $preco = (float)($_POST['preco'] ?? 0);

    if (empty($nome) || empty($fornecedor)) {
        echo json_encode(['error' => 'Nome e fornecedor são obrigatórios']);
        exit;
    }
    if ($estoque < 0) {
        echo json_encode(['error' => 'Estoque inválido']);
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

    $produtoAtualizado = new Produto($nome, $descricao, $foto, (int)$fornecedor, $usuario_id);
    $produtoAtualizado->setId($id);
    $produtoAtualizado->setEstoqueId($produtoExistente->getEstoqueId());
    $produtoAtualizado->setQuantidade($estoque);
    $produtoAtualizado->setPreco($preco);

    if ($produtoDao->atualizarProduto($produtoAtualizado)) {
        $estoqueId = $produtoExistente->getEstoqueId();
        
        $estoqueObjeto = new Estoque($estoque, $preco);
        $estoqueObjeto->setId($estoqueId);

        if ($estoqueDao->atualizar($estoqueObjeto)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Erro ao atualizar o estoque e preço']);
        }
    } else {
        echo json_encode(['error' => 'Erro ao atualizar o produto']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao processar: ' . $e->getMessage()]);
}
?>