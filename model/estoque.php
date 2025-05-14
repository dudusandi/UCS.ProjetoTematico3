<?php

class Estoque {
    private $quantidade;
    private $preco;
    private $produto;
    
    public function __construct($quantidade, $preco, $produto) {
        $this->quantidade = $quantidade;
        $this->preco = $preco;
        $this->produto = $produto;
    }
    
    // Getters
    public function getQuantidade() {
        return $this->quantidade;
    }
    
    public function getPreco() {
        return $this->preco;
    }
    
    public function getProduto() {
        return $this->produto;
    }
    
    // Setters
    public function setQuantidade($quantidade) {
        $this->quantidade = $quantidade;
    }
    
    public function setPreco($preco) {
        $this->preco = $preco;
    }
    
    public function setProduto($produto) {
        $this->produto = $produto;
    }
    
    // Métodos adicionais
    public function adicionarQuantidade($quantidade) {
        $this->quantidade += $quantidade;
    }
    
    public function removerQuantidade($quantidade) {
        if ($this->quantidade >= $quantidade) {
            $this->quantidade -= $quantidade;
            return true;
        }
        return false;
    }
    
    public function verificarDisponibilidade($quantidade) {
        return $this->quantidade >= $quantidade;
    }
}

?>