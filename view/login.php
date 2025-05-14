<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ECOXchange</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">ECO<span>Xchange</span></div>
    
        <?php if (isset($_GET['erro'])): ?>
            <p class="error-message">Email ou senha inválidos.</p>
        <?php endif; ?>
        <form action="../controllers/login_controller.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required>
            <button type="submit">Entrar</button>
        </form>
        <a href="cadastro_cliente.php">Cadastre-se</a>
    </div>
</body>
</html>