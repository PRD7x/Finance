<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    // Pega o valor para recalcular a diferença
    $stmt = $conexao->prepare("SELECT valor FROM reservas WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $valor = (float)$row['valor'];
        
        $stmt_del = $conexao->prepare("DELETE FROM reservas WHERE id = ?");
        $stmt_del->bind_param("i", $id);
        if ($stmt_del->execute()) {
            // Remove o valor da reserva e do patrimonio total
            $stmt_upd = $conexao->prepare("UPDATE finance_data SET reserva = reserva - ?, patrimonio = patrimonio - ? WHERE user_id = ?");
            $stmt_upd->bind_param("ddi", $valor, $valor, $user_id);
            $stmt_upd->execute();
        }
    }
}
header("Location: reserve.php");
exit();
?>
