<?php
require_once '../model/fornecedor.php';
require_once '../model/endereco.php';
require_once '../dao/fornecedor_dao.php';
require_once '../config/database.php'; 

class FornecedorController {
    private $fornecedorDAO;

    public function __construct() {

        $this->fornecedorDAO = new FornecedorDAO(Database::getConnection());
    }


    //Cadastra um novo fornecedor
    public function cadastrarFornecedor($nome, $descricao, $telefone, $email, $rua, $numero, $complemento, $bairro, $cep, $cidade, $estado) {
        try {
            $endereco = new Endereco($rua, $numero, $bairro, $cep, $cidade, $estado, $complemento);
            $fornecedor = new Fornecedor($nome, $descricao, $telefone, $email, $endereco);
            $this->fornecedorDAO->cadastrarFornecedor($fornecedor);
            header('Location: ../view/cadastro_fornecedor.php?mensagem=Cadastro realizado com sucesso');
            exit;
        } catch (Exception $e) {
            header('Location: ../view/cadastro_fornecedor.php?mensagem=Erro ao cadastrar: ' . urlencode($e->getMessage()));
            exit;
        }
    }


    // Lista todos os fornecedores
    public function listarFornecedores() {
        try {
            $fornecedores = $this->fornecedorDAO->listarTodos();
            return $fornecedores;
        } catch (Exception $e) {
            return [];
        }
    }


    // Edita um fornecedor existente
    public function editarFornecedor($id, $nome, $descricao, $telefone, $email, $rua, $numero, $complemento, $bairro, $cep, $cidade, $estado) {
        try {
            $fornecedorExistente = $this->fornecedorDAO->buscarPorId($id);
            if ($fornecedorExistente) {
                $endereco = new Endereco($rua, $numero, $bairro, $cep, $cidade, $estado, $complemento);
                $fornecedorExistente->setNome($nome);
                $fornecedorExistente->setDescricao($descricao);
                $fornecedorExistente->setTelefone($telefone);
                $fornecedorExistente->setEmail($email);
                $fornecedorExistente->setEndereco($endereco);
                $this->fornecedorDAO->atualizarFornecedor($fornecedorExistente);
                header('Location: ../view/listar_fornecedor.php?mensagem=Edição realizada com sucesso');
                exit;
            } else {
                header('Location: ../view/listar_fornecedor.php?mensagem=Fornecedor não encontrado');
                exit;
            }
        } catch (Exception $e) {
            header('Location: ../view/listar_fornecedor.php?mensagem=Erro ao editar: ' . urlencode($e->getMessage()));
            exit;
        }
    }
}

// Recebe todos os dados com POST e envia os dados para o banco de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new FornecedorController();
    if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
        $controller->cadastrarFornecedor(
            $_POST['nome'],
            $_POST['descricao'],
            $_POST['telefone'],
            $_POST['email'],
            $_POST['rua'],
            $_POST['numero'],
            $_POST['complemento'],
            $_POST['bairro'],
            $_POST['cep'],
            $_POST['cidade'],
            $_POST['estado']
        );
    } elseif (isset($_POST['acao']) && $_POST['acao'] === 'editar') {
        $controller->editarFornecedor(
            $_POST['id'],
            $_POST['nome'],
            $_POST['descricao'],
            $_POST['telefone'],
            $_POST['email'],
            $_POST['rua'],
            $_POST['numero'],
            $_POST['complemento'],
            $_POST['bairro'],
            $_POST['cep'],
            $_POST['cidade'],
            $_POST['estado']
        );
    }
}
?>