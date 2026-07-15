feather.replace();

function abrirModalReserva() {
    document.getElementById('modalReserva').classList.add('show');
    document.getElementById('formReserva').action = 'add_reserve.php';
    document.querySelector('#modalReserva h3').innerText = 'Adicionar à Reserva';
    document.getElementById('inputId').value = '';
    document.getElementById('formReserva').reset();
    calcularRendimento();
}

function abrirModalEdicaoReserva(id, desc, inst, tipo, valor, data, ind, perc) {
    document.getElementById('modalReserva').classList.add('show');
    document.getElementById('formReserva').action = 'edit_reserve.php';
    document.querySelector('#modalReserva h3').innerText = 'Editar Reserva';
    
    document.getElementById('inputId').value = id;
    document.getElementById('inputDescricao').value = desc;
    document.getElementById('inputInstituicao').value = inst;
    document.getElementById('inputTipoAplicacao').value = tipo;
    document.getElementById('inputValor').value = valor;
    document.getElementById('inputData').value = data;
    document.getElementById('inputIndexador').value = ind;
    document.getElementById('inputPorcentagem').value = perc;
    
    calcularRendimento();
}

function fecharModalReserva() {
    document.getElementById('modalReserva').classList.remove('show');
    document.getElementById('formReserva').reset();
    calcularRendimento(); // Zera os valores visuais ao fechar
}

function calcularRendimento() {
    const valor = parseFloat(document.getElementById('inputValor').value) || 0;
    const indexador = document.getElementById('inputIndexador').value;
    const porcentagem = parseFloat(document.getElementById('inputPorcentagem').value) || 100;

    let taxaAnual = 0;
    let taxaMensal = 0;

    if (indexador === 'Poupança') {
        taxaAnual = 0.0617 * (porcentagem / 100);
        taxaMensal = 0.0050017 * (porcentagem / 100);
    } else if (indexador === 'CDI') {
        taxaAnual = 0.1050 * (porcentagem / 100);
        taxaMensal = (Math.pow(1 + 0.1050, 1/12) - 1) * (porcentagem / 100);
    }

    const rendMensal = valor * taxaMensal;
    const rendAnual = valor * taxaAnual;

    document.getElementById('rendMensal').innerText = rendMensal.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
    document.getElementById('rendAnual').innerText = rendAnual.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
    
    const btnSubmit = document.querySelector('#formReserva button[type="submit"]');
    if(valor > 0) {
        btnSubmit.style.background = '#06b6d4';
        btnSubmit.style.color = '#fff';
    } else {
        btnSubmit.style.background = '#53657f';
        btnSubmit.style.color = '#a0aec0';
    }
}
