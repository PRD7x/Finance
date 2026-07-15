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
    $valor = floatval($_POST['valor']);
    $data_investimento = $_POST['data_investimento'];
    $observacoes = trim($_POST['observacoes']);

    if (!empty($descricao) && $valor > 0 && !empty($data_investimento)) {
        // Insere o investimento na tabela
        $stmt = $conexao->prepare("INSERT INTO investimentos (user_id, descricao, valor, data_investimento, observacoes) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isdss", $user_id, $descricao, $valor, $data_investimento, $observacoes);
        
        if ($stmt->execute()) {
            // Atualiza também o "Patrimônio" na tabela finance_data para refletir no Dashboard
            $stmt_update = $conexao->prepare("UPDATE finance_data SET patrimonio = patrimonio + ? WHERE user_id = ?");
            $stmt_update->bind_param("di", $valor, $user_id);
            $stmt_update->execute();

            // Redireciona de volta para o nome correto do arquivo em inglês
            header("Location: investments.php?success=1");
            exit();
        }
    }
}

// Se der erro ou acesso direto
header("Location: investments.php?error=1");
exit();
?>