<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Meu Patrimônio</title>

    <!-- Importação do arquivo de estilo da página de login/cadastro -->
    <link class="styles" rel="stylesheet" href="login.css">
    

</head>
<body>

    <!-- Card de cadastro centralizado -->
    <div class="login-card">

        <!-- Logotipo do app (emoji de dinheiro) -->
        <div class="logo">
            💰
        </div>

        <h1>Criar Conta</h1>

        <p>Cadastre-se para gerenciar suas finanças</p>

        <!-- PHP verifica se há mensagens de erro recebidas por parâmetro GET e as exibe na tela -->
        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg">
                ⚠️ <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Formulário de cadastro que envia dados para auth.php por método POST -->
        <form action="auth.php" method="post">
            <!-- Campo oculto para identificar a ação de cadastro a ser processada pelo auth.php -->
            <input type="hidden" name="action" value="register">

            <!-- Entrada do E-mail do novo usuário -->
            <div class="input-group">
                <label>E-mail</label>
                <input type="email" name="email" placeholder="Digite seu e-mail" required>
            </div>

            <!-- Entrada da Senha do novo usuário -->
            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="senha" placeholder="Crie uma senha" required>
            </div>

            <!-- Confirmação da Senha digitada -->
            <div class="input-group">
                <label>Confirmar Senha</label>
                <input type="password" name="confirm_senha" placeholder="Confirme sua senha" required>
            </div>

            <!-- Botão para submeter o cadastro -->
            <input class="button" type="submit" name="submit" value="Cadastrar">

        </form>

        <!-- Link de navegação para retornar ao formulário de login -->
        <div class="links" style="justify-content: center;">
            <a href="login.php">Já tem uma conta? Faça login</a>
        </div>

    </div>

</body>
</html>
