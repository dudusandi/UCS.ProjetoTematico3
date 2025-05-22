<?php

class Mensagem {
    private $id;
    private $remetente_id;
    private $destinatario_id;
    private $conteudo;
    private $data_envio;
    private $lida;

    // Construtor
    public function __construct($id = null, $remetente_id = null, $destinatario_id = null, $conteudo = null, $data_envio = null, $lida = false) {
        $this->id = $id;
        $this->remetente_id = $remetente_id;
        $this->destinatario_id = $destinatario_id;
        $this->conteudo = $conteudo;
        $this->data_envio = $data_envio;
        $this->lida = $lida;
    }

    // Getters
    public function getId() {
        return $this->id;
    }

    public function getRemetenteId() {
        return $this->remetente_id;
    }

    public function getDestinatarioId() {
        return $this->destinatario_id;
    }

    public function getConteudo() {
        return $this->conteudo;
    }

    public function getDataEnvio() {
        return $this->data_envio;
    }

    public function isLida() {
        return $this->lida;
    }

    // Setters
    public function setId($id) {
        $this->id = $id;
    }

    public function setRemetenteId($remetente_id) {
        $this->remetente_id = $remetente_id;
    }

    public function setDestinatarioId($destinatario_id) {
        $this->destinatario_id = $destinatario_id;
    }

    public function setConteudo($conteudo) {
        $this->conteudo = $conteudo;
    }

    public function setDataEnvio($data_envio) {
        $this->data_envio = $data_envio;
    }

    public function setLida($lida) {
        $this->lida = $lida;
    }
}

?> 