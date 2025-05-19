<?php
class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            $host = '192.168.1.55';
            $dbname = 'pt3';
            $user = 'postgres';
            $pass = 'dsds';

            try {
                // Configurar opções do PDO
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true
                ];
                
                error_log("Tentando conexão com o banco de dados: host=$host, dbname=$dbname, user=$user");
                self::$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass, $options);
                error_log("Conexão com o banco de dados estabelecida com sucesso");
            } catch (PDOException $e) {
                error_log("ERRO DE CONEXÃO COM O BANCO DE DADOS: " . $e->getMessage());
                throw new Exception("Erro de conexão: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
    
    /**
     * Testa a conexão com o banco de dados e verifica tabelas específicas
     */
    public static function testConnection($tablesToCheck = ['pedidos', 'itens_pedido']) {
        try {
            $pdo = self::getConnection();
            $result = ['success' => true, 'tables' => []];
            
            // Verificar se o servidor está respondendo
            $pdo->query("SELECT 1");
            
            // Verificar tabelas específicas
            foreach ($tablesToCheck as $table) {
                try {
                    $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
                    $result['tables'][$table] = true;
                } catch (PDOException $e) {
                    $result['tables'][$table] = false;
                    $result['success'] = false;
                }
            }
            
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false, 
                'error' => $e->getMessage()
            ];
        }
    }
}

/* 
Tabelas SQL

CREATE TABLE produtos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    foto BYTEA,
    fornecedor_id INTEGER NOT NULL,
    estoque_id INTEGER NOT NULL,
    usuario_id INTEGER NOT NULL,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id),
    FOREIGN KEY (estoque_id) REFERENCES estoques(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);


CREATE TABLE enderecos (
    id SERIAL PRIMARY KEY,
    rua VARCHAR(255) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(100),
    bairro VARCHAR(100) NOT NULL,
    cep VARCHAR(10) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    estado VARCHAR(2) NOT NULL
);

CREATE TABLE estoques (
    id SERIAL PRIMARY KEY,
    quantidade INTEGER NOT NULL CHECK (quantidade >= 0),
    preco DECIMAL(10, 2) NOT NULL CHECK (preco >= 0),
);

CREATE TABLE clientes (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    email VARCHAR(255) NOT NULL UNIQUE,
    cartao_credito VARCHAR(50),
    endereco_id INTEGER NOT NULL,
    senha VARCHAR(255) NOT NULL,
    FOREIGN KEY (endereco_id) REFERENCES enderecos(id) ON DELETE RESTRICT
);
*/




?>

