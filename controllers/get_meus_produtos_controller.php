<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    http_response_code(401); 
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$id_usuario_logado = $_SESSION['usuario_id'];
$response = ['success' => false, 'produtos' => []];

try {
    $produtoDao = new ProdutoDAO();
    
    $produtosDoUsuario = $produtoDao->buscarPorUsuarioId($id_usuario_logado, '', null, null);

    if ($produtosDoUsuario) {
        $produtosFormatados = [];
        foreach ($produtosDoUsuario as $produto) {
            
            
            $fotoBase64 = null;
            if ($produto['foto']) {
                
                
                
                 
                $fotoBase64 = base64_encode($produto['foto']);
            }
            $produtosFormatados[] = [
                'id' => $produto['id'],
                'nome' => $produto['nome'],
                'descricao' => $produto['descricao'],
                'preco' => $produto['preco'],
                'foto' => $fotoBase64 
            ];
        }
        $response['success'] = true;
        $response['produtos'] = $produtosFormatados;
    } else {
        
        $response['success'] = true; 
    }

} catch (Exception $e) {
    http_response_code(500); 
    $response['error'] = 'Erro ao buscar produtos: ' . $e->getMessage();
    error_log("Erro em get_meus_produtos_controller.php: " . $e->getMessage());
}

echo json_encode($response);
?> 