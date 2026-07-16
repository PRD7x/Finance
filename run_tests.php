<?php
/**
 * Script de Testes Automatizados de Ponta a Ponta (E2E) do Sistema Financeiro
 * Executa requisições HTTP reais contra a URL online e valida a persistência no banco de dados.
 * Pode ser rodado via CLI (php run_tests.php) ou via Navegador.
 */

// Define cabeçalho para exibição no navegador (HTML) ou CLI
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>Testes Automatizados - Meu Patrimônio (Online)</title>";
    echo "<style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #0f172a; color: #e2e8f0; padding: 20px; max-width: 900px; margin: 0 auto; }
        h1, h2 { color: #38bdf8; border-bottom: 1px solid #334155; padding-bottom: 8px; }
        .test-case { background-color: #1e293b; border-left: 4px solid #64748b; padding: 12px; margin-bottom: 10px; border-radius: 4px; }
        .test-case.success { border-left-color: #10b981; }
        .test-case.failed { border-left-color: #ef4444; }
        .status { font-weight: bold; }
        .status.success { color: #10b981; }
        .status.failed { color: #ef4444; }
        .summary-card { background-color: #1e293b; border: 2px solid #38bdf8; border-radius: 8px; padding: 20px; margin-top: 20px; text-align: center; }
        .positive { color: #10b981; font-size: 24px; font-weight: bold; }
        .negative { color: #ef4444; font-size: 24px; font-weight: bold; }
    </style></head><body>";
    echo "<h1>Meu Patrimônio - Testes de Integração E2E (Online)</h1>";
} else {
    // Cores no terminal
    define('CLR_RESET', "\033[0m");
    define('CLR_SUCCESS', "\033[1;32m");
    define('CLR_FAILED', "\033[1;31m");
    define('CLR_HEADER', "\033[1;36m");
    define('CLR_INFO', "\033[1;34m");
    echo CLR_HEADER . "========================================================\n";
    echo " MEU PATRIMÔNIO - TESTES DE INTEGRAÇÃO E2E (ONLINE)    \n";
    echo "========================================================\n\n" . CLR_RESET;
}

require_once 'config.php';

// URL do site online obtido do repositório
$baseUrl = 'https://financetest.up.railway.app';
$cookieFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cookie_finance_test_' . uniqid() . '.txt';

// Estado global de testes
$totalTests = 0;
$passedTests = 0;
$errors = [];

// Função auxiliar para requisições HTTP usando cURL
function http_request($url, $method = 'GET', $fields = [], $cookieFile = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Permite SSL auto-assinado ou sem verificação estrita
    
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    }
    
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    return [
        'body' => $response,
        'info' => $info
    ];
}

// Função auxiliar para registrar casos de teste
function assertTest($description, $assertion) {
    global $totalTests, $passedTests, $errors;
    $totalTests++;
    
    if ($assertion) {
        $passedTests++;
        if (php_sapi_name() === 'cli') {
            echo "   [ " . CLR_SUCCESS . "OK" . CLR_RESET . " ] $description\n";
        } else {
            echo "<div class='test-case success'>✔ <span class='status success'>[PASSOU]</span> $description</div>";
        }
    } else {
        $errors[] = $description;
        if (php_sapi_name() === 'cli') {
            echo "   [ " . CLR_FAILED . "FALHOU" . CLR_RESET . " ] $description\n";
        } else {
            echo "<div class='test-case failed'>✘ <span class='status failed'>[FALHOU]</span> $description</div>";
        }
    }
}

// Início dos Testes Individuais E2E
if (php_sapi_name() === 'cli') {
    echo CLR_INFO . "--- 1. FLUXO DE CADASTRO E LOGIN (ONLINE) ---" . CLR_RESET . "\n";
} else {
    echo "<h2>1. Fluxo de Cadastro e Login (Online)</h2>";
}

$emailTeste = "temp_test_user_" . uniqid() . "@finance.com";
$senhaTeste = "SenhaForte123@";

// Teste 1.1: Registro de Usuário via POST HTTP
$regResponse = http_request("$baseUrl/auth.php", 'POST', [
    'action' => 'register',
    'email' => $emailTeste,
    'senha' => $senhaTeste,
    'confirm_senha' => $senhaTeste
], $cookieFile);

// Validação no banco de dados correspondente
$stmt_check_user = $conexao->prepare("SELECT idusers FROM users WHERE email = ?");
$stmt_check_user->bind_param("s", $emailTeste);
$stmt_check_user->execute();
$idUsuarioTeste = $stmt_check_user->get_result()->fetch_assoc()['idusers'] ?? 0;
assertTest("Registro de novo usuário via chamada POST HTTP na URL online", $idUsuarioTeste > 0);

// Teste 1.2: Inicialização automática de finance_data
$stmt_fin_check = $conexao->prepare("SELECT * FROM finance_data WHERE user_id = ?");
$stmt_fin_check->bind_param("i", $idUsuarioTeste);
$stmt_fin_check->execute();
$res_fin_check = $stmt_fin_check->get_result();
$finance_row = $res_fin_check->fetch_assoc();
$financeDataCriada = ($finance_row !== null);
assertTest("Inicialização automática dos dados de finanças zerados no banco", $financeDataCriada && (float)$finance_row['salario'] === 0.0);

// Teste 1.3: Login do Usuário via POST HTTP para obter Cookie de Sessão
$loginResponse = http_request("$baseUrl/auth.php", 'POST', [
    'action' => 'login',
    'email' => $emailTeste,
    'senha' => $senhaTeste
], $cookieFile);

// Verifica se o redirecionamento foi para index.php (indicativo de login bem-sucedido)
$loginSucesso = (strpos($loginResponse['info']['url'], 'index.php') !== false);
assertTest("Login do usuário via chamada POST HTTP na URL online e persistência de Cookie", $loginSucesso);


if (php_sapi_name() === 'cli') {
    echo "\n" . CLR_INFO . "--- 2. ATUALIZAÇÃO DE SALÁRIO ---" . CLR_RESET . "\n";
} else {
    echo "<h2>2. Atualização de Salário</h2>";
}

// Teste 2.1: Atualização de Salário via POST para update_finance.php
$salarioResponse = http_request("$baseUrl/update_finance.php", 'POST', [
    'field' => 'salario',
    'value' => 5000.00
], $cookieFile);

// Validação no banco
$stmt_sal_check = $conexao->prepare("SELECT salario FROM finance_data WHERE user_id = ?");
$stmt_sal_check->bind_param("i", $idUsuarioTeste);
$stmt_sal_check->execute();
$sal_check_val = (float)$stmt_sal_check->get_result()->fetch_assoc()['salario'];
assertTest("Atualização do salário para R$ 5.000,00 via POST HTTP na URL online", $sal_check_val === 5000.00);


if (php_sapi_name() === 'cli') {
    echo "\n" . CLR_INFO . "--- 3. MÓDULO DE DESPESAS (FIXAS E VARIÁVEIS) ---" . CLR_RESET . "\n";
} else {
    echo "<h2>3. Módulo de Despesas (Fixas e Variáveis)</h2>";
}

// Teste 3.1: Adicionar Despesa Fixa via POST para add_expense.php
$valorFixa = 1500.00;
http_request("$baseUrl/add_expense.php", 'POST', [
    'tipo_despesa' => 'Fixa',
    'nome' => 'Aluguel',
    'valor' => $valorFixa,
    'dia_vencimento' => 10,
    'categoria_item' => 'Moradia',
    'observacoes' => 'Aluguel Mensal'
], $cookieFile);

// Validação no banco
$stmt_check_fix = $conexao->prepare("SELECT id, valor FROM despesas WHERE user_id = ? AND tipo_despesa = 'Fixa' AND nome = 'Aluguel'");
$stmt_check_fix->bind_param("i", $idUsuarioTeste);
$stmt_check_fix->execute();
$fix_row = $stmt_check_fix->get_result()->fetch_assoc();
$idDespesaFixa = $fix_row['id'] ?? 0;

$stmt_fin_check2 = $conexao->prepare("SELECT gastos_fixos, gastos_mes FROM finance_data WHERE user_id = ?");
$stmt_fin_check2->bind_param("i", $idUsuarioTeste);
$stmt_fin_check2->execute();
$res_fin2 = $stmt_fin_check2->get_result()->fetch_assoc();
assertTest("Adicionar Despesa Fixa (Aluguel: R$ 1.500,00) via HTTP e atualizar painel no banco", 
    $idDespesaFixa > 0 && (float)$res_fin2['gastos_fixos'] === 1500.00 && (float)$res_fin2['gastos_mes'] === 1500.00);

// Teste 3.2: Adicionar Despesa Variável via POST para add_expense.php
$valorVariavel = 800.00;
http_request("$baseUrl/add_expense.php", 'POST', [
    'tipo_despesa' => 'Variável',
    'nome' => 'Supermercado',
    'valor' => $valorVariavel,
    'dia_vencimento' => 15,
    'categoria_item' => 'Alimentação',
    'observacoes' => 'Compras do mês'
], $cookieFile);

// Validação no banco
$stmt_check_var = $conexao->prepare("SELECT id FROM despesas WHERE user_id = ? AND tipo_despesa = 'Variável' AND nome = 'Supermercado'");
$stmt_check_var->bind_param("i", $idUsuarioTeste);
$stmt_check_var->execute();
$idDespesaVar = $stmt_check_var->get_result()->fetch_assoc()['id'] ?? 0;

$stmt_fin_check3 = $conexao->prepare("SELECT gastos_fixos, gastos_mes FROM finance_data WHERE user_id = ?");
$stmt_fin_check3->bind_param("i", $idUsuarioTeste);
$stmt_fin_check3->execute();
$res_fin3 = $stmt_fin_check3->get_result()->fetch_assoc();
assertTest("Adicionar Despesa Variável (Mercado: R$ 800,00) via HTTP e atualizar painel no banco", 
    $idDespesaVar > 0 && (float)$res_fin3['gastos_fixos'] === 1500.00 && (float)$res_fin3['gastos_mes'] === 2300.00);

// Teste 3.3: Pagar Despesa via GET para pay_expense.php
$dataHoje = date('Y-m-d');
http_request("$baseUrl/pay_expense.php?id=$idDespesaVar", 'GET', [], $cookieFile);

// Validação no banco
$stmt_pay_check = $conexao->prepare("SELECT status, data_quitacao FROM despesas WHERE id = ?");
$stmt_pay_check->bind_param("i", $idDespesaVar);
$stmt_pay_check->execute();
$res_pay_check = $stmt_pay_check->get_result()->fetch_assoc();
assertTest("Quitar despesa (Marcar como Pago e preencher data de quitação) via GET HTTP", 
    $res_pay_check['status'] === 'Pago' && $res_pay_check['data_quitacao'] === $dataHoje);

// Teste 3.4: Despagar Despesa via GET para unpay_expense.php
http_request("$baseUrl/unpay_expense.php?id=$idDespesaVar", 'GET', [], $cookieFile);

// Validação no banco
$stmt_unpay_check = $conexao->prepare("SELECT status, data_quitacao FROM despesas WHERE id = ?");
$stmt_unpay_check->bind_param("i", $idDespesaVar);
$stmt_unpay_check->execute();
$res_unpay_check = $stmt_unpay_check->get_result()->fetch_assoc();
assertTest("Estornar pagamento de despesa (Marcar como Pendente e limpar quitação) via GET HTTP", 
    $res_unpay_check['status'] === 'Pendente' && $res_unpay_check['data_quitacao'] === null);

// Teste 3.5: Editar Despesa via POST para edit_expense.php
$novoValorVar = 900.00; // R$ 100,00 de aumento
http_request("$baseUrl/edit_expense.php", 'POST', [
    'id' => $idDespesaVar,
    'tipo_despesa' => 'Variável',
    'nome' => 'Supermercado Gourmet',
    'valor' => $novoValorVar,
    'dia_vencimento' => 15,
    'categoria_item' => 'Alimentação',
    'observacoes' => 'Gourmet'
], $cookieFile);

// Validação no banco
$stmt_fin_check4 = $conexao->prepare("SELECT gastos_mes FROM finance_data WHERE user_id = ?");
$stmt_fin_check4->bind_param("i", $idUsuarioTeste);
$stmt_fin_check4->execute();
$res_fin4 = $stmt_fin_check4->get_result()->fetch_assoc();
assertTest("Editar Despesa (Aumento de R$ 800,00 para R$ 900,00) via POST HTTP e atualizar painel", 
    (float)$res_fin4['gastos_mes'] === 2400.00);

// Teste 3.6: Deletar Despesa via GET para delete_expense.php
http_request("$baseUrl/delete_expense.php?id=$idDespesaVar", 'GET', [], $cookieFile);

// Validação no banco
$stmt_fin_check5 = $conexao->prepare("SELECT gastos_mes FROM finance_data WHERE user_id = ?");
$stmt_fin_check5->bind_param("i", $idUsuarioTeste);
$stmt_fin_check5->execute();
$res_fin5 = $stmt_fin_check5->get_result()->fetch_assoc();

$stmt_check_deleted = $conexao->prepare("SELECT id FROM despesas WHERE id = ?");
$stmt_check_deleted->bind_param("i", $idDespesaVar);
$stmt_check_deleted->execute();
$deletedOk = ($stmt_check_deleted->get_result()->num_rows === 0);

assertTest("Excluir despesa e deduzir valor do painel de controle financeiro via GET HTTP", 
    $deletedOk && (float)$res_fin5['gastos_mes'] === 1500.00);


if (php_sapi_name() === 'cli') {
    echo "\n" . CLR_INFO . "--- 4. MÓDULO DE CARTÕES DE CRÉDITO ---" . CLR_RESET . "\n";
} else {
    echo "<h2>4. Módulo de Cartões de Crédito</h2>";
}

// Teste 4.1: Adicionar Cartão de Crédito via POST para add_card.php
$limiteTotal = 3000.00;
http_request("$baseUrl/add_card.php", 'POST', [
    'nome_cartao' => 'Nubank Teste',
    'bandeira' => 'Mastercard',
    'cor' => '#820ad1',
    'limite_total' => $limiteTotal,
    'dia_fechamento' => 28,
    'dia_vencimento' => 5
], $cookieFile);

// Validação no banco
$stmt_check_card = $conexao->prepare("SELECT id FROM cartoes WHERE user_id = ? AND nome_cartao = 'Nubank Teste'");
$stmt_check_card->bind_param("i", $idUsuarioTeste);
$stmt_check_card->execute();
$idCartao = $stmt_check_card->get_result()->fetch_assoc()['id'] ?? 0;
assertTest("Criar novo Cartão de Crédito via POST HTTP na URL online", $idCartao > 0);

// Teste 4.2: Adicionar Despesa no Cartão (Parcela Única) via POST para add_card_expense.php
$valorDespesaCard = 1000.00;
http_request("$baseUrl/add_card_expense.php", 'POST', [
    'descricao' => 'Celular',
    'cartao_id' => $idCartao,
    'categoria' => 'Eletrônicos',
    'data_compra' => '2026-07-15',
    'valor_total' => $valorDespesaCard,
    'parcelas' => '1/1'
], $cookieFile);

// Validação no banco
$stmt_check_cexp = $conexao->prepare("SELECT id FROM despesas_cartao WHERE user_id = ? AND cartao_id = ? AND descricao = 'Celular'");
$stmt_check_cexp->bind_param("ii", $idUsuarioTeste, $idCartao);
$stmt_check_cexp->execute();
$idDespesaCard = $stmt_check_cexp->get_result()->fetch_assoc()['id'] ?? 0;

$stmt_check_c = $conexao->prepare("SELECT limite_disponivel, fatura_atual FROM cartoes WHERE id = ?");
$stmt_check_c->bind_param("i", $idCartao);
$stmt_check_c->execute();
$res_c = $stmt_check_c->get_result()->fetch_assoc();

$stmt_check_f = $conexao->prepare("SELECT cartao FROM finance_data WHERE user_id = ?");
$stmt_check_f->bind_param("i", $idUsuarioTeste);
$stmt_check_f->execute();
$res_f = $stmt_check_f->get_result()->fetch_assoc();

assertTest("Adicionar Despesa no Cartão (R$ 1.000,00) via POST HTTP e atualizar limites/painel", 
    $idDespesaCard > 0 && 
    (float)$res_c['limite_disponivel'] === 2000.00 && 
    (float)$res_c['fatura_atual'] === 1000.00 && 
    (float)$res_f['cartao'] === 1000.00);

// Teste 4.3: Pagar Despesa do Cartão via GET para pay_card_expense.php
http_request("$baseUrl/pay_card_expense.php?id=$idDespesaCard", 'GET', [], $cookieFile);

// Validação no banco
$stmt_check_c2 = $conexao->prepare("SELECT limite_disponivel, fatura_atual FROM cartoes WHERE id = ?");
$stmt_check_c2->bind_param("i", $idCartao);
$stmt_check_c2->execute();
$res_c2 = $stmt_check_c2->get_result()->fetch_assoc();

$stmt_check_f2 = $conexao->prepare("SELECT cartao FROM finance_data WHERE user_id = ?");
$stmt_check_f2->bind_param("i", $idUsuarioTeste);
$stmt_check_f2->execute();
$res_f2 = $stmt_check_f2->get_result()->fetch_assoc();
assertTest("Quitar despesa do Cartão via GET HTTP e atualizar limites/painel", 
    (float)$res_c2['limite_disponivel'] === 3000.00 && 
    (float)$res_c2['fatura_atual'] === 0.00 &&
    (float)$res_f2['cartao'] === 0.00);

// Teste 4.4: Estornar Pagamento da Despesa do Cartão via GET para unpay_card_expense.php
http_request("$baseUrl/unpay_card_expense.php?id=$idDespesaCard", 'GET', [], $cookieFile);

// Validação no banco
$stmt_check_c3 = $conexao->prepare("SELECT limite_disponivel, fatura_atual FROM cartoes WHERE id = ?");
$stmt_check_c3->bind_param("i", $idCartao);
$stmt_check_c3->execute();
$res_c3 = $stmt_check_c3->get_result()->fetch_assoc();

$stmt_check_f3 = $conexao->prepare("SELECT cartao FROM finance_data WHERE user_id = ?");
$stmt_check_f3->bind_param("i", $idUsuarioTeste);
$stmt_check_f3->execute();
$res_f3 = $stmt_check_f3->get_result()->fetch_assoc();
assertTest("Estornar quitação da despesa do Cartão via GET HTTP e restaurar limites e faturas", 
    (float)$res_c3['limite_disponivel'] === 2000.00 && 
    (float)$res_c3['fatura_atual'] === 1000.00 &&
    (float)$res_f3['cartao'] === 1000.00);

// Teste 4.5: Pagamento Parcial de Despesa Parcelada via GET para pay_card_installment.php
$valorParcelado = 600.00;
$totalParcelas = 3;
$valorUmaParcela = 200.00;

// Cria compra parcelada de R$ 600,00 em 3x via HTTP
http_request("$baseUrl/add_card_expense.php", 'POST', [
    'descricao' => 'Geladeira',
    'cartao_id' => $idCartao,
    'categoria' => 'Eletro',
    'data_compra' => '2026-07-15',
    'valor_total' => $valorParcelado,
    'parcelas' => '1/3'
], $cookieFile);

// Busca id da despesa parcelada
$stmt_check_part = $conexao->prepare("SELECT id FROM despesas_cartao WHERE user_id = ? AND cartao_id = ? AND descricao = 'Geladeira'");
$stmt_check_part->bind_param("ii", $idUsuarioTeste, $idCartao);
$stmt_check_part->execute();
$idDespesaPart = $stmt_check_part->get_result()->fetch_assoc()['id'] ?? 0;

// Paga uma parcela via HTTP
http_request("$baseUrl/pay_card_installment.php?id=$idDespesaPart", 'GET', [], $cookieFile);

// Validação no banco
$stmt_check_c4 = $conexao->prepare("SELECT limite_disponivel, fatura_atual FROM cartoes WHERE id = ?");
$stmt_check_c4->bind_param("i", $idCartao);
$stmt_check_c4->execute();
$res_c4 = $stmt_check_c4->get_result()->fetch_assoc();

$stmt_check_part2 = $conexao->prepare("SELECT parcelas FROM despesas_cartao WHERE id = ?");
$stmt_check_part2->bind_param("i", $idDespesaPart);
$stmt_check_part2->execute();
$res_part = $stmt_check_part2->get_result()->fetch_assoc();
assertTest("Pagar uma parcela de despesa parcelada via GET HTTP (Abater R$ 200,00 e ir para 2/3)", 
    $idDespesaPart > 0 &&
    (float)$res_c4['fatura_atual'] === 1400.00 && 
    (float)$res_c4['limite_disponivel'] === 1600.00 && 
    $res_part['parcelas'] === '2/3');


if (php_sapi_name() === 'cli') {
    echo "\n" . CLR_INFO . "--- 5. RESERVA DE EMERGÊNCIA ---" . CLR_RESET . "\n";
} else {
    echo "<h2>5. Módulo de Reserva de Emergência</h2>";
}

// Teste 5.1: Adicionar Reserva de Emergência via POST para add_reserve.php
$valorReserva = 2000.00;
http_request("$baseUrl/add_reserve.php", 'POST', [
    'descricao' => 'Reserva NuConta',
    'instituicao' => 'Nubank',
    'tipo_aplicacao' => 'CDB',
    'valor' => $valorReserva,
    'data_atualizacao' => '2026-07-15',
    'indexador' => 'CDI',
    'porcentagem_indexador' => 100.0,
    'observacoes' => 'Reserva Geral'
], $cookieFile);

// Validação no banco
$stmt_check_res = $conexao->prepare("SELECT id FROM reservas WHERE user_id = ? AND descricao = 'Reserva NuConta'");
$stmt_check_res->bind_param("i", $idUsuarioTeste);
$stmt_check_res->execute();
$idReserva = $stmt_check_res->get_result()->fetch_assoc()['id'] ?? 0;

$stmt_check_r = $conexao->prepare("SELECT reserva, patrimonio FROM finance_data WHERE user_id = ?");
$stmt_check_r->bind_param("i", $idUsuarioTeste);
$stmt_check_r->execute();
$res_r = $stmt_check_r->get_result()->fetch_assoc();
assertTest("Adicionar Reserva de Emergência (R$ 2.000,00) via POST HTTP e atualizar painel", 
    $idReserva > 0 && (float)$res_r['reserva'] === 2000.00 && (float)$res_r['patrimonio'] === 2000.00);

// Teste 5.2: Editar Reserva de Emergência via POST para edit_reserve.php
$novoValorReserva = 2500.00; // R$ 500,00 a mais
http_request("$baseUrl/edit_reserve.php", 'POST', [
    'id' => $idReserva,
    'descricao' => 'Reserva NuConta Editada',
    'instituicao' => 'Nubank',
    'tipo_aplicacao' => 'CDB',
    'valor' => $novoValorReserva,
    'data_atualizacao' => '2026-07-15',
    'indexador' => 'CDI',
    'porcentagem_indexador' => 100.0,
    'observacoes' => 'Reserva Geral'
], $cookieFile);

// Validação no banco
$stmt_check_r2 = $conexao->prepare("SELECT reserva, patrimonio FROM finance_data WHERE user_id = ?");
$stmt_check_r2->bind_param("i", $idUsuarioTeste);
$stmt_check_r2->execute();
$res_r2 = $stmt_check_r2->get_result()->fetch_assoc();
assertTest("Editar Reserva de Emergência (Para R$ 2.500,00) via POST HTTP e recalcular totais", 
    (float)$res_r2['reserva'] === 2500.00 && (float)$res_r2['patrimonio'] === 2500.00);


if (php_sapi_name() === 'cli') {
    echo "\n" . CLR_INFO . "--- 6. MÓDULO DE INVESTIMENTOS ---" . CLR_RESET . "\n";
} else {
    echo "<h2>6. Módulo de Investimentos</h2>";
}

// Teste 6.1: Adicionar Investimento via POST para add_investment.php
$valorInv = 3000.00;
http_request("$baseUrl/add_investment.php", 'POST', [
    'descricao' => 'Ações PETR4',
    'valor' => $valorInv,
    'data_investimento' => '2026-07-15',
    'observacoes' => '100 cotas'
], $cookieFile);

// Validação no banco
$stmt_check_inv = $conexao->prepare("SELECT id FROM investimentos WHERE user_id = ? AND descricao = 'Ações PETR4'");
$stmt_check_inv->bind_param("i", $idUsuarioTeste);
$stmt_check_inv->execute();
$idInvestimento = $stmt_check_inv->get_result()->fetch_assoc()['id'] ?? 0;

$stmt_check_i = $conexao->prepare("SELECT patrimonio FROM finance_data WHERE user_id = ?");
$stmt_check_i->bind_param("i", $idUsuarioTeste);
$stmt_check_i->execute();
$res_i = $stmt_check_i->get_result()->fetch_assoc();
assertTest("Adicionar Investimento (PETR4: R$ 3.000,00) via POST HTTP e atualizar Patrimônio Total", 
    $idInvestimento > 0 && (float)$res_i['patrimonio'] === 5500.00); // 2500 reserva + 3000 inv

// Teste 6.2: Editar Investimento via POST para edit_investment.php
$novoValorInv = 3200.00; // R$ 200,00 a mais
http_request("$baseUrl/edit_investment.php", 'POST', [
    'id' => $idInvestimento,
    'descricao' => 'Ações PETR4 Editadas',
    'valor' => $novoValorInv,
    'data_investimento' => '2026-07-15',
    'observacoes' => '100 cotas'
], $cookieFile);

// Validação no banco
$stmt_check_i2 = $conexao->prepare("SELECT patrimonio FROM finance_data WHERE user_id = ?");
$stmt_check_i2->bind_param("i", $idUsuarioTeste);
$stmt_check_i2->execute();
$res_i2 = $stmt_check_i2->get_result()->fetch_assoc();
assertTest("Editar Investimento (Para R$ 3.200,00) via POST HTTP e atualizar Patrimônio Total", 
    (float)$res_i2['patrimonio'] === 5700.00);


if (php_sapi_name() === 'cli') {
    echo "\n" . CLR_INFO . "--- 7. ANÁLISE DE SAÚDE FINANCEIRA (SALÁRIO VS GASTOS) ---" . CLR_RESET . "\n";
} else {
    echo "<h2>7. Análise de Saúde Financeira (Salário vs. Gastos)</h2>";
}

// Realiza a leitura consolidada da vida financeira no banco de dados.
// Salário = R$ 5.000,00.
// Gastos fixos ativos = R$ 1.500,00 (Aluguel, pendente).
// Gastos do Cartão pendentes = Celular (R$ 1000 pendente) + Parcela Geladeira (R$ 400 pendente) = R$ 1.400,00.
// Gastos Totais = R$ 2.900,00.
// Saldo = 5000 - 2900 = +2100.00.

$stmt_resumo = $conexao->prepare("SELECT salario, gastos_mes, cartao FROM finance_data WHERE user_id = ?");
$stmt_resumo->bind_param("i", $idUsuarioTeste);
$stmt_resumo->execute();
$resumo = $stmt_resumo->get_result()->fetch_assoc();

$salarioFinal = (float)$resumo['salario'];
$gastosMes = (float)$resumo['gastos_mes']; // R$ 1500,00
$gastosCartao = (float)$resumo['cartao'];    // R$ 1400,00
$gastosTotais = $gastosMes + $gastosCartao;  // R$ 2.900,00

$saldoFinal = $salarioFinal - $gastosTotais; // +R$ 2100.00
$estaPositivo = ($saldoFinal >= 0);

assertTest("Leitura correta do Salário consolidado do usuário (R$ " . number_format($salarioFinal, 2, ',', '.') . ")", $salarioFinal === 5000.0);
assertTest("Leitura correta dos Gastos consolidados do usuário (R$ " . number_format($gastosTotais, 2, ',', '.') . ")", $gastosTotais === 2900.0);

// Cenário 1: Vida Financeira POSITIVA
if (php_sapi_name() === 'cli') {
    echo "\n" . CLR_INFO . "Cenário A: Salário (R$ 5.000,00) > Gastos (R$ 2.900,00)" . CLR_RESET . "\n";
    echo "  Salário:        R$ " . number_format($salarioFinal, 2, ',', '.') . "\n";
    echo "  Gastos Totais:  R$ " . number_format($gastosTotais, 2, ',', '.') . "\n";
    echo "  Saldo Líquido:  R$ " . number_format($saldoFinal, 2, ',', '.') . "\n";
    echo "  Resultado:      " . ($estaPositivo ? CLR_SUCCESS . "POSITIVO (Saudável)" . CLR_RESET : CLR_FAILED . "NEGATIVO" . CLR_RESET) . "\n";
} else {
    echo "<div class='summary-card'>";
    echo "<h3>Cenário A: Salário (R$ 5.000,00) > Gastos (R$ 2.900,00)</h3>";
    echo "<p>Salário Cadastrado: <strong>R$ " . number_format($salarioFinal, 2, ',', '.') . "</strong></p>";
    echo "<p>Gastos Totais Ativos: <strong>R$ " . number_format($gastosTotais, 2, ',', '.') . "</strong> (Mensal: R$ " . number_format($gastosMes, 2, ',', '.') . " + Fatura Cartão: R$ " . number_format($gastosCartao, 2, ',', '.') . ")</p>";
    echo "<p>Saldo Líquido: <strong class='" . ($estaPositivo ? "positive" : "negative") . "'>R$ " . number_format($saldoFinal, 2, ',', '.') . "</strong></p>";
    echo "<p>Resultado da Vida Financeira: <span class='" . ($estaPositivo ? "positive" : "negative") . "'>" . ($estaPositivo ? "POSITIVO (Saudável)" : "NEGATIVO (Alerta)") . "</span></p>";
    echo "</div>";
}

// Cenário 2: Vida Financeira NEGATIVA (Simulação com Salário = R$ 2.000,00)
$salarioMenor = 2000.00;
$saldoNegativo = $salarioMenor - $gastosTotais; // -900.00
$estaNegativo = ($saldoNegativo < 0);

if (php_sapi_name() === 'cli') {
    echo "\n" . CLR_INFO . "Cenário B (Simulação): Salário (R$ 2.000,00) < Gastos (R$ 2.900,00)" . CLR_RESET . "\n";
    echo "  Salário:        R$ " . number_format($salarioMenor, 2, ',', '.') . "\n";
    echo "  Gastos Totais:  R$ " . number_format($gastosTotais, 2, ',', '.') . "\n";
    echo "  Saldo Líquido:  R$ " . number_format($saldoNegativo, 2, ',', '.') . "\n";
    echo "  Resultado:      " . ($estaNegativo ? CLR_FAILED . "NEGATIVO (Crítico)" . CLR_RESET : CLR_SUCCESS . "POSITIVO" . CLR_RESET) . "\n";
} else {
    echo "<div class='summary-card'>";
    echo "<h3>Cenário B (Simulação): Salário (R$ 2.000,00) < Gastos (R$ 2.900,00)</h3>";
    echo "<p>Salário Cadastrado: <strong>R$ " . number_format($salarioMenor, 2, ',', '.') . "</strong></p>";
    echo "<p>Gastos Totais Ativos: <strong>R$ " . number_format($gastosTotais, 2, ',', '.') . "</strong></p>";
    echo "<p>Saldo Líquido: <strong class='negative'>R$ " . number_format($saldoNegativo, 2, ',', '.') . "</strong></p>";
    echo "<p>Resultado da Vida Financeira: <span class='negative'>NEGATIVO (Crítico)</span></p>";
    echo "</div>";
}


if (php_sapi_name() === 'cli') {
    echo "\n" . CLR_INFO . "--- 8. LIMPEZA AUTOMÁTICA DOS DADOS DE TESTE (CLEANUP) ---" . CLR_RESET . "\n";
} else {
    echo "<h2>8. Limpeza Automática dos Dados de Teste (Cleanup)</h2>";
}

// Remove arquivo de cookies temporário
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

// Limpa todos os dados de teste do banco de dados remoto
$conexao->query("DELETE FROM reservas WHERE user_id = $idUsuarioTeste");
$conexao->query("DELETE FROM investimentos WHERE user_id = $idUsuarioTeste");
$conexao->query("DELETE FROM despesas_cartao WHERE user_id = $idUsuarioTeste");
$conexao->query("DELETE FROM cartoes WHERE user_id = $idUsuarioTeste");
$conexao->query("DELETE FROM despesas WHERE user_id = $idUsuarioTeste");
$conexao->query("DELETE FROM finance_data WHERE user_id = $idUsuarioTeste");
$conexao->query("DELETE FROM users WHERE idusers = $idUsuarioTeste");

// Validar se a limpeza foi concluída
$res_user_check = $conexao->query("SELECT idusers FROM users WHERE idusers = $idUsuarioTeste");
$cleanOk = ($res_user_check->num_rows === 0);
assertTest("Limpeza completa de todos os dados do usuário temporário no banco de dados", $cleanOk);


// RESULTADO FINAL DOS TESTES
if (php_sapi_name() === 'cli') {
    echo "\n========================================================\n";
    echo " RESULTADO DOS TESTES E2E: \n";
    echo " Total de Testes: $totalTests \n";
    echo " Passou:          " . CLR_SUCCESS . "$passedTests" . CLR_RESET . " / $totalTests \n";
    if (!empty($errors)) {
        echo " Falhas detectadas:\n";
        foreach ($errors as $err) {
            echo "   - $err\n";
        }
    } else {
        echo CLR_SUCCESS . " TODOS OS TESTES PASSARAM COM SUCESSO! \n" . CLR_RESET;
    }
    echo "========================================================\n";
} else {
    echo "<h2>Resultado Consolidado</h2>";
    echo "<div class='summary-card'>";
    echo "<p>Total de Testes E2E Executados: <strong>$totalTests</strong></p>";
    echo "<p>Sucessos: <strong class='positive'>$passedTests</strong> / $totalTests</p>";
    if (!empty($errors)) {
        echo "<p class='negative'>Ocorreram falhas nos seguintes testes:</p><ul>";
        foreach ($errors as $err) {
            echo "<li>$err</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='positive'>🏆 TODOS OS TESTES INDIVIDUAIS ONLINE PASSARAM COM SUCESSO!</p>";
    }
    echo "</div>";
    echo "</body></html>";
}
?>
