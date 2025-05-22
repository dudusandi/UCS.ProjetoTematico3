<?php
require_once __DIR__ . '/../model/mensagem.php';
require_once __DIR__ . '/../config/database.php'; // Adicionado para usar a classe Database com PDO

class MensagemDAO {
    private $pdo; // Alterado de $conn para $pdo

    public function __construct() {
        $this->pdo = Database::getConnection(); // Obtém a conexão PDO
    }

    /**
     * Envia (insere) uma nova mensagem no banco de dados.
     * @param Mensagem $mensagem O objeto Mensagem a ser salvo.
     * @return bool Retorna true se a mensagem foi salva com sucesso, false caso contrário.
     */
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
            // Logar erro ou tratar de forma apropriada
            // error_log("Erro ao enviar mensagem: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca todas as mensagens entre dois usuários.
     * @param int $usuario1_id ID do primeiro usuário.
     * @param int $usuario2_id ID do segundo usuário.
     * @return array Retorna um array de objetos Mensagem.
     */
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
                    (bool)$row['lida'] // PDO com PostgreSQL geralmente retorna booleano diretamente
                );
            }
        } catch (PDOException $e) {
            // error_log("Erro ao buscar conversa: " . $e->getMessage());
        }
        return $mensagens;
    }

    /**
     * Busca as últimas conversas de um usuário (uma mensagem por conversa).
     * Útil para listar chats recentes.
     * @param int $usuario_id ID do usuário.
     * @return array Retorna um array de objetos Mensagem representando a última mensagem de cada conversa.
     */
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
            // Reordenar para que as conversas mais recentes apareçam primeiro na lista geral
            usort($conversas, function($a, $b) {
                return strtotime($b->getDataEnvio()) - strtotime($a->getDataEnvio());
            });
        } catch (PDOException $e) {
            // error_log("Erro ao buscar últimas conversas: " . $e->getMessage());
        }
        return $conversas;
    }

    /**
     * Marca mensagens como lidas.
     * @param int $destinatario_id O ID do destinatário (usuário logado).
     * @param int $remetente_id O ID do remetente da conversa.
     * @return bool Retorna true se a atualização foi bem sucedida, false caso contrário.
     */
    public function marcarMensagensComoLidas($destinatario_id, $remetente_id) {
        $query = "UPDATE mensagens SET lida = TRUE 
                  WHERE destinatario_id = ? AND remetente_id = ? AND lida = FALSE";
        try {
            $stmt = $this->pdo->prepare($query);
            $result = $stmt->execute([$destinatario_id, $remetente_id]);
            return (bool)$result;
        } catch (PDOException $e) {
            // error_log("Erro ao marcar mensagens como lidas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Conta mensagens não lidas para um usuário.
     * @param int $usuario_id O ID do usuário.
     * @return int O número de mensagens não lidas.
     */
    public function contarMensagensNaoLidas($usuario_id) {
        $query = "SELECT COUNT(*) FROM mensagens WHERE destinatario_id = ? AND lida = FALSE";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$usuario_id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            // error_log("Erro ao contar mensagens não lidas: " . $e->getMessage());
            return 0;
        }
    }

}
?> 