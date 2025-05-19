<?php
$basePath = realpath(dirname(__DIR__));
require_once "$basePath/config/database.php";
require_once "$basePath/model/fornecedor.php";
require_once "$basePath/model/endereco.php";

class FornecedorDAO {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function cadastrarFornecedor(Fornecedor $fornecedor) {
        try {
            $this->pdo->beginTransaction();

            $endereco = $fornecedor->getEndereco();
            $sqlEndereco = "INSERT INTO enderecos 
                           (rua, numero, complemento, bairro, cep, cidade, estado) 
                           VALUES (:rua, :numero, :complemento, :bairro, :cep, :cidade, :estado) 
                           RETURNING id";
            $stmtEndereco = $this->pdo->prepare($sqlEndereco);
            $stmtEndereco->execute([
                ':rua' => $endereco->getRua(),
                ':numero' => $endereco->getNumero(),
                ':complemento' => $endereco->getComplemento(),
                ':bairro' => $endereco->getBairro(),
                ':cep' => $endereco->getCep(),
                ':cidade' => $endereco->getCidade(),
                ':estado' => $endereco->getEstado()
            ]);

            $enderecoId = $stmtEndereco->fetch(PDO::FETCH_ASSOC)['id'];

            $sqlFornecedor = "INSERT INTO fornecedores 
                             (nome, descricao, telefone, email, endereco_id) 
                             VALUES (:nome, :descricao, :telefone, :email, :endereco_id)";
            $stmtFornecedor = $this->pdo->prepare($sqlFornecedor);
            $stmtFornecedor->execute([
                ':nome' => $fornecedor->getNome(),
                ':descricao' => $fornecedor->getDescricao(),
                ':telefone' => $fornecedor->getTelefone(),
                ':email' => $fornecedor->getEmail(),
                ':endereco_id' => $enderecoId
            ]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function buscarNomePorId($fornecedorId) {
        try {
            $sql = "SELECT nome FROM fornecedores WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $fornecedorId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['nome'] : 'Desconhecido';
        } catch (PDOException $e) {
            // error_log("Erro ao buscar fornecedor: " . $e->getMessage());
            throw $e;
        }
    }

    public function listarTodos($limit = null, $offset = null) {
        try {
            $sql = "SELECT f.id, f.nome, f.descricao, f.telefone, f.email,
                           e.rua, e.numero, e.bairro, e.cidade, e.estado, e.cep
                    FROM fornecedores f
                    LEFT JOIN enderecos e ON f.endereco_id = e.id";
            if ($limit !== null && $offset !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            $stmt = $this->pdo->prepare($sql);
            if ($limit !== null && $offset !== null) {
                $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            }
            $stmt->execute();

            $fornecedores = [];
            while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $endereco = new Endereco(
                    $linha['rua'],
                    $linha['numero'],
                    $linha['bairro'],
                    $linha['cidade'],
                    $linha['estado'],
                    $linha['cep']
                );
                $fornecedor = new Fornecedor(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['telefone'],
                    $linha['email'],
                    $endereco
                );
                $fornecedor->setId($linha['id']);
                $fornecedores[] = $fornecedor;
            }
            return $fornecedores;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function contarTodos() {
        try {
            $sql = "SELECT COUNT(*) FROM fornecedores";
            $stmt = $this->pdo->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function buscarFornecedoresDinamicos($termo, $limite = 6, $offset = 0) {
        try {
            $termo = "%{$termo}%";
            $sql = "SELECT f.id, f.nome, f.descricao, f.telefone, f.email, 
                           e.rua, e.numero, e.bairro, e.cep, e.cidade, e.estado, e.complemento
                    FROM fornecedores f
                    JOIN enderecos e ON f.endereco_id = e.id
                    WHERE LOWER(f.nome) LIKE LOWER(:termo) 
                    OR LOWER(f.descricao) LIKE LOWER(:termo) 
                    OR LOWER(f.email) LIKE LOWER(:termo) 
                    OR LOWER(f.telefone) LIKE LOWER(:termo) 
                    OR LOWER(e.rua) LIKE LOWER(:termo) 
                    OR LOWER(e.cidade) LIKE LOWER(:termo) 
                    OR LOWER(e.estado) LIKE LOWER(:termo) 
                    ORDER BY f.nome 
                    LIMIT :limite OFFSET :offset";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':termo', $termo, PDO::PARAM_STR);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $fornecedores = [];
            while ($linha = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $endereco = new Endereco(
                    $linha['rua'],
                    $linha['numero'],
                    $linha['bairro'],
                    $linha['cep'],
                    $linha['cidade'],
                    $linha['estado'],
                    $linha['complemento']
                );

                $fornecedor = new Fornecedor(
                    $linha['nome'],
                    $linha['descricao'],
                    $linha['telefone'],
                    $linha['email'],
                    $endereco
                );
                $fornecedor->setId($linha['id']);
                $fornecedores[] = $fornecedor;
            }
            
            return $fornecedores;
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar fornecedores");
        }
    }

    public function contarFornecedoresBuscados($termo) {
        try {
            $termo = "%{$termo}%";
            $sql = "SELECT COUNT(*) 
                    FROM fornecedores f
                    JOIN enderecos e ON f.endereco_id = e.id
                    WHERE LOWER(f.nome) LIKE LOWER(:termo) 
                    OR LOWER(f.descricao) LIKE LOWER(:termo) 
                    OR LOWER(f.email) LIKE LOWER(:termo) 
                    OR LOWER(f.telefone) LIKE LOWER(:termo) 
                    OR LOWER(e.rua) LIKE LOWER(:termo) 
                    OR LOWER(e.cidade) LIKE LOWER(:termo) 
                    OR LOWER(e.estado) LIKE LOWER(:termo)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':termo', $termo, PDO::PARAM_STR);
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Erro ao contar fornecedores");
        }
    }

    public function buscarPorId($id) {
        $sql = "SELECT f.id, f.nome, f.descricao, f.telefone, f.email, 
                       e.rua, e.numero, e.bairro, e.cep, e.cidade, e.estado, e.complemento
                FROM fornecedores f
                JOIN enderecos e ON f.endereco_id = e.id
                WHERE f.id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);

            $endereco = new Endereco(
                $linha['rua'],
                $linha['numero'],
                $linha['bairro'],
                $linha['cep'],
                $linha['cidade'],
                $linha['estado'],
                $linha['complemento']
            );

            $fornecedor = new Fornecedor(
                $linha['nome'],
                $linha['descricao'],
                $linha['telefone'],
                $linha['email'],
                $endereco
            );
            $fornecedor->setId($linha['id']); 
            return $fornecedor;
        }

        return null;
    }

    public function listarFornecedores() {
        try {
            $sql = "SELECT id, nome FROM fornecedores ORDER BY nome ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function atualizarFornecedor(Fornecedor $fornecedor) {
        try {
            $this->pdo->beginTransaction();

            $endereco = $fornecedor->getEndereco();
            $sqlEndereco = "UPDATE enderecos SET 
                           rua = :rua, 
                           numero = :numero, 
                           complemento = :complemento, 
                           bairro = :bairro, 
                           cep = :cep, 
                           cidade = :cidade, 
                           estado = :estado
                           WHERE id = (SELECT endereco_id FROM fornecedores WHERE id = :fornecedorId)";
            $stmtEndereco = $this->pdo->prepare($sqlEndereco);
            $stmtEndereco->bindParam(":rua", $endereco->getRua());
            $stmtEndereco->bindParam(":numero", $endereco->getNumero());
            $stmtEndereco->bindParam(":complemento", $endereco->getComplemento());
            $stmtEndereco->bindParam(":bairro", $endereco->getBairro());
            $stmtEndereco->bindParam(":cep", $endereco->getCep());
            $stmtEndereco->bindParam(":cidade", $endereco->getCidade());
            $stmtEndereco->bindParam(":estado", $endereco->getEstado());
            $stmtEndereco->bindParam(":fornecedorId", $fornecedor->getId());
            $stmtEndereco->execute();

            $sqlFornecedor = "UPDATE fornecedores SET 
                             nome = :nome, 
                             descricao = :descricao, 
                             telefone = :telefone, 
                             email = :email
                             WHERE id = :id";
            $stmtFornecedor = $this->pdo->prepare($sqlFornecedor);
            $stmtFornecedor->bindParam(":nome", $fornecedor->getNome());
            $stmtFornecedor->bindParam(":descricao", $fornecedor->getDescricao());
            $stmtFornecedor->bindParam(":telefone", $fornecedor->getTelefone());
            $stmtFornecedor->bindParam(":email", $fornecedor->getEmail());
            $stmtFornecedor->bindParam(":id", $fornecedor->getId());
            $resultado = $stmtFornecedor->execute();

            $this->pdo->commit();
            return $resultado;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw new Exception("Erro ao atualizar fornecedor: " . $e->getMessage());
        }
    }

    public function removerFornecedor($id) {
        try {
            // Verificar se o fornecedor está associado a produtos
            $sqlCheckProdutos = "SELECT COUNT(*) FROM produtos WHERE fornecedor_id = :id";
            $stmtCheckProdutos = $this->pdo->prepare($sqlCheckProdutos);
            $stmtCheckProdutos->bindParam(":id", $id, PDO::PARAM_INT);
            $stmtCheckProdutos->execute();
            $count = $stmtCheckProdutos->fetchColumn();

            if ($count > 0) {
                throw new Exception('Não é possível excluir o fornecedor porque ele está associado a produtos');
            }

            $this->pdo->beginTransaction();

            $sqlGetEndereco = "SELECT endereco_id FROM fornecedores WHERE id = :id";
            $stmtGet = $this->pdo->prepare($sqlGetEndereco);
            $stmtGet->bindParam(":id", $id, PDO::PARAM_INT);
            $stmtGet->execute();

            if ($stmtGet->rowCount() == 0) {
                $this->pdo->rollBack();
                throw new Exception("Fornecedor não encontrado");
            }

            $enderecoId = $stmtGet->fetch(PDO::FETCH_ASSOC)['endereco_id'];

            $sqlFornecedor = "DELETE FROM fornecedores WHERE id = :id";
            $stmtFornecedor = $this->pdo->prepare($sqlFornecedor);
            $stmtFornecedor->bindParam(":id", $id, PDO::PARAM_INT);
            $stmtFornecedor->execute();

            if ($enderecoId) {
                $sqlEndereco = "DELETE FROM enderecos WHERE id = :id";
                $stmtEndereco = $this->pdo->prepare($sqlEndereco);
                $stmtEndereco->bindParam(":id", $enderecoId, PDO::PARAM_INT);
                $stmtEndereco->execute();
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new Exception("Erro ao remover fornecedor: " . $e->getMessage());
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
?>