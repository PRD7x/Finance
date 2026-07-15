<?php
// Inicia a sessão para permitir persistência de dados de login do usuário entre as páginas
session_start();

// Importa o arquivo de configuração de conexão com o banco de dados
require_once 'config.php';

// Verifica se a requisição atual é do tipo POST (formulário enviado)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura a ação do formulário (pode ser 'login' ou 'register')
    $action = $_POST['action'] ?? '';

    // ==============================================================================
    // FLUXO DE LOGIN
    // ==============================================================================
    if ($action === 'login') {
        // Coleta e remove espaços em branco extras do e-mail e da senha
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');

        // Validação básica: impede o prosseguimento caso algum campo esteja vazio
        if (empty($email) || empty($senha)) {
            header("Location: login.php?error=" . urlencode("Por favor, preencha todos os campos."));
            exit();
        }

        // Prepara a consulta SQL usando Prepared Statement para evitar vulnerabilidade de SQL Injection
        // Seleciona o id, email e a senha criptografada do usuário
        $stmt = $conexao->prepare("SELECT idusers, email, senha FROM users WHERE email = ?");
        if ($stmt) {
            // Vincula o parâmetro de entrada (tipo 's' para string) à consulta preparada
            $stmt->bind_param("s", $email);
            // Executa a consulta
            $stmt->execute();
            // Obtém o resultado gerado
            $result = $stmt->get_result();

            // Verifica se um registro correspondente foi encontrado
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Compara a senha digitada pelo usuário com a senha criptografada do banco (usando password_verify)
                if (password_verify($senha, $user['senha'])) {
                    // Login efetuado com sucesso!
                    // Salva as informações de identificação do usuário na sessão ativa do PHP
                    $_SESSION['user_id'] = $user['idusers'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Redireciona o usuário autenticado para a página inicial (dashboard)
                    header("Location: index.php");
                    exit();
                }
            }
            
            // Caso o e-mail não exista ou a verificação de senha falhe
            header("Location: login.php?error=" . urlencode("E-mail ou senha incorretos."));
            exit();
        } else {
            // Em caso de falha interna ao preparar a consulta de banco de dados
            header("Location: login.php?error=" . urlencode("Erro no servidor. Tente novamente mais tarde."));
            exit();
        }

    // ==============================================================================
    // FLUXO DE CADASTRO (REGISTRO)
    // ==============================================================================
    } elseif ($action === 'register') {
        // Coleta e sanitiza os dados de entrada do formulário de cadastro
        $email = trim($_POST['email'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $confirm_senha = trim($_POST['confirm_senha'] ?? '');

        // Validação: garante que todos os campos obrigatórios foram preenchidos
        if (empty($email) || empty($senha) || empty($confirm_senha)) {
            header("Location: register.php?error=" . urlencode("Por favor, preencha todos os campos."));
            exit();
        }

        // Validação: garante que a confirmação de senha é idêntica à senha informada
        if ($senha !== $confirm_senha) {
            header("Location: register.php?error=" . urlencode("As senhas não coincidem."));
            exit();
        }

        // Validação: valida se o formato de e-mail é válido utilizando filtros nativos do PHP
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: register.php?error=" . urlencode("Por favor, insira um e-mail válido."));
            exit();
        }

        // Verifica no banco se o e-mail que o usuário quer cadastrar já existe
        $stmt = $conexao->prepare("SELECT idusers FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            // Se retornar qualquer linha, significa que o e-mail já está em uso
            if ($result->num_rows > 0) {
                header("Location: register.php?error=" . urlencode("Este e-mail já está cadastrado."));
                exit();
            }
        }

        // Cria uma senha criptografada (hash seguro) usando o algoritmo padrão (atualmente bcrypt)
        // Isso evita salvar a senha em texto limpo no banco por motivos de segurança
        $hashed_senha = password_hash($senha, PASSWORD_DEFAULT);

        // Prepara o SQL para inserir o novo usuário na tabela `users`
        $stmt = $conexao->prepare("INSERT INTO users (email, senha) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $hashed_senha);
            if ($stmt->execute()) {
                // Obtém o ID do usuário recém-inserido
                $user_id = $conexao->insert_id;

                // Inicializa o registro de controle financeiro do usuário na tabela `finance_data`
                // Define todos os valores financeiros iniciais como zero (0.00)
                $stmt_finance = $conexao->prepare("INSERT INTO finance_data (user_id, patrimonio, salario, reserva, gastos_fixos, cartao, gastos_mes) VALUES (?, 0, 0, 0, 0, 0, 0)");
                if ($stmt_finance) {
                    $stmt_finance->bind_param("i", $user_id);
                    $stmt_finance->execute();
                }

                // Redireciona para a tela de login informando que o cadastro foi bem-sucedido
                header("Location: login.php?success=" . urlencode("Cadastro realizado com sucesso! Faça login."));
                exit();
            } else {
                // Caso ocorra falha na execução do insert do usuário
                header("Location: register.php?error=" . urlencode("Erro ao cadastrar usuário."));
                exit();
            }
        } else {
            // Em caso de falha interna ao preparar a inserção do usuário
            header("Location: register.php?error=" . urlencode("Erro no servidor. Tente novamente mais tarde."));
            exit();
        }
    }
}

// Se o script for acessado de forma incorreta (ex: diretamente via GET no navegador),
// redireciona o usuário para a página de login.
header("Location: login.php");
exit();
?>
