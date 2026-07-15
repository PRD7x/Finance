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

// Busca todas as despesas do usuário
$stmt_lista = $conexao->prepare("SELECT * FROM despesas WHERE user_id = ? ORDER BY dia_vencimento ASC");
$stmt_lista->bind_param("i", $user_id);
$stmt_lista->execute();
$resultado = $stmt_lista->get_result();

$despesas_fixas = [];
$despesas_variaveis = [];
$despesas_quitadas = [];

$total_fixas = 0;
$total_variaveis = 0;

$mes_atual = date('m/Y');

while($row = $resultado->fetch_assoc()) {
    if ($row['status'] === 'Pago') {
        $despesas_quitadas[] = $row;
    } else {
        if($row['tipo_despesa'] === 'Fixa') {
            $despesas_fixas[] = $row;
            $total_fixas += (float)$row['valor'];
        } else {
            // Mostra todas as despesas variáveis pendentes para o usuário não as perder de vista, além das do mês atual
            $despesas_variaveis[] = $row;
            $total_variaveis += (float)$row['valor'];
        }
    }
}

$total_geral = $total_fixas + $total_variaveis;
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despesas - Meu Património</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="css/expenses.css">
</head>
<body>

    <?php include 'components/navbar.php'; ?>

    <div class="header-page">
        <div>
            <h1>Despesas Mensais</h1>
            <p>Gastos fixos e variáveis</p>
        </div>
        <button class="btn-primary" style="background:#1e293b; border:1px solid #334155; display:flex; align-items:center; gap:8px;" onclick="abrirModalDespesa()">
            <i data-feather="plus"></i> Nova Despesa
        </button>
    </div>

    <div class="dashboard-cards">
        <div class="card-despesa bg-laranja">
            <h3>Gastos Fixos</h3>
            <h2>R$ <?php echo number_format($total_fixas, 2, ',', '.'); ?></h2>
            <p><?php echo count($despesas_fixas); ?> despesas ativas</p>
        </div>
        
        <div class="card-despesa bg-vermelho">
            <h3>Gastos Variáveis (Mês Atual)</h3>
            <h2>R$ <?php echo number_format($total_variaveis, 2, ',', '.'); ?></h2>
            <p><?php echo count($despesas_variaveis); ?> lançamentos este mês</p>
        </div>

        <div class="card-despesa bg-dark">
            <h3>Total do Mês</h3>
            <h2>R$ <?php echo number_format($total_geral, 2, ',', '.'); ?></h2>
            <p>Fixos + Variáveis</p>
        </div>
    </div>

    <div class="container-tabela">
        <div class="tabs-menu">
            <button class="tab-btn active" onclick="mostrarAba('fixas')" id="btn-fixas">Gastos Fixos</button>
            <button class="tab-btn" onclick="mostrarAba('variaveis')" id="btn-variaveis">Gastos Variáveis</button>
            <button class="tab-btn" onclick="mostrarAba('quitados')" id="btn-quitados">Histórico Quitados</button>
        </div>
        
        <div id="aba-fixas">
            <?php if(empty($despesas_fixas)): ?>
                <div class="empty-state">
                    <h3 style="margin-bottom: 10px;">Nenhum gasto fixo cadastrado</h3>
                    <button class="btn-primary" style="background:#d97706;" onclick="abrirModalDespesa()">+ Adicionar Gasto Fixo</button>
                </div>
            <?php else: ?>
                <div class="container-tabela">
                    <table class="tabela-dados">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($despesas_fixas as $desp): ?>
                            <tr>
                                <td>
                                    <a href="pay_expense.php?id=<?php echo $desp['id']; ?>" class="toggle-status" title="Marcar como Pago"></a>
                                </td>
                                <td><strong><?php echo htmlspecialchars($desp['nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($desp['categoria_item']); ?></td>
                                <td style="font-weight:bold;">R$ <?php echo number_format($desp['valor'], 2, ',', '.'); ?></td>
                                <td>Dia <?php echo str_pad($desp['dia_vencimento'], 2, "0", STR_PAD_LEFT); ?></td>
                                <td>
                                    <button class="btn-icon" title="Editar" onclick="abrirModalEdicaoDespesa(<?php echo $desp['id']; ?>, '<?php echo $desp['tipo_despesa']; ?>', '<?php echo htmlspecialchars(addslashes($desp['nome'])); ?>', <?php echo $desp['valor']; ?>, <?php echo $desp['dia_vencimento']; ?>, '<?php echo htmlspecialchars(addslashes($desp['categoria_item'])); ?>', '<?php echo htmlspecialchars(addslashes($desp['observacoes'])); ?>')"><i data-feather="edit-2"></i></button>
                                    <a href="delete_expense.php?id=<?php echo $desp['id']; ?>" class="btn-icon" title="Excluir Despesa" onclick="return confirm('Tem certeza que deseja excluir esta despesa fixa?');"><i data-feather="trash-2"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div id="aba-variaveis" style="display:none;">
            <?php if(empty($despesas_variaveis)): ?>
                <div class="empty-state">
                    <i data-feather="inbox" style="width:40px; height:40px; color:#475569; margin-bottom:15px;"></i>
                    <h3 style="margin-bottom: 10px;">Nenhum gasto variável cadastrado</h3>
                    <p style="color: #a0aec0; margin-bottom: 20px;">Comece adicionando gastos como luz, água, gás, etc.</p>
                    <button class="btn-primary" style="background:#1e293b; border:1px solid #334155;" onclick="abrirModalDespesa()">+ Adicionar Gasto Variável</button>
                </div>
            <?php else: ?>
                <div class="container-tabela">
                    <table class="tabela-dados">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Mês/Ano</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Observações</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($despesas_variaveis as $desp): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($desp['categoria_item']); ?></strong>
                                    <?php if(!empty($desp['mes_ano']) && $desp['mes_ano'] !== $mes_atual): ?>
                                        <span class="badge-atrasado" style="background:#f59e0b; color:white; font-size:10px; padding:2px 6px; border-radius:4px; margin-left:8px; font-weight:500;">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($desp['mes_ano']); ?></td>
                                <td>Dia <?php echo str_pad($desp['dia_vencimento'], 2, "0", STR_PAD_LEFT); ?></td>
                                <td style="font-weight:bold;">R$ <?php echo number_format($desp['valor'], 2, ',', '.'); ?></td>
                                <td style="color:#a0aec0;"><?php echo htmlspecialchars($desp['observacoes']); ?></td>
                                <td>
                                    <a href="pay_expense.php?id=<?php echo $desp['id']; ?>" class="btn-icon" title="Marcar como Pago"><i data-feather="check-circle" style="color:#10b981;"></i></a>
                                    <button class="btn-icon" title="Editar" onclick="abrirModalEdicaoDespesa(<?php echo $desp['id']; ?>, '<?php echo $desp['tipo_despesa']; ?>', '<?php echo htmlspecialchars(addslashes($desp['nome'])); ?>', <?php echo $desp['valor']; ?>, <?php echo $desp['dia_vencimento']; ?>, '<?php echo htmlspecialchars(addslashes($desp['categoria_item'])); ?>', '<?php echo htmlspecialchars(addslashes($desp['observacoes'])); ?>')"><i data-feather="edit-2"></i></button>
                                    <a href="delete_expense.php?id=<?php echo $desp['id']; ?>" class="btn-icon" title="Excluir Despesa" onclick="return confirm('Tem certeza que deseja excluir esta despesa variável?');"><i data-feather="trash-2"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div id="aba-quitados" style="display:none;">
            <?php if(empty($despesas_quitadas)): ?>
                <div class="empty-state">
                    <i data-feather="calendar" style="width:40px; height:40px; color:#475569; margin-bottom:15px;"></i>
                    <h3 style="margin-bottom: 10px;">Nenhum gasto quitado ainda</h3>
                    <p style="color: #a0aec0;">Marque os gastos como pagos para aparecerem aqui</p>
                </div>
            <?php else: ?>
                <div class="container-tabela">
                    <table class="tabela-dados">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th>Mês/Ano</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Data Quitação</th>
                                <th>Observações</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($despesas_quitadas as $desp): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($desp['categoria_item']); ?></strong></td>
                                <td><?php echo htmlspecialchars($desp['mes_ano']); ?></td>
                                <td>Dia <?php echo str_pad($desp['dia_vencimento'], 2, "0", STR_PAD_LEFT); ?></td>
                                <td style="color: #10b981; font-weight:bold;">R$ <?php echo number_format($desp['valor'], 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($desp['data_quitacao'])); ?></td>
                                <td style="color:#a0aec0;"><?php echo htmlspecialchars($desp['observacoes']); ?></td>
                                <td>
                                    <a href="unpay_expense.php?id=<?php echo $desp['id']; ?>" class="btn-icon" title="Reverter para Não Pago" onclick="return confirm('Deseja reverter esta despesa para não paga?');">
                                        <i data-feather="rotate-ccw" style="color: #f59e0b;"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div id="modalDespesa" class="modal-overlay">
        <div class="modal-content" style="max-width: 500px; background:#1e293b; border:1px solid #334155;">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.05);">
                <h3>Nova Despesa</h3>
                <button type="button" class="close-btn" onclick="fecharModalDespesa()"><i data-feather="x"></i></button>
            </div>
            
            <form id="formDespesa" action="add_expense.php" method="POST">
                <input type="hidden" name="id" id="inputIdDespesa">
                <div class="modal-body">
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size:14px;">Tipo de Despesa *</label>
                        <select name="tipo_despesa" id="inputTipoDespesa" class="custom-select" required>
                            <option value="Fixa">Despesa Fixa</option>
                            <option value="Variável">Despesa Variável</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size:14px;">Nome da Despesa *</label>
                        <input type="text" name="nome" id="inputNomeDespesa" placeholder="Ex: Aluguel, Internet, Academia" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                    </div>

                    <div style="display:flex; gap:15px; margin-bottom: 15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Valor Mensal *</label>
                            <input type="number" step="0.01" name="valor" id="inputValorDespesa" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px; font-size:14px;">Dia do Vencimento</label>
                            <input type="number" name="dia_vencimento" id="inputDiaVencimento" min="1" max="31" value="10" required style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white;">
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size:14px;">Categoria</label>
                        <select name="categoria_item" id="inputCategoriaDespesa" class="custom-select">
                            <option value="Casa">Casa (Água, Luz, Aluguel)</option>
                            <option value="Transporte">Transporte</option>
                            <option value="Alimentação">Alimentação</option>
                            <option value="Saúde">Saúde</option>
                            <option value="Outro" selected>Outro</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px; font-size:14px;">Observações</label>
                        <textarea name="observacoes" id="inputObservacoesDespesa" rows="3" style="width:100%; padding:10px; border-radius:5px; border:1px solid #4a5b76; background:#0f172a; color:white; resize:none;"></textarea>
                    </div>
                </div>

                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.05);">
                    <button type="button" class="btn-secondary" style="background:none; border:none;" onclick="fecharModalDespesa()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background:#334155; border:1px solid #4a5b76; color:#a0aec0;">Adicionar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/expenses.js"></script>
    <script src="js/navbar.js"></script>
</body>
</html>