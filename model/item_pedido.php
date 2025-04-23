<?php

class ItemPedido {
    private $quantidade;
    private $preco;
    private $produto;
    private $pedido;
    
    public function __construct($quantidade, $preco, $produto, $pedido) {
        $this->quantidade = $quantidade;
        $this->preco = $preco;
        $this->produto = $produto;
        $this->pedido = $pedido;
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
    
    public function getPedido() {
        return $this->pedido;
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
    
    public function setPedido($pedido) {
        $this->pedido = $pedido;
    }
    
    // Método para calcular o valor total do item
    public function calcularTotal() {
        return $this->quantidade * $this->preco;
    }
}
?>