<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

if (isset($_GET['id'])) {
    $id_despesa = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // Busca a despesa de cartão quitada
    $stmt_find = $conexao->prepare("SELECT valor_total, cartao_id, status, parcelas FROM despesas_cartao WHERE id = ? AND user_id = ? AND status = 'Quitado'");
    $stmt_find->bind_param("ii", $id_despesa, $user_id);
    $stmt_find->execute();
    $res = $stmt_find->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $valor_total = (float)$row['valor_total'];
        $cartao_id = $row['cartao_id'];
        $parcelas_str = $row['parcelas'];
        
        // Determina a parcela atual e o total de parcelas
        $parcela_atual = 1;
        $total_parcelas = 1;
        if (strpos($parcelas_str, '/') !== false) {
            $partes = explode('/', $parcelas_str);
            $parcela_atual = max(1, intval($partes[0]));
            $total_parcelas = max(1, intval($partes[1]));
        } else if (preg_match('/^(\d+)/', trim($parcelas_str), $matches)) {
            $total_parcelas = max(1, intval($matches[1]));
        }
        
        if ($total_parcelas > 1) {
            // Se for parcelado, decrementa uma parcela atual (ex: de 12/12 para 11/12)
            $nova_parcela_atual = max(1, $parcela_atual - 1);
            $nova_parcelas_str = $nova_parcela_atual . '/' . $total_parcelas;
            $valor_reverter = $valor_total / $total_parcelas;
            
            $stmt_upd = $conexao->prepare("UPDATE despesas_cartao SET parcelas = ?, status = 'Pendente', data_quitacao = NULL WHERE id = ?");
            $stmt_upd->bind_param("si", $nova_parcelas_str, $id_despesa);
        } else {
            // Se for parcela única
            $valor_reverter = $valor_total;
            $stmt_upd = $conexao->prepare("UPDATE despesas_cartao SET status = 'Pendente', data_quitacao = NULL WHERE id = ?");
            $stmt_upd->bind_param("i", $id_despesa);
        }
        
        if ($stmt_upd->execute()) {
            // Adiciona o valor de volta no total de faturas do painel principal
            $stmt_fin = $conexao->prepare("UPDATE finance_data SET cartao = cartao + ? WHERE user_id = ?");
            $stmt_fin->bind_param("di", $valor_reverter, $user_id);
            $stmt_fin->execute();
            
            // Reduz o limite disponível e aumenta a fatura atual do cartão específico
            $stmt_card = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual + ?, limite_disponivel = limite_disponivel - ? WHERE id = ?");
            $stmt_card->bind_param("ddi", $valor_reverter, $valor_reverter, $cartao_id);
            $stmt_card->execute();
        }
    }
}
header("Location: cards.php?tab=despesas");
exit();
?>
