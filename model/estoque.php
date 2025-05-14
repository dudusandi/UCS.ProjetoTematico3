<?php

class Estoque {
    private $id;
    private $quantidade;
    private $preco;

    public function __construct($quantidade, $preco) {
        $this->quantidade = $quantidade;
        $this->preco = $preco;
    }

    public function getId() {
        return $this->id;
    }

    public function getQuantidade() {
        return $this->quantidade;
    }

    public function getPreco() {
        return $this->preco;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setQuantidade($quantidade) {
        $this->quantidade = $quantidade;
    }

    public function setPreco($preco) {
        $this->preco = $preco;
    }
}

?>