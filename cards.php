<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$first_letter = strtoupper(substr($email, 0, 1));

// Vai buscar os cartões
$stmt_cartoes = $conexao->prepare("SELECT * FROM cartoes WHERE user_id = ? ORDER BY id DESC");
$stmt_cartoes->bind_param("i", $user_id);
$stmt_cartoes->execute();
$result_cartoes = $stmt_cartoes->get_result();
$lista_cartoes = [];
while($row = $result_cartoes->fetch_assoc()) {
    $lista_cartoes[] = $row;
}

// Vai buscar as despesas dos cartões
$stmt_despesas = $conexao->prepare("
    SELECT d.*, c.nome_cartao, c.cor 
    FROM despesas_cartao d 
    JOIN cartoes c ON d.cartao_id = c.id 
    WHERE d.user_id = ? 
    ORDER BY d.data_compra DESC
");
$stmt_despesas->bind_param("i", $user_id);
$stmt_despesas->execute();
$result_despesas = $stmt_despesas->get_result();

$despesas_pendentes = [];
$despesas_quitadas = [];

while($row = $result_despesas->fetch_assoc()) {
    if ($row['status'] === 'Quitado') {
        $despesas_quitadas[] = $row;
    } else {
        $despesas_pendentes[] = $row;
    }
}

function adjustBrightness($hex, $steps) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $steps = max(-255, min(255, $steps));

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cartões de Crédito - Meu Património</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="css/cards.css">
</head>
<body>

    <?php include 'components/navbar.php'; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" id="alert-msg" style="margin: 20px 40px 0 40px; padding: 12px 20px; background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; border-radius: 8px; color: #10b981; display: flex; align-items: center; gap: 8px; font-size: 14px;">
            <i data-feather="check-circle" style="width: 18px; height: 18px; min-width: 18px;"></i>
            <span>
                <?php 
                    $success_msg = $_GET['success'];
                    if ($success_msg === 'deleted') {
                        echo "Cartão e todas as suas despesas excluídos com sucesso!";
                    } elseif ($success_msg === 'invoice_paid') {
                        $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
                        echo "Fatura paga com sucesso! {$count} compra(s) quitada(s) ou atualizada(s).";
                    } elseif ($success_msg === '1') {
                        echo "Despesa salva com sucesso!";
                    } elseif ($success_msg === '2') {
                        echo "Cartão atualizado com sucesso!";
                    } else {
                        echo "Operação realizada com sucesso!";
                    }
                ?>
            </span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['info']) && $_GET['info'] === 'no_expenses'): ?>
        <div class="alert alert-info" id="alert-msg" style="margin: 20px 40px 0 40px; padding: 12px 20px; background: rgba(59, 130, 246, 0.15); border: 1px solid #3b82f6; border-radius: 8px; color: #3b82f6; display: flex; align-items: center; gap: 8px; font-size: 14px;">
            <i data-feather="info" style="width: 18px; height: 18px; min-width: 18px;"></i>
            <span>Nenhuma despesa pendente encontrada para o mês selecionado neste cartão.</span>
        </div>
    <?php endif; ?>

    <div class="header-page">
        <h1><i data-feather="credit-card"></i> Cartões de Crédito</h1>
        <p>Gerencie seus cartões e despesas</p>
    </div>

    <div class="container-principal">
        <div class="tabs-menu">
            <button class="tab-btn active" onclick="mostrarAba('despesas')" id="btn-despesas">Despesas</button>
            <button class="tab-btn" onclick="mostrarAba('cartoes')" id="btn-cartoes">Meus Cartões</button>
            <button class="tab-btn" onclick="mostrarAba('quitadas')" id="btn-quitadas">Quitadas</button>
        </div>
        
        <!-- Aba 1: Despesas -->
        <div id="aba-despesas">
            <div class="aba-header">
                <h2>Despesas no Cartão</h2>
                <button class="btn-primary" style="background:transparent; border:none; color:#a0aec0; display:flex; align-items:center; gap:5px;" onclick="abrirModalDespesaCartao()">
                    <i data-feather="plus"></i> Nova Despesa
                </button>
            </div>

            <?php if(empty($despesas_pendentes)): ?>
                <div class="empty-state">
                    <i data-feather="inbox" style="width:40px; height:40px; color:#475569; margin-bottom:15px;"></i>
                    <h3 style="margin-bottom: 10px;">Nenhuma despesa cadastrada</h3>
                    <p style="color: #a0aec0; margin-bottom: 20px;">Comece adicionando suas compras no cartão</p>
                    <button class="btn-primary" style="background:#1e293b; border:1px solid #334155;" onclick="abrirModalDespesaCartao()">+ Adicionar Despesa</button>
                </div>
            <?php else: ?>
                <div class="container-tabela">
                    <table class="tabela-dados">
                        <thead><tr><th>Descrição</th><th>Cartão</th><th>Categoria</th><th>Valor Total</th><th>Parcelas</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead>
                        <tbody>
                            <?php foreach($despesas_pendentes as $desp): 
                                // Normaliza a cor do cartão
                                $cor_hex = $desp['cor'] ?? '#8b5cf6';
                                if (!str_starts_with($cor_hex, '#')) {
                                    $mapa = [ 'roxo' => '#8b5cf6', 'azul' => '#3b82f6', 'verde' => '#10b981', 'laranja' => '#f59e0b', 'vermelho' => '#ef4444', 'preto' => '#1e293b' ];
                                    $cor_hex = $mapa[strtolower($cor_hex)] ?? '#1e293b';
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($desp['descricao']); ?></strong></td>
                                <td>
                                    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?php echo $cor_hex; ?>; margin-right:6px; vertical-align:middle; box-shadow: 0 0 6px <?php echo $cor_hex; ?>;"></span>
                                    <span style="vertical-align:middle; font-weight:600; color:<?php echo $cor_hex; ?>;"><?php echo htmlspecialchars($desp['nome_cartao']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($desp['categoria']); ?></td>
                                <td style="font-weight:bold;">R$ <?php echo number_format($desp['valor_total'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($desp['parcelas']); ?></td>
                                <td><span style="background:rgba(245, 158, 11, 0.2); color:#fbbf24; padding:4px 8px; border-radius:4px; font-size:12px;"><?php echo $desp['status']; ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($desp['data_compra'])); ?></td>
                                <td>
                                    <?php
                                        $parcelas_str = $desp['parcelas'];
                                        $parcela_atual = 1;
                                        $total_parcelas = 1;
                                        if (strpos($parcelas_str, '/') !== false) {
                                            $partes = explode('/', $parcelas_str);
                                            $parcela_atual = max(1, intval($partes[0]));
                                            $total_parcelas = max(1, intval(end($partes)));
                                        } else if (preg_match('/^(\d+)/', trim($parcelas_str), $matches)) {
                                            $total_parcelas = max(1, intval($matches[1]));
                                        }
                                        $parcelas_pagas = $parcela_atual - 1;
                                        
                                        if ($total_parcelas > 1) {
                                            $valor_parcela = $desp['valor_total'] / $total_parcelas;
                                            echo '<button type="button" class="btn-icon" style="background:none; border:none; color:#10b981; cursor:pointer;" title="Adiantar Parcela" onclick="abrirModalAdiantarParcela(' . $desp['id'] . ', \'' . htmlspecialchars(addslashes($desp['descricao'])) . '\', ' . $valor_parcela . ', ' . ($parcelas_pagas + 1) . ', ' . $total_parcelas . ')"><i data-feather="dollar-sign"></i></button>';
                                        } else {
                                            echo '<a href="pay_card_expense.php?id=' . $desp['id'] . '" class="btn-icon" style="background:none; border:none; color:#10b981; cursor:pointer;" title="Marcar como Quitada"><i data-feather="check-circle"></i></a>';
                                        }
                                    ?>
                                    <button class="btn-icon" style="background:none; border:none; color:#a0aec0; cursor:pointer;" title="Editar" onclick="abrirModalEdicaoDespesaCartao(<?php echo $desp['id']; ?>, '<?php echo htmlspecialchars(addslashes($desp['descricao'])); ?>', <?php echo $desp['cartao_id']; ?>, '<?php echo htmlspecialchars(addslashes($desp['categoria'])); ?>', '<?php echo $desp['data_compra']; ?>', <?php echo $desp['valor_total']; ?>, '<?php echo htmlspecialchars(addslashes($desp['parcelas'])); ?>')"><i data-feather="edit-2"></i></button>
                                    <a href="delete_card_expense.php?id=<?php echo $desp['id']; ?>" class="btn-icon" style="background:none; border:none; color:#a0aec0; cursor:pointer;" title="Excluir" onclick="return confirm('Excluir esta despesa? O limite será restaurado.');"><i data-feather="trash-2"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Aba 2: Meus Cartões -->
        <div id="aba-cartoes" style="display:none;">
            <div class="aba-header">
                <h2>Meus Cartões</h2>
                <button class="btn-primary" style="background:transparent; border:none; color:#a0aec0; display:flex; align-items:center; gap:5px;" onclick="abrirModalCartao()">
                    <i data-feather="plus"></i> Novo Cartão
                </button>
            </div>

            <?php if(empty($lista_cartoes)): ?>
                <div class="empty-state">
                    <h3 style="margin-bottom: 10px;">Nenhum cartão cadastrado</h3>
                    <button class="btn-primary" style="background:#334155;" onclick="abrirModalCartao()">Adicionar Cartão</button>
                </div>
            <?php else: ?>
                <div class="cards-grid">
                      <?php foreach($lista_cartoes as $cartao): 
                         // Normaliza a cor do cartão
                         $cor_banco = $cartao['cor'] ?? '#1e293b';
                         if (!str_starts_with($cor_banco, '#')) {
                             $mapa = [ 'roxo' => '#8b5cf6', 'azul' => '#3b82f6', 'verde' => '#10b981', 'laranja' => '#f59e0b', 'vermelho' => '#ef4444', 'preto' => '#1e293b' ];
                             $cor_hex = $mapa[strtolower($cor_banco)] ?? '#1e293b';
                             $cor_css = "cartao-" . strtolower($cor_banco);
                         } else {
                             $cor_hex = $cor_banco;
                             // Se for hex, mapeia para as classes CSS padrão
                             $mapa_hex = [ '#8b5cf6' => 'roxo', '#3b82f6' => 'azul', '#10b981' => 'verde', '#f59e0b' => 'laranja', '#ef4444' => 'vermelho', '#1e293b' => 'preto' ];
                             $cor_nome = $mapa_hex[$cor_hex] ?? 'preto';
                             $cor_css = "cartao-" . $cor_nome;
                         }
                      ?>
                        <div class="credit-card-ui <?php echo $cor_css; ?>">
                            <div class="card-ui-header">
                                <div>
                                    <div class="card-ui-name"><?php echo htmlspecialchars($cartao['nome_cartao']); ?></div>
                                    <div class="card-ui-brand"><?php echo htmlspecialchars($cartao['bandeira']); ?></div>
                                </div>
                                <div style="display:flex; gap:12px; align-items:center;">
                                    <!-- Botões de Ações juntos -->
                                    <div style="display:flex; gap:6px; align-items:center;">
                                        <button class="btn-icon" style="background:rgba(255,255,255,0.2); border:none; color:white; cursor:pointer; padding:5px 8px; border-radius:5px; display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:600;" title="Editar Cartão" onclick="abrirModalEdicaoCartao(<?php echo $cartao['id']; ?>, '<?php echo htmlspecialchars(addslashes($cartao['nome_cartao'])); ?>', '<?php echo htmlspecialchars(addslashes($cartao['bandeira'])); ?>', <?php echo $cartao['limite_total']; ?>, <?php echo $cartao['dia_fechamento'] ?? 25; ?>, <?php echo $cartao['dia_vencimento']; ?>, '<?php echo htmlspecialchars(addslashes($cartao['cor'])); ?>')"><i data-feather="edit-2" style="width:14px; height:14px;"></i> Editar</button>
                                        <a href="delete_card.php?id=<?php echo $cartao['id']; ?>" class="btn-icon" style="background:rgba(255,255,255,0.2); border:none; color:white; cursor:pointer; padding:5px 8px; border-radius:5px; display:inline-flex; align-items:center;" title="Excluir Cartão" onclick="return confirm('Deseja realmente excluir este cartão? Todas as despesas vinculadas a ele serão apagadas permanentemente.');"><i data-feather="trash-2" style="width:14px; height:14px;"></i></a>
                                    </div>
                                    
                                    <!-- O chip do cartão com a cor de uso do cartão (separado no canto direito) -->
                                    <div class="card-ui-chip" style="background: linear-gradient(135deg, <?php echo $cor_hex; ?> 0%, <?php echo adjustBrightness($cor_hex, -25); ?> 100%); box-shadow: 0 0 6px <?php echo $cor_hex; ?>; border: 1.5px solid rgba(255,255,255,0.25);" title="Cor de uso: <?php echo $cor_hex; ?>"></div>
                                </div>
                            </div>
                            <div class="card-ui-body">
                                <div class="card-ui-limit">
                                    <span>Limite Disponível</span>
                                    <strong>R$ <?php echo number_format($cartao['limite_disponivel'], 2, ',', '.'); ?></strong>
                                </div>
                            </div>
                            <div class="card-ui-footer">
                                <div class="card-ui-invoice">
                                    <span>Fatura Atual</span>
                                    <strong>R$ <?php echo number_format($cartao['fatura_atual'], 2, ',', '.'); ?></strong>
                                </div>
                                <div>
                                    <button class="btn-primary" style="background:rgba(255,255,255,0.25); border:1px solid rgba(255,255,255,0.3); font-size:12px; padding:6px 12px; border-radius:8px; color:white; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px; transition: 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.25)'" onclick="abrirModalPagarFatura(<?php echo $cartao['id']; ?>, '<?php echo htmlspecialchars(addslashes($cartao['nome_cartao'])); ?>')">
                                        <i data-feather="check-square" style="width:14px; height:14px;"></i> Pagar Fatura
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Aba 3: Quitadas -->
        <div id="aba-quitadas" style="display:none;">
            <div class="aba-header"><h2>Despesas Quitadas</h2></div>
            <?php if(empty($despesas_quitadas)): ?>
                <div class="empty-state">
                    <i data-feather="calendar" style="width:40px; height:40px; color:#475569; margin-bottom:15px;"></i>
                    <h3 style="margin-bottom: 10px;">Nenhuma despesa quitada</h3>
                    <p style="color: #a0aec0;">As despesas pagas aparecerão aqui</p>
                </div>
            <?php else: ?>
                <div class="container-tabela">
                    <table class="tabela-dados">
                        <thead><tr><th>Cartão</th><th>Categoria</th><th>Valor Total</th><th>Parcelas</th><th>Data Compra</th><th>Data Quitação</th><th>Ações</th></tr></thead>
                        <tbody>
                            <?php foreach($despesas_quitadas as $desp): 
                                // Normaliza a cor do cartão
                                $cor_hex = $desp['cor'] ?? '#8b5cf6';
                                if (!str_starts_with($cor_hex, '#')) {
                                    $mapa = [ 'roxo' => '#8b5cf6', 'azul' => '#3b82f6', 'verde' => '#10b981', 'laranja' => '#f59e0b', 'vermelho' => '#ef4444', 'preto' => '#1e293b' ];
                                    $cor_hex = $mapa[strtolower($cor_hex)] ?? '#1e293b';
                                }
                            ?>
                            <tr>
                                <td>
                                    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:<?php echo $cor_hex; ?>; margin-right:6px; vertical-align:middle; box-shadow: 0 0 6px <?php echo $cor_hex; ?>;"></span>
                                    <span style="vertical-align:middle; font-weight:600; color:<?php echo $cor_hex; ?>;"><?php echo htmlspecialchars($desp['nome_cartao']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($desp['categoria']); ?></td>
                                <td style="color: #10b981; font-weight:bold;">R$ <?php echo number_format($desp['valor_total'], 2, ',', '.'); ?></td>
                                <td><?php echo htmlspecialchars($desp['parcelas']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($desp['data_compra'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($desp['data_quitacao'])); ?></td>
                                <td>
                                    <a href="unpay_card_expense.php?id=<?php echo $desp['id']; ?>" class="btn-icon" style="background:none; border:none; color:#f59e0b; cursor:pointer;" title="Reverter para Não Pago" onclick="return confirm('Deseja reverter esta despesa para não paga? O limite e a fatura do cartão serão recalculados.');"><i data-feather="rotate-ccw"></i></a>
                                    <a href="delete_card_expense.php?id=<?php echo $desp['id']; ?>" class="btn-icon" style="background:none; border:none; color:#a0aec0; cursor:pointer;" title="Excluir Histórico" onclick="return confirm('Apagar este registro do histórico de quitadas?');"><i data-feather="trash-2"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Adicionar Cartão com Opção de Cor -->
    <div id="modalCartao" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px; background:#1e293b; border:1px solid #334155;">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.05);">
                <h3>Adicionar Novo Cartão</h3>
                <button type="button" class="close-btn" onclick="fecharModalCartao()"><i data-feather="x"></i></button>
            </div>
            <form id="formCartao" action="add_card.php" method="POST">
                <div class="modal-body">
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size:14px;">Nome do Cartão *</label>
                        <input type="text" name="nome_cartao" placeholder="Ex: Nubank, Itaú" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                    </div>
                    
                    <div style="display:flex; gap:15px; margin-bottom: 15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Cor de Identificação</label>
                            <input type="color" name="cor" value="#8b5cf6" style="width:100%; height:40px; padding:0; border:radius:5px; border:1px solid #4a5b76; background:none; cursor:pointer;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Bandeira</label>
                            <select name="bandeira" class="custom-select" required>
                                <option value="Mastercard">Mastercard</option>
                                <option value="Visa">Visa</option>
                                <option value="Elo">Elo</option>
                                <option value="American Express">American Express</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom: 15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Limite Total (R$) *</label>
                            <input type="number" step="0.01" name="limite_total" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Vencimento (Dia)</label>
                            <input type="number" name="dia_vencimento" min="1" max="31" value="10" style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.05);">
                    <button type="button" class="btn-secondary" style="background:none; border:none;" onclick="fecharModalCartao()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background:#334155; border:1px solid #4a5b76;">Guardar Cartão</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Cartão -->
    <div id="modalEditarCartao" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px; background:#1e293b; border:1px solid #334155;">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.05);">
                <h3>Editar Cartão</h3>
                <button type="button" class="close-btn" onclick="fecharModalEdicaoCartao()"><i data-feather="x"></i></button>
            </div>
            <form id="formEditarCartao" action="edit_card.php" method="POST">
                <input type="hidden" name="id" id="editCardId">
                <div class="modal-body">
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size:14px;">Nome do Cartão *</label>
                        <input type="text" name="nome_cartao" id="editCardNome" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                    </div>
                    
                    <div style="display:flex; gap:15px; margin-bottom: 15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Bandeira *</label>
                            <select name="bandeira" id="editCardBandeira" class="custom-select" required>
                                <option value="Mastercard">Mastercard</option>
                                <option value="Visa">Visa</option>
                                <option value="Elo">Elo</option>
                                <option value="American Express">American Express</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Limite Total (R$) *</label>
                            <input type="number" step="0.01" name="limite" id="editCardLimite" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                        </div>
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom: 15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Dia do Fechamento</label>
                            <input type="number" name="dia_fechamento" id="editCardDiaFechamento" min="1" max="31" style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Dia do Vencimento</label>
                            <input type="number" name="dia_vencimento" id="editCardDiaVencimento" min="1" max="31" style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size:14px;">Cor de Identificação</label>
                        <input type="color" name="cor" id="editCardCor" style="width:100%; height:40px; padding:0; border-radius:5px; border:1px solid #4a5b76; background:none; cursor:pointer;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.05);">
                    <button type="button" class="btn-secondary" style="background:none; border:none;" onclick="fecharModalEdicaoCartao()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background:#334155; border:1px solid #4a5b76;">Atualizar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Adicionar Despesa no Cartão -->
    <div id="modalDespesaCartao" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px; background:#1e293b; border:1px solid #334155;">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.05);">
                <h3>Adicionar Despesa no Cartão</h3>
                <button type="button" class="close-btn" onclick="fecharModalDespesaCartao()"><i data-feather="x"></i></button>
            </div>
            <form id="formDespesaCartao" action="add_card_expense.php" method="POST">
                <input type="hidden" name="id" id="inputIdDespesaCartao">
                <div class="modal-body">
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size:14px;">Descrição *</label>
                        <input type="text" name="descricao" id="inputDescricaoCartao" placeholder="Ex: Supermercado, Restaurante" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size:14px;">Cartão utilizado *</label>
                        <select name="cartao_id" id="inputCartaoIdCartao" class="custom-select" required onchange="atualizarCorSelectCartao()">
                            <?php foreach($lista_cartoes as $cartao): 
                                // Normaliza a cor do cartão
                                $cor_hex = $cartao['cor'] ?? '#8b5cf6';
                                if (!str_starts_with($cor_hex, '#')) {
                                    $mapa = [ 'roxo' => '#8b5cf6', 'azul' => '#3b82f6', 'verde' => '#10b981', 'laranja' => '#f59e0b', 'vermelho' => '#ef4444', 'preto' => '#1e293b' ];
                                    $cor_hex = $mapa[strtolower($cor_hex)] ?? '#1e293b';
                                }
                            ?>
                                <option value="<?php echo $cartao['id']; ?>" data-cor="<?php echo $cor_hex; ?>" style="color: <?php echo $cor_hex; ?>; font-weight: 600; background: #0f172a;">
                                    ● <?php echo htmlspecialchars($cartao['nome_cartao'] . ' (' . $cartao['bandeira'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom: 15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Categoria</label>
                            <select name="categoria" id="inputCategoriaCartao" class="custom-select">
                                <option value="Alimentação">Alimentação</option>
                                <option value="Transporte">Transporte</option>
                                <option value="Lazer">Lazer</option>
                                <option value="Saúde">Saúde</option>
                                <option value="Outro" selected>Outro</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Data da Compra *</label>
                            <input type="date" name="data_compra" id="inputDataCartao" value="<?php echo date('Y-m-d'); ?>" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                        </div>
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom: 15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Valor Total (R$) *</label>
                            <input type="number" step="0.01" name="valor_total" id="inputValorCartao" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Parcelas</label>
                            <input type="text" name="parcelas" id="inputParcelasCartao" placeholder="Ex: 1/1, 1/12" value="1/1" style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.05);">
                    <button type="button" class="btn-secondary" style="background:none; border:none;" onclick="fecharModalDespesaCartao()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background:#f43f5e; border:1px solid #be123c;">Adicionar Compra</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalAdiantarParcela" class="modal-overlay" style="z-index:9999;">
        <div class="modal-content" style="max-width: 400px; background:#1e293b; border:1px solid #334155; text-align:center;">
            <div style="padding: 20px;">
                <h3 style="margin-bottom: 15px;">Adiantar Parcela</h3>
                <p style="color:#a0aec0; margin-bottom:20px;">Você tem certeza que deseja adiantar uma parcela desta compra?</p>
                
                <h4 id="adiantarDescricao" style="margin-bottom: 5px;"></h4>
                <div style="color:#10b981; font-weight:bold; font-size:18px; margin-bottom: 5px;">Parcela R$ <span id="adiantarValor"></span></div>
                <div style="color:#64748b; font-size:14px; margin-bottom:25px;" id="adiantarProgresso"></div>
                
                <div style="display:flex; justify-content:center; gap:15px;">
                    <button type="button" class="btn-secondary" style="background:none; border:none;" onclick="fecharModalAdiantarParcela()">Cancelar</button>
                    <button type="button" class="btn-primary" style="background:#10b981; border:none; color:white;" onclick="confirmarAdiantamento()">Sim, com certeza</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmação Final -->
    <div id="modalConfirmacaoFinal" class="modal-overlay" style="z-index:10000;">
        <div class="modal-content" style="max-width: 400px; background:#1e293b; border:1px solid #ef4444; border-top: 4px solid #ef4444; text-align:center;">
            <div style="padding: 20px;">
                <i data-feather="alert-triangle" style="color:#ef4444; width:48px; height:48px; margin-bottom:15px;"></i>
                <h3 style="color:#ef4444; margin-bottom: 15px;">Confirmação Final</h3>
                <p style="margin-bottom:20px;"><strong>⚠️ ATENÇÃO: Esta ação não pode ser desfeita!</strong></p>
                <p style="color:#a0aec0; margin-bottom:25px; font-size:14px;">Após confirmar, a parcela será marcada como paga e você não poderá reverter esta operação.</p>
                <p style="color:#a0aec0; margin-bottom:25px; font-size:14px;">Tem certeza absoluta que deseja continuar?</p>
                
                <div style="display:flex; justify-content:center; gap:15px;">
                    <button type="button" class="btn-secondary" style="background:none; border:none;" onclick="fecharModalConfirmacaoFinal()">Não, cancelar</button>
                    <a id="btnConfirmarDefinitivo" href="#" class="btn-primary" style="background:#ef4444; border:none; color:white; padding:10px 15px; text-decoration:none;">Sim, confirmar definitivamente</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pagar Fatura -->
    <div id="modalPagarFatura" class="modal-overlay" style="z-index:9999;">
        <div class="modal-content" style="max-width: 450px; background:#1e293b; border:1px solid #334155;">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:12px; display:flex; justify-content:between; align-items:center;">
                <h3>Pagar Fatura - <span id="pagarFaturaNomeCartao"></span></h3>
                <button type="button" class="close-btn" style="background:none; border:none; color:#a0aec0; cursor:pointer;" onclick="fecharModalPagarFatura()"><i data-feather="x"></i></button>
            </div>
            <form id="formPagarFatura" action="pay_card_invoice.php" method="POST">
                <input type="hidden" name="cartao_id" id="pagarFaturaCartaoId">
                <div class="modal-body" style="padding:20px 0;">
                    <p style="color:#a0aec0; font-size:14px; margin-bottom:20px;">
                        Selecione o mês e ano correspondente para quitar todas as compras pendentes deste período.
                    </p>
                    <div style="display:flex; gap:15px; margin-bottom:15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px; color:#a0aec0;">Mês</label>
                            <select name="mes" id="pagarFaturaMes" class="custom-select" required>
                                <option value="1">Janeiro</option>
                                <option value="2">Fevereiro</option>
                                <option value="3">Março</option>
                                <option value="4">Abril</option>
                                <option value="5">Maio</option>
                                <option value="6">Junho</option>
                                <option value="7">Julho</option>
                                <option value="8">Agosto</option>
                                <option value="9">Setembro</option>
                                <option value="10">Outubro</option>
                                <option value="11">Novembro</option>
                                <option value="12">Dezembro</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px; color:#a0aec0;">Ano</label>
                            <select name="ano" id="pagarFaturaAno" class="custom-select" required>
                                <?php 
                                    $ano_atual = intval(date('Y'));
                                    for ($i = $ano_atual - 2; $i <= $ano_atual + 2; $i++) {
                                        $selected = ($i === $ano_atual) ? 'selected' : '';
                                        echo "<option value='{$i}' {$selected}>{$i}</option>";
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.05); display:flex; justify-content:flex-end; gap:12px; padding-top:12px;">
                    <button type="button" class="btn-secondary" style="background:none; border:none; color:#a0aec0; cursor:pointer;" onclick="fecharModalPagarFatura()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background:#10b981; border:none; color:white; padding:10px 15px; border-radius:6px; font-weight:600; cursor:pointer;">Confirmar Pagamento</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/cards.js"></script>
    <script src="js/navbar.js"></script>
</body>
</html>