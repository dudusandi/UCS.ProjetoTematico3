<?php
$basePath = realpath(dirname(__DIR__));
require_once "$basePath/dao/cliente_dao.php";
require_once "$basePath/model/cliente.php";
require_once "$basePath/model/endereco.php";
require_once "$basePath/config/database.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Coletar e sanitizar dados do formulário - Versão atualizada sem FILTER_SANITIZE_STRING
    $nome = trim(htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8'));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $senha = trim($_POST['senha'] ?? '');
    $telefone = trim(htmlspecialchars($_POST['telefone'] ?? '', ENT_QUOTES, 'UTF-8'));
    $cartaoCredito = trim(htmlspecialchars($_POST['cartao_credito'] ?? '', ENT_QUOTES, 'UTF-8'));
    
    // Dados de endereço - Versão atualizada
    $rua = trim(htmlspecialchars($_POST['rua'] ?? '', ENT_QUOTES, 'UTF-8'));
    $numero = trim(htmlspecialchars($_POST['numero'] ?? '', ENT_QUOTES, 'UTF-8'));
    $complemento = trim(htmlspecialchars($_POST['complemento'] ?? '', ENT_QUOTES, 'UTF-8'));
    $bairro = trim(htmlspecialchars($_POST['bairro'] ?? '', ENT_QUOTES, 'UTF-8'));
    $cep = trim(htmlspecialchars($_POST['cep'] ?? '', ENT_QUOTES, 'UTF-8'));
    $cidade = trim(htmlspecialchars($_POST['cidade'] ?? '', ENT_QUOTES, 'UTF-8'));
    $estado = trim(htmlspecialchars($_POST['estado'] ?? '', ENT_QUOTES, 'UTF-8'));

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
            header("Location: ../view/cadastro.php?erro=campos_obrigatorios&campo=" . urlencode($campo));
            exit;
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../view/cadastro.php?erro=email_invalido");
        exit;
    }

    try {
        // Criar objetos
        $endereco = new Endereco($rua, $numero, $bairro, $cep, $cidade, $estado, $complemento);
        $cliente = new Cliente($nome, $telefone, $email, $cartaoCredito, $endereco);

        // Criar hash da senha antes de criar o cliente
        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

        
        // Agora passando a senha no construtor
        $pdo = Database::getConnection();
        $clienteDao = new ClienteDAO($pdo);

        $resultado = $clienteDao->cadastrarCliente($cliente, $senhaHash);
        
        
        // Verificar se email já existe
        if ($clienteDao->emailExiste($email)) {
            header("Location: ../view/cadastro.php?erro=email_existente");
            exit;
        }

        if ($resultado) {
            header('Location: ../view/cadastro.php?sucesso=1');
            exit;
        } else {
            throw new Exception("Não foi possível completar o cadastro.");
        }
    } catch (Exception $e) {
        error_log("Erro no cadastro: " . $e->getMessage());
        header('Location: ../view/cadastro.php?erro=erro_sistema');
        exit;
    }
} else {
    header('Location: ../view/cadastro.php');
    exit;
}