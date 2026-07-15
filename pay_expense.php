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
    
    // Atualiza a despesa para "Pago" e preenche a data de quitação
    $stmt = $conexao->prepare("UPDATE despesas SET status = 'Pago', data_quitacao = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $data_hoje, $id_despesa, $user_id);
    $stmt->execute();
}

// Redireciona de volta para a aba do histórico
header("Location: expenses.php?tab=quitados");
exit();
?>