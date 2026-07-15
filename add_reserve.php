<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Captura os novos campos do formulário
    $descricao = trim($_POST['descricao']);
    $instituicao = trim($_POST['instituicao']);
    $tipo_aplicacao = trim($_POST['tipo_aplicacao']);
    $valor = floatval($_POST['valor']);
    $data_atualizacao = $_POST['data_atualizacao'];
    $indexador = $_POST['indexador'];
    $porcentagem_indexador = floatval($_POST['porcentagem_indexador']);
    $observacoes = trim($_POST['observacoes']);
    
    // A taxa anual já não é inserida à mão, o sistema calculará visualmente
    $taxa_anual = 0;

    if (!empty($descricao) && $valor > 0) {
        // Insere na tabela de reservas com a nova estrutura de colunas
        $stmt = $conexao->prepare("INSERT INTO reservas (user_id, descricao, instituicao, tipo_aplicacao, valor, data_atualizacao, indexador, porcentagem_indexador, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdssds", $user_id, $descricao, $instituicao, $tipo_aplicacao, $valor, $data_atualizacao, $indexador, $porcentagem_indexador, $observacoes);
        
        if ($stmt->execute()) {
            // Atualiza o total da "Reserva" e do "Património" no dashboard
            $stmt_update = $conexao->prepare("UPDATE finance_data SET reserva = reserva + ?, patrimonio = patrimonio + ? WHERE user_id = ?");
            $stmt_update->bind_param("ddi", $valor, $valor, $user_id);
            $stmt_update->execute();

            header("Location: reserve.php?success=1");
            exit();
        }
    }
}

header("Location: reserve.php?error=1");
exit();
?>