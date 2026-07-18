<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cartao_id = intval($_POST['cartao_id']);
    $mes = intval($_POST['mes']);
    $ano = intval($_POST['ano']);
    $user_id = $_SESSION['user_id'];
    $data_hoje = date('Y-m-d');
    
    if ($cartao_id > 0 && $mes >= 1 && $mes <= 12 && $ano > 2000) {
        
        // Busca as despesas pendentes deste cartão, deste usuário, compradas no mês e ano selecionados
        $stmt_find = $conexao->prepare("
            SELECT id, valor_total, cartao_id, parcelas, status 
            FROM despesas_cartao 
            WHERE cartao_id = ? 
              AND user_id = ? 
              AND status != 'Quitado' 
              AND MONTH(data_compra) = ? 
              AND YEAR(data_compra) = ?
        ");
        $stmt_find->bind_param("iiii", $cartao_id, $user_id, $mes, $ano);
        $stmt_find->execute();
        $res = $stmt_find->get_result();
        
        $total_despesas = $res->num_rows;
        
        if ($total_despesas > 0) {
            $conexao->begin_transaction();
            try {
                while ($row = $res->fetch_assoc()) {
                    $id_despesa = $row['id'];
                    $valor_total = (float)$row['valor_total'];
                    $parcelas_str = $row['parcelas'];
                    
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
                        // Despesa parcelada: adianta apenas uma parcela
                        $valor_parcela = $valor_total / $total_parcelas;
                        $parcela_atual++;
                        $nova_parcelas_str = $parcela_atual . '/' . $total_parcelas;
                        $novo_status = ($parcela_atual >= $total_parcelas) ? 'Quitado' : 'Pendente';
                        
                        // 1. Atualiza despesa
                        $stmt_upd = $conexao->prepare("UPDATE despesas_cartao SET parcelas = ?, status = ?, data_quitacao = ? WHERE id = ?");
                        $stmt_upd->bind_param("sssi", $nova_parcelas_str, $novo_status, $data_hoje, $id_despesa);
                        $stmt_upd->execute();
                        
                        // 2. Reduz o painel financeiro principal
                        $stmt_fin = $conexao->prepare("UPDATE finance_data SET cartao = cartao - ? WHERE user_id = ?");
                        $stmt_fin->bind_param("di", $valor_parcela, $user_id);
                        $stmt_fin->execute();
                        
                        // 3. Restaura o limite do cartão e diminui a fatura
                        $stmt_card = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual - ?, limite_disponivel = limite_disponivel + ? WHERE id = ?");
                        $stmt_card->bind_param("ddi", $valor_parcela, $valor_parcela, $cartao_id);
                        $stmt_card->execute();
                    } else {
                        // Parcela única: quita totalmente a despesa
                        // 1. Atualiza despesa para Quitado
                        $stmt_upd = $conexao->prepare("UPDATE despesas_cartao SET status = 'Quitado', data_quitacao = ? WHERE id = ?");
                        $stmt_upd->bind_param("si", $data_hoje, $id_despesa);
                        $stmt_upd->execute();
                        
                        // 2. Reduz o painel financeiro principal
                        $stmt_fin = $conexao->prepare("UPDATE finance_data SET cartao = cartao - ? WHERE user_id = ?");
                        $stmt_fin->bind_param("di", $valor_total, $user_id);
                        $stmt_fin->execute();
                        
                        // 3. Restaura o limite do cartão e diminui a fatura
                        $stmt_card = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual - ?, limite_disponivel = limite_disponivel + ? WHERE id = ?");
                        $stmt_card->bind_param("ddi", $valor_total, $valor_total, $cartao_id);
                        $stmt_card->execute();
                    }
                }
                
                $conexao->commit();
                header("Location: cards.php?tab=cartoes&success=invoice_paid&count=" . $total_despesas);
                exit();
            } catch (Exception $e) {
                $conexao->rollback();
                die("Erro ao processar pagamento da fatura: " . $e->getMessage());
            }
        } else {
            // Nenhuma despesa pendente comprada nesse mês
            header("Location: cards.php?tab=cartoes&info=no_expenses");
            exit();
        }
    }
}

header("Location: cards.php");
exit();
?>
