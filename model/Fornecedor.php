<?php

class Fornecedor {
    private $nome;
    private $descricao;
    private $telefone;
    private $email;
    private $endereco;
    private $produtos = array();
    
    public function __construct($nome, $descricao, $telefone, $email, $endereco) {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->telefone = $telefone;
        $this->email = $email;
        $this->endereco = $endereco;
    }
    
    // Getters
    public function getNome() {
        return $this->nome;
    }
    
    public function getDescricao() {
        return $this->descricao;
    }
    
    public function getTelefone() {
        return $this->telefone;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    public function getEndereco() {
        return $this->endereco;
    }
    
    public function getProdutos() {
        return $this->produtos;
    }
    
    // Setters
    public function setNome($nome) {
        $this->nome = $nome;
    }
    
    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }
    
    public function setTelefone($telefone) {
        $this->telefone = $telefone;
    }
    
    public function setEmail($email) {
        $this->email = $email;
    }
    
    public function setEndereco($endereco) {
        $this->endereco = $endereco;
    }
    
    // Métodos para gerenciar produtos
    public function adicionarProduto($produto) {
        $this->produtos[] = $produto;
    }
    
    public function removerProduto($produto) {
        $index = array_search($produto, $this->produtos);
        if ($index !== false) {
            unset($this->produtos[$index]);
        }
    }
}

?>