<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #6e8efb, #a777e3);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            text-align: center;
            transition: transform 0.3s ease;
        }



        h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 2em;
        }

        .error-message {
            background: #ffe6e6;
            color: #d32f2f;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.95em;
        }

        label {
            font-size: 1.1em;
            color: #333;
            margin-bottom: 5px;
            display: block;
            text-align: left;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #6e8efb;
            outline: none;
            box-shadow: 0 0 8px rgba(110, 142, 251, 0.3);
        }

        button {
            width: 100%;
            padding: 12px;
            background: #6e8efb;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 1.1em;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #5a78e0;
        }

        button:focus {
            outline: 2px solid #5a78e0;
            outline-offset: 2px;
        }

        a {
            display: block;
            margin-top: 15px;
            color: #6e8efb;
            text-decoration: none;
            font-size: 1em;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #5a78e0;
        }

        a:focus {
            outline: 2px solid #6e8efb;
            outline-offset: 2px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 20px;
            }

            h2 {
                font-size: 1.8em;
            }

            input[type="email"],
            input[type="password"],
            button {
                font-size: 0.95em;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
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
        <a href="cadastro.php">Cadastre-se</a>
    </div>
</body>
</html>