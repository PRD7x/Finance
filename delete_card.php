<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

if (isset($_GET['id'])) {
    $cartao_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // 1. Busca a fatura_atual do cartão para reverter do dashboard principal (finance_data)
    $stmt_find = $conexao->prepare("SELECT fatura_atual FROM cartoes WHERE id = ? AND user_id = ?");
    $stmt_find->bind_param("ii", $cartao_id, $user_id);
    $stmt_find->execute();
    $res = $stmt_find->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $fatura_atual = (float)$row['fatura_atual'];
        
        // Iniciando transação para garantir integridade dos dados
        $conexao->begin_transaction();
        
        try {
            // 2. Apaga as despesas vinculadas a este cartão
            $stmt_del_expenses = $conexao->prepare("DELETE FROM despesas_cartao WHERE cartao_id = ? AND user_id = ?");
            $stmt_del_expenses->bind_param("ii", $cartao_id, $user_id);
            $stmt_del_expenses->execute();
            
            // 3. Apaga o cartão
            $stmt_del_card = $conexao->prepare("DELETE FROM cartoes WHERE id = ? AND user_id = ?");
            $stmt_del_card->bind_param("ii", $cartao_id, $user_id);
            $stmt_del_card->execute();
            
            // 4. Deduz a fatura_atual do cartão do total de faturas no painel principal (finance_data)
            $stmt_upd_finance = $conexao->prepare("UPDATE finance_data SET cartao = cartao - ? WHERE user_id = ?");
            $stmt_upd_finance->bind_param("di", $fatura_atual, $user_id);
            $stmt_upd_finance->execute();
            
            // Confirma as alterações
            $conexao->commit();
            
            header("Location: cards.php?success=deleted");
            exit();
        } catch (Exception $e) {
            $conexao->rollback();
            die("Erro ao excluir o cartão: " . $e->getMessage());
        }
    } else {
        die("Cartão não encontrado ou não pertence a este usuário.");
    }
}

header("Location: cards.php");
exit();
?>
