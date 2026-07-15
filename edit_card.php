<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $user_id = $_SESSION['user_id'];
    
    $nome_cartao = trim($_POST['nome_cartao']);
    $bandeira = trim($_POST['bandeira']);
    $limite = floatval($_POST['limite']);
    $dia_fechamento = intval($_POST['dia_fechamento']);
    $dia_vencimento = intval($_POST['dia_vencimento']);
    $cor = trim($_POST['cor']);
    
    // Verifica se a cor é HEX (se não for, garante #0f172a)
    if (!preg_match('/^#[a-f0-9]{6}$/i', $cor)) {
        // Se vier como 'roxo', converte para hex
        $mapa_cores = [
            'roxo' => '#8b5cf6',
            'azul' => '#3b82f6',
            'verde' => '#10b981',
            'laranja' => '#f59e0b',
            'vermelho' => '#ef4444',
            'preto' => '#1e293b'
        ];
        if (isset($mapa_cores[strtolower($cor)])) {
            $cor = $mapa_cores[strtolower($cor)];
        } else {
            $cor = '#1e293b';
        }
    }

    if (!empty($nome_cartao) && $limite > 0) {
        
        // Pega o cartão antigo para ver se o limite total mudou (para ajustar limite disponível)
        $stmt_old = $conexao->prepare("SELECT limite_total FROM cartoes WHERE id = ? AND user_id = ?");
        $stmt_old->bind_param("ii", $id, $user_id);
        $stmt_old->execute();
        $res = $stmt_old->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $limite_antigo = (float)$row['limite_total'];
            $diferenca_limite = $limite - $limite_antigo;
            
            $stmt = $conexao->prepare("UPDATE cartoes SET nome_cartao=?, bandeira=?, limite_total=?, limite_disponivel=limite_disponivel+?, dia_fechamento=?, dia_vencimento=?, cor=? WHERE id=? AND user_id=?");
            $stmt->bind_param("ssddiissi", $nome_cartao, $bandeira, $limite, $diferenca_limite, $dia_fechamento, $dia_vencimento, $cor, $id, $user_id);
            
            if ($stmt->execute()) {
                header("Location: cards.php?success=2");
                exit();
            } else {
                die("Erro na Base de Dados: " . $stmt->error);
            }
        }
    }
}

header("Location: cards.php?error=2");
exit();
?>
