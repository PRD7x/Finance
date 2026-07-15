<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $user_id = $_SESSION['user_id'];
    
    $tipo_despesa_novo = $_POST['tipo_despesa'];
    $nome = trim($_POST['nome']);
    $valor_novo = floatval($_POST['valor']);
    $dia_vencimento = intval($_POST['dia_vencimento']);
    $categoria_item = $_POST['categoria_item']; 
    $observacoes = trim($_POST['observacoes']);

    if (!empty($nome) && $valor_novo > 0) {
        
        $stmt_old = $conexao->prepare("SELECT valor, tipo_despesa FROM despesas WHERE id = ? AND user_id = ?");
        $stmt_old->bind_param("ii", $id, $user_id);
        $stmt_old->execute();
        $res = $stmt_old->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $valor_antigo = (float)$row['valor'];
            $tipo_despesa_antigo = $row['tipo_despesa'];
            
            $stmt_upd = $conexao->prepare("UPDATE despesas SET tipo_despesa=?, nome=?, valor=?, dia_vencimento=?, categoria_item=?, observacoes=? WHERE id=?");
            $stmt_upd->bind_param("ssdissi", $tipo_despesa_novo, $nome, $valor_novo, $dia_vencimento, $categoria_item, $observacoes, $id);
            
            if ($stmt_upd->execute()) {
                
                if ($tipo_despesa_antigo === $tipo_despesa_novo) {
                    $diferenca = $valor_novo - $valor_antigo;
                    
                    if ($tipo_despesa_novo === 'Fixa') {
                        $stmt_fin = $conexao->prepare("UPDATE finance_data SET gastos_fixos = gastos_fixos + ?, gastos_mes = gastos_mes + ? WHERE user_id = ?");
                        $stmt_fin->bind_param("ddi", $diferenca, $diferenca, $user_id);
                        $stmt_fin->execute();
                    } else {
                        $stmt_fin = $conexao->prepare("UPDATE finance_data SET gastos_mes = gastos_mes + ? WHERE user_id = ?");
                        $stmt_fin->bind_param("di", $diferenca, $user_id);
                        $stmt_fin->execute();
                    }
                } else {
                    // Mudou o tipo (Fixa <-> Variável)
                    // 1. Remove o antigo
                    if ($tipo_despesa_antigo === 'Fixa') {
                        $stmt_rem = $conexao->prepare("UPDATE finance_data SET gastos_fixos = gastos_fixos - ?, gastos_mes = gastos_mes - ? WHERE user_id = ?");
                        $stmt_rem->bind_param("ddi", $valor_antigo, $valor_antigo, $user_id);
                        $stmt_rem->execute();
                    } else {
                        $stmt_rem = $conexao->prepare("UPDATE finance_data SET gastos_mes = gastos_mes - ? WHERE user_id = ?");
                        $stmt_rem->bind_param("di", $valor_antigo, $user_id);
                        $stmt_rem->execute();
                    }
                    
                    // 2. Adiciona o novo
                    if ($tipo_despesa_novo === 'Fixa') {
                        $stmt_add = $conexao->prepare("UPDATE finance_data SET gastos_fixos = gastos_fixos + ?, gastos_mes = gastos_mes + ? WHERE user_id = ?");
                        $stmt_add->bind_param("ddi", $valor_novo, $valor_novo, $user_id);
                        $stmt_add->execute();
                    } else {
                        $stmt_add = $conexao->prepare("UPDATE finance_data SET gastos_mes = gastos_mes + ? WHERE user_id = ?");
                        $stmt_add->bind_param("di", $valor_novo, $user_id);
                        $stmt_add->execute();
                    }
                }

                header("Location: expenses.php?success=1");
                exit();
            }
        }
    }
}
header("Location: expenses.php?error=1");
exit();
?>
