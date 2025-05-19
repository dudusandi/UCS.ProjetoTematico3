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

    public function cadastrarProduto(Produto $produto, $quantidade, $preco) {
        try {
            $this->pdo->beginTransaction();

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

            $stmt->bindValue(':nome', $produto->getNome(), PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $produto->getDescricao(), PDO::PARAM_STR);
            $stmt->bindValue(':foto', $produto->getFoto(), PDO::PARAM_LOB);
            $stmt->bindValue(':fornecedor_id', $fornecedorId, PDO::PARAM_INT);
            $stmt->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
            $stmt->bindValue(':usuario_id', $usuarioId, PDO::PARAM_INT);

            $stmt->execute();

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

    public function contarTodosProdutos() {
        try {
            $sql = "SELECT COUNT(*) FROM produtos";
            $stmt = $this->pdo->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw $e;
        }
    }

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
            throw $e;
        }
    }

    public function buscarPorId($id) {
        try {
            if (!is_numeric($id) || $id <= 0) {
                throw new Exception("ID inválido");
            }

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

            $linha = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$linha) {
                return null;
            }

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
        } catch (PDOException $e) {
            throw $e;
        }
    }

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

    public function atualizarProduto(Produto $produto) {
        try {
            $sql = "UPDATE produtos SET 
                    nome = :nome, 
                    descricao = :descricao, 
                    foto = :foto, 
                    fornecedor_id = :fornecedor_id
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $produto->getNome(), PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $produto->getDescricao(), PDO::PARAM_STR);
            $stmt->bindValue(':foto', $produto->getFoto(), PDO::PARAM_LOB);
            $stmt->bindValue(':fornecedor_id', $produto->getFornecedorId(), PDO::PARAM_INT);
            $stmt->bindValue(':id', $produto->getId(), PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function removerProduto($id) {
        try {
            $produto = $this->buscarPorId($id);
            if ($produto) {
                if ($produto->getFoto()) {
                }

                $estoqueId = $produto->getEstoqueId();
                $sqlDeleteProduto = "DELETE FROM produtos WHERE id = :id";
                $stmtDeleteProduto = $this->pdo->prepare($sqlDeleteProduto);
                $stmtDeleteProduto->bindValue(':id', $id, PDO::PARAM_INT);
                $stmtDeleteProduto->execute();

                if ($estoqueId) {
                    $sqlCheckEstoque = "SELECT COUNT(*) FROM produtos WHERE estoque_id = :estoque_id";
                    $stmtCheckEstoque = $this->pdo->prepare($sqlCheckEstoque);
                    $stmtCheckEstoque->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
                    $stmtCheckEstoque->execute();
                    $contagemReferencias = $stmtCheckEstoque->fetchColumn();

                    if ($contagemReferencias == 0) {
                        $this->estoqueDAO->excluir($estoqueId); 
                    }
                }

                return true;
            }
            throw new Exception("Produto não encontrado");
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function buscarProdutos($termo = '', $limite = null, $offset = null) {
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
            
            if ($limite !== null && $offset !== null) {
                $sql .= " LIMIT :limite OFFSET :offset";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':termo', $termoPesquisa, PDO::PARAM_STR);

            if ($limite !== null && $offset !== null) {
                $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            }

            $stmt->execute();

            $produtos = [];
            while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $foto = null;
                if ($linha['foto']) {
                    $foto = stream_get_contents($linha['foto']);
                }

                $produtos[] = [
                    'id' => $linha['id'],
                    'nome' => $linha['nome'],
                    'descricao' => $linha['descricao'],
                    'foto' => $foto,
                    'fornecedor_id' => $linha['fornecedor_id'],
                    'quantidade' => $linha['quantidade'],
                    'preco' => $linha['preco'],
                    'fornecedor_nome' => $linha['fornecedor_nome'] ?? 'Sem fornecedor'
                ];
            }

            return $produtos;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function buscarProdutosPorIds($ids) {
        try {
            if (empty($ids)) {
                return [];
            }
            $ids = array_filter(array_map('intval', $ids), function($id) {
                return $id > 0;
            });

            if (empty($ids)) {
                return [];
            }

            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.fornecedor_id, p.estoque_id, p.usuario_id,
                           e.quantidade, e.preco,
                           f.nome AS fornecedor_nome
                    FROM produtos p
                    LEFT JOIN estoques e ON p.estoque_id = e.id
                    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
                    WHERE p.id IN ($placeholders)
                    ORDER BY p.id DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($ids);

            $produtos = [];
            while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
                try {
                    // Converte valores nulos para valores padrão
                    $linha['nome'] = $linha['nome'] ?? '';
                    $linha['descricao'] = $linha['descricao'] ?? '';
                    $linha['foto'] = $linha['foto'] ?? null;
                    $linha['fornecedor_id'] = $linha['fornecedor_id'] ?? 0;
                    $linha['usuario_id'] = $linha['usuario_id'] ?? 0;
                    $linha['quantidade'] = $linha['quantidade'] ?? 0;
                    $linha['preco'] = $linha['preco'] ?? 0;

                    $produto = new Produto(
                        $linha['nome'],
                        $linha['descricao'],
                        $linha['foto'],
                        $linha['fornecedor_id'],
                        $linha['usuario_id']
                    );
                    $produto->setId($linha['id']);
                    $produto->setEstoqueId($linha['estoque_id'] ?? 0);
                    $produto->setQuantidade($linha['quantidade']);
                    $produto->setPreco($linha['preco']);
                    $produto->fornecedor_nome = $linha['fornecedor_nome'] ?? 'Sem fornecedor';
                    $produtos[] = $produto;
                } catch (Exception $e) {
                    continue;
                }
            }
            return $produtos;
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
?>