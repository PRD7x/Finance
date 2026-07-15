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
    $data_hoje = date('Y-m-d');
    
    // Buscar a despesa de cartão
    $stmt_find = $conexao->prepare("SELECT valor_total, cartao_id FROM despesas_cartao WHERE id = ? AND user_id = ? AND status != 'Quitada'");
    $stmt_find->bind_param("ii", $id_despesa, $user_id);
    $stmt_find->execute();
    $res = $stmt_find->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $valor = (float)$row['valor_total'];
        $cartao_id = $row['cartao_id'];
        
        // Atualiza status e data_quitacao
        $stmt_upd = $conexao->prepare("UPDATE despesas_cartao SET status = 'Quitada', data_quitacao = ? WHERE id = ?");
        $stmt_upd->bind_param("si", $data_hoje, $id_despesa);
        
        if ($stmt_upd->execute()) {
            // Reduz o total de fatura do cartão no painel financeiro principal
            $stmt_fin = $conexao->prepare("UPDATE finance_data SET cartao = cartao - ? WHERE user_id = ?");
            $stmt_fin->bind_param("di", $valor, $user_id);
            $stmt_fin->execute();
            
            // Restaura o limite do cartão específico e diminui sua fatura atual
            $stmt_card = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual - ?, limite_disponivel = limite_disponivel + ? WHERE id = ?");
            $stmt_card->bind_param("ddi", $valor, $valor, $cartao_id);
            $stmt_card->execute();
        }
    }
}
header("Location: cards.php");
exit();
?>
