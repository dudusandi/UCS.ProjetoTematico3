<?php
class Pedido {
    private $id;
    private $numero;
    private $dataPedido;
    private $dataEntrega;
    private $dataEnvio;
    private $dataCancelamento;
    private $situacao; 
    private $cliente;
    private $itensPedido = array();
    private $valor_total;
    
    public function __construct($id, $numero, $dataPedido, $cliente, $valor_total = 0.0, $dataEntrega = null, $situacao = "NOVO", $dataEnvio = null, $dataCancelamento = null) {
        $this->id = (int)$id;
        $this->numero = $numero;
        $this->dataPedido = $dataPedido;
        $this->cliente = $cliente;
        $this->valor_total = (float)$valor_total;
        $this->dataEntrega = $dataEntrega;
        $this->situacao = $situacao;
        $this->dataEnvio = $dataEnvio;
        $this->dataCancelamento = $dataCancelamento;
    }
    
    public function getId() {
        return $this->id;
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
    
    public function getDataEnvio() {
        return $this->dataEnvio;
    }
    
    public function getDataCancelamento() {
        return $this->dataCancelamento;
    }
    
    public function getSituacao() {
        return $this->situacao;
    }
    
    public function getCliente() {
        return $this->cliente;
    }
    
    public function &getItensPedido() {
        return $this->itensPedido;
    }
    
    public function getValorTotal() {
        return $this->valor_total;
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
    
    public function setDataEnvio($dataEnvio) {
        $this->dataEnvio = $dataEnvio;
    }
    
    public function setDataCancelamento($dataCancelamento) {
        $this->dataCancelamento = $dataCancelamento;
    }
    
    public function setSituacao($situacao) {
        $this->situacao = $situacao;
    }
}

?>