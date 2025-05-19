<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/estoque.php';

class EstoqueDAO {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }


    public function inserir(Estoque $estoque) {
        try {
            $sql = "INSERT INTO estoques (quantidade, preco) 
                    VALUES (:quantidade, :preco) 
                    RETURNING id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':quantidade' => $estoque->getQuantidade(),
                ':preco' => $estoque->getPreco()
            ]);

            $estoqueId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            $estoque->setId($estoqueId);

            return $estoqueId;
        } catch (PDOException $e) {
            throw new Exception("Erro ao inserir estoque: " . $e->getMessage());
        }
    }

    public function buscarQuantidadePorId($estoqueId) {
        try {
            $sql = "SELECT quantidade FROM estoques WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $estoqueId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['quantidade'] : 0;
        } catch (PDOException $e) {
            // error_log("Erro ao buscar estoque: " . $e->getMessage());
            throw $e;
        }
    }

    public function atualizarQuantidade($estoqueId, $quantidade) {
        try {
            $sql = "UPDATE estoques SET quantidade = :quantidade WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
            $stmt->bindValue(':id', $estoqueId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function buscarPorId($id) {
        $sql = "SELECT id, quantidade, preco 
                FROM estoques 
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $linha = $stmt->fetch(PDO::FETCH_ASSOC);
            $estoque = new Estoque(
                $linha['quantidade'],
                $linha['preco']
            );
            $estoque->setId($linha['id']);
            return $estoque;
        }

        return null;
    }

    public function atualizar(Estoque $estoque) {
        try {
            $sql = "UPDATE estoques 
                    SET quantidade = :quantidade, preco = :preco 
                    WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':quantidade' => $estoque->getQuantidade(),
                ':preco' => $estoque->getPreco(),
                ':id' => $estoque->getId()
            ]);

            return true;
        } catch (PDOException $e) {
            throw new Exception("Erro ao atualizar estoque: " . $e->getMessage());
        }
    }

    public function excluir($id) {
        try {
            $sql = "DELETE FROM estoques WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
?>
