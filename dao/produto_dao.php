<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/produto.php';
require_once __DIR__ . '/../dao/estoque_dao.php';

class ProdutoDAO {
    private $pdo;
    private $estoqueDAO;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->estoqueDAO = new EstoqueDAO($pdo);
    }

    // Cadastra um novo produto
    public function cadastrarProduto(Produto $produto, $quantidade, $preco) {
        try {
            $this->pdo->beginTransaction();

            // Validações
            $nome = $produto->getNome() ?? '';
            $fornecedorId = (int)($produto->getFornecedorId() ?? 0);
            $usuarioId = (int)($produto->getUsuarioId() ?? 0);

            if (empty($nome) || $fornecedorId <= 0) {
                throw new Exception("Nome e fornecedor são obrigatórios");
            }
            if ($usuarioId === 0) {
                throw new Exception("ID do usuário é obrigatório");
            }

            $estoque = new Estoque($quantidade, $preco);
            $estoqueId = $this->estoqueDAO->inserir($estoque);

            $sql = "INSERT INTO produtos 
                    (nome, descricao, foto, fornecedor_id, estoque_id, usuario_id) 
                    VALUES (:nome, :descricao, :foto, :fornecedor_id, :estoque_id, :usuario_id)
                    RETURNING id";
            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                ':nome' => $produto->getNome(),
                ':descricao' => $produto->getDescricao(),
                ':foto' => $produto->getFoto(),
                ':fornecedor_id' => $fornecedorId,
                ':estoque_id' => $estoqueId,
                ':usuario_id' => $usuarioId
            ]);

            $produtoId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            $produto->setId($produtoId);
            $produto->setEstoqueId($estoqueId);
            $produto->setQuantidade($quantidade);
            $produto->setPreco($preco);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Lista todos os produtos sem paginação
    public function listarTodosProdutos($itensPorPagina = null, $offset = null) {
        try {
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.fornecedor_id, p.estoque_id, p.usuario_id,
                           e.quantidade, e.preco,
                           f.nome AS fornecedor_nome
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
                    ORDER BY p.id DESC";
            if ($itensPorPagina !== null && $offset !== null) {
                $sql .= " LIMIT :itensPorPagina OFFSET :offset";
            }
            $stmt = $this->pdo->prepare($sql);
            if ($itensPorPagina !== null && $offset !== null) {
                $stmt->bindValue(':itensPorPagina', $itensPorPagina, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            $stmt->execute();
    
            $produtos = [];
            while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produto = new Produto(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['foto'],
                    $linha['fornecedor_id'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoqueId($linha['estoque_id']);
                $produto->setQuantidade($linha['quantidade']);
                $produto->setPreco($linha['preco']);
                $produto->fornecedor_nome = $linha['fornecedor_nome'] ?? 'Sem fornecedor';
                $produtos[] = $produto;
            }
            return $produtos;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    // Verifica se o nome do produto já existe
    public function nomeExiste($nome, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM produtos WHERE LOWER(nome) = LOWER(:nome)";
            if ($excludeId) {
                $sql .= " AND id != :excludeId";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            if ($excludeId) {
                $stmt->bindValue(':excludeId', $excludeId, PDO::PARAM_INT);
            }
            $stmt->execute();
            $exists = $stmt->fetchColumn() > 0;
            return $exists;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    // Conta todos os produtos
    public function contarTodosProdutos() {
        try {
            $sql = "SELECT COUNT(*) FROM produtos";
            $stmt = $this->pdo->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    // Excluir produto
    public function excluir($id) {
        try {
            $sql = "DELETE FROM produtos WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    // Busca produtos dinamicamente sem paginação
    public function buscarProdutosDinamicos($termo, $itensPorPagina = null, $offset = null) {
        try {
            $termoPesquisa = '%' . strtolower($termo) . '%';
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.fornecedor_id, p.estoque_id, p.usuario_id,
                           e.quantidade, e.preco,
                           f.nome AS fornecedor_nome
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
                    WHERE LOWER(p.nome) LIKE :termo OR LOWER(p.descricao) LIKE :termo
                    ORDER BY p.id DESC";
            
            if ($itensPorPagina !== null && $offset !== null) {
                $sql .= " LIMIT :itensPorPagina OFFSET :offset";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':termo', $termoPesquisa, PDO::PARAM_STR);
            
            if ($itensPorPagina !== null && $offset !== null) {
                $stmt->bindValue(':itensPorPagina', $itensPorPagina, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            
            $stmt->execute();

            $produtos = [];
            while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $produto = new Produto(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['foto'],
                    $linha['fornecedor_id'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoqueId($linha['estoque_id']);
                $produto->setQuantidade($linha['quantidade']);
                $produto->setPreco($linha['preco']);
                $produto->fornecedor_nome = $linha['fornecedor_nome'] ?? 'Sem fornecedor';
                $produtos[] = $produto;
            }
            return $produtos;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    // Conta produtos encontrados com base no termo
    public function contarProdutosBuscados($termo) {
        try {
            $termoPesquisa = '%' . strtolower($termo) . '%';
            $sql = "SELECT COUNT(*) 
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
                    WHERE LOWER(p.nome) LIKE :termo OR LOWER(p.descricao) LIKE :termo";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':termo', $termoPesquisa, PDO::PARAM_STR);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em contarProdutosBuscados: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    // Buscar produto por ID
    public function buscarPorId($id) {
        try {
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.fornecedor_id, p.estoque_id, p.usuario_id,
                           e.quantidade, e.preco,
                           f.nome AS fornecedor_nome
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
                    WHERE p.id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $linha = $stmt->fetch(PDO::FETCH_ASSOC);
                $produto = new Produto(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['foto'],
                    $linha['fornecedor_id'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoqueId($linha['estoque_id']);
                $produto->setQuantidade($linha['quantidade']);
                $produto->setPreco($linha['preco']);
                $produto->fornecedor_nome = $linha['fornecedor_nome'] ?? 'Sem fornecedor';
                error_log("buscarPorId - Produto ID: {$linha['id']}, Fornecedor Nome: " . ($linha['fornecedor_nome'] ?? 'Sem fornecedor'));
                return $produto;
            }
            return null;
        } catch (PDOException $e) {
            error_log(date('[Y-m-d H:i:s] ') . "Erro em buscarPorId: " . $e->getMessage() . PHP_EOL);
            throw $e;
        }
    }

    // Buscar produto por nome
    public function buscarPorNome($nome) {
        try {
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.fornecedor_id, p.estoque_id, p.usuario_id,
                           e.quantidade, e.preco,
                           f.nome AS fornecedor_nome
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
                    WHERE LOWER(p.nome) = LOWER(:nome)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $nome, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $linha = $stmt->fetch(PDO::FETCH_ASSOC);
                $produto = new Produto(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['foto'],
                    $linha['fornecedor_id'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);
                $produto->setEstoqueId($linha['estoque_id']);
                $produto->setQuantidade($linha['quantidade']);
                $produto->setPreco($linha['preco']);
                $produto->fornecedor_nome = $linha['fornecedor_nome'] ?? 'Sem fornecedor';
                return $produto;
            }
            return null;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    // Atualizar produto
    public function atualizarProduto(Produto $produto) {
        try {
            $this->pdo->beginTransaction();

            $estoqueId = $produto->getEstoqueId();
            if ($estoqueId) {
                $estoque = new Estoque($produto->getQuantidade(), $produto->getPreco());
                $estoque->setId($estoqueId);
                $this->estoqueDAO->atualizar($estoque);
            }

            $sql = "UPDATE produtos SET nome = :nome, descricao = :descricao, foto = :foto, fornecedor_id = :fornecedor_id 
                    WHERE id = :id AND usuario_id = :usuario_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $produto->getNome(), PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $produto->getDescricao() ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':foto', $produto->getFoto() ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':fornecedor_id', $produto->getFornecedorId(), PDO::PARAM_INT);
            $stmt->bindValue(':id', $produto->getId(), PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $produto->getUsuarioId(), PDO::PARAM_INT);
            $success = $stmt->execute();

            $this->pdo->commit();
            return $success;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Remover produto
    public function removerProduto($id) {
        try {
            $produto = $this->buscarPorId($id);
            if ($produto) {
                // Remove a foto, se existir
                if ($produto->getFoto()) {
                    $caminhoFoto = __DIR__ . '/../../public/uploads/imagens/' . $produto->getFoto();
                    if (file_exists($caminhoFoto)) {
                        unlink($caminhoFoto);
                    }
                }

                $estoqueId = $produto->getEstoqueId();
                if ($estoqueId) {
                    $this->estoqueDAO->remover($estoqueId);
                }

                $sql = "DELETE FROM produtos WHERE id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                return true;
            }
            throw new Exception("Produto não encontrado");
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function buscarProdutos($termo = '', $pagina = 1, $itensPorPagina = 12) {
        $offset = ($pagina - 1) * $itensPorPagina;
        
        try {
            $sql = "SELECT p.*, f.nome as fornecedor_nome 
                    FROM produtos p 
                    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id 
                    WHERE 1=1";
            $params = [];
            
            if (!empty($termo)) {
                $sql .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
                $params[] = "%$termo%";
                $params[] = "%$termo%";
            }
            
            $sql .= " ORDER BY p.nome LIMIT ? OFFSET ?";
            $params[] = $itensPorPagina;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Busca total de registros
            $sqlCount = "SELECT COUNT(*) FROM produtos p WHERE 1=1";
            if (!empty($termo)) {
                $sqlCount .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
            }
            $stmtCount = $this->pdo->prepare($sqlCount);
            if (!empty($termo)) {
                $stmtCount->execute(["%$termo%", "%$termo%"]);
            } else {
                $stmtCount->execute();
            }
            $total = $stmtCount->fetchColumn();
            
            return [
                'produtos' => $produtos,
                'total' => $total
            ];
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar produtos: " . $e->getMessage());
            throw new Exception("Erro ao buscar produtos");
        }
    }
}
?>