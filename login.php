<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Meu Patrimônio</title>

    <!-- Importação do arquivo de estilo da página de login -->
    <link rel="stylesheet" href="login.css">
    

</head>
<body>

    <!-- Container principal centralizado do card de login -->
    <div class="login-card">

        <!-- Ícone representativo do aplicativo (emoji de dinheiro) -->
        <div class="logo">
            💰
        </div>

        <h1>Meu Patrimônio</h1>

        <p>Faça login para acessar suas finanças</p>

        <!-- PHP verifica se há uma mensagem de erro na query string (GET) e a exibe -->
        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg">
                ⚠️ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- PHP verifica se há uma mensagem de sucesso na query string (GET) e a exibe -->
        <?php if (isset($_GET['success'])): ?>
            <div class="success-msg">
                ✅ <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de autenticação que envia dados para auth.php por método POST -->
        <form action="auth.php" method="post">
            <!-- Campo oculto para identificar a ação que o auth.php deve executar -->
            <input type="hidden" name="action" value="login">

            <!-- Grupo de entrada do E-mail -->
            <div class="input-group">
                <label>E-mail</label>
                <input type="email" name="email" placeholder="Digite seu e-mail" required>
            </div>

            <!-- Grupo de entrada da Senha -->
            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="senha" placeholder="Digite sua senha" required>
            </div>

            <!-- Botão de submissão do formulário -->
            <input class="button" type="submit" name="submit" value="Entrar">

        </form>

        <!-- Links úteis na parte inferior para recuperação de senha e criação de novas contas -->
        <div class="links">
            <a href="#">Esqueci minha senha</a>
            <a href="register.php">Criar conta</a>
        </div>

    </div>

</body>
</html>