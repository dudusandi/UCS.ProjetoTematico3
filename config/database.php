<?php
class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            $host = '192.168.1.55';
            $dbname = 'web2';
            $user = 'postgres';
            $pass = 'dsds';

            try {
                self::$pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                throw new Exception("Erro de conexão: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}

/* 
Tabelas SQL

CREATE TABLE produtos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    foto VARCHAR(255),
    fornecedor_id INTEGER NOT NULL,
    estoque_id INTEGER NOT NULL,
    usuario_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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

