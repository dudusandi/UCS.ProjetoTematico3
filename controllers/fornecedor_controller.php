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

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    //Cadastra um novo fornecedor
    public function cadastrarFornecedor($nome, $descricao, $telefone, $email, $rua, $numero, $complemento, $bairro, $cep, $cidade, $estado) {
        try {
            if (empty($nome) || empty($email)) {
                throw new Exception("Campos obrigatórios não preenchidos.");
            }

            $endereco = new Endereco($rua, $numero, $bairro, $cep, $cidade, $estado, $complemento);
            $fornecedor = new Fornecedor($nome, $descricao, $telefone, $email, $endereco);
            $this->fornecedorDAO->cadastrarFornecedor($fornecedor);
            
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'mensagem' => 'Cadastro realizado com sucesso']);
                exit;
            }
            header('Location: ../view/cadastro_fornecedor.php?mensagem=Cadastro realizado com sucesso&tipo_mensagem=success');
            exit;
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'erro' => $e->getMessage()]);
                exit;
            }
            header('Location: ../view/cadastro_fornecedor.php?mensagem=Erro ao cadastrar: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
            exit;
        }
    }

    public function listarFornecedores() {
        try {
            $fornecedores = $this->fornecedorDAO->listarTodos();
            return $fornecedores;
        } catch (Exception $e) {
            return [];
        }
    }

    public function editarFornecedor($id, $nome, $descricao, $telefone, $email, $rua, $numero, $complemento, $bairro, $cep, $cidade, $estado) {
        try {
            if (empty($id) || empty($nome) || empty($email)) {
                throw new Exception("Campos obrigatórios não preenchidos para edição.");
            }

            $fornecedorExistente = $this->fornecedorDAO->buscarPorId($id);
            if ($fornecedorExistente) {
                $endereco = new Endereco($rua, $numero, $bairro, $cep, $cidade, $estado, $complemento);
                $fornecedorExistente->setNome($nome);
                $fornecedorExistente->setDescricao($descricao);
                $fornecedorExistente->setTelefone($telefone);
                $fornecedorExistente->setEmail($email);
                $fornecedorExistente->setEndereco($endereco);
                $this->fornecedorDAO->atualizarFornecedor($fornecedorExistente);

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'mensagem' => 'Edição realizada com sucesso']);
                    exit;
                }
                header('Location: ../view/listar_fornecedor.php?mensagem=Edição realizada com sucesso&tipo_mensagem=success');
                exit;
            } else {
                 if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    http_response_code(404);
                    echo json_encode(['success' => false, 'erro' => 'Fornecedor não encontrado']);
                    exit;
                }
                header('Location: ../view/listar_fornecedor.php?mensagem=Fornecedor não encontrado&tipo_mensagem=erro');
                exit;
            }
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'erro' => $e->getMessage()]);
                exit;
            }
            header('Location: ../view/listar_fornecedor.php?mensagem=Erro ao editar: ' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new FornecedorController();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cadastrar') {
        $controller->cadastrarFornecedor(
            $_POST['nome'] ?? '', $_POST['descricao'] ?? '', $_POST['telefone'] ?? '', 
            $_POST['email'] ?? '', $_POST['rua'] ?? '', $_POST['numero'] ?? '', 
            $_POST['complemento'] ?? '', $_POST['bairro'] ?? '', $_POST['cep'] ?? '', 
            $_POST['cidade'] ?? '', $_POST['estado'] ?? ''
        );
    } elseif ($acao === 'editar') {
        $controller->editarFornecedor(
            $_POST['id'] ?? 0, $_POST['nome'] ?? '', $_POST['descricao'] ?? '', 
            $_POST['telefone'] ?? '', $_POST['email'] ?? '', $_POST['rua'] ?? '', 
            $_POST['numero'] ?? '', $_POST['complemento'] ?? '', $_POST['bairro'] ?? '', 
            $_POST['cep'] ?? '', $_POST['cidade'] ?? '', $_POST['estado'] ?? ''
        );
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'erro' => 'Ação POST desconhecida.']);
            exit;
        }
        header('Location: ../view/dashboard.php?mensagem=Ação POST desconhecida&tipo_mensagem=erro');
        exit;
    }
}
?>