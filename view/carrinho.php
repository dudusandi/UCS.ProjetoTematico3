<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../dao/produto_dao.php';

try {
    $pdo = Database::getConnection();
    $produtoDao = new ProdutoDAO($pdo);
} catch (Exception $e) {
    error_log("Erro ao conectar ao banco: " . $e->getMessage());
    $mensagem = "Erro ao carregar produtos: " . $e->getMessage();
    $tipoMensagem = 'erro';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho - ECOxchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
    <div class="header">
        <div class="logo">ECO<span>exchange</span></div>
        <div class="user-options">
            <span>Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
            <a href="../controllers/logout_controller.php">Sair</a>
            <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="container mt-4">
        <h2>Seu Carrinho</h2>
        
        <div id="carrinho-vazio" class="text-center py-5" style="display: none;">
            <i class="bi bi-cart-x" style="font-size: 4rem;"></i>
            <h3 class="mt-3">Seu carrinho está vazio</h3>
            <a href="dashboard.php" class="btn btn-primary mt-3">
                <i class="bi bi-arrow-left"></i> Continuar Comprando
            </a>
        </div>

        <div id="carrinho-itens">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Preço</th>
                            <th>Quantidade</th>
                            <th>Subtotal</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="itens-carrinho">
                        <!-- Itens serão inseridos aqui via JavaScript -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td id="total-carrinho" colspan="2"><strong>R$ 0,00</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <button onclick="carrinho.limpar()" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Limpar Carrinho
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Continuar Comprando
                </a>
                <button onclick="finalizarCompra()" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Finalizar Compra
                </button>
            </div>
        </div>

        <!-- Modal de confirmação do pedido -->
        <div class="modal fade" id="modalConfirmacao" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar Pedido</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p>Deseja finalizar o pedido com <span id="qtd-itens">0</span> produtos?</p>
                        <p>Total: <strong id="total-modal">R$ 0,00</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-success" id="btn-confirmar-pedido">
                            <i class="bi bi-check-circle"></i> Confirmar Pedido
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de sucesso do pedido -->
        <div class="modal fade" id="modalSucesso" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Pedido Realizado com Sucesso!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                        <p>Seu pedido foi finalizado com sucesso!</p>
                        <p><strong>Número do pedido:</strong> <span id="numero-pedido"></span></p>
                        <p><strong>Valor total:</strong> <span id="valor-total"></span></p>
                    </div>
                    <div class="modal-footer">
                        <a href="dashboard.php" class="btn btn-primary">Continuar Comprando</a>
                        <a href="meus-pedidos.php" class="btn btn-success">Meus Pedidos</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="carrinho.js"></script>
    <script>
        // Função para atualizar a interface do carrinho
        function atualizarInterfaceCarrinho() {
            const itens = carrinho.obterItens();
            const tbody = document.getElementById('itens-carrinho');
            const carrinhoVazio = document.getElementById('carrinho-vazio');
            const carrinhoItens = document.getElementById('carrinho-itens');
            
            console.log('Atualizando interface do carrinho...');
            console.log('Itens no carrinho:', itens);
            
            // Buscar detalhes do carrinho diretamente do controller do carrinho
            fetch('../controllers/carrinho.php?json=1')
                .then(response => {
                    console.log('Resposta recebida do servidor:', response);
                    
                    if (!response.ok) {
                        throw new Error(`Erro na requisição: ${response.status} ${response.statusText}`);
                    }
                    
                    return response.text().then(text => {
                        console.log('Texto da resposta:', text);
                        
                        if (!text || text.trim() === '') {
                            throw new Error('Resposta vazia recebida do servidor');
                        }
                        
                        try {
                            // Tentar analisar o JSON
                            return JSON.parse(text);
                        } catch (error) {
                            console.error('Erro ao analisar JSON:', error);
                            console.error('Texto recebido:', text);
                            throw new Error('Resposta inválida do servidor: ' + error.message);
                        }
                    });
                })
                .then(data => {
                    console.log('Resposta do servidor:', data);
                    
                    if (!data.success) {
                        throw new Error(data.erro || 'Erro ao buscar produtos');
                    }

                    const produtos = data.produtos;
                    let html = '';
                    let total = data.total || 0;

                    if (!produtos || produtos.length === 0) {
                        console.log('Nenhum produto no carrinho');
                        carrinhoVazio.style.display = 'block';
                        carrinhoItens.style.display = 'none';
                        return;
                    }

                    console.log('Exibindo', produtos.length, 'produtos no carrinho');
                    carrinhoVazio.style.display = 'none';
                    carrinhoItens.style.display = 'block';

                    produtos.forEach(produto => {
                        html += `
                            <tr>
                                <td>${produto.nome}</td>
                                <td>R$ ${parseFloat(produto.preco).toFixed(2)}</td>
                                <td>
                                    <input type="number" 
                                           value="${produto.quantidade}" 
                                           min="1" 
                                           max="${produto.estoque_disponivel || 1}"
                                           onchange="carrinho.atualizarQuantidade(${produto.id}, this.value)"
                                           class="form-control form-control-sm" 
                                           style="width: 80px;">
                                </td>
                                <td>R$ ${parseFloat(produto.subtotal).toFixed(2)}</td>
                                <td>
                                    <button onclick="carrinho.removerItem(${produto.id})" 
                                            class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });

                    tbody.innerHTML = html;
                    document.getElementById('total-carrinho').innerHTML = 
                        `<strong>R$ ${parseFloat(total).toFixed(2)}</strong>`;
                })
                .catch(error => {
                    console.error('Erro ao buscar produtos:', error);
                    carrinhoVazio.style.display = 'block';
                    carrinhoItens.style.display = 'none';
                    alert('Erro ao carregar produtos do carrinho: ' + error.message);
                });
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Garantir que a interface seja atualizada assim que a página carregar
            atualizarInterfaceCarrinho();
            
            // Atualizar interface quando o carrinho mudar
            carrinho.onChange = atualizarInterfaceCarrinho;
            
            // Configurar o botão de confirmação do pedido
            document.getElementById('btn-confirmar-pedido').addEventListener('click', function() {
                const modalConfirmacao = bootstrap.Modal.getInstance(document.getElementById('modalConfirmacao'));
                modalConfirmacao.hide();
                
                // Exibir indicador de carregamento
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processando...';
                this.disabled = true;
                
                // Enviar pedido para o servidor
                fetch('../controllers/finalizar_pedido.php', {
                    method: 'POST', 
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Resposta recebida do servidor:', response);
                    
                    if (!response.ok) {
                        throw new Error(`Erro na requisição: ${response.status} ${response.statusText}`);
                    }
                    
                    return response.text().then(text => {
                        console.log('Texto da resposta:', text);
                        
                        if (!text || text.trim() === '') {
                            throw new Error('Resposta vazia recebida do servidor');
                        }
                        
                        try {
                            // Tentar analisar o JSON
                            return JSON.parse(text);
                        } catch (error) {
                            console.error('Erro ao analisar JSON:', error);
                            console.error('Texto recebido:', text);
                            throw new Error('Resposta inválida do servidor: ' + error.message);
                        }
                    });
                })
                .then(data => {
                    // Resetar o botão
                    this.innerHTML = '<i class="bi bi-check-circle"></i> Confirmar Pedido';
                    this.disabled = false;
                    
                    if (data.success) {
                        // Exibir modal de sucesso
                        document.getElementById('numero-pedido').textContent = data.pedido.numero;
                        document.getElementById('valor-total').textContent = `R$ ${data.pedido.valor_total}`;
                        
                        const modalSucesso = new bootstrap.Modal(document.getElementById('modalSucesso'));
                        modalSucesso.show();
                        
                        // Atualizar interface do carrinho
                        atualizarInterfaceCarrinho();
                    } else {
                        alert(data.mensagem || 'Erro ao finalizar pedido');
                    }
                })
                .catch(error => {
                    console.error('Erro ao finalizar pedido:', error);
                    this.innerHTML = '<i class="bi bi-check-circle"></i> Confirmar Pedido';
                    this.disabled = false;
                    alert('Erro ao finalizar pedido: ' + error.message);
                });
            });
        });

        // Função para finalizar compra
        function finalizarCompra() {
            // Buscar detalhes do carrinho para o modal
            fetch('../controllers/carrinho.php?json=1')
                .then(response => response.json())
                .then(data => {
                    if (!data.success || !data.produtos || data.produtos.length === 0) {
                        alert('Seu carrinho está vazio!');
                        return;
                    }
                    
                    // Atualizar informações no modal
                    document.getElementById('qtd-itens').textContent = data.produtos.length;
                    document.getElementById('total-modal').textContent = `R$ ${parseFloat(data.total).toFixed(2)}`;
                    
                    // Exibir modal de confirmação
                    const modalConfirmacao = new bootstrap.Modal(document.getElementById('modalConfirmacao'));
                    modalConfirmacao.show();
                })
                .catch(error => {
                    console.error('Erro ao preparar compra:', error);
                    alert('Erro ao preparar compra. Tente novamente.');
                });
        }
    </script>
</body>
</html> 