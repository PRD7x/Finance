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


const modal = document.getElementById('updateModal');
const updateForm = document.getElementById('updateForm');
const modalTitle = document.getElementById('modalTitle');
const modalField = document.getElementById('modalField');
const modalValue = document.getElementById('modalValue');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelModalBtn = document.getElementById('cancelModalBtn');

// Adiciona evento de clique em todos os botões da seção de atalhos rápidos
document.querySelectorAll('.action-btn').forEach(button => {
    button.addEventListener('click', () => {
        const field = button.getAttribute('data-field');
        const title = button.getAttribute('data-title');
        
        modalTitle.textContent = title;
        modalField.value = field;
        
        // Carrega o valor atual daquele campo como valor inicial do input do modal
        const valorAtual = dados[field];
        modalValue.value = valorAtual > 0 ? valorAtual : '';
        modalValue.focus();
        
        // Exibe o modal overlay adicionando a classe "show"
        modal.classList.add('show');
    });
});

// Oculta a janela modal e limpa os campos digitados
function fecharModal() {
    modal.classList.remove('show');
    updateForm.reset();
}

// Associa o fechamento aos botões de fechar (X) e cancelar
closeModalBtn.addEventListener('click', fecharModal);
cancelModalBtn.addEventListener('click', fecharModal);

// Fecha o modal se o usuário clicar na área borrada externa (overlay)
modal.addEventListener('click', (e) => {
    if (e.target === modal) {
        fecharModal();
    }
});

// Escuta a submissão do formulário do Modal para salvamento genérico dos campos
updateForm.addEventListener('submit', (e) => {
    e.preventDefault(); // Evita recarregamento de página
    
    const field = modalField.value;
    const value = modalValue.value;
    
    // Constrói os dados a serem transmitidos por POST
    const formData = new FormData();
    formData.append('field', field);
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