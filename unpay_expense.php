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
    
    // Busca o tipo de despesa para saber para qual aba redirecionar
    $tipo = 'fixas';
    $stmt_tipo = $conexao->prepare("SELECT tipo_despesa FROM despesas WHERE id = ? AND user_id = ?");
    $stmt_tipo->bind_param("ii", $id_despesa, $user_id);
    $stmt_tipo->execute();
    $res_tipo = $stmt_tipo->get_result();
    if ($row_tipo = $res_tipo->fetch_assoc()) {
        $tipo = ($row_tipo['tipo_despesa'] === 'Fixa') ? 'fixas' : 'variaveis';
    }
    
    // Atualiza a despesa para "Pendente" e limpa a data de quitação
    $stmt = $conexao->prepare("UPDATE despesas SET status = 'Pendente', data_quitacao = NULL WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id_despesa, $user_id);
    $stmt->execute();
}

// Redireciona de volta para a aba correspondente
header("Location: expenses.php?tab=" . $tipo);
exit();
?>
