<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $tipo_despesa = $_POST['tipo_despesa']; // 'Fixa' ou 'Variável'
    $nome = trim($_POST['nome']);
    $valor = floatval($_POST['valor']);
    $dia_vencimento = intval($_POST['dia_vencimento']);
    $categoria_item = $_POST['categoria_item']; 
    $observacoes = trim($_POST['observacoes']);
    
    $status = 'Pendente'; 
    $mes_ano = date('m/Y'); // Guarda o mês atual para referência nas variáveis
    
    if (!empty($nome) && $valor > 0) {
        
        $stmt = $conexao->prepare("INSERT INTO despesas (user_id, tipo_despesa, nome, valor, dia_vencimento, categoria_item, observacoes, status, mes_ano) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdissss", $user_id, $tipo_despesa, $nome, $valor, $dia_vencimento, $categoria_item, $observacoes, $status, $mes_ano);
        
        if ($stmt->execute()) {
            
            // Atualiza o painel financeiro principal (Dashboard)
            if ($tipo_despesa === 'Fixa') {
                $stmt_update = $conexao->prepare("UPDATE finance_data SET gastos_fixos = gastos_fixos + ?, gastos_mes = gastos_mes + ? WHERE user_id = ?");
                $stmt_update->bind_param("ddi", $valor, $valor, $user_id);
            } else {
                $stmt_update = $conexao->prepare("UPDATE finance_data SET gastos_mes = gastos_mes + ? WHERE user_id = ?");
                $stmt_update->bind_param("di", $valor, $user_id);
            }
            $stmt_update->execute();

            header("Location: expenses.php?success=1");
            exit();
        }
    }
}

header("Location: expenses.php?error=1");
exit();
?>