<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $user_id = $_SESSION['user_id'];
    $descricao = trim($_POST['descricao']);
    $valor_novo = floatval($_POST['valor']);
    $data_investimento = $_POST['data_investimento'];
    $observacoes = trim($_POST['observacoes']);

    if (!empty($descricao) && $valor_novo > 0 && !empty($data_investimento)) {
        // Pega o valor antigo
        $stmt_old = $conexao->prepare("SELECT valor FROM investimentos WHERE id = ? AND user_id = ?");
        $stmt_old->bind_param("ii", $id, $user_id);
        $stmt_old->execute();
        $res = $stmt_old->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $valor_antigo = (float)$row['valor'];
            $diferenca = $valor_novo - $valor_antigo;

            $stmt_upd = $conexao->prepare("UPDATE investimentos SET descricao=?, valor=?, data_investimento=?, observacoes=? WHERE id=?");
            $stmt_upd->bind_param("sdssi", $descricao, $valor_novo, $data_investimento, $observacoes, $id);
            
            if ($stmt_upd->execute()) {
                // Atualiza o Patrimonio com a diferença (+ ou -)
                $stmt_fin = $conexao->prepare("UPDATE finance_data SET patrimonio = patrimonio + ? WHERE user_id = ?");
                $stmt_fin->bind_param("di", $diferenca, $user_id);
                $stmt_fin->execute();

                header("Location: investments.php?success=1");
                exit();
            }
        }
    }
}
header("Location: investments.php?error=1");
exit();
?>
