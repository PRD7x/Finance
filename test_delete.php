<?php
require 'config.php';
$id = 3;
$user_id = 2;
$stmt = $conexao->prepare("SELECT valor, tipo_despesa FROM despesas WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
print_r($row);

if ($row) {
    $valor = (float)$row['valor'];
    $tipo = $row['tipo_despesa'];
    echo "Tipo: $tipo, Valor: $valor\n";
    
    // Simulate delete
    $stmt_del = $conexao->prepare("DELETE FROM despesas WHERE id = ?");
    $stmt_del->bind_param("i", $id);
    $res_del = $stmt_del->execute();
    echo "Delete success: " . ($res_del ? 'true' : 'false') . "\n";
    if (!$res_del) {
        echo "Error: " . $stmt_del->error . "\n";
    }

    if ($res_del) {
        // Subtrai os valores do painel finance_data
        if ($tipo === 'Fixa') {
            $stmt_upd = $conexao->prepare("UPDATE finance_data SET gastos_fixos = gastos_fixos - ?, gastos_mes = gastos_mes - ? WHERE user_id = ?");
            $stmt_upd->bind_param("ddi", $valor, $valor, $user_id);
        } else {
            $stmt_upd = $conexao->prepare("UPDATE finance_data SET gastos_mes = gastos_mes - ? WHERE user_id = ?");
            $stmt_upd->bind_param("di", $valor, $user_id);
        }
        $res_upd = $stmt_upd->execute();
        echo "Update success: " . ($res_upd ? 'true' : 'false') . "\n";
        if (!$res_upd) {
            echo "Error upd: " . $stmt_upd->error . "\n";
        }
    }
} else {
    echo "No row found\n";
}
