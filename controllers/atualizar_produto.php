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

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
    $estoqueDao = new EstoqueDAO($pdo);

    // Verificar ID
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['error' => 'ID inválido']);
        exit;
    }

    // Buscar Produtos
    $produtoExistente = $produtoDao->buscarPorId($id);
    if (!$produtoExistente) {
        echo json_encode(['error' => 'Produto não encontrado']);
        exit;
    }

    // Dados do formulário
    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
    $descricao = trim(htmlspecialchars($_POST['descricao'] ?? '', ENT_QUOTES, 'UTF-8'));
    $fornecedor = trim(htmlspecialchars($_POST['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8'));
    $estoque = (int)($_POST['estoque'] ?? 0);
    $preco = (float)($_POST['preco'] ?? 0);

    // Validações
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

    // Obtém a foto atual do produto existente
    $foto = $produtoExistente->getFoto() ?? null;

    // Salvar Foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['foto'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 16 * 1024 * 1024; //Tamanho Maximo de 16mb pro raspberry não chorar.

        if (!in_array($file['type'], $allowedTypes) || $file['size'] > $maxSize) {
            echo json_encode(['error' => 'Foto inválida. Use JPEG, PNG ou GIF (máx. 16MB)']);
            exit;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nomeArquivo = uniqid('produto_') . '.' . strtolower($ext);
        $caminhoDestino = __DIR__ . '/../public/uploads/imagens/' . $nomeArquivo;

        if (move_uploaded_file($file['tmp_name'], $caminhoDestino)) {
            if ($foto && file_exists(__DIR__ . '/../public/uploads/imagens/' . basename($foto))) {
                unlink(__DIR__ . '/../public/uploads/imagens/' . basename($foto));
                error_log(date('[Y-m-d H:i:s] ') . "Foto antiga removida: $foto" . PHP_EOL);
            }

            $foto = $nomeArquivo; 
            error_log(date('[Y-m-d H:i:s] ') . "Nova foto salva: $foto" . PHP_EOL);

        } else {
            echo json_encode(['error' => 'Erro ao salvar a foto']);
            exit;
        }
    }

    // Novo Produto
    $produtoAtualizado = new Produto($nome, $descricao, $foto, (int)$fornecedor, $usuario_id);
    $produtoAtualizado->setId($id);
    $produtoAtualizado->setEstoqueId($produtoExistente->getEstoqueId());
    $produtoAtualizado->setQuantidade($estoque);
    $produtoAtualizado->setPreco($preco);


    // Atualiza produto
    if ($produtoDao->atualizarProduto($produtoAtualizado)) {
        $estoqueId = $produtoExistente->getEstoqueId();
        if ($estoqueDao->atualizarQuantidade($estoqueId, $estoque)) {
            echo json_encode(['success' => true, 'foto' => $foto]);
        } else {
            echo json_encode(['error' => 'Erro ao atualizar o estoque']);
        }
    } else {
        echo json_encode(['error' => 'Erro ao atualizar o produto']);
    }
} catch (Exception $e) {
    error_log(date('[Y-m-d H:i:s] ') . "Erro em atualizar_produto.php: " . $e->getMessage() . PHP_EOL);
    echo json_encode(['error' => 'Erro ao processar: ' . $e->getMessage()]);
}
?>