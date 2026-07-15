// ==============================================================================
// INICIALIZAÇÃO E CONFIGURAÇÃO
// ==============================================================================

// Inicializa a biblioteca Feather Icons para substituir tags <i data-feather="...">
// pelos respectivos vetores SVG dos ícones.
feather.replace();

// Carrega os dados financeiros iniciais fornecidos pelo PHP (ponte de dados no HTML)
// Caso não estejam definidos, inicializa um objeto vazio com valores padrão em zero.
let dados = typeof initialFinanceData !== 'undefined' ? initialFinanceData : {
    patrimonio: 0,
    salario: 0,
    reserva: 0,
    gastos_fixos: 0,
    cartao: 0,
    gastos_mes: 0
};

// ==============================================================================
// HELPER: FORMATAÇÃO DE MOEDA (BRL)
// ==============================================================================
// Formata um valor numérico para o formato de moeda Real Brasileiro (R$ XX.XXX,XX)
const moeda = valor =>
    valor.toLocaleString("pt-BR", {
        style: "currency",
        currency: "BRL"
    });

// ==============================================================================
// RENDERIZAÇÃO DO PAINEL (DASHBOARD)
// ==============================================================================
// Atualiza dinamicamente os valores exibidos na tela com base no objeto de dados atual
function renderDashboard(data) {
    // Atualiza o texto do Patrimônio Total
    document.getElementById("patrimonio").textContent = moeda(data.patrimonio);
    
    // Atualiza o texto do Salário: exibe o valor formatado ou 'Não cadastrado' caso seja zero
    const salarioEl = document.getElementById("salario");
    if (salarioEl) {
        salarioEl.textContent = data.salario > 0 ? moeda(data.salario) : "Não cadastrado";
    }
    
    // Atualiza os textos dos demais cartões estáticos formatados como moeda BRL
    document.getElementById("reserva").textContent = moeda(data.reserva);
    document.getElementById("fixos").textContent = moeda(data.gastos_fixos);
    document.getElementById("cartao").textContent = moeda(data.cartao);
    document.getElementById("mes").textContent = moeda(data.gastos_mes);
}

// Executa a primeira renderização dos dados assim que o arquivo JS é carregado
renderDashboard(dados);


const modal = document.getElementById('updateSalaryModal');
const updateForm = document.getElementById('updateSalaryForm');
const modalValue = document.getElementById('salaryValue');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelModalBtn = document.getElementById('cancelModalBtn');

// Abre o modal de salário ao acionar os gatilhos data-action="edit-salary"
document.querySelectorAll('[data-action="edit-salary"]').forEach(trigger => {
    trigger.addEventListener('click', (e) => {
        e.preventDefault();
        
        // Carrega o valor atual do salário no input
        const valorAtual = dados.salario;
        modalValue.value = valorAtual > 0 ? valorAtual : '';
        
        // Exibe o modal
        modal.classList.add('show');
        modalValue.focus();
    });
});

// Oculta a janela modal e limpa os campos digitados
function fecharModal() {
    modal.classList.remove('show');
    updateForm.reset();
}

// Associa o fechamento aos botões de fechar (X) e cancelar
if (closeModalBtn) closeModalBtn.addEventListener('click', fecharModal);
if (cancelModalBtn) cancelModalBtn.addEventListener('click', fecharModal);

// Fecha o modal se o usuário clicar na área borrada externa (overlay)
if (modal) {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            fecharModal();
        }
    });
}

// Escuta a submissão do formulário do Salário
if (updateForm) {
    updateForm.addEventListener('submit', (e) => {
        e.preventDefault(); // Evita recarregamento de página
        
        const value = modalValue.value;
        
        // Constrói os dados a serem transmitidos por POST
        const formData = new FormData();
        formData.append('field', 'salario');
        formData.append('value', value);
        
        // Dispara a requisição assíncrona fetch para o arquivo PHP de processamento financeiro
        fetch('update_finance.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Sincroniza a memória local com a resposta e atualiza o painel
                dados = result.data;
                renderDashboard(dados);
                fecharModal();
            } else {
                alert('Erro: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            alert('Erro ao conectar-se ao servidor. Tente novamente.');
        });
    });
}