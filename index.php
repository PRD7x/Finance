<?php
// Inicia a sessão PHP para verificar o estado de autenticação do usuário
session_start();

// Segurança: redireciona para a página de login se o usuário não estiver autenticado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Importa o arquivo de configuração de banco de dados
require_once 'config.php';

// Coleta as variáveis de sessão do usuário logado
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];

// Obtém a primeira letra do e-mail em maiúsculo para exibir como avatar na navbar
$first_letter = strtoupper(substr($email, 0, 1));

// Prepara e executa consulta para carregar as informações financeiras do usuário ativo
$stmt = $conexao->prepare("SELECT * FROM finance_data WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$finance = $result->fetch_assoc();

// Caso o usuário ainda não possua registros de dados financeiros, inicializa no banco com zeros
if (!$finance) {
    $stmt_insert = $conexao->prepare("INSERT INTO finance_data (user_id) VALUES (?)");
    $stmt_insert->bind_param("i", $user_id);
    $stmt_insert->execute();
    
    // Define os valores locais padrão como 0.00
    $finance = [
        'patrimonio' => 0.00,
        'salario' => 0.00,
        'reserva' => 0.00,
        'gastos_fixos' => 0.00,
        'cartao' => 0.00,
        'gastos_mes' => 0.00
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Patrimônio - Dashboard</title>

    <!-- Importação da folha de estilos principal do painel -->
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    
    <!-- Biblioteca Feather Icons para renderização de ícones modernos baseados em tags "data-feather" -->
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>

    <!-- Cabeçalho Principal (Navbar) -->
    <?php include 'components/navbar.php'; ?>

    <!-- Container Principal do Dashboard -->
    <main class="container">

        <!-- Seção Hero com saudação principal -->
        <section class="hero">
            <h1>Meu Patrimônio</h1>
            <p>Visão completa das suas finanças</p>
        </section>

        <!-- Grid de Cartões contendo informações financeiras -->
        <section class="cards">

            <!-- Cartão: Patrimônio Total -->
            <a href="investments.php" class="card-link">
                <div class="card clickable-card">
                    <div class="card-icon blue">
                        <i data-feather="wallet"></i>
                    </div>
                    <h3>Patrimônio Total</h3>
                    <h2 id="patrimonio">R$ <?php echo number_format($finance['patrimonio'], 2, ',', '.'); ?></h2>
                    <span>Total Geral</span>
                </div>
            </a>

            <!-- Cartão: Salário (Mês Atual) -->
            <div class="card clickable-card" data-action="edit-salary" style="cursor: pointer;">
                <div class="card-icon green">
                    <i data-feather="dollar-sign"></i>
                </div>
                <h3>Salário (Mês Atual)</h3>
                <h2 id="salario"><?php echo $finance['salario'] > 0 ? 'R$ ' . number_format($finance['salario'], 2, ',', '.') : 'Não cadastrado'; ?></h2>
                <span>Média 3 meses</span>
            </div>

            <!-- Cartão: Reserva de Emergência -->
            <a href="reserve.php" class="card-link">
                <div class="card clickable-card">
                    <div class="card-icon cyan">
                        <i data-feather="shield"></i>
                    </div>
                    <h3>Reserva de Emergência</h3>
                    <h2 id="reserva">R$ <?php echo number_format($finance['reserva'], 2, ',', '.'); ?></h2>
                    <span>Segurança</span>
                </div>
            </a>

            <!-- Cartão: Gastos Fixos -->
            <a href="expenses.php?tab=fixas" class="card-link">
                <div class="card clickable-card">
                    <div class="card-icon yellow">
                        <i data-feather="home"></i>
                    </div>
                    <h3>Gastos Fixos</h3>
                    <h2 id="fixos">R$ <?php echo number_format($finance['gastos_fixos'], 2, ',', '.'); ?></h2>
                    <span>Mensal</span>
                </div>
            </a>

            <!-- Cartão: Fatura de Cartões -->
            <a href="cards.php" class="card-link">
                <div class="card clickable-card">
                    <div class="card-icon purple">
                        <i data-feather="credit-card"></i>
                    </div>
                    <h3>Cartão</h3>
                    <h2 id="cartao">R$ <?php echo number_format($finance['cartao'], 2, ',', '.'); ?></h2>
                    <span>Parcelas a pagar</span>
                </div>
            </a>

            <!-- Cartão: Gastos Totais do Mês (Calculado automaticamente: Fixos + Variáveis) -->
            <a href="expenses.php?tab=variaveis" class="card-link">
                <div class="card clickable-card">
                    <div class="card-icon red">
                        <i data-feather="calendar"></i>
                    </div>
                    <h3>Gastos do Mês</h3>
                    <h2 id="mes">R$ <?php echo number_format($finance['gastos_mes'], 2, ',', '.'); ?></h2>
                    <span>Resumo mensal</span>
                </div>
            </a>

        </section>

        <!-- Seção de Acesso Rápido -->
        <section class="quick-access">
            <h2>Acesso Rápido</h2>
            <div class="actions">
                <a href="investments.php" class="action-btn">
                    <i data-feather="trending-up"></i>
                    <span>Investimentos</span>
                </a>

                <a href="reserve.php" class="action-btn">
                    <i data-feather="shield"></i>
                    <span>Reserva</span>
                </a>

                <a href="expenses.php" class="action-btn">
                    <i data-feather="dollar-sign"></i>
                    <span>Despesas</span>
                </a>

                <a href="cards.php" class="action-btn">
                    <i data-feather="credit-card"></i>
                    <span>Cartões</span>
                </a>

                <!-- Aciona a edição de Salário direto no modal de salário -->
                <button class="action-btn" data-action="edit-salary">
                    <i data-feather="briefcase"></i>
                    <span>Salário</span>
                </button>
            </div>
        </section>

    </main>

    <!-- Modal para atualização do Salário -->
    <div id="updateSalaryModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Atualizar Salário (Mês Atual)</h3>
                <button type="button" id="closeModalBtn" class="close-btn">&times;</button>
            </div>
            <form id="updateSalaryForm">
                <div class="modal-body">
                    <label for="salaryValue">Novo Salário (R$)</label>
                    <input type="number" step="0.01" min="0" id="salaryValue" name="value" placeholder="Digite o novo valor do salário" required>
                </div>
                <div class="modal-footer">
                    <button type="button" id="cancelModalBtn" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ponte de Dados do Backend para o Javascript -->
    <!-- Injeta os valores atuais do PHP no escopo JavaScript do cliente para sincronização sem recarregar -->
    <script>
        const initialFinanceData = {
            patrimonio: <?php echo (float)$finance['patrimonio']; ?>,
            salario: <?php echo (float)$finance['salario']; ?>,
            reserva: <?php echo (float)$finance['reserva']; ?>,
            gastos_fixos: <?php echo (float)$finance['gastos_fixos']; ?>,
            cartao: <?php echo (float)$finance['cartao']; ?>,
            gastos_mes: <?php echo (float)$finance['gastos_mes']; ?>
        };
    </script>
    
    <!-- Script lógico que controla as interações do painel -->
    <script src="script.js?v=<?php echo time(); ?>"></script>
    <script src="js/navbar.js?v=<?php echo time(); ?>"></script>

</body>
</html>
