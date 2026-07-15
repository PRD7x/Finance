<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $descricao = trim($_POST['descricao']);
    $cartao_id = intval($_POST['cartao_id']);
    $categoria = $_POST['categoria'];
    $data_compra = $_POST['data_compra'];
    $valor_total = floatval($_POST['valor_total']);
    $parcelas = trim($_POST['parcelas']);
    $status = 'Pendente';
    
    if (!empty($descricao) && $cartao_id > 0 && $valor_total > 0) {
        
        // 1. Insere a compra na tabela do histórico do cartão
        $stmt = $conexao->prepare("INSERT INTO despesas_cartao (user_id, cartao_id, descricao, categoria, valor_total, parcelas, status, data_compra) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissdsss", $user_id, $cartao_id, $descricao, $categoria, $valor_total, $parcelas, $status, $data_compra);
        
        if ($stmt->execute()) {
            
            // 2. Atualiza o Cartão: Aumenta a fatura atual e deduz do limite disponível
            $stmt_cartao = $conexao->prepare("UPDATE cartoes SET fatura_atual = fatura_atual + ?, limite_disponivel = limite_disponivel - ? WHERE id = ? AND user_id = ?");
            $stmt_cartao->bind_param("ddii", $valor_total, $valor_total, $cartao_id, $user_id);
            $stmt_cartao->execute();

            // 3. Atualiza o total de faturas no Dashboard Principal (finance_data)
            $stmt_finance = $conexao->prepare("UPDATE finance_data SET cartao = cartao + ? WHERE user_id = ?");
            $stmt_finance->bind_param("di", $valor_total, $user_id);
            $stmt_finance->execute();

            header("Location: cards.php?success=1");
            exit();
        } else {
            // Se houver um erro de base de dados, isto vai mostrar qual é o erro para ajudar a corrigir
            die("Erro na Base de Dados: " . $stmt->error);
        }
    }
}

header("Location: cards.php?error=1");
exit();
?>