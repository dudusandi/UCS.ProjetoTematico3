<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/produto.php';

class ProdutoDAO {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function cadastrarProduto(Produto $produto) {
        try {
            $this->pdo->beginTransaction();

            $sqlProduto = "INSERT INTO produtos (nome, descricao, foto, usuario_id, preco) 
                           VALUES (:nome, :descricao, :foto, :usuario_id, :preco) RETURNING id";
            $stmtProduto = $this->pdo->prepare($sqlProduto);
            $stmtProduto->bindValue(':nome', $produto->getNome());
            $stmtProduto->bindValue(':descricao', $produto->getDescricao());
            $stmtProduto->bindValue(':foto', $produto->getFoto(), PDO::PARAM_LOB);
            $stmtProduto->bindValue(':usuario_id', $produto->getUsuarioId());
            $stmtProduto->bindValue(':preco', $produto->getPreco());
            $stmtProduto->execute();

            $produtoId = $stmtProduto->fetch(PDO::FETCH_ASSOC)['id'];
            $produto->setId($produtoId);

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
                throw new InvalidArgumentException("ID do produto inválido.");
            }

            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.usuario_id, p.preco, 
                           c.nome AS proprietario_nome 
                    FROM produtos p
                    LEFT JOIN clientes c ON p.usuario_id = c.id
                    WHERE p.id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $linha = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$linha) {
                return null;
            }


            return [
                'id' => $linha['id'],
                'nome' => $linha['nome'],
                'descricao' => $linha['descricao'],
                'foto' => $linha['foto'], // Assumindo que foto é BLOB e será tratado no JS/PHP para exibição
                'preco' => $linha['preco'],
                'usuario_id' => $linha['usuario_id'], // ID do proprietário
                'proprietario_nome' => $linha['proprietario_nome'] ?? 'Nome não disponível' // Nome do proprietário
            ];


        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar produto por ID: " . $e->getMessage());
        } catch (InvalidArgumentException $e) {
            throw $e; 
        }
    }

    public function buscarPorNome($nome) {
        try {
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.usuario_id, p.preco
                    FROM produtos p
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
                    $linha['preco'],
                    $linha['usuario_id']
                );
                $produto->setId($linha['id']);

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
                    usuario_id = :usuario_id,
                    preco = :preco 
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':nome', $produto->getNome(), PDO::PARAM_STR);
            $stmt->bindValue(':descricao', $produto->getDescricao(), PDO::PARAM_STR);
            $stmt->bindValue(':foto', $produto->getFoto(), PDO::PARAM_LOB);
            $stmt->bindValue(':usuario_id', $produto->getUsuarioId(), PDO::PARAM_INT);
            $stmt->bindValue(':preco', $produto->getPreco());
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
                if ($produto['foto']) {
                }

                $sqlDeleteProduto = "DELETE FROM produtos WHERE id = :id";
                $stmtDeleteProduto = $this->pdo->prepare($sqlDeleteProduto);
                $stmtDeleteProduto->bindValue(':id', $id, PDO::PARAM_INT);
                $stmtDeleteProduto->execute();

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

            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.usuario_id, p.preco
                    FROM produtos p
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
                    'preco' => $linha['preco'],
                    'usuario_id' => $linha['usuario_id']
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
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.preco, p.usuario_id
                    FROM produtos p
                    WHERE p.id IN ($placeholders)
                    ORDER BY p.id DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($ids);

            $produtos = [];
            while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
                try {
                    $linha['nome'] = $linha['nome'] ?? '';
                    $linha['descricao'] = $linha['descricao'] ?? '';
                    $linha['foto'] = $linha['foto'] ?? null;
                    $linha['preco'] = $linha['preco'] ?? 0.0;
                    $linha['usuario_id'] = $linha['usuario_id'] ?? 0;


                    $produto = new Produto(
                        $linha['nome'],
                        $linha['descricao'],
                        $linha['foto'],
                        (float)$linha['preco'],
                        $linha['usuario_id']
                    );
                    $produto->setId($linha['id']);

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

    public function buscarTodos() {
        throw new Exception("O método buscarTodos() precisa ser revisado ou removido.");
    }

    public function buscarPorUsuarioId($usuario_id, $termo = '', $limite = null, $offset = null) {
        try {
            $termoPesquisa = '%' . strtolower($termo) . '%';
            $sql = "SELECT p.id, p.nome, p.descricao, p.foto, p.preco, p.usuario_id
                    FROM produtos p
                    WHERE p.usuario_id = :usuario_id";
            
            if (!empty($termo)) {
                $sql .= " AND (LOWER(p.nome) LIKE :termo OR LOWER(p.descricao) LIKE :termo)";
            }
            
            $sql .= " ORDER BY p.id DESC";

            if ($limite !== null && $offset !== null) {
                $sql .= " LIMIT :limite OFFSET :offset";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
            if (!empty($termo)) {
                $stmt->bindValue(':termo', $termoPesquisa, PDO::PARAM_STR);
            }
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
                    'preco' => $linha['preco'],
                    'usuario_id' => $linha['usuario_id']
                ];
            }
            return $produtos;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function contarProdutosPorUsuarioId($usuario_id, $termo = '') {
        try {
            $sql = "SELECT COUNT(*) 
                    FROM produtos p
                    WHERE p.usuario_id = :usuario_id";
            
            if (!empty($termo)) {
                $termoPesquisa = '%' . strtolower($termo) . '%';
                $sql .= " AND (LOWER(p.nome) LIKE :termo OR LOWER(p.descricao) LIKE :termo)";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':usuario_id', $usuario_id, PDO::PARAM_INT);
            if (!empty($termo)) {
                $stmt->bindValue(':termo', $termoPesquisa, PDO::PARAM_STR);
            }
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
?>