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

// Lógica para Saúde Financeira
$salarioFinal = (float)($finance['salario'] ?? 0.00);
$gastosMes = (float)($finance['gastos_mes'] ?? 0.00);
$gastosCartao = (float)($finance['cartao'] ?? 0.00);
$gastosTotais = $gastosMes + $gastosCartao;
$saldoFinal = $salarioFinal - $gastosTotais;
$estaPositivo = ($saldoFinal >= 0);

// Porcentagem de gastos em relação ao salário
$porcentagemGastos = $salarioFinal > 0 ? round(($gastosTotais / $salarioFinal) * 100) : 0;
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
    
    <!-- Biblioteca Chart.js para renderização de gráficos modernos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        <!-- Seção: Saúde Financeira -->
        <section class="financial-health">
            <h2>Saúde Financeira</h2>
            <div class="health-container">
                <div class="chart-wrapper">
                    <canvas id="healthChart"></canvas>
                    <div class="chart-center-text">
                        <span class="label">Saldo</span>
                        <span class="value <?php echo $estaPositivo ? 'text-green' : 'text-red'; ?>">
                            R$ <?php echo number_format($saldoFinal, 2, ',', '.'); ?>
                        </span>
                    </div>
                </div>
                
                <div class="health-info">
                    <h3>A sua vida financeira está <span class="<?php echo $estaPositivo ? 'text-green' : 'text-red'; ?>"><?php echo $estaPositivo ? 'POSITIVA (Saudável)' : 'NEGATIVA (Crítica)'; ?></span></h3>
                    <p class="description">
                        <?php if ($salarioFinal == 0 && $gastosTotais == 0): ?>
                            Nenhum dado financeiro cadastrado até o momento. Por favor, registre o seu salário e adicione despesas ou cartões para visualizar a análise.
                        <?php elseif ($estaPositivo): ?>
                            Parabéns! Suas receitas são suficientes para cobrir todas as despesas mensais e faturas de cartão. Sobram <strong>R$ <?php echo number_format($saldoFinal, 2, ',', '.'); ?></strong> para você economizar ou investir.
                        <?php else: ?>
                            Atenção! Seus gastos totais excedem seu salário cadastrado em <strong>R$ <?php echo number_format(abs($saldoFinal), 2, ',', '.'); ?></strong>. Recomendamos avaliar seus gastos fixos e reduzir despesas supérfluas.
                        <?php endif; ?>
                    </p>
                    
                    <div class="financial-bars">
                        <div class="bar-item">
                            <span>Receitas (Salário): R$ <?php echo number_format($salarioFinal, 2, ',', '.'); ?> (100%)</span>
                            <div class="progress-bar-container">
                                <div class="progress-bar bg-green" style="width: <?php echo $salarioFinal > 0 ? '100%' : '0%'; ?>;"></div>
                            </div>
                        </div>
                        <div class="bar-item">
                            <span>Gastos Totais: R$ <?php echo number_format($gastosTotais, 2, ',', '.'); ?> (<?php echo $porcentagemGastos; ?>%)</span>
                            <div class="progress-bar-container">
                                <div class="progress-bar <?php echo $estaPositivo ? 'bg-orange' : 'bg-red'; ?>" style="width: <?php echo min(100, $porcentagemGastos); ?>%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

    <!-- Script para renderização do gráfico Chart.js -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('healthChart').getContext('2d');
            
            const salario = <?php echo $salarioFinal; ?>;
            const gastos = <?php echo $gastosTotais; ?>;
            
            // Se salário e gastos forem zero, desenhamos um gráfico cinza neutro
            const dataValues = (salario === 0 && gastos === 0) ? [1] : [salario, gastos];
            const dataColors = (salario === 0 && gastos === 0) ? ['#334155'] : ['#10b981', '#ef4444'];
            const dataLabels = (salario === 0 && gastos === 0) ? ['Sem dados'] : ['Receitas (Salário)', 'Gastos Totais'];
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: dataLabels,
                    datasets: [{
                        data: dataValues,
                        backgroundColor: dataColors,
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    cutout: '80%',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: (salario !== 0 || gastos !== 0),
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>

</body>
</html>
