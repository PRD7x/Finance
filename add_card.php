<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    $nome_cartao = trim($_POST['nome_cartao']);
    $bandeira = $_POST['bandeira'];
    $cor = $_POST['cor']; // Recebe a nova cor
    $limite_total = floatval($_POST['limite_total']);
    
    // Adicionados de volta os dias de fechamento e vencimento
    $dia_fechamento = isset($_POST['dia_fechamento']) ? intval($_POST['dia_fechamento']) : 1;
    $dia_vencimento = isset($_POST['dia_vencimento']) ? intval($_POST['dia_vencimento']) : 10;
    
    $limite_disponivel = $limite_total;
    $fatura_atual = 0.00;
    
    if (!empty($nome_cartao) && $limite_total > 0) {
        
        $stmt = $conexao->prepare("INSERT INTO cartoes (user_id, nome_cartao, bandeira, cor, limite_total, limite_disponivel, fatura_atual, dia_fechamento, dia_vencimento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdddii", $user_id, $nome_cartao, $bandeira, $cor, $limite_total, $limite_disponivel, $fatura_atual, $dia_fechamento, $dia_vencimento);
        
        if ($stmt->execute()) {
            header("Location: cards.php?success=1");
            exit();
        }
    }
}

header("Location: cards.php?error=1");
exit();
?>