<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../dao/produto_dao.php';

if (!isset($_SESSION['carrinhos'])) {
    $_SESSION['carrinhos'] = [];
}

function obterCarrinhoAtual() {
    if (!isset($_SESSION['usuario_id'])) {
        return [];
    }
    
    $clienteId = $_SESSION['usuario_id'];
    
    if (!isset($_SESSION['carrinhos'])) {
        $_SESSION['carrinhos'] = [];
    }
    
    if (!isset($_SESSION['carrinhos'][$clienteId])) {
        $_SESSION['carrinhos'][$clienteId] = [];
    }
    
    return $_SESSION['carrinhos'][$clienteId];
}

function adicionarAoCarrinho($produtoId, $quantidade = 1) {
    if (!isset($_SESSION['usuario_id'])) {
        return ['success' => false, 'erro' => 'Usuário não logado'];
    }

    $clienteId = $_SESSION['usuario_id'];

    try {
        $pdo = Database::getConnection();
        $produtoDao = new ProdutoDAO($pdo);
        $produto = $produtoDao->buscarPorId($produtoId);

        if (!$produto) {
            return ['success' => false, 'erro' => 'Produto não encontrado'];
        }

        $estoqueDisponivel = $produto->getQuantidade();

        $carrinho = obterCarrinhoAtual();
        $quantidadeJaNoCarrinho = isset($carrinho[$produtoId]) ? (int)$carrinho[$produtoId] : 0;
        
        if ($quantidade <= 0) {
             return ['success' => false, 'erro' => 'Quantidade inválida.'];
        }

        if (isset($carrinho[$produtoId])) {
             if (($carrinho[$produtoId] + $quantidade) > $estoqueDisponivel) {
                 return ['success' => false, 'erro' => 'Estoque insuficiente. Quantidade disponível: ' . $estoqueDisponivel . '. Você já tem ' . $carrinho[$produtoId] . ' no carrinho.'];
             }
        } else {
            if ($quantidade > $estoqueDisponivel) {
                return ['success' => false, 'erro' => 'Estoque insuficiente. Quantidade disponível: ' . $estoqueDisponivel];
            }
        }
        
        if (isset($carrinho[$produtoId])) {
            $carrinho[$produtoId] += $quantidade;
        } else {
            $carrinho[$produtoId] = $quantidade;
        }

        $_SESSION['carrinhos'][$clienteId] = $carrinho;
        return ['success' => true, 'mensagem' => 'Produto adicionado com sucesso!'];

    } catch (Exception $e) {
        return ['success' => false, 'erro' => 'Erro ao adicionar produto: ' . $e->getMessage()];
    }
}

function removerDoCarrinho($produtoId) {
    if (!isset($_SESSION['usuario_id'])) {
        return;
    }
    
    $clienteId = $_SESSION['usuario_id'];
    $carrinho = obterCarrinhoAtual();
    
    if (isset($carrinho[$produtoId])) {
        unset($carrinho[$produtoId]);
        $_SESSION['carrinhos'][$clienteId] = $carrinho;
    }
}

function atualizarQuantidade($produtoId, $novaQuantidade) {
    if (!isset($_SESSION['usuario_id'])) {
        return ['success' => false, 'erro' => 'Usuário não logado'];
    }

    $clienteId = $_SESSION['usuario_id'];

    try {
        $pdo = Database::getConnection();
        $produtoDao = new ProdutoDAO($pdo);
        $produto = $produtoDao->buscarPorId($produtoId);

        if (!$produto) {
            return ['success' => false, 'erro' => 'Produto não encontrado'];
        }

        $estoqueDisponivel = $produto->getQuantidade();

        if ($novaQuantidade <= 0) {
            $carrinho = obterCarrinhoAtual();
            unset($carrinho[$produtoId]);
            $_SESSION['carrinhos'][$clienteId] = $carrinho;
            return ['success' => true, 'mensagem' => 'Produto removido do carrinho.', 'removido' => true];
        }

        if ($novaQuantidade > $estoqueDisponivel) {
            return ['success' => false, 'erro' => 'Estoque insuficiente. Quantidade disponível: ' . $estoqueDisponivel];
        }

        $carrinho = obterCarrinhoAtual();
        $carrinho[$produtoId] = $novaQuantidade;
        $_SESSION['carrinhos'][$clienteId] = $carrinho;
        
        return ['success' => true, 'mensagem' => 'Quantidade atualizada com sucesso.'];

    } catch (Exception $e) {
        return ['success' => false, 'erro' => 'Erro ao atualizar quantidade: ' . $e->getMessage()];
    }
}

function limparCarrinho() {
    if (!isset($_SESSION['usuario_id'])) {
        return;
    }
    
    $clienteId = $_SESSION['usuario_id'];
    $_SESSION['carrinhos'][$clienteId] = [];
}

function obterProdutosCarrinho() {
    if (!isset($_SESSION['usuario_id'])) {
        return [];
    }

    try {
        $pdo = Database::getConnection();
        $produtoDao = new ProdutoDAO($pdo);
        
        $produtos = [];
        $carrinho = obterCarrinhoAtual();
        
        foreach ($carrinho as $produtoId => $quantidadeNoCarrinho) {
            $produto = $produtoDao->buscarPorId($produtoId);
            if ($produto) {
                $produtos[] = [
                    'id' => $produto->getId(),
                    'nome' => $produto->getNome(),
                    'preco' => $produto->getPreco(),
                    'quantidade' => $quantidadeNoCarrinho, 
                    'estoque_disponivel' => $produto->getQuantidade(), 
                    'subtotal' => $produto->getPreco() * $quantidadeNoCarrinho
                ];
            }
        }
        return $produtos;
    } catch (Exception $e) {
        return [];
    }
}

function calcularTotalCarrinho() {
    $produtos = obterProdutosCarrinho();
    $total = 0;
    foreach ($produtos as $produto) {
        $total += $produto['subtotal'];
    }
    return $total;
}

if (!defined('CARRINHO_LOGIC_ONLY')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['usuario_id'])) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'erro' => 'Usuário não está logado', 'login_requerido' => true]);
            } else {
                header('Location: ../view/login.php');
            }
            exit;
        }

        $acao = $_POST['acao'] ?? '';
        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 1);
        $resposta = null;

        switch ($acao) {
            case 'adicionar':
                $resposta = adicionarAoCarrinho($produtoId, $quantidade);
                break;
            case 'remover':
                removerDoCarrinho($produtoId); 
                $resposta = ['success' => true, 'mensagem' => 'Produto removido com sucesso.', 'removido' => true]; 
                break;
            case 'atualizar':
                $resposta = atualizarQuantidade($produtoId, $quantidade);
                break;
            case 'limpar':
                limparCarrinho();
                $resposta = ['success' => true, 'mensagem' => 'Carrinho limpo com sucesso.'];
                break;
            default:
                http_response_code(400);
                $resposta = ['success' => false, 'erro' => 'Ação do carrinho desconhecida ou inválida.'];
        }

        header('Content-Type: application/json');
        echo json_encode($resposta);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['json'])) {
        if (!isset($_SESSION['usuario_id'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'erro' => 'Usuário não está logado',
                'produtos' => [],
                'total' => 0
            ]);
            exit;
        }

        try {
            $produtos = obterProdutosCarrinho();
            $total = calcularTotalCarrinho();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'produtos' => $produtos,
                'total' => $total
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'erro' => 'Erro ao obter carrinho: ' . $e->getMessage(),
                'produtos' => [],
                'total' => 0
            ]);
        }
        exit;
    }
}
