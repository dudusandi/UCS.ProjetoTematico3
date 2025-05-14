<?php
class Pedido {
    private $numero;
    private $dataPedido;
    private $dataEntrega;
    private $situacao; 
    private $cliente;
    private $itensPedido = array();
    
    public function __construct($numero, $dataPedido, $cliente, $dataEntrega = null, $situacao = "NOVO") {
        $this->numero = $numero;
        $this->dataPedido = $dataPedido;
        $this->dataEntrega = $dataEntrega;
        $this->situacao = $situacao;
        $this->cliente = $cliente;
    }
    
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
    
    public function setNumero($numero) {
        $this->numero = $numero;
    }
    
    public function setDataPedido($dataPedido) {
        $this->dataPedido = $dataPedido;
    }
    
    public function setDataEntrega($dataEntrega) {
        $this->dataEntrega = $dataEntrega;
    }
    
  
}

?>