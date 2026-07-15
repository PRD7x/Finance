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
    SELECT d.*, c.nome_cartao 
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
    if ($row['status'] === 'Quitada') {
        $despesas_quitadas[] = $row;
    } else {
        $despesas_pendentes[] = $row;
    }
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
                <table class="tabela-dados">
                    <thead><tr><th>Descrição</th><th>Cartão</th><th>Categoria</th><th>Valor Total</th><th>Parcelas</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php foreach($despesas_pendentes as $desp): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($desp['descricao']); ?></strong></td>
                            <td><?php echo htmlspecialchars($desp['nome_cartao']); ?></td>
                            <td><?php echo htmlspecialchars($desp['categoria']); ?></td>
                            <td style="font-weight:bold;">R$ <?php echo number_format($desp['valor_total'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($desp['parcelas']); ?></td>
                            <td><span style="background:rgba(245, 158, 11, 0.2); color:#fbbf24; padding:4px 8px; border-radius:4px; font-size:12px;"><?php echo $desp['status']; ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($desp['data_compra'])); ?></td>
                            <td>
                                <?php
                                    $parcelas_str = $desp['parcelas'];
                                    $parcelas_pagas = isset($desp['parcelas_pagas']) ? (int)$desp['parcelas_pagas'] : 0;
                                    $total_parcelas = 1;
                                    if (preg_match('/^(\d+)/', trim($parcelas_str), $matches)) {
                                        $total_parcelas = max(1, intval($matches[1]));
                                    }
                                    
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
                        // Aplica a cor vinda do banco ou 'preto' como padrão
                        $cor_css = "cartao-" . ($cartao['cor'] ?? 'preto');
                    ?>
                        <div class="credit-card-ui <?php echo $cor_css; ?>">
                            <div class="card-ui-header">
                                <div>
                                    <div class="card-ui-name"><?php echo htmlspecialchars($cartao['nome_cartao']); ?></div>
                                    <div class="card-ui-brand"><?php echo htmlspecialchars($cartao['bandeira']); ?></div>
                                </div>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <button class="btn-icon" style="background:rgba(255,255,255,0.2); border:none; color:white; cursor:pointer; padding:5px; border-radius:5px;" title="Editar Cartão" onclick="abrirModalEdicaoCartao(<?php echo $cartao['id']; ?>, '<?php echo htmlspecialchars(addslashes($cartao['nome_cartao'])); ?>', '<?php echo htmlspecialchars(addslashes($cartao['bandeira'])); ?>', <?php echo $cartao['limite_total']; ?>, <?php echo $cartao['dia_fechamento'] ?? 25; ?>, <?php echo $cartao['dia_vencimento']; ?>, '<?php echo htmlspecialchars(addslashes($cartao['cor'])); ?>')"><i data-feather="edit-2" style="width:16px; height:16px;"></i> Editar</button>
                                    <div class="card-ui-chip"></div>
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
                <table class="tabela-dados">
                    <thead><tr><th>Cartão</th><th>Categoria</th><th>Valor Total</th><th>Parcelas</th><th>Data Compra</th><th>Data Quitação</th><th>Ações</th></tr></thead>
                    <tbody>
                        <?php foreach($despesas_quitadas as $desp): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($desp['nome_cartao']); ?></strong></td>
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
                        <select name="cartao_id" id="inputCartaoIdCartao" class="custom-select" required>
                            <?php foreach($lista_cartoes as $cartao): ?>
                                <option value="<?php echo $cartao['id']; ?>"><?php echo htmlspecialchars($cartao['nome_cartao'] . ' (' . $cartao['bandeira'] . ')'); ?></option>
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

    <script src="js/cards.js"></script>
    <script src="js/navbar.js"></script>
</body>
</html>