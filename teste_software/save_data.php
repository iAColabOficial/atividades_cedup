<?php
/**
 * Teste de Software - Salvamento de Dados
 * Professor Leandro Rodrigues
 * Sistema de Atividades Educacionais
 */

// Configurações de erro e headers
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não mostrar erros para não quebrar JSON

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido. Use POST.'
    ]);
    exit;
}

try {
    // Incluir configurações
    require_once '../config/config.php';
    
    // Conectar ao banco
    $pdo = getConnection();
    
    // Ler dados JSON
    $json_input = file_get_contents('php://input');
    if (empty($json_input)) {
        throw new Exception('Nenhum dado recebido');
    }
    
    $data = json_decode($json_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }
    
    // Validar dados obrigatórios
    $required_fields = [
        'user_name', 'user_registration', 'resposta_login', 
        'resposta_carrinho', 'resposta_cadastro', 'resposta_reflexao'
    ];
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo obrigatório ausente: {$field}");
        }
    }
    
    // Validar tamanho mínimo das respostas
    $min_length = 50;
    $answers = [
        'Login' => $data['resposta_login'],
        'Carrinho' => $data['resposta_carrinho'],
        'Cadastro' => $data['resposta_cadastro'],
        'Reflexão' => $data['resposta_reflexao']
    ];
    
    foreach ($answers as $scenario => $answer) {
        if (strlen(trim($answer)) < $min_length) {
            throw new Exception("Resposta do cenário '{$scenario}' muito curta. Mínimo: {$min_length} caracteres.");
        }
    }
    
    // Preparar dados para inserção
    $user_id = sanitizeInput($data['user_id'] ?? $data['user_registration']);
    $user_name = sanitizeInput($data['user_name']);
    $user_email = sanitizeInput($data['user_email'] ?? '');
    $user_registration = sanitizeInput($data['user_registration']);
    $user_course = sanitizeInput($data['user_course'] ?? '');
    $user_turma = sanitizeInput($data['user_turma'] ?? '');
    
    $resposta_login = sanitizeInput($data['resposta_login']);
    $resposta_carrinho = sanitizeInput($data['resposta_carrinho']);
    $resposta_cadastro = sanitizeInput($data['resposta_cadastro']);
    $resposta_reflexao = sanitizeInput($data['resposta_reflexao']);
    
    $total_scenarios = intval($data['total_scenarios'] ?? 3);
    $final_score = floatval($data['final_score'] ?? 0);
    $time_spent_seconds = intval($data['time_spent_seconds'] ?? 0);
    
    // Tratar datas
    $start_time = date('Y-m-d H:i:s', strtotime($data['start_time'] ?? 'now'));
    $end_time = date('Y-m-d H:i:s', strtotime($data['end_time'] ?? 'now'));
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Inserir resultado principal
    $stmt = $pdo->prepare("
        INSERT INTO teste_software_results 
        (user_id, user_name, user_email, user_registration, user_course, user_turma,
         resposta_login, resposta_carrinho, resposta_cadastro, resposta_reflexao,
         total_scenarios, final_score, time_spent_seconds, start_time, end_time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $user_id, $user_name, $user_email, $user_registration, $user_course, $user_turma,
        $resposta_login, $resposta_carrinho, $resposta_cadastro, $resposta_reflexao,
        $total_scenarios, $final_score, $time_spent_seconds, $start_time, $end_time
    ]);
    
    if (!$result) {
        throw new Exception('Erro ao inserir resultado principal');
    }
    
    $result_id = $pdo->lastInsertId();
    
    // Inserir respostas detalhadas (opcional, para análises futuras)
    $detailed_answers = [
        [
            'scenario_id' => 'login',
            'scenario_title' => 'Formulário de Login',
            'user_answer' => $resposta_login,
            'time_spent' => intval($data['scenario_times']['answer1'] ?? 0)
        ],
        [
            'scenario_id' => 'carrinho', 
            'scenario_title' => 'Carrinho de Compras',
            'user_answer' => $resposta_carrinho,
            'time_spent' => intval($data['scenario_times']['answer2'] ?? 0)
        ],
        [
            'scenario_id' => 'cadastro',
            'scenario_title' => 'Cadastro de Cliente', 
            'user_answer' => $resposta_cadastro,
            'time_spent' => intval($data['scenario_times']['answer3'] ?? 0)
        ],
        [
            'scenario_id' => 'reflexao',
            'scenario_title' => 'Reflexão Final',
            'user_answer' => $resposta_reflexao,
            'time_spent' => intval($data['scenario_times']['answer4'] ?? 0)
        ]
    ];
    
    $stmt_answers = $pdo->prepare("
        INSERT INTO teste_software_answers 
        (result_id, scenario_id, scenario_title, user_answer, answer_length, 
         time_spent_scenario, quality_score, points_earned)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($detailed_answers as $answer) {
        $answer_length = strlen($answer['user_answer']);
        $quality_score = calculateQualityScore($answer['user_answer'], $answer['scenario_id']);
        $points_earned = intval($final_score / 4); // Dividir pontuação igualmente
        
        $stmt_answers->execute([
            $result_id,
            $answer['scenario_id'],
            $answer['scenario_title'],
            $answer['user_answer'],
            $answer_length,
            $answer['time_spent'],
            $quality_score,
            $points_earned
        ]);
    }
    
    // Commit da transação
    $pdo->commit();
    
    // Log da atividade
    logActivity($user_id, 'TESTE_SOFTWARE', 'Atividade concluída', [
        'user_name' => $user_name,
        'final_score' => $final_score,
        'time_spent' => $time_spent_seconds
    ]);
    
    // Resposta de sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Atividade salva com sucesso!',
        'data' => [
            'result_id' => $result_id,
            'final_score' => $final_score,
            'time_spent' => $time_spent_seconds,
            'scenarios_saved' => count($detailed_answers),
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback em caso de erro
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log do erro
    error_log('Erro no save_data.php (Teste Software): ' . $e->getMessage());
    
    // Resposta de erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor',
        'error_code' => 'SAVE_ERROR',
        'debug_info' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

/**
 * Sanitizar entrada de dados
 */
function sanitizeInput($input) {
    if (is_string($input)) {
        return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
    return $input;
}

/**
 * Calcular pontuação de qualidade baseada no conteúdo
 */
function calculateQualityScore($answer, $scenario_id) {
    $score = 0;
    $answer_lower = strtolower($answer);
    
    // Pontuação base por tamanho
    $length = strlen($answer);
    if ($length >= 300) $score += 30;
    elseif ($length >= 200) $score += 20;
    elseif ($length >= 100) $score += 15;
    elseif ($length >= 50) $score += 10;
    
    // Palavras-chave por cenário
    $keywords = [
        'login' => [
            'validação' => 10, 'senha' => 8, 'usuário' => 8, 'autenticação' => 10,
            'segurança' => 10, 'erro' => 6, 'bloqueio' => 8, 'tentativas' => 6,
            'campo' => 4, 'obrigatório' => 6, 'vazio' => 4, 'incorreto' => 6
        ],
        'carrinho' => [
            'produto' => 8, 'quantidade' => 8, 'estoque' => 10, 'preço' => 6,
            'total' => 6, 'finalizar' => 8, 'remover' => 6, 'adicionar' => 6,
            'compra' => 6, 'frete' => 8, 'desconto' => 6, 'pagamento' => 8
        ],
        'cadastro' => [
            'cpf' => 10, 'email' => 8, 'validação' => 10, 'obrigatório' => 8,
            'formato' => 8, 'duplicado' => 10, 'confirmar' => 8, 'senha' => 6,
            'campo' => 4, 'telefone' => 6, 'endereço' => 6, 'cep' => 8
        ],
        'reflexao' => [
            'qualidade' => 10, 'confiabilidade' => 10, 'usuário' => 6, 'defeito' => 8,
            'custo' => 8, 'segurança' => 8, 'teste' => 6, 'software' => 4,
            'importância' => 8, 'garantir' => 6, 'experiência' => 6, 'sistema' => 4
        ]
    ];
    
    if (isset($keywords[$scenario_id])) {
        foreach ($keywords[$scenario_id] as $keyword => $points) {
            if (strpos($answer_lower, $keyword) !== false) {
                $score += $points;
            }
        }
    }
    
    // Pontuação por estrutura (frases, pontuação)
    $sentences = preg_split('/[.!?]+/', $answer);
    if (count($sentences) >= 3) $score += 10;
    if (count($sentences) >= 5) $score += 5;
    
    // Limitar pontuação máxima
    return min($score, 100);
}

/**
 * Registrar atividade no log (se sistema de log existir)
 */
function logActivity($user_id, $module, $action, $details = []) {
    try {
        $pdo = getConnection();
        
        // Verificar se tabela de logs existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
        if ($stmt->rowCount() == 0) {
            return; // Tabela não existe, ignorar log
        }
        
        // Inserir log
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, module, action, description, level)
            VALUES (?, ?, ?, ?, 'INFO')
        ");
        
        $description = json_encode($details);
        $stmt->execute([$user_id, $module, $action, $description]);
        
    } catch (Exception $e) {
        // Ignorar erros de log para não afetar o processo principal
        error_log('Erro ao registrar log: ' . $e->getMessage());
    }
}

/**
 * Obter configurações do módulo
 */
function getModuleConfig() {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->query("SELECT config_key, config_value FROM teste_software_config");
        $configs = [];
        
        while ($row = $stmt->fetch()) {
            $configs[$row['config_key']] = $row['config_value'];
        }
        
        return $configs;
        
    } catch (Exception $e) {
        // Retornar configurações padrão se houver erro
        return [
            'module_enabled' => 'true',
            'max_time_minutes' => '45',
            'min_answer_length' => '50',
            'auto_feedback' => 'true',
            'passing_score' => '60'
        ];
    }
}

/**
 * Validar se módulo está ativo
 */
function validateModuleStatus() {
    $config = getModuleConfig();
    
    if ($config['module_enabled'] !== 'true') {
        throw new Exception('Módulo Teste de Software está desativado');
    }
    
    return true;
}

// Validar status do módulo antes de processar
try {
    validateModuleStatus();
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'MODULE_DISABLED'
    ]);
    exit;
}
?>