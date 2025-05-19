<?php
define('BASE_PATH', realpath(dirname(__DIR__)));

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/model/cliente.php';
require_once BASE_PATH . '/model/endereco.php';
require_once BASE_PATH . '/dao/cliente_dao.php';

class ClienteController {
    private $clienteDAO;

    public function __construct() {
        $this->clienteDAO = new ClienteDAO(Database::getConnection());
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    public function cadastrarCliente($nome, $email, $senha, $telefone, $cartaoCredito, $rua, $numero, $complemento, $bairro, $cep, $cidade, $estado) {
        try {
            $camposObrigatorios = [
                'nome' => $nome, 'email' => $email, 'senha' => $senha, 'rua' => $rua,
                'numero' => $numero, 'bairro' => $bairro, 'cep' => $cep,
                'cidade' => $cidade, 'estado' => $estado
            ];
            foreach ($camposObrigatorios as $campo => $valor) {
                if (empty($valor)) {
                    throw new Exception("O campo $campo é obrigatório.");
                }
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido.');
            }
            if ($this->clienteDAO->emailExiste($email)) {
                throw new Exception('Email já cadastrado.');
            }

            $endereco = new Endereco($rua, $numero, $bairro, $cep, $cidade, $estado, $complemento);
            $cliente = new Cliente($nome, $telefone, $email, $cartaoCredito, $endereco);
            $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

            $resultado = $this->clienteDAO->cadastrarCliente($cliente, $senhaHash);

            if ($resultado) {
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'mensagem' => 'Cadastro realizado com sucesso']);
                    exit;
                }
                header('Location: ../view/listar_clientes.php?mensagem=Cadastro realizado com sucesso&tipo_mensagem=success');
                exit;
            } else {
                throw new Exception('Não foi possível completar o cadastro.');
            }
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'erro' => $e->getMessage()]);
                exit;
            }
            header('Location: ../view/cadastro_cliente.php?mensagem=' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
            exit;
        }
    }

    public function listarClientes() {
        try {
            $clientes = $this->clienteDAO->listarTodos();
            return $clientes;
        } catch (Exception $e) {
            return [];
        }
    }

    public function editarCliente($id, $nome, $telefone, $email, $cartaoCredito, $rua, $numero, $complemento, $bairro, $cep, $cidade, $estado) {
        try {
             $camposObrigatorios = [
                'nome' => $nome, 'telefone' => $telefone, 'email' => $email,
                'cartaoCredito' => $cartaoCredito, 'rua' => $rua, 'numero' => $numero,
                'bairro' => $bairro, 'cep' => $cep, 'cidade' => $cidade, 'estado' => $estado
            ];
            foreach ($camposObrigatorios as $campo => $valor) {
                if (empty($valor)) {
                    throw new Exception("O campo $campo é obrigatório.");
                }
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido.');
            }
            if ($this->clienteDAO->emailExiste($email, $id)) {
                throw new Exception('Email já cadastrado');
            }

            $clienteExistente = $this->clienteDAO->buscarPorId($id);
            if ($clienteExistente) {
                $endereco = new Endereco($rua, $numero, $bairro, $cep, $cidade, $estado, $complemento);
                $clienteExistente->setNome($nome);
                $clienteExistente->setTelefone($telefone);
                $clienteExistente->setEmail($email);
                $clienteExistente->setCartaoCredito($cartaoCredito);
                $clienteExistente->setEndereco($endereco);
                $this->clienteDAO->atualizarCliente($clienteExistente);

                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'mensagem' => 'Edição realizada com sucesso']);
                    exit;
                }
                header('Location: ../view/listar_clientes.php?mensagem=Edição realizada com sucesso&tipo_mensagem=success');
                exit;
            } else {
                throw new Exception('Cliente não encontrado.');
            }
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'erro' => $e->getMessage()]);
                exit;
            }
            header('Location: ../view/editar_cliente.php?id=' . $id . '&mensagem=' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
            exit;
        }
    }

    public function buscarClientePorId($id) {
        try {
            $cliente = $this->clienteDAO->buscarPorId($id);
            return $cliente ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function excluirCliente($id) {
        try {
            if (empty($id)) {
                throw new Exception('ID do cliente não fornecido.');
            }
            $clienteExistente = $this->clienteDAO->buscarPorId($id);
            if (!$clienteExistente) {
                 throw new Exception('Cliente não encontrado.');
            }

            $this->clienteDAO->removerCliente($id);

            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'mensagem' => 'Cliente removido com sucesso']);
                exit;
            }
            header('Location: ../view/listar_clientes.php?mensagem=Cliente+removido+com+sucesso&tipo_mensagem=success');
            exit;
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'erro' => $e->getMessage()]);
                exit;
            }
            header('Location: ../view/listar_clientes.php?mensagem=' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new ClienteController();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'cadastrar') {
        $controller->cadastrarCliente(
            $_POST['nome'] ?? '', $_POST['email'] ?? '', $_POST['senha'] ?? '',
            $_POST['telefone'] ?? '', $_POST['cartao_credito'] ?? '',
            $_POST['rua'] ?? '', $_POST['numero'] ?? '', $_POST['complemento'] ?? '',
            $_POST['bairro'] ?? '', $_POST['cep'] ?? '', $_POST['cidade'] ?? '',
            $_POST['estado'] ?? ''
        );
    } elseif ($acao === 'editar') {
        $controller->editarCliente(
            $_POST['id'] ?? 0, $_POST['nome'] ?? '', $_POST['telefone'] ?? '',
            $_POST['email'] ?? '', $_POST['cartao_credito'] ?? '',
            $_POST['rua'] ?? '', $_POST['numero'] ?? '', $_POST['complemento'] ?? '',
            $_POST['bairro'] ?? '', $_POST['cep'] ?? '', $_POST['cidade'] ?? '',
            $_POST['estado'] ?? ''
        );
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'erro' => 'Ação POST desconhecida.']);
            exit;
        }
        header('Location: ../view/dashboard.php?mensagem=Ação desconhecida&tipo_mensagem=erro');
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    $controller = new ClienteController();
    if ($_GET['acao'] === 'excluir' && isset($_GET['id'])) {
        $controller->excluirCliente($_GET['id'] ?? 0);
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'erro' => 'Ação GET desconhecida.']);
            exit;
        }
        header('Location: ../view/dashboard.php?mensagem=Ação GET desconhecida&tipo_mensagem=erro');
        exit;
    }
}