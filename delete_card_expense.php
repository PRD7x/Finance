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
$stmt = $conexao->prepare("SELECT valor_total, cartao_id, status, parcelas FROM despesas_cartao WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $valor = (float)$row['valor_total'];
    $cartao_id = $row['cartao_id'];
    $status = $row['status'];
    $parcelas_str = $row['parcelas'];
    
    $stmt_del = $conexao->prepare("DELETE FROM despesas_cartao WHERE id = ?");
    $stmt_del->bind_param("i", $id);
    
    if ($stmt_del->execute()) {
        if ($status !== 'Quitado') {
            // Calcula o valor real restante que ainda não foi pago/adiantado
            $parcela_atual = 1;
            $total_parcelas = 1;
            if (strpos($parcelas_str, '/') !== false) {
                $partes = explode('/', $parcelas_str);
                $parcela_atual = max(1, intval($partes[0]));
                $total_parcelas = max(1, intval($partes[1]));
            } else if (preg_match('/^(\d+)/', trim($parcelas_str), $matches)) {
                $total_parcelas = max(1, intval($matches[1]));
            }
            
            $valor_restante = $valor;
            if ($total_parcelas > 1) {
                $parcelas_pagas = $parcela_atual - 1;
                $valor_restante = $valor * ($total_parcelas - $parcelas_pagas) / $total_parcelas;
            }
            
            // Se era pendente, precisa reverter do limite, fatura e dashboard o valor restante
            $stmt_fin = $conexao->prepare("UPDATE finance_data SET cartao = cartao - ? WHERE user_id = ?");
            $stmt_fin->bind_param("di", $valor_restante, $user_id);
            if (!$stmt_fin->execute()) {
                die("Debug: Falha ao atualizar dashboard: " . $stmt_fin->error);
            }
            
            $stmt_card = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual - ?, limite_disponivel = limite_disponivel + ? WHERE id = ?");
            $stmt_card->bind_param("ddi", $valor_restante, $valor_restante, $cartao_id);
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
