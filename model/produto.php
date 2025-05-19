<?php
class Produto {
    private $id;
    private $nome;
    private $descricao;
    private $foto;
    private $fornecedor_id;
    private $estoque_id;
    private $usuario_id;
    private $quantidade;
    private $preco;
    public $fornecedor_nome; 

    public function __construct($nome, $descricao, $foto, $fornecedor_id, $usuario_id) {
        $this->setNome($nome);
        $this->setDescricao($descricao);
        $this->setFoto($foto);
        $this->setFornecedorId($fornecedor_id);
        $this->setUsuarioId($usuario_id);
    }

    public function getId() { return $this->id; }
    
    public function setId($id) { 
        if (!is_numeric($id) || $id < 0) {
            throw new Exception("ID inválido");
        }
        $this->id = (int)$id; 
    }

    public function getNome() { return $this->nome; }
    
    public function setNome($nome) { 
        if (empty($nome)) {
            throw new Exception("Nome é obrigatório");
        }
        $this->nome = $nome; 
    }

    public function getDescricao() { return $this->descricao; }
    
    public function setDescricao($descricao) { 
        $this->descricao = $descricao; 
    }

    public function getFoto() { return $this->foto; }
    
    public function setFoto($foto) { 
        $this->foto = $foto; 
    }

    public function getFornecedorId() { return $this->fornecedor_id; }
    
    public function setFornecedorId($fornecedor_id) { 
        if (!is_numeric($fornecedor_id) || $fornecedor_id < 0) {
            throw new Exception("ID do fornecedor inválido");
        }
        $this->fornecedor_id = (int)$fornecedor_id; 
    }

    public function getEstoqueId() { return $this->estoque_id; }
    
    public function setEstoqueId($estoque_id) { 
        if (!is_numeric($estoque_id) || $estoque_id < 0) {
            throw new Exception("ID do estoque inválido");
        }
        $this->estoque_id = (int)$estoque_id; 
    }

    public function getUsuarioId() { return $this->usuario_id; }
    
    public function setUsuarioId($usuario_id) { 
        if (!is_numeric($usuario_id) || $usuario_id < 0) {
            throw new Exception("ID do usuário inválido");
        }
        $this->usuario_id = (int)$usuario_id; 
    }

    public function getQuantidade() { return $this->quantidade; }
    
    public function setQuantidade($quantidade) { 
        if (!is_numeric($quantidade) || $quantidade < 0) {
            throw new Exception("Quantidade inválida");
        }
        $this->quantidade = (int)$quantidade; 
    }

    public function getPreco() { return $this->preco; }
    
    public function setPreco($preco) { 
        if (!is_numeric($preco) || $preco < 0) {
            throw new Exception("Preço inválido");
        }
        $this->preco = (float)$preco; 
    }

    public function getFornecedorNome() {
        return $this->fornecedor_nome ?? 'Sem fornecedor';
    }
}
?>