<?php
class Notificacao {
    private $id;
    private $usuario_id_destino;
    private $usuario_id_origem;
    private $produto_id;
    private $tipo_notificacao;
    private $mensagem;
    private $link;
    private $lida;
    private $data_criacao;

    // Construtor
    public function __construct(
        $usuario_id_destino,
        $tipo_notificacao,
        $mensagem,
        $usuario_id_origem = null,
        $produto_id = null,
        $link = null
    ) {
        $this->usuario_id_destino = $usuario_id_destino;
        $this->usuario_id_origem = $usuario_id_origem;
        $this->produto_id = $produto_id;
        $this->tipo_notificacao = $tipo_notificacao;
        $this->mensagem = $mensagem;
        $this->link = $link;
        $this->lida = false; 

    }

    // Getters
    public function getId() { return $this->id; }
    public function getUsuarioIdDestino() { return $this->usuario_id_destino; }
    public function getUsuarioIdOrigem() { return $this->usuario_id_origem; }
    public function getProdutoId() { return $this->produto_id; }
    public function getTipoNotificacao() { return $this->tipo_notificacao; }
    public function getMensagem() { return $this->mensagem; }
    public function getLink() { return $this->link; }
    public function isLida() { return $this->lida; }
    public function getDataCriacao() { return $this->data_criacao; }

    public function setId($id) {
        $this->id = $id;
    }

    public function setLida($lida) {
        $this->lida = (bool)$lida;
    }
    
    public function setDataCriacao($data_criacao) {
        $this->data_criacao = $data_criacao;
    }

    public function setUsuarioIdDestino($usuario_id_destino) {
        $this->usuario_id_destino = $usuario_id_destino;
    }

    public function setUsuarioIdOrigem($usuario_id_origem) {
        $this->usuario_id_origem = $usuario_id_origem;
    }

    public function setProdutoId($produto_id) {
        $this->produto_id = $produto_id;
    }

    public function setTipoNotificacao($tipo_notificacao) {
        $this->tipo_notificacao = $tipo_notificacao;
    }

    public function setMensagem($mensagem) {
        $this->mensagem = $mensagem;
    }

    public function setLink($link) {
        $this->link = $link;
    }
} 