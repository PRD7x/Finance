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

// 1. Vai buscar os gastos fixos para calcular a Meta de 6 meses
$stmt_finance = $conexao->prepare("SELECT gastos_fixos FROM finance_data WHERE user_id = ?");
$stmt_finance->bind_param("i", $user_id);
$stmt_finance->execute();
$finance = $stmt_finance->get_result()->fetch_assoc();
$gastos_fixos = $finance ? (float)$finance['gastos_fixos'] : 0.00;

$meta_6_meses = $gastos_fixos * 6;

// 2. Vai buscar todas as reservas e faz os cálculos matemáticos do rendimento
$stmt_lista = $conexao->prepare("SELECT * FROM reservas WHERE user_id = ? ORDER BY data_registro DESC");
$stmt_lista->bind_param("i", $user_id);
$stmt_lista->execute();
$reservas = $stmt_lista->get_result();

$total_reserva = 0;
$rendimento_mensal_total = 0;
$rendimento_anual_total = 0;
$lista_reservas = [];

while($res = $reservas->fetch_assoc()) {
    $valor = (float)$res['valor'];
    $total_reserva += $valor;
    
    $indexador = $res['indexador'];
    $pct = (float)$res['porcentagem_indexador'] / 100;
    
    $taxa_anual = 0;
    $taxa_mensal = 0;

    if ($indexador === 'Poupança') {
        $taxa_anual = 0.0617 * $pct;
        $taxa_mensal = 0.0050017 * $pct;
    } else if ($indexador === 'CDI') {
        $taxa_anual = 0.1050 * $pct;
        $taxa_mensal = (pow(1 + 0.1050, 1/12) - 1) * $pct;
    }

    $res['taxa_anual_calc'] = $taxa_anual * 100;
    $res['rend_mensal_calc'] = $valor * $taxa_mensal;
    
    $rendimento_mensal_total += $res['rend_mensal_calc'];
    $rendimento_anual_total += $valor * $taxa_anual;
    
    $lista_reservas[] = $res;
}

// 3. Calcula as percentagens e os meses de cobertura
$meses_cobertura = ($gastos_fixos > 0) ? floor($total_reserva / $gastos_fixos) : 0;
$porcentagem_meta = ($meta_6_meses > 0) ? min(($total_reserva / $meta_6_meses) * 100, 100) : 0;
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Emergência - Meu Património</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="css/reserve.css">
</head>
<body>

    <?php include 'components/navbar.php'; ?>

    <div class="header-page">
        <div>
            <h1>Reserva de Emergência</h1>
            <p>Acompanhe a sua segurança financeira</p>
        </div>
        <button class="btn-primary" style="background:#06b6d4; display:flex; align-items:center; gap:8px;" onclick="abrirModalReserva()">
            <i data-feather="plus"></i> Adicionar Valor
        </button>
    </div>

    <div class="dashboard-cards">
        <div class="card-reserva bg-ciano">
            <i data-feather="shield" style="position: absolute; right: 20px; top: 20px; opacity: 0.2; width: 60px; height: 60px;"></i>
            <h3>Reserva Total</h3>
            <h2>R$ <?php echo number_format($total_reserva, 2, ',', '.'); ?></h2>
            <p><?php echo $meses_cobertura; ?> meses de cobertura</p>
        </div>
        
        <div class="card-reserva bg-dark">
            <h3>Meta (6 meses)</h3>
            <h2>R$ <?php echo number_format($meta_6_meses, 2, ',', '.'); ?></h2>
            <div class="progresso-bg">
                <div class="progresso-bar" style="width: <?php echo $porcentagem_meta; ?>%;"></div>
            </div>
            <p style="margin-top: 8px; color: #a0aec0;"><?php echo number_format($porcentagem_meta, 1, ',', '.'); ?>% da meta alcançada</p>
        </div>

        <div class="card-reserva bg-verde">
            <i data-feather="trending-up" style="position: absolute; right: 20px; top: 20px; opacity: 0.2; width: 60px; height: 60px;"></i>
            <h3>Rendimento Estimado</h3>
            <h2>R$ <?php echo number_format($rendimento_mensal_total, 2, ',', '.'); ?></h2>
            <p>por mês (aproximadamente)</p>
        </div>
    </div>

    <div class="container-tabela">
        <div class="tabela-cabecalho">Minhas Aplicações</div>
        
        <?php if(empty($lista_reservas)): ?>
            <div class="empty-state">
                <i data-feather="shield" style="width: 48px; height: 48px; color: #a0aec0; margin-bottom: 15px;"></i>
                <h3 style="margin-bottom: 10px;">Nenhuma reserva cadastrada</h3>
                <p style="color: #a0aec0; margin-bottom: 20px;">Comece a sua reserva de emergência agora mesmo.</p>
                <button class="btn-primary" style="background:#06b6d4;" onclick="abrirModalReserva()">Adicionar Reserva</button>
            </div>
        <?php else: ?>
            <table class="tabela-dados">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th>Instituição</th>
                        <th>Tipo</th>
                        <th>Valor Atual</th>
                        <th>Rentabilidade</th>
                        <th>Taxa Anual</th>
                        <th>Rend. Mensal</th>
                        <th>Atualização</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($lista_reservas as $res): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($res['descricao']); ?></strong></td>
                        <td><?php echo htmlspecialchars($res['instituicao']); ?></td>
                        <td><?php echo htmlspecialchars($res['tipo_aplicacao']); ?></td>
                        <td style="font-weight: bold; color: #06b6d4;">R$ <?php echo number_format($res['valor'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($res['indexador']); ?> (<?php echo number_format($res['porcentagem_indexador'], 0); ?>%)</td>
                        <td><?php echo number_format($res['taxa_anual_calc'], 2, ',', '.'); ?>%</td>
                        <td style="color: #10b981;">R$ <?php echo number_format($res['rend_mensal_calc'], 2, ',', '.'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($res['data_atualizacao'])); ?></td>
                        <td>
                            <button class="btn-icon" style="background:none; border:none; color:#a0aec0; cursor:pointer;" title="Editar" onclick="abrirModalEdicaoReserva(<?php echo $res['id']; ?>, '<?php echo htmlspecialchars(addslashes($res['descricao'])); ?>', '<?php echo htmlspecialchars(addslashes($res['instituicao'])); ?>', '<?php echo htmlspecialchars(addslashes($res['tipo_aplicacao'])); ?>', <?php echo $res['valor']; ?>, '<?php echo $res['data_atualizacao']; ?>', '<?php echo htmlspecialchars(addslashes($res['indexador'])); ?>', <?php echo $res['porcentagem_indexador']; ?>)"><i data-feather="edit-2"></i></button>
                            <a href="delete_reserve.php?id=<?php echo $res['id']; ?>" class="btn-icon" style="background:none; border:none; color:#a0aec0; cursor:pointer;" title="Excluir" onclick="return confirm('Excluir esta reserva? O valor sairá do patrimônio.');"><i data-feather="trash-2"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div id="modalReserva" class="modal-overlay">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header">
                <h3>Adicionar à Reserva</h3>
                <button type="button" class="close-btn" onclick="fecharModalReserva()"><i data-feather="x"></i></button>
            </div>
            
            <form id="formReserva" action="add_reserve.php" method="POST">
                <input type="hidden" name="id" id="inputId">
                <div class="modal-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                        <div style="grid-column: 1 / -1;">
                            <label>Descrição *</label>
                            <input type="text" name="descricao" id="inputDescricao" placeholder="Ex: Fundo de Emergência Principal" required>
                        </div>
                        
                        <div>
                            <label>Instituição Financeira</label>
                            <input type="text" name="instituicao" id="inputInstituicao" placeholder="Ex: Nubank">
                        </div>
                        
                        <div>
                            <label>Tipo de Aplicação *</label>
                            <input type="text" name="tipo_aplicacao" id="inputTipoAplicacao" placeholder="Ex: CDB 100% CDI" required>
                        </div>

                        <div>
                            <label>Valor Atual (R$) *</label>
                            <input type="number" step="0.01" id="inputValor" name="valor" onkeyup="calcularRendimento()" required>
                        </div>
                        
                        <div>
                            <label>Data de Atualização</label>
                            <input type="date" name="data_atualizacao" id="inputData" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div>
                            <label>Indexador</label>
                            <select id="inputIndexador" name="indexador" class="custom-select" onchange="calcularRendimento()">
                                <option value="Poupança">Poupança</option>
                                <option value="CDI">CDI</option>
                            </select>
                        </div>
                        
                        <div>
                            <label>% do Indexador (Ex: 100)</label>
                            <input type="number" id="inputPorcentagem" name="porcentagem_indexador" value="100" onkeyup="calcularRendimento()">
                        </div>
                    </div>

                    <div style="background: rgba(0,0,0,0.15); padding: 15px; border-radius: 12px; display: flex; align-items: center; justify-content: space-around; border: 1px solid rgba(255,255,255,0.05);">
                        <div style="text-align: center;">
                            <span style="display: block; font-size: 12px; color: #a0aec0; margin-bottom: 4px;">Rendimento Mensal</span>
                            <strong id="rendMensal" style="color: #06b6d4; font-size: 18px;">R$ 0,00</strong>
                        </div>
                        <i data-feather="arrow-right" style="color: rgba(255,255,255,0.2);"></i>
                        <div style="text-align: center;">
                            <span style="display: block; font-size: 12px; color: #a0aec0; margin-bottom: 4px;">Rendimento Anual</span>
                            <strong id="rendAnual" style="color: #06b6d4; font-size: 18px;">R$ 0,00</strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="fecharModalReserva()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background:#06b6d4;">Salvar Reserva</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/reserve.js"></script>
    <script src="js/navbar.js"></script>
</body>
</html>