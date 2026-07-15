<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Debug: Sessão não encontrada. Usuário não está logado.");
}

require_once 'config.php';

if (!isset($_GET['id'])) {
    die("Debug: ID não foi fornecido na URL.");
}

$id_despesa = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Primeiro, busca a despesa para saber o valor e tipo antes de excluir
$stmt = $conexao->prepare("SELECT valor, tipo_despesa FROM despesas WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id_despesa, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $valor = (float)$row['valor'];
    $tipo = $row['tipo_despesa'];
    
    // Exclui a despesa
    $stmt_del = $conexao->prepare("DELETE FROM despesas WHERE id = ?");
    $stmt_del->bind_param("i", $id_despesa);
    
    if ($stmt_del->execute()) {
        // Subtrai os valores do painel finance_data
        if ($tipo === 'Fixa') {
            $stmt_upd = $conexao->prepare("UPDATE finance_data SET gastos_fixos = gastos_fixos - ?, gastos_mes = gastos_mes - ? WHERE user_id = ?");
            $stmt_upd->bind_param("ddi", $valor, $valor, $user_id);
        } else {
            $stmt_upd = $conexao->prepare("UPDATE finance_data SET gastos_mes = gastos_mes - ? WHERE user_id = ?");
            $stmt_upd->bind_param("di", $valor, $user_id);
        }
        if (!$stmt_upd->execute()) {
            die("Debug: Erro ao atualizar finance_data: " . $stmt_upd->error);
        }
        
        // Sucesso
        header("Location: expenses.php");
        exit();
    } else {
        die("Debug: Erro ao tentar executar o DELETE no MySQL: " . $stmt_del->error);
    }
} else {
    die("Debug: Despesa com id $id_despesa e user_id $user_id não foi encontrada no banco.");
}
