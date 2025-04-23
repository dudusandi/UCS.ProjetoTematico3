<?php

class Produto {
    private $nome;
    private $descricao;
    private $foto;
    private $fornecedor;
    private $estoque;
    
    public function __construct($nome, $descricao, $foto, $fornecedor) {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->foto = $foto;
        $this->fornecedor = $fornecedor;
    }
    
    // Getters
    public function getNome() {
        return $this->nome;
    }
    
    public function getDescricao() {
        return $this->descricao;
    }
    
    public function getFoto() {
        return $this->foto;
    }
    
    public function getFornecedor() {
        return $this->fornecedor;
    }
    
    public function getEstoque() {
        return $this->estoque;
    }
    
    // Setters
    public function setNome($nome) {
        $this->nome = $nome;
    }
    
    public function setDescricao($descricao) {
        $this->descricao = $descricao;
    }
    
    public function setFoto($foto) {
        $this->foto = $foto;
    }
    
    public function setFornecedor($fornecedor) {
        $this->fornecedor = $fornecedor;
    }
    
    public function setEstoque($estoque) {
        $this->estoque = $estoque;
    }
}

?>