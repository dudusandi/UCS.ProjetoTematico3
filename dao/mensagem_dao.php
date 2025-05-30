<?php
require_once __DIR__ . '/../model/mensagem.php';
require_once __DIR__ . '/../config/database.php'; 

class MensagemDAO {
    private $pdo; 

    public function __construct() {
        $this->pdo = Database::getConnection(); 
    }

    public function enviarMensagem(Mensagem $mensagem) {
        $query = "INSERT INTO mensagens (remetente_id, destinatario_id, conteudo) VALUES (?, ?, ?)";
        try {
            $stmt = $this->pdo->prepare($query);
            $result = $stmt->execute([
                $mensagem->getRemetenteId(),
                $mensagem->getDestinatarioId(),
                $mensagem->getConteudo()
            ]);
            return (bool)$result;
        } catch (PDOException $e) {

            return false;
        }
    }


    public function buscarConversa($usuario1_id, $usuario2_id) {
        $mensagens = [];
        $query = "SELECT * FROM mensagens 
                  WHERE (remetente_id = ? AND destinatario_id = ?) 
                     OR (remetente_id = ? AND destinatario_id = ?) 
                  ORDER BY data_envio ASC";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$usuario1_id, $usuario2_id, $usuario2_id, $usuario1_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $mensagens[] = new Mensagem(
                    $row['id'],
                    $row['remetente_id'],
                    $row['destinatario_id'],
                    $row['conteudo'],
                    $row['data_envio'],
                    (bool)$row['lida'] 
                );
            }
        } catch (PDOException $e) {
        }
        return $mensagens;
    }


    public function buscarUltimasConversas($usuario_id) {
        $conversas = [];
        $query = "SELECT DISTINCT ON (GREATEST(remetente_id, destinatario_id), LEAST(remetente_id, destinatario_id)) 
                         id, remetente_id, destinatario_id, conteudo, data_envio, lida 
                  FROM mensagens 
                  WHERE remetente_id = ? OR destinatario_id = ? 
                  ORDER BY GREATEST(remetente_id, destinatario_id), LEAST(remetente_id, destinatario_id), data_envio DESC";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$usuario_id, $usuario_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $conversas[] = new Mensagem(
                    $row['id'],
                    $row['remetente_id'],
                    $row['destinatario_id'],
                    $row['conteudo'],
                    $row['data_envio'],
                    (bool)$row['lida']
                );
            }
            usort($conversas, function($a, $b) {
                return strtotime($b->getDataEnvio()) - strtotime($a->getDataEnvio());
            });
        } catch (PDOException $e) {
        }
        return $conversas;
    }


    public function marcarMensagensComoLidas($destinatario_id, $remetente_id) {
        $query = "UPDATE mensagens SET lida = TRUE 
                  WHERE destinatario_id = ? AND remetente_id = ? AND lida = FALSE";
        try {
            $stmt = $this->pdo->prepare($query);
            $result = $stmt->execute([$destinatario_id, $remetente_id]);
            return (bool)$result;
        } catch (PDOException $e) {
            return false;
        }
    }


    public function contarMensagensNaoLidas($usuario_id) {
        $query = "SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND lida = FALSE";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$usuario_id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function buscarNovasMensagens($usuario1_id, $usuario2_id, $ultimo_id_conhecido) {
        $mensagens = [];
        $query = "SELECT * FROM mensagens 
                  WHERE ((remetente_id = :u1 AND destinatario_id = :u2) 
                     OR (remetente_id = :u2 AND destinatario_id = :u1)) 
                    AND id > :ultimo_id
                  ORDER BY data_envio ASC";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':u1', $usuario1_id, PDO::PARAM_INT);
            $stmt->bindParam(':u2', $usuario2_id, PDO::PARAM_INT);
            $stmt->bindParam(':ultimo_id', $ultimo_id_conhecido, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $mensagens[] = new Mensagem(
                    $row['id'],
                    $row['remetente_id'],
                    $row['destinatario_id'],
                    $row['conteudo'],
                    $row['data_envio'],
                    (bool)$row['lida'] 
                );
            }
        } catch (PDOException $e) {
            // Logar o erro seria uma boa prática aqui
            // error_log("Erro em buscarNovasMensagens: " . $e->getMessage());
        }
        return $mensagens;
    }

}
?> 