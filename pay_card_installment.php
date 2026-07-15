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
    $stmt_find = $conexao->prepare("SELECT valor_total, cartao_id, parcelas FROM despesas_cartao WHERE id = ? AND user_id = ? AND status != 'Quitado'");
    $stmt_find->bind_param("ii", $id_despesa, $user_id);
    $stmt_find->execute();
    $res = $stmt_find->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $valor_total = (float)$row['valor_total'];
        $cartao_id = $row['cartao_id'];
        $parcelas_str = $row['parcelas'];
        
        // Extrai a parcela atual e o total de parcelas a partir do formato "ATUAL/TOTAL"
        $parcela_atual = 1;
        $total_parcelas = 1;
        
        if (strpos($parcelas_str, '/') !== false) {
            $partes = explode('/', $parcelas_str);
            $parcela_atual = max(1, intval($partes[0]));
            $total_parcelas = max(1, intval($partes[1]));
        } else if (preg_match('/^(\d+)/', trim($parcelas_str), $matches)) {
            $total_parcelas = max(1, intval($matches[1]));
        }
        
        // Calcula o valor de UMA parcela
        $valor_parcela = $total_parcelas > 0 ? ($valor_total / $total_parcelas) : $valor_total;
        
        // Incrementa a parcela atual
        $parcela_atual++;
        
        // Gera a nova string de parcelas (ex: "2/12")
        $nova_parcelas_str = $parcela_atual . '/' . $total_parcelas;
        
        // Se pagou todas as parcelas (ex: 12/12), muda o status para Quitado, senão continua Pendente
        $novo_status = ($parcela_atual >= $total_parcelas) ? 'Quitado' : 'Pendente';
        
        $stmt_upd = $conexao->prepare("UPDATE despesas_cartao SET parcelas = ?, status = ?, data_quitacao = ? WHERE id = ?");
        $stmt_upd->bind_param("sssi", $nova_parcelas_str, $novo_status, $data_hoje, $id_despesa);
        
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
