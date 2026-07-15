<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    die("Debug: Usuário não autenticado."); 
}

require_once 'config.php';

if (!isset($_GET['id'])) {
    die("Debug: ID não fornecido.");
}

$id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Busca a despesa
$stmt = $conexao->prepare("SELECT valor_total, cartao_id, status FROM despesas_cartao WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $valor = (float)$row['valor_total'];
    $cartao_id = $row['cartao_id'];
    $status = $row['status'];
    
    $stmt_del = $conexao->prepare("DELETE FROM despesas_cartao WHERE id = ?");
    $stmt_del->bind_param("i", $id);
    
    if ($stmt_del->execute()) {
        if ($status !== 'Quitada') {
            // Se era pendente, precisa reverter do limite, fatura e dashboard
            $stmt_fin = $conexao->prepare("UPDATE finance_data SET cartao = cartao - ? WHERE user_id = ?");
            $stmt_fin->bind_param("di", $valor, $user_id);
            if (!$stmt_fin->execute()) {
                die("Debug: Falha ao atualizar dashboard: " . $stmt_fin->error);
            }
            
            $stmt_card = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual - ?, limite_disponivel = limite_disponivel + ? WHERE id = ?");
            $stmt_card->bind_param("ddi", $valor, $valor, $cartao_id);
            if (!$stmt_card->execute()) {
                die("Debug: Falha ao atualizar limite do cartão: " . $stmt_card->error);
            }
        }
        
        // Sucesso
        header("Location: cards.php");
        exit();
    } else {
        die("Debug: Falha ao deletar do banco: " . $stmt_del->error);
    }
} else {
    die("Debug: Despesa de cartão não encontrada para este id/user_id.");
}
?>
