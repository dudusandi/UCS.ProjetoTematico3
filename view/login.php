<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ECOxChange</title>
    <link rel="stylesheet" href="login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="logo">ECO<span>Exchange</span></div>
    
        <?php if (isset($_GET['erro'])): ?>
            <div class="error-message">
                ❌ Email ou senha inválidos. Tente novamente.
            </div>
        <?php endif; ?>
        
        <form action="../controllers/login_controller.php" method="POST">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Digite seu email" required>
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
            </div>
            
            <button type="submit" class="btn-primary">Entrar</button>
        </form>
        
        <a href="cadastro_cliente.php" class="signup-link">
            Não tem uma conta? Cadastre-se
        </a>
    </div>
</body>
</html>