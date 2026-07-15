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

// Calcula o total investido (Atenção: assumindo que a tabela no banco ainda se chama 'investimentos')
$stmt_total = $conexao->prepare("SELECT SUM(valor) as total FROM investimentos WHERE user_id = ?");
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$row_total = $result_total->fetch_assoc();
$total_investido = $row_total['total'] ? (float)$row_total['total'] : 0.00;

// Procura a lista de investimentos
$stmt_lista = $conexao->prepare("SELECT * FROM investimentos WHERE user_id = ? ORDER BY data_investimento DESC");
$stmt_lista->bind_param("i", $user_id);
$stmt_lista->execute();
$investimentos = $stmt_lista->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investimentos - Meu Patrimônio</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="css/investments.css">
</head>
<body>

    <?php include 'components/navbar.php'; ?>

    <div class="banner-verde">
        <div>
            <span>Total em Investimentos</span>
            <h1>R$ <?php echo number_format($total_investido, 2, ',', '.'); ?></h1>
        </div>
    </div>

    <div class="container-lista">
        <div class="header-lista">
            <h2>Meus Ativos</h2>
            <button class="btn-novo" onclick="abrirModalInvestimento()">
                <i data-feather="plus"></i> Novo Investimento
            </button>
        </div>

        <table class="tabela-invest">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Data</th>
                    <th>Observações</th>
                    <th>Valor</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while($inv = $investimentos->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($inv['descricao']); ?></strong></td>
                    <td><?php echo date('d/m/Y', strtotime($inv['data_investimento'])); ?></td>
                    <td style="color: #a0aec0; font-size: 14px;"><?php echo htmlspecialchars($inv['observacoes']); ?></td>
                    <td class="valor-positivo">R$ <?php echo number_format($inv['valor'], 2, ',', '.'); ?></td>
                    <td>
                        <button class="btn-icon" style="background:none; border:none; color:#a0aec0; cursor:pointer;" title="Editar" onclick="abrirModalEdicaoInvestimento(<?php echo $inv['id']; ?>, '<?php echo htmlspecialchars(addslashes($inv['descricao'])); ?>', <?php echo $inv['valor']; ?>, '<?php echo $inv['data_investimento']; ?>', '<?php echo htmlspecialchars(addslashes($inv['observacoes'])); ?>')"><i data-feather="edit-2"></i></button>
                        <a href="delete_investment.php?id=<?php echo $inv['id']; ?>" class="btn-icon" style="background:none; border:none; color:#a0aec0; cursor:pointer;" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este investimento? O valor será deduzido do patrimônio.');"><i data-feather="trash-2"></i></a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($investimentos->num_rows == 0): ?>
                <tr><td colspan="4" style="text-align:center; color:#a0aec0;">Nenhum investimento registrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal Novo Investimento -->
    <div id="modalInvestimento" class="modal-overlay">
        <div class="modal-content" style="width: 500px;">
            <div class="modal-header">
                <h2>Adicionar Investimento</h2>
                <button type="button" class="close-btn" onclick="fecharModalInvestimento()"><i data-feather="x"></i></button>
            </div>
            <!-- O formulário agora aponta para o novo arquivo em inglês -->
            <form id="formInvestimento" action="add_investment.php" method="POST">
                <input type="hidden" name="id" id="inputId">
                <div class="modal-body">
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px;">Descrição (Ex: Tesouro Direto)</label>
                        <input type="text" name="descricao" id="inputDescricao" required style="width:100%; padding:10px; border-radius:5px; border:none; background:#53657f; color:white;">
                    </div>
                    <div style="margin-bottom: 15px; display:flex; gap:15px;">
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px;">Valor (R$)</label>
                            <input type="number" step="0.01" name="valor" id="inputValor" required style="width:100%; padding:10px; border-radius:5px; border:none; background:#53657f; color:white;">
                        </div>
                        <div style="flex:1;">
                            <label style="display:block; margin-bottom:5px;">Data</label>
                            <input type="date" name="data_investimento" id="inputData" required style="width:100%; padding:10px; border-radius:5px; border:none; background:#53657f; color:white;">
                        </div>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px;">Observações</label>
                        <textarea name="observacoes" id="inputObs" rows="3" style="width:100%; padding:10px; border-radius:5px; border:none; background:#53657f; color:white; resize:none;"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="fecharModalInvestimento()">Cancelar</button>
                    <button type="submit" class="btn-primary" style="background:#10b981;">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/investments.js"></script>
    <script src="js/navbar.js"></script>
</body>
</html>