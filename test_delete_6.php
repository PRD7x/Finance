<?php
require 'config.php';
$id_despesa = 6;
$user_id = 2;

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
        echo "Deleted successfully!\n";
        // Subtrai os valores do painel finance_data
        if ($tipo === 'Fixa') {
            $stmt_upd = $conexao->prepare("UPDATE finance_data SET gastos_fixos = gastos_fixos - ?, gastos_mes = gastos_mes - ? WHERE user_id = ?");
            $stmt_upd->bind_param("ddi", $valor, $valor, $user_id);
        } else {
            $stmt_upd = $conexao->prepare("UPDATE finance_data SET gastos_mes = gastos_mes - ? WHERE user_id = ?");
            $stmt_upd->bind_param("di", $valor, $user_id);
        }
        if ($stmt_upd->execute()) {
            echo "Updated finance_data successfully!\n";
        } else {
            echo "Failed to update finance_data: " . $stmt_upd->error . "\n";
        }
    } else {
        echo "Failed to delete: " . $stmt_del->error . "\n";
    }
} else {
    echo "No row found\n";
}
