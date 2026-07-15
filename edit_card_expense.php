<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $user_id = $_SESSION['user_id'];
    
    $descricao = trim($_POST['descricao']);
    $cartao_id_novo = intval($_POST['cartao_id']);
    $categoria = $_POST['categoria'];
    $data_compra = $_POST['data_compra'];
    $valor_novo = floatval($_POST['valor_total']);
    $parcelas = trim($_POST['parcelas']);

    if (!empty($descricao) && $cartao_id_novo > 0 && $valor_novo > 0) {
        
        // Pega o estado anterior
        $stmt_old = $conexao->prepare("SELECT valor_total, cartao_id, status FROM despesas_cartao WHERE id = ? AND user_id = ?");
        $stmt_old->bind_param("ii", $id, $user_id);
        $stmt_old->execute();
        $res = $stmt_old->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $valor_antigo = (float)$row['valor_total'];
            $cartao_id_antigo = (int)$row['cartao_id'];
            $status = $row['status'];
            
            // Só permite editar despesas que ainda não foram quitadas
            if ($status === 'Pendente') {
                $diferenca = $valor_novo - $valor_antigo;

                $stmt_upd = $conexao->prepare("UPDATE despesas_cartao SET descricao=?, cartao_id=?, categoria=?, data_compra=?, valor_total=?, parcelas=? WHERE id=?");
                $stmt_upd->bind_param("sissssi", $descricao, $cartao_id_novo, $categoria, $data_compra, $valor_novo, $parcelas, $id);
                
                if ($stmt_upd->execute()) {
                    
                    if ($cartao_id_novo === $cartao_id_antigo) {
                        // Mesma cartão: ajusta a diferença
                        $stmt_card = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual + ?, limite_disponivel = limite_disponivel - ? WHERE id = ?");
                        $stmt_card->bind_param("ddi", $diferenca, $diferenca, $cartao_id_antigo);
                        $stmt_card->execute();
                    } else {
                        // Cartão diferente: reverte no antigo, aplica no novo
                        $stmt_reverte = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual - ?, limite_disponivel = limite_disponivel + ? WHERE id = ?");
                        $stmt_reverte->bind_param("ddi", $valor_antigo, $valor_antigo, $cartao_id_antigo);
                        $stmt_reverte->execute();
                        
                        $stmt_aplica = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual + ?, limite_disponivel = limite_disponivel - ? WHERE id = ?");
                        $stmt_aplica->bind_param("ddi", $valor_novo, $valor_novo, $cartao_id_novo);
                        $stmt_aplica->execute();
                    }
                    
                    // Atualiza o dashboard
                    $stmt_fin = $conexao->prepare("UPDATE finance_data SET cartao = cartao + ? WHERE user_id = ?");
                    $stmt_fin->bind_param("di", $diferenca, $user_id);
                    $stmt_fin->execute();

                    header("Location: cards.php?success=1");
                    exit();
                }
            } else {
                // Não permite editar quitadas por segurança
                header("Location: cards.php?error=quitada");
                exit();
            }
        }
    }
}
header("Location: cards.php?error=1");
exit();
?>
