feather.replace();

function mostrarAba(aba) {
    document.getElementById('aba-fixas').style.display = aba === 'fixas' ? 'block' : 'none';
    document.getElementById('aba-variaveis').style.display = aba === 'variaveis' ? 'block' : 'none';
    document.getElementById('aba-quitados').style.display = aba === 'quitados' ? 'block' : 'none';
    
    document.getElementById('btn-fixas').classList.toggle('active', aba === 'fixas');
    document.getElementById('btn-variaveis').classList.toggle('active', aba === 'variaveis');
    document.getElementById('btn-quitados').classList.toggle('active', aba === 'quitados');
}

function abrirModalDespesa() {
    document.getElementById('modalDespesa').classList.add('show');
    document.getElementById('formDespesa').action = 'add_expense.php';
    document.querySelector('#modalDespesa h3').innerText = 'Nova Despesa';
    document.getElementById('inputIdDespesa').value = '';
    document.getElementById('formDespesa').reset();
}

function abrirModalEdicaoDespesa(id, tipo, nome, valor, dia, categoria, obs) {
    document.getElementById('modalDespesa').classList.add('show');
    document.getElementById('formDespesa').action = 'edit_expense.php';
    document.querySelector('#modalDespesa h3').innerText = 'Editar Despesa';
    
    document.getElementById('inputIdDespesa').value = id;
    document.getElementById('inputTipoDespesa').value = tipo;
    document.getElementById('inputNomeDespesa').value = nome;
    document.getElementById('inputValorDespesa').value = valor;
    document.getElementById('inputDiaVencimento').value = dia;
    document.getElementById('inputCategoriaDespesa').value = categoria;
    document.getElementById('inputObservacoesDespesa').value = obs;
}

function fecharModalDespesa() {
    document.getElementById('modalDespesa').classList.remove('show');
    document.getElementById('formDespesa').reset();
}

window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        mostrarAba(tab);
    }
});

