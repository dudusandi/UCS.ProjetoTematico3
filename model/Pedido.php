<?php
class Pedido {
    private $numero;
    private $dataPedido;
    private $dataEntrega;
    private $situacao; // NOVO, ENTREGUE ou CANCELADO
    private $cliente;
    private $itensPedido = array();
    
    public function __construct($numero, $dataPedido, $cliente, $dataEntrega = null, $situacao = "NOVO") {
        $this->numero = $numero;
        $this->dataPedido = $dataPedido;
        $this->dataEntrega = $dataEntrega;
        $this->situacao = $situacao;
        $this->cliente = $cliente;
    }
    
    // Getters
    public function getNumero() {
        return $this->numero;
    }
    
    public function getDataPedido() {
        return $this->dataPedido;
    }
    
    public function getDataEntrega() {
        return $this->dataEntrega;
    }
    
    public function getSituacao() {
        return $this->situacao;
    }
    
    public function getCliente() {
        return $this->cliente;
    }
    
    public function getItensPedido() {
        return $this->itensPedido;
    }
    
    // Setters
    public function setNumero($numero) {
        $this->numero = $numero;
    }
    
    public function setDataPedido($dataPedido) {
        $this->dataPedido = $dataPedido;
    }
    
    public function setDataEntrega($dataEntrega) {
        $this->dataEntrega = $dataEntrega;
    }
    
    public function setSituacao($situacao) {
        if (in_array($situacao, ["NOVO", "ENTREGUE", "CANCELADO"])) {
            $this->situacao = $situacao;
            return true;
        }
        return false;
    }
    
    public function setCliente($cliente) {
        $this->cliente = $cliente;
    }
    
    // Métodos para gerenciar itens do pedido
    public function adicionarItem($itemPedido) {
        $this->itensPedido[] = $itemPedido;
    }
    
    public function removerItem($itemPedido) {
        $index = array_search($itemPedido, $this->itensPedido);
        if ($index !== false) {
            unset($this->itensPedido[$index]);
        }
    }
    
    // Método para calcular o valor total do pedido
    public function calcularValorTotal() {
        $total = 0;
        foreach ($this->itensPedido as $item) {
            $total += $item->getPreco() * $item->getQuantidade();
        }
        return $total;
    }
    
    // Métodos para alterar a situação do pedido
    public function entregar() {
        $this->situacao = "ENTREGUE";
        $this->dataEntrega = date('Y-m-d');
    }
    
    public function cancelar() {
        $this->situacao = "CANCELADO";
    }
}

?>