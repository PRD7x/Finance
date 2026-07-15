<?php
// Inicia a sessão para identificar o usuário autenticado que está enviando a requisição
session_start();

// Define o cabeçalho de resposta HTTP como JSON para que o Javascript receba o retorno estruturado
header('Content-Type: application/json');

// Validação de Sessão: se o ID do usuário não estiver definido, retorna erro de autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sessão expirada. Faça login novamente.']);
    exit();
}

// Importa a conexão com o banco de dados
require_once 'config.php';

// Armazena o ID do usuário conectado
$user_id = $_SESSION['user_id'];

// Processa apenas requisições HTTP do tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta as variáveis enviadas pelo formulário ou AJAX (campo a ser alterado e o novo valor)
    $field = $_POST['field'] ?? '';
    $value = floatval($_POST['value'] ?? 0);

    // Lista branca (Whitelist) de campos válidos para atualização direta pelo usuário
    // Isso impede qualquer alteração arbitrária de colunas não autorizadas do banco de dados
    $allowed_fields = ['patrimonio', 'reserva', 'gastos_fixos', 'cartao', 'salario'];

    // Validação: se o campo recebido não estiver na Whitelist, recusa a requisição
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['success' => false, 'error' => 'Campo inválido para atualização.']);
        exit();
    }

    // Validação de Negócio: não é permitido cadastrar valores negativos nas finanças
    if ($value < 0) {
        echo json_encode(['success' => false, 'error' => 'O valor não pode ser negativo.']);
        exit();
    }

    // Prepara a consulta SQL dinâmica para atualização
    // Como o nome da coluna ($field) vem da whitelist controlada, não há perigo de SQL Injection na coluna.
    // O valor do parâmetro é passado com bind_param para garantir a segurança.
    $query = "UPDATE finance_data SET $field = ? WHERE user_id = ?";
    $stmt = $conexao->prepare($query);
    if ($stmt) {
        // Vincula os parâmetros: "d" para double/float (valor) e "i" para integer (ID do usuário)
        $stmt->bind_param("di", $value, $user_id);
        
        // Executa a query de atualização do campo
        if ($stmt->execute()) {
            
            // Regra de Negócio: Removido o recálculo incorreto (gastos_mes = gastos_fixos + cartao)
            // pois gastos_mes engloba Fixos + Variáveis, e Cartão é separado.

            // Seleciona novamente as informações financeiras completas e atualizadas do usuário no banco
            $stmt_select = $conexao->prepare("SELECT * FROM finance_data WHERE user_id = ?");
            if ($stmt_select) {
                $stmt_select->bind_param("i", $user_id);
                $stmt_select->execute();
                $result = $stmt_select->get_result();
                $finance = $result->fetch_assoc();

                // Retorna o JSON de sucesso enviando todo o payload financeiro atualizado para o frontend redesenhar a tela
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'patrimonio' => (float)$finance['patrimonio'],
                        'salario' => (float)$finance['salario'],
                        'reserva' => (float)$finance['reserva'],
                        'gastos_fixos' => (float)$finance['gastos_fixos'],
                        'cartao' => (float)$finance['cartao'],
                        'gastos_mes' => (float)$finance['gastos_mes']
                    ]
                ]);
                exit();
            }
        }
    }
    
    // Retorna falha genérica se não conseguir salvar no banco de dados
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar os dados no servidor.']);
    exit();
}

// Retorna erro se a rota for acessada de forma inadequada (por exemplo, via requisição GET)
echo json_encode(['success' => false, 'error' => 'Método de requisição inválido.']);
exit();
?>
