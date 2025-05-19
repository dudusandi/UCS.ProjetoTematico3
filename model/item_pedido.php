<?php

class ItemPedido {
    private $quantidade;
    private $preco_unitario;
    private $produto;
    private $pedido;
    private $subtotal;
    
    public function __construct($quantidade, $preco_unitario, $produto, $pedido, $subtotal = null) {
        $this->quantidade = (int)$quantidade;
        $this->preco_unitario = (float)$preco_unitario;
        $this->produto = $produto;
        $this->pedido = $pedido;
        
        if ($subtotal !== null) {
            $this->subtotal = (float)$subtotal;
        } else {
            $this->subtotal = $this->quantidade * $this->preco_unitario;
        }
    }
    
    public function getQuantidade() {
        return $this->quantidade;
    }
    
    public function getPrecoUnitario() {
        return $this->preco_unitario;
    }
    
    public function getProduto() {
        return $this->produto;
    }
    
    public function getPedido() {
        return $this->pedido;
    }
    
    public function getSubtotal() {
        return $this->subtotal;
    }
    
    public function setQuantidade($quantidade) {
        $this->quantidade = (int)$quantidade;
        $this->subtotal = $this->quantidade * $this->preco_unitario;
    }
    
    public function setPrecoUnitario($preco_unitario) {
        $this->preco_unitario = (float)$preco_unitario;
        $this->subtotal = $this->quantidade * $this->preco_unitario;
    }
    
    public function setProduto($produto) {
        $this->produto = $produto;
    }
    
    public function setPedido($pedido) {
        $this->pedido = $pedido;
    }
    
}
?>