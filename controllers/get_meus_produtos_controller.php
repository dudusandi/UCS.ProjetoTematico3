<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit;
}

$id_usuario_logado = $_SESSION['usuario_id'];
$response = ['success' => false, 'produtos' => []];

try {
    $produtoDao = new ProdutoDAO();
    // Buscar todos os produtos do usuário, sem termo de busca, limite ou offset
    $produtosDoUsuario = $produtoDao->buscarPorUsuarioId($id_usuario_logado, '', null, null);

    if ($produtosDoUsuario) {
        $produtosFormatados = [];
        foreach ($produtosDoUsuario as $produto) {
            // A função buscarPorUsuarioId já retorna a foto como stream_get_contents (string)
            // e o JavaScript espera base64.
            $fotoBase64 = null;
            if ($produto['foto']) {
                // Se já for uma string base64 (improvável vindo direto do DAO como está), usar diretamente.
                // Caso contrário, se for binário, codificar.
                // Assumindo que stream_get_contents no DAO já fez a leitura do LOB.
                 // O DAO já faz stream_get_contents, então é uma string binária.
                $fotoBase64 = base64_encode($produto['foto']);
            }
            $produtosFormatados[] = [
                'id' => $produto['id'],
                'nome' => $produto['nome'],
                'descricao' => $produto['descricao'],
                'preco' => $produto['preco'],
                'foto' => $fotoBase64 // Enviar como base64 para o JS
            ];
        }
        $response['success'] = true;
        $response['produtos'] = $produtosFormatados;
    } else {
        // Se não houver produtos, retorna sucesso com array vazio, o JS trata isso.
        $response['success'] = true; 
    }

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    $response['error'] = 'Erro ao buscar produtos: ' . $e->getMessage();
    error_log("Erro em get_meus_produtos_controller.php: " . $e->getMessage());
}

echo json_encode($response);
?> 