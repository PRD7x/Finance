<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $user_id = $_SESSION['user_id'];
    
    $descricao = trim($_POST['descricao']);
    $instituicao = trim($_POST['instituicao']);
    $tipo_aplicacao = trim($_POST['tipo_aplicacao']);
    $valor_novo = floatval($_POST['valor']);
    $data_atualizacao = $_POST['data_atualizacao'];
    $indexador = $_POST['indexador'];
    $porcentagem_indexador = floatval($_POST['porcentagem_indexador']);
    $observacoes = trim($_POST['observacoes']);

    if (!empty($descricao) && $valor_novo > 0) {
        // Pega o valor antigo
        $stmt_old = $conexao->prepare("SELECT valor FROM reservas WHERE id = ? AND user_id = ?");
        $stmt_old->bind_param("ii", $id, $user_id);
        $stmt_old->execute();
        $res = $stmt_old->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $valor_antigo = (float)$row['valor'];
            $diferenca = $valor_novo - $valor_antigo;

            $stmt_upd = $conexao->prepare("UPDATE reservas SET descricao=?, instituicao=?, tipo_aplicacao=?, valor=?, data_atualizacao=?, indexador=?, porcentagem_indexador=?, observacoes=? WHERE id=?");
            $stmt_upd->bind_param("sssdssdsi", $descricao, $instituicao, $tipo_aplicacao, $valor_novo, $data_atualizacao, $indexador, $porcentagem_indexador, $observacoes, $id);
            
            if ($stmt_upd->execute()) {
                // Atualiza a Reserva e o Patrimonio com a diferença (+ ou -)
                $stmt_fin = $conexao->prepare("UPDATE finance_data SET reserva = reserva + ?, patrimonio = patrimonio + ? WHERE user_id = ?");
                $stmt_fin->bind_param("ddi", $diferenca, $diferenca, $user_id);
                $stmt_fin->execute();

                header("Location: reserve.php?success=1");
                exit();
            }
        }
    }
}
header("Location: reserve.php?error=1");
exit();
?>
