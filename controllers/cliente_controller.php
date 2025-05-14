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

    public function cadastrarCliente($nome, $email, $senha, $telefone, $cartaoCredito, $rua, $numero, $complemento, $bairro, $cep, $cidade, $estado) {
        try {
            // Validação básica
            $camposObrigatorios = [
                'nome' => $nome,
                'email' => $email,
                'senha' => $senha,
                'rua' => $rua,
                'numero' => $numero,
                'bairro' => $bairro,
                'cep' => $cep,
                'cidade' => $cidade,
                'estado' => $estado
            ];

            foreach ($camposObrigatorios as $campo => $valor) {
                if (empty($valor)) {
                    throw new Exception("O campo $campo é obrigatório.");
                }
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido.');
            }

            // Verificar se o email existe
            if ($this->clienteDAO->emailExiste($email)) {
                throw new Exception('Email já cadastrado.');
            }

            // Criar objetos
            $endereco = new Endereco($rua, $numero, $bairro, $cep, $cidade, $estado, $complemento);
            $cliente = new Cliente($nome, $telefone, $email, $cartaoCredito, $endereco);
            $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

            // Cadastrar cliente
            $resultado = $this->clienteDAO->cadastrarCliente($cliente, $senhaHash);

            if ($resultado) {
                header('Location: ../view/listar_clientes.php?mensagem=Cadastro realizado com sucesso&tipo_mensagem=success');
                exit;
            } else {
                throw new Exception('Não foi possível completar o cadastro.');
            }
        } catch (Exception $e) {
            error_log("Erro no cadastro de cliente: " . $e->getMessage());
            header('Location: ../view/cadastro_cliente.php?mensagem=' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
            exit;
        }
    }

    // Listar Clientes
    public function listarClientes() {
        try {
            $clientes = $this->clienteDAO->listarTodos();
            return $clientes;
        } catch (Exception $e) {
            error_log("Erro ao listar clientes: " . $e->getMessage());
            return [];
        }
    }

    // Editar Clientes
    public function editarCliente($id, $nome, $telefone, $email, $cartaoCredito, $rua, $numero, $complemento, $bairro, $cep, $cidade, $estado) {
        try {
            // Validação básica
            $camposObrigatorios = [
                'nome' => $nome,
                'telefone' => $telefone,
                'email' => $email,
                'cartaoCredito' => $cartaoCredito,
                'rua' => $rua,
                'numero' => $numero,
                'bairro' => $bairro,
                'cep' => $cep,
                'cidade' => $cidade,
                'estado' => $estado
            ];

            foreach ($camposObrigatorios as $campo => $valor) {
                if (empty($valor)) {
                    throw new Exception("O campo $campo é obrigatório.");
                }
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email inválido.');
            }

            // Verificar se o email já existe
            if ($this->clienteDAO->emailExiste($email, $id)) {
                throw new Exception('Email já cadastrado');
            }

            // Usar método buscarPorId no clienteDAO para verificar se o cliente existe
            $clienteExistente = $this->clienteDAO->buscarPorId($id);
            if ($clienteExistente) {
                $endereco = new Endereco($rua, $numero, $bairro, $cep, $cidade, $estado, $complemento);
                $clienteExistente->setNome($nome);
                $clienteExistente->setTelefone($telefone);
                $clienteExistente->setEmail($email);
                $clienteExistente->setCartaoCredito($cartaoCredito);
                $clienteExistente->setEndereco($endereco);
                $this->clienteDAO->atualizarCliente($clienteExistente);
                header('Location: ../view/listar_clientes.php?mensagem=Edição realizada com sucesso&tipo_mensagem=success');
                exit;
            } else {
                throw new Exception('Cliente não encontrado.');
            }
        } catch (Exception $e) {
            error_log("Erro ao editar cliente: " . $e->getMessage());
            header('Location: ../view/editar_cliente.php?id=' . $id . '&mensagem=' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
            exit;
        }
    }

    // Buscar clientes por ID
    public function buscarClientePorId($id) {
        try {
            $cliente = $this->clienteDAO->buscarPorId($id);
            return $cliente ?: null;
        } catch (Exception $e) {
            error_log("Erro ao buscar cliente por ID: " . $e->getMessage());
            return null;
        }
    }

    // Excluir Cliente
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
            header('Location: ../view/listar_clientes.php?mensagem=Cliente+removido+com+sucesso&tipo_mensagem=success');
            exit;
        } catch (Exception $e) {
            error_log("Erro ao excluir cliente: " . $e->getMessage());
            header('Location: ../view/listar_clientes.php?mensagem=' . urlencode($e->getMessage()) . '&tipo_mensagem=erro');
            exit;
        }
    }
}

// Recebe todos os dados com POST e envia os dados para o banco de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new ClienteController();
    if (isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
        $controller->cadastrarCliente(
            $_POST['nome'],
            $_POST['email'],
            $_POST['senha'],
            $_POST['telefone'],
            $_POST['cartao_credito'],
            $_POST['rua'],
            $_POST['numero'],
            $_POST['complemento'],
            $_POST['bairro'],
            $_POST['cep'],
            $_POST['cidade'],
            $_POST['estado']
        );
    } elseif (isset($_POST['acao']) && $_POST['acao'] === 'editar') {
        $controller->editarCliente(
            $_POST['id'],
            $_POST['nome'],
            $_POST['telefone'],
            $_POST['email'],
            $_POST['cartao_credito'],
            $_POST['rua'],
            $_POST['numero'],
            $_POST['complemento'],
            $_POST['bairro'],
            $_POST['cep'],
            $_POST['cidade'],
            $_POST['estado']
        );
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    $controller = new ClienteController();
    if ($_GET['acao'] === 'excluir' && isset($_GET['id'])) {
        $controller->excluirCliente($_GET['id']);
    }
}