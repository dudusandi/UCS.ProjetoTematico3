<?php
class Produto {
    private $id;
    private $nome;
    private $descricao;
    private $foto;
    private $usuario_id;
    private $preco;

    public function __construct($nome, $descricao, $foto_param, $preco, $usuario_id) {
        $this->nome = $nome;
        $this->descricao = $descricao;
        $this->foto = $foto_param;
        $this->setPreco($preco);
        $this->setUsuarioId($usuario_id);
    }

    public function getId() { return $this->id; }
    
    public function setId($id) { 
        $this->id = $id; 
    }

    public function getNome() { return $this->nome; }
    
    public function setNome($nome) { 
        $this->nome = $nome; 
    }

    public function getDescricao() { return $this->descricao; }
    
    public function setDescricao($descricao) { 
        $this->descricao = $descricao; 
    }

    public function getFoto() { return $this->foto; }
    
    public function setFoto($foto_param) { 
        $this->foto = $foto_param; 
    }

    public function getPreco() { return $this->preco; }
    
    public function setPreco($preco) { 
        if (!is_numeric($preco) || $preco < 0) {
            $this->preco = 0.0;
            return;
        }
        $this->preco = (float)$preco; 
    }

    public function getUsuarioId() { return $this->usuario_id; }
    
    public function setUsuarioId($usuario_id) { 
        if (!is_numeric($usuario_id) || $usuario_id <= 0) {
            $this->usuario_id = null;
            return;
        }
        $this->usuario_id = (int)$usuario_id; 
    }
}
?>