<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/produto.php';
// require_once __DIR__ . '/../dao/estoque_dao.php';

class ProdutoDAO {
    private $pdo;
    // private $estoqueDAO;

    public function __construct() {
        $this->pdo = Database::getConnection();
        // $this->estoqueDAO = new EstoqueDAO($this->pdo);
    }

    public function cadastrarProduto(Produto $produto) {
        try {
            $this->pdo->beginTransaction();

            // $sqlEstoque = "INSERT INTO estoques (quantidade, preco, produto_id) VALUES (:quantidade, :preco, NULL) RETURNING id";
            // $stmtEstoque = $this->pdo->prepare($sqlEstoque);
            // $stmtEstoque->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
            // $stmtEstoque->bindValue(':preco', $preco, PDO::PARAM_STR);
            // $stmtEstoque->execute();
            // $estoqueId = $stmtEstoque->fetch(PDO::FETCH_ASSOC)['id'];

            $sqlProduto = "INSERT INTO produtos (nome, descricao, foto, usuario_id, preco) 
                           VALUES (:nome, :descricao, :foto, :usuario_id, :preco) RETURNING id";
            $stmtProduto = $this->pdo->prepare($sqlProduto);
            $stmtProduto->bindValue(':nome', $produto->getNome());
            $stmtProduto->bindValue(':descricao', $produto->getDescricao());
            $stmtProduto->bindValue(':foto', $produto->getFoto(), PDO::PARAM_LOB);
            // $stmtProduto->bindValue(':estoque_id', $estoqueId);
            $stmtProduto->bindValue(':usuario_id', $produto->getUsuarioId());
            $stmtProduto->bindValue(':preco', $produto->getPreco());
            $stmtProduto->execute();

            $produtoId = $stmtProduto->fetch(PDO::FETCH_ASSOC)['id'];
            $produto->setId($produtoId);
            // $produto->setEstoqueId($estoqueId);
            // $produto->setQuantidade($quantidade);
            // $produto->setPreco($preco);

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

            // Modificado para fazer JOIN com clientes e buscar o nome do usuário
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

            // O construtor do Produto pode precisar ser ajustado se você quiser que ele armazene proprietario_nome
            // Por enquanto, vamos retornar um array associativo do DAO para simplificar a passagem de dados para o JS
            // ou garantir que o objeto Produto tenha como obter essa informação.
            // Para este exemplo, vou retornar um array com todos os dados necessários.
            
            // Se o seu objeto Produto não tem um campo para proprietario_nome, 
            // e você não quer modificá-lo, retornar um array aqui é uma opção direta para o AJAX.
            return [
                'id' => $linha['id'],
                'nome' => $linha['nome'],
                'descricao' => $linha['descricao'],
                'foto' => $linha['foto'], // Assumindo que foto é BLOB e será tratado no JS/PHP para exibição
                'preco' => $linha['preco'],
                'usuario_id' => $linha['usuario_id'], // ID do proprietário
                'proprietario_nome' => $linha['proprietario_nome'] ?? 'Nome não disponível' // Nome do proprietário
            ];

            /* // Se preferir manter retornando o objeto Produto, você precisaria:
            // 1. Adicionar um campo e getter/setter para proprietario_nome no model/produto.php
            // 2. Modificar o construtor do Produto para aceitar proprietario_nome
            // 3. Instanciar e retornar o objeto Produto aqui:
            $produto = new Produto(
                $linha['nome'],
                $linha['descricao'],
                $linha['foto'],
                $linha['preco'],
                $linha['usuario_id']
                // , $linha['proprietario_nome'] // se o construtor for atualizado
            );
            $produto->setId($linha['id']);
            // $produto->setProprietarioNome($linha['proprietario_nome']); // se tiver setter
            return $produto;
            */

        } catch (PDOException $e) {
            // Logar o erro em um ambiente de produção
            // error_log("Erro no ProdutoDAO->buscarPorId: " . $e->getMessage());
            throw new Exception("Erro ao buscar produto por ID: " . $e->getMessage());
        } catch (InvalidArgumentException $e) {
            // error_log("Argumento inválido em ProdutoDAO->buscarPorId: " . $e->getMessage());
            throw $e; // Re-lança a exceção para ser tratada pelo chamador
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
                // $produto->setEstoqueId($linha['estoque_id']);
                // $produto->setQuantidade($linha['quantidade']);
                // $produto->setPreco($linha['preco']);
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

                // $estoqueId = $produto->getEstoqueId();
                $sqlDeleteProduto = "DELETE FROM produtos WHERE id = :id";
                $stmtDeleteProduto = $this->pdo->prepare($sqlDeleteProduto);
                $stmtDeleteProduto->bindValue(':id', $id, PDO::PARAM_INT);
                $stmtDeleteProduto->execute();

                // if ($estoqueId) {
                //     $sqlCheckEstoque = "SELECT COUNT(*) FROM produtos WHERE estoque_id = :estoque_id";
                //     $stmtCheckEstoque = $this->pdo->prepare($sqlCheckEstoque);
                //     $stmtCheckEstoque->bindValue(':estoque_id', $estoqueId, PDO::PARAM_INT);
                //     $stmtCheckEstoque->execute();
                //     $contagemReferencias = $stmtCheckEstoque->fetchColumn();

                //     if ($contagemReferencias == 0) {
                //         $this->estoqueDAO->excluir($estoqueId); 
                //     }
                // }

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
                    // Converte valores nulos para valores padrão
                    $linha['nome'] = $linha['nome'] ?? '';
                    $linha['descricao'] = $linha['descricao'] ?? '';
                    $linha['foto'] = $linha['foto'] ?? null;
                    $linha['preco'] = $linha['preco'] ?? 0.0;
                    $linha['usuario_id'] = $linha['usuario_id'] ?? 0;
                    // $linha['quantidade'] = $linha['quantidade'] ?? 0;
                    // $linha['preco'] = $linha['preco'] ?? 0;

                    $produto = new Produto(
                        $linha['nome'],
                        $linha['descricao'],
                        $linha['foto'],
                        (float)$linha['preco'],
                        $linha['usuario_id']
                    );
                    $produto->setId($linha['id']);
                    // $produto->setEstoqueId($linha['estoque_id'] ?? 0);
                    // $produto->setQuantidade($linha['quantidade']);
                    // $produto->setPreco($linha['preco']);
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
        // Este método parece problemático (usa $this->conn, mysqli, construtor antigo).
        // O método buscarProdutos() já pode ser usado para buscar todos se nenhum termo for passado.
        // Recomenda-se revisar ou remover este método.
        /*
        $sql = "SELECT id, nome, descricao, preco, imagem, usuario_id FROM produtos"; // Adicionado usuario_id
        $result = $this->conn->query($sql);
        $produtos = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $produtos[] = new Produto($row['id'], $row['nome'], $row['descricao'], $row['imagem'], $row['preco'], $row['usuario_id']); // Passar usuario_id
            }
        }
        return $produtos;
        */
        // Para manter a funcionalidade temporariamente, pode-se chamar buscarProdutos sem argumentos.
        // return $this->buscarProdutos(); // Isso retornaria um array de arrays, não objetos Produto.
        // Melhor deixar comentado ou remover e ajustar onde é chamado.
        // Lançando uma exceção para indicar que este método precisa de revisão.
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
                    // Em DAOs, é melhor retornar os dados brutos ou objetos Produto.
                    // A conversão para base64 pode ser feita na camada de apresentação/controller se necessário.
                    $foto = stream_get_contents($linha['foto']); 
                }

                // Retornando array de dados, como em buscarProdutos()
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