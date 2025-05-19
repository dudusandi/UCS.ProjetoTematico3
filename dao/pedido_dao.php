<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/pedido.php';
require_once __DIR__ . '/../model/item_pedido.php';
require_once __DIR__ . '/../dao/cliente_dao.php';
require_once __DIR__ . '/../dao/produto_dao.php';
require_once __DIR__ . '/../dao/estoque_dao.php';

class PedidoDAO {
    private $pdo;
    private $clienteDAO;
    private $produtoDAO;
    private $estoqueDAO;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->clienteDAO = new ClienteDAO($pdo);
        $this->produtoDAO = new ProdutoDAO($pdo);
        $this->estoqueDAO = new EstoqueDAO($pdo);
    }


    public function criarPedido($clienteId, $itensPedido) {
        try {
            if (empty($itensPedido) || !is_array($itensPedido)) {
                throw new Exception("Nenhum item válido para o pedido");
            }
            
            $numero = 'P' . date(format: 'dmyHis');

            $this->pdo->beginTransaction();

            $sqlPedido = "INSERT INTO pedidos (numero, cliente_id, data_pedido, situacao) 
                          VALUES (:numero, :cliente_id, NOW(), 'NOVO')";
            $stmtPedido = $this->pdo->prepare($sqlPedido);
            $stmtPedido->bindParam(':numero', $numero);
            $stmtPedido->bindParam(':cliente_id', $clienteId);
            $stmtPedido->execute();
            
            $pedidoId = $this->pdo->lastInsertId('pedidos_id_seq');
            
            if (!$pedidoId) {
                $sqlId = "SELECT id FROM pedidos WHERE numero = :numero";
                $stmtId = $this->pdo->prepare($sqlId);
                $stmtId->bindParam(':numero', $numero);
                $stmtId->execute();
                $pedidoResult = $stmtId->fetch(PDO::FETCH_ASSOC);
                
                if (!$pedidoResult || !isset($pedidoResult['id'])) {
                    throw new Exception("Falha ao obter ID do pedido criado");
                }
                
                $pedidoId = $pedidoResult['id'];
            }
            
            $valorTotal = 0;
            
            $sqlItem = "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, subtotal) 
                        VALUES (:pedido_id, :produto_id, :quantidade, :preco_unitario, :subtotal)";
            $stmtItem = $this->pdo->prepare($sqlItem);
            
            foreach ($itensPedido as $item) {
                if (!isset($item['id']) || !isset($item['quantidade']) || !isset($item['preco'])) {
                    continue;
                }
                
                $produtoId = (int)$item['id'];
                $quantidadePedida = (int)$item['quantidade'];
                $precoUnitario = (float)$item['preco'];
                
                // Buscar produto para verificar estoque
                $produto = $this->produtoDAO->buscarPorId($produtoId);
                if (!$produto) {
                    throw new Exception("Produto com ID {$produtoId} não encontrado no estoque.");
                }

                $estoqueAtual = $produto->getQuantidade();
                $estoqueId = $produto->getEstoqueId();

                if ($quantidadePedida <= 0) {
                    throw new Exception("Quantidade inválida para o produto {$produto->getNome()}.");
                }

                if ($estoqueAtual < $quantidadePedida) {
                    throw new Exception("Estoque insuficiente para o produto {$produto->getNome()}. Disponível: {$estoqueAtual}, Pedido: {$quantidadePedida}");
                }

                $subtotal = $precoUnitario * $quantidadePedida;

                $stmtItem->bindParam(':pedido_id', $pedidoId);
                $stmtItem->bindParam(':produto_id', $produtoId);
                $stmtItem->bindParam(':quantidade', $quantidadePedida);
                $stmtItem->bindParam(':preco_unitario', $precoUnitario);
                $stmtItem->bindParam(':subtotal', $subtotal);
                $stmtItem->execute();
                
                $novoEstoque = $estoqueAtual - $quantidadePedida;
                $this->estoqueDAO->atualizarQuantidade($estoqueId, $novoEstoque);

                $valorTotal += $subtotal;
            }
            
            $sqlTotal = "UPDATE pedidos SET valor_total = :valor_total WHERE id = :pedido_id";
            $stmtTotal = $this->pdo->prepare($sqlTotal);
            $stmtTotal->bindParam(':valor_total', $valorTotal);
            $stmtTotal->bindParam(':pedido_id', $pedidoId);
            $stmtTotal->execute();
            
            $this->pdo->commit();
            
            return [
                'id' => $pedidoId,
                'numero' => $numero,
                'valor_total' => $valorTotal
            ];
        } catch (Exception $e) {
            if ($this->pdo && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
    

    public function buscarPorId($pedidoId) {
        try {
            $sql = "SELECT p.id, p.numero, p.data_pedido, p.data_entrega, 
                           p.situacao, p.cliente_id, p.valor_total,
                           p.data_envio, p.data_cancelamento                   -- Novas colunas
                    FROM pedidos p
                    WHERE p.id = :pedido_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':pedido_id', $pedidoId);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cliente = $this->clienteDAO->buscarPorId($row['cliente_id']);

                $pedido = new Pedido(
                    $row['id'],
                    $row['numero'],
                    $row['data_pedido'],
                    $cliente, 
                    $row['valor_total'],
                    $row['data_entrega'],
                    $row['situacao'],
                    $row['data_envio'],         // Passando data_envio
                    $row['data_cancelamento']  // Passando data_cancelamento
                );
                $this->carregarItensPedido($pedido, $row['id']);
                
                return $pedido;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Erro em PedidoDAO->buscarPorId: " . $e->getMessage());
            throw $e;
        }
    }
    

    private function carregarItensPedido($pedido, $pedidoId) {
        $sql = "SELECT ip.id, ip.produto_id, ip.quantidade, ip.preco_unitario, ip.subtotal
                FROM itens_pedido ip
                WHERE ip.pedido_id = :pedido_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':pedido_id', $pedidoId);
        $stmt->execute();
        
        $itensAdicionados = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $produto = $this->produtoDAO->buscarPorId($row['produto_id']);
            
            if ($produto) {
                $itemPedido = new ItemPedido(
                    $row['quantidade'],
                    $row['preco_unitario'],
                    $produto,
                    $pedido,
                    $row['subtotal']
                );
                $pedido->getItensPedido()[] = $itemPedido; 
                $itensAdicionados++;
            } else {
                error_log("Produto com ID " . $row['produto_id'] . " não encontrado para o item do pedido " . $row['id'] . " do pedido ID " . $pedidoId);
            }
        }
    }
    

    public function listarPedidosCliente($clienteId) {
        try {
            $sql = "SELECT p.id, p.numero, p.data_pedido, p.data_entrega, p.situacao, 
                           p.cliente_id, p.valor_total
                    FROM pedidos p
                    WHERE p.cliente_id = :cliente_id
                    ORDER BY p.data_pedido DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':cliente_id', $clienteId);
            $stmt->execute();
            
            $pedidos = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $pedidos[] = [
                    'id' => $row['id'],
                    'numero' => $row['numero'],
                    'data_pedido' => $row['data_pedido'],
                    'data_entrega' => $row['data_entrega'],
                    'situacao' => $row['situacao'],
                    'valor_total' => $row['valor_total']
                ];
            }
            
            return $pedidos;
        } catch (Exception $e) {
            throw $e;
        }
    }
    

    public function atualizarStatusPedido($pedidoId, $novaSituacao) {
        try {
            $sqlUpdates = ["situacao = :novaSituacao"];
            $params = [':pedidoId' => $pedidoId, ':novaSituacao' => $novaSituacao];

            $agora = date('c');

            if ($novaSituacao === 'ENVIADO') {

                $sqlUpdates[] = "data_envio = :dataEnvio";
                $params[':dataEnvio'] = $agora;
                $sqlUpdates[] = "data_cancelamento = NULL";
            } 
            else if ($novaSituacao === 'ENTREGUE') {
                $sqlUpdates[] = "data_entrega = :dataEntrega";
                $params[':dataEntrega'] = $agora;
            } 
            else if ($novaSituacao === 'CANCELADO') {
                $sqlUpdates[] = "data_cancelamento = :dataCancelamento";
                $params[':dataCancelamento'] = $agora;

            } else if ($novaSituacao === 'NOVO' || $novaSituacao === 'EM_PREPARACAO') {

            }

            $sql = "UPDATE pedidos SET " . implode(", ", $sqlUpdates) . " WHERE id = :pedidoId";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);

        } catch (Exception $e) {
            error_log("Erro em PedidoDAO->atualizarStatusPedido: " . $e->getMessage());
            throw $e;
        }
    }


    public function listarTodosPedidos($pagina = 1, $itensPorPagina = 10, $termoBusca = null) {
        try {
            $offset = ($pagina - 1) * $itensPorPagina;

            $sqlBase = "FROM pedidos p JOIN clientes c ON p.cliente_id = c.id";
            $condicoes = [];
            $params = [];

            if (!empty($termoBusca)) {
                $condicoes[] = "(LOWER(p.numero) LIKE :termoBusca OR LOWER(c.nome) LIKE :termoBusca)";
                $params[':termoBusca'] = '%' . strtolower($termoBusca) . '%';
            }

            $sqlWhere = "";
            if (count($condicoes) > 0) {
                $sqlWhere = " WHERE " . implode(" AND ", $condicoes);
            }

            $sql = "SELECT p.id, p.numero, p.data_pedido, p.situacao, p.valor_total,
                           c.nome as nome_cliente, c.email as email_cliente
                    $sqlBase
                    $sqlWhere
                    ORDER BY p.data_pedido DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sqlTotal = "SELECT COUNT(p.id) $sqlBase $sqlWhere";
            $stmtTotal = $this->pdo->prepare($sqlTotal);
            foreach ($params as $key => $value) { 
                $stmtTotal->bindValue($key, $value);
            }
            $stmtTotal->execute();
            $totalPedidos = $stmtTotal->fetchColumn();
            
            return [
                'pedidos' => $pedidos,
                'total' => (int)$totalPedidos,
                'pagina' => $pagina,
                'itensPorPagina' => $itensPorPagina
            ];
            
        } catch (Exception $e) {
            error_log("Erro em listarTodosPedidos (DAO): " . $e->getMessage());
            throw $e; 
        }
    }
}
?> 