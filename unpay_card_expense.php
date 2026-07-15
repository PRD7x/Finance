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
    $stmt_find = $conexao->prepare("SELECT valor_total, cartao_id, status, parcelas, parcelas_pagas FROM despesas_cartao WHERE id = ? AND user_id = ? AND status = 'Quitada'");
    $stmt_find->bind_param("ii", $id_despesa, $user_id);
    $stmt_find->execute();
    $res = $stmt_find->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $valor_total = (float)$row['valor_total'];
        $cartao_id = $row['cartao_id'];
        $parcelas_str = $row['parcelas'];
        $parcelas_pagas = (int)$row['parcelas_pagas'];
        
        // Determina o número total de parcelas
        $total_parcelas = 1;
        if (preg_match('/^(\d+)/', trim($parcelas_str), $matches)) {
            $total_parcelas = max(1, intval($matches[1]));
        }
        
        if ($total_parcelas > 1) {
            // Se for parcelado, decrementa uma parcela paga
            $nova_parcelas_pagas = max(0, $parcelas_pagas - 1);
            $valor_reverter = $valor_total / $total_parcelas;
            
            $stmt_upd = $conexao->prepare("UPDATE despesas_cartao SET parcelas_pagas = ?, status = 'Pendente', data_quitacao = NULL WHERE id = ?");
            $stmt_upd->bind_param("ii", $nova_parcelas_pagas, $id_despesa);
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
