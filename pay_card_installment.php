<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Debug: Usuário não autenticado.");
}

require_once 'config.php';

if (isset($_GET['id'])) {
    $id_despesa = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    $data_hoje = date('Y-m-d');
    
    // Busca a despesa
    $stmt_find = $conexao->prepare("SELECT valor_total, cartao_id, parcelas, parcelas_pagas FROM despesas_cartao WHERE id = ? AND user_id = ? AND status != 'Quitada'");
    $stmt_find->bind_param("ii", $id_despesa, $user_id);
    $stmt_find->execute();
    $res = $stmt_find->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $valor_total = (float)$row['valor_total'];
        $cartao_id = $row['cartao_id'];
        $parcelas_str = $row['parcelas'];
        $parcelas_pagas = (int)$row['parcelas_pagas'];
        
        // Tenta extrair o total de parcelas do inicio da string (ex: "4x", "4/", "4 ")
        $total_parcelas = 1;
        if (preg_match('/^(\d+)/', trim($parcelas_str), $matches)) {
            $total_parcelas = max(1, intval($matches[1]));
        }
        
        // Calcula o valor de UMA parcela
        $valor_parcela = $valor_total / $total_parcelas;
        
        // Incrementa as parcelas pagas
        $parcelas_pagas++;
        
        // Se pagou todas as parcelas, muda o status para Quitada
        $novo_status = ($parcelas_pagas >= $total_parcelas) ? 'Quitada' : 'Pendente';
        
        $stmt_upd = $conexao->prepare("UPDATE despesas_cartao SET parcelas_pagas = ?, status = ?, data_quitacao = ? WHERE id = ?");
        $stmt_upd->bind_param("issi", $parcelas_pagas, $novo_status, $data_hoje, $id_despesa);
        
        if ($stmt_upd->execute()) {
            // Reduz apenas UMA PARCELA do painel financeiro principal
            $stmt_fin = $conexao->prepare("UPDATE finance_data SET cartao = cartao - ? WHERE user_id = ?");
            $stmt_fin->bind_param("di", $valor_parcela, $user_id);
            $stmt_fin->execute();
            
            // Restaura o limite do cartao com UMA PARCELA
            $stmt_card = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual - ?, limite_disponivel = limite_disponivel + ? WHERE id = ?");
            $stmt_card->bind_param("ddi", $valor_parcela, $valor_parcela, $cartao_id);
            $stmt_card->execute();
        } else {
            die("Debug: Falha ao atualizar despesa: " . $stmt_upd->error);
        }
    } else {
        die("Debug: Despesa não encontrada ou já quitada.");
    }
}
header("Location: cards.php");
exit();
?>
