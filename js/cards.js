feather.replace();

function mostrarAba(aba) {
    document.getElementById('aba-despesas').style.display = aba === 'despesas' ? 'block' : 'none';
    document.getElementById('aba-cartoes').style.display = aba === 'cartoes' ? 'block' : 'none';
    document.getElementById('aba-quitadas').style.display = aba === 'quitadas' ? 'block' : 'none';
    document.getElementById('btn-despesas').classList.toggle('active', aba === 'despesas');
    document.getElementById('btn-cartoes').classList.toggle('active', aba === 'cartoes');
    document.getElementById('btn-quitadas').classList.toggle('active', aba === 'quitadas');
}

function abrirModalCartao() {
    document.getElementById('modalCartao').classList.add('show');
}

function fecharModalCartao() {
    document.getElementById('modalCartao').classList.remove('show');
    document.getElementById('formCartao').reset();
}

function abrirModalDespesaCartao() {
    document.getElementById('modalDespesaCartao').classList.add('show');
    document.getElementById('formDespesaCartao').action = 'add_card_expense.php';
    document.querySelector('#modalDespesaCartao h3').innerText = 'Adicionar Despesa no Cartão';
    document.getElementById('inputIdDespesaCartao').value = '';
    document.getElementById('formDespesaCartao').reset();
    
    // Atualiza a cor do select baseado na primeira opção
    setTimeout(atualizarCorSelectCartao, 50);
}

function abrirModalEdicaoDespesaCartao(id, desc, cartaoId, cat, data, valor, parcelas) {
    document.getElementById('modalDespesaCartao').classList.add('show');
    document.getElementById('formDespesaCartao').action = 'edit_card_expense.php';
    document.querySelector('#modalDespesaCartao h3').innerText = 'Editar Despesa';

    document.getElementById('inputIdDespesaCartao').value = id;
    document.getElementById('inputDescricaoCartao').value = desc;
    document.getElementById('inputCartaoIdCartao').value = cartaoId;
    document.getElementById('inputCategoriaCartao').value = cat;
    document.getElementById('inputDataCartao').value = data;
    document.getElementById('inputValorCartao').value = valor;
    document.getElementById('inputParcelasCartao').value = parcelas;
    
    // Atualiza a cor do select após carregar o valor
    setTimeout(atualizarCorSelectCartao, 50);
}

function fecharModalDespesaCartao() {
    document.getElementById('modalDespesaCartao').classList.remove('show');
    document.getElementById('formDespesaCartao').reset();
}

function abrirModalEdicaoCartao(id, nome, bandeira, limite, diaFechamento, diaVencimento, cor) {
    document.getElementById('modalEditarCartao').classList.add('show');
    document.getElementById('editCardId').value = id;
    document.getElementById('editCardNome').value = nome;
    document.getElementById('editCardBandeira').value = bandeira;
    document.getElementById('editCardLimite').value = limite;
    document.getElementById('editCardDiaFechamento').value = diaFechamento;
    document.getElementById('editCardDiaVencimento').value = diaVencimento;
    
    // Converte nome de cor para hex se necessario (legado)
    let hexColor = cor;
    if (!cor.startsWith('#')) {
        const mapa = { 'roxo': '#8b5cf6', 'azul': '#3b82f6', 'verde': '#10b981', 'laranja': '#f59e0b', 'vermelho': '#ef4444', 'preto': '#1e293b' };
        hexColor = mapa[cor.toLowerCase()] || '#1e293b';
    }
    document.getElementById('editCardCor').value = hexColor;
}

function fecharModalEdicaoCartao() {
    document.getElementById('modalEditarCartao').classList.remove('show');
    document.getElementById('formEditarCartao').reset();
}

let currentAdiantarId = null;

function abrirModalAdiantarParcela(id, desc, valor, numParcela, totalParcelas) {
    currentAdiantarId = id;
    document.getElementById('adiantarDescricao').innerText = desc;
    document.getElementById('adiantarValor').innerText = valor.toFixed(2).replace('.', ',');
    document.getElementById('adiantarProgresso').innerText = numParcela + ' de ' + totalParcelas;
    
    document.getElementById('modalAdiantarParcela').classList.add('show');
}

function fecharModalAdiantarParcela() {
    document.getElementById('modalAdiantarParcela').classList.remove('show');
    currentAdiantarId = null;
}

function confirmarAdiantamento() {
    document.getElementById('modalAdiantarParcela').classList.remove('show');
    document.getElementById('modalConfirmacaoFinal').classList.add('show');
    document.getElementById('btnConfirmarDefinitivo').href = 'pay_card_installment.php?id=' + currentAdiantarId;
}

function fecharModalConfirmacaoFinal() {
    document.getElementById('modalConfirmacaoFinal').classList.remove('show');
}

function atualizarCorSelectCartao() {
    const select = document.getElementById('inputCartaoIdCartao');
    if (!select) return;
    const option = select.options[select.selectedIndex];
    if (option) {
        const cor = option.getAttribute('data-cor');
        if (cor) {
            select.style.color = cor;
            select.style.borderColor = cor;
            select.style.fontWeight = '700';
        } else {
            select.style.color = 'white';
            select.style.borderColor = '#4a5b76';
            select.style.fontWeight = 'normal';
        }
    }
}

window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        mostrarAba(tab);
    }
    
    // Inicializa a cor do select de cartões
    atualizarCorSelectCartao();
});