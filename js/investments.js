feather.replace();

function abrirModalInvestimento() {
    document.getElementById('modalInvestimento').classList.add('show');
    document.getElementById('formInvestimento').action = 'add_investment.php';
    document.getElementById('inputId').value = '';
    document.querySelector('#modalInvestimento h2').innerText = 'Adicionar Investimento';
}

function abrirModalEdicaoInvestimento(id, desc, valor, data, obs) {
    document.getElementById('modalInvestimento').classList.add('show');
    document.getElementById('formInvestimento').action = 'edit_investment.php';
    document.querySelector('#modalInvestimento h2').innerText = 'Editar Investimento';
    
    document.getElementById('inputId').value = id;
    document.getElementById('inputDescricao').value = desc;
    document.getElementById('inputValor').value = valor;
    document.getElementById('inputData').value = data;
    document.getElementById('inputObs').value = obs;
}

function fecharModalInvestimento() {
    document.getElementById('modalInvestimento').classList.remove('show');
    document.getElementById('formInvestimento').reset();
}
