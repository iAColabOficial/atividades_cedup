<?php
/**
 * Sistema de Salvamento de Dados - R&S Lab - VERSÃO ESTÁVEL
 * Professor Leandro Rodrigues
 */

// Debug mode
$debug_mode = true;

function debugLog($message) {
    global $debug_mode;
    if ($debug_mode) {
        error_log("[RS_LAB_DEBUG] " . $message);
    }
}

debugLog("=== INICIANDO save_data.php RS LAB ===");

// Incluir configurações globais
$config_paths = [
    '../config/config.php',
    __DIR__ . '/../config/config.php',
    '../../config/config.php'
];

$config_loaded = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $config_loaded = true;
        debugLog("Config carregado de: " . $path);
        break;
    }
}

if (!$config_loaded) {
    debugLog("ERRO FATAL: Configuração não encontrada");
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Configuração não encontrada']));
}

// Incluir autenticação
$auth_paths = [
    '../auth/auth.php',
    __DIR__ . '/../auth/auth.php',
    '../../auth/auth.php'
];

$auth_loaded = false;
foreach ($auth_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $auth_loaded = true;
        debugLog("Auth carregado de: " . $path);
        break;
    }
}

if (!$auth_loaded) {
    debugLog("ERRO FATAL: Sistema de autenticação não encontrado");
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Sistema de autenticação não encontrado']));
}

// Verificar autenticação
try {
    $user = requireAuth();
    debugLog("Usuário autenticado: ID=" . $user['id'] . ", Nome=" . ($user['name'] ?? 'N/A'));
} catch (Exception $e) {
    debugLog("ERRO na autenticação: " . $e->getMessage());
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

// Headers de segurança
try {
    if (function_exists('setSecurityHeaders')) {
        setSecurityHeaders();
    }
} catch (Exception $e) {
    debugLog("AVISO: Headers de segurança: " . $e->getMessage());
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    debugLog("ERRO: Método não é POST");
    if (function_exists('jsonResponse')) {
        jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    } else {
        http_response_code(405);
        die(json_encode(['success' => false, 'message' => 'Método não permitido']));
    }
}

// Verificar Content-Type
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($content_type, 'application/json') === false) {
    debugLog("ERRO: Content-Type incorreto");
    if (function_exists('jsonResponse')) {
        jsonResponse(['success' => false, 'message' => 'Content-Type deve ser application/json'], 400);
    } else {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Content-Type deve ser application/json']));
    }
}

try {
    // Obter dados JSON
    $json_input = file_get_contents('php://input');
    debugLog("JSON recebido (200 chars): " . substr($json_input, 0, 200));
    
    $data = json_decode($json_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        debugLog("ERRO: JSON inválido - " . json_last_error_msg());
        if (function_exists('jsonResponse')) {
            jsonResponse(['success' => false, 'message' => 'JSON inválido'], 400);
        } else {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'JSON inválido']));
        }
    }
    
    debugLog("Campos recebidos: " . implode(', ', array_keys($data)));
    
    // Validar dados básicos
    $validation_errors = validateRSLabData($data, $user);
    if (!empty($validation_errors)) {
        debugLog("ERRO: Validação falhou - " . implode("; ", $validation_errors));
        if (function_exists('jsonResponse')) {
            jsonResponse(['success' => false, 'message' => 'Dados inválidos', 'errors' => $validation_errors], 400);
        } else {
            http_response_code(400);
            die(json_encode(['success' => false, 'message' => 'Dados inválidos', 'errors' => $validation_errors]));
        }
    }
    
    // Conectar ao banco
    $pdo = getConnection();
    debugLog("Conexão com banco estabelecida");
    
    // Iniciar transação
    $pdo->beginTransaction();
    debugLog("Transação iniciada");
    
    try {
        // Salvar dados
        $result = saveToRSLabResults($pdo, $user, $data);
        
        // Confirmar transação
        $pdo->commit();
        debugLog("Transação confirmada - ID: " . $result['result_id']);
        
        // Resposta de sucesso
        if (function_exists('jsonResponse')) {
            jsonResponse($result);
        } else {
            header('Content-Type: application/json');
            echo json_encode($result);
        }
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        debugLog("ERRO PDO: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    debugLog("ERRO geral: " . $e->getMessage());
    if (function_exists('jsonResponse')) {
        jsonResponse(['success' => false, 'message' => 'Erro interno do servidor'], 500);
    } else {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Erro interno do servidor']));
    }
}

/**
 * Salvar na tabela rs_lab_results
 */
function saveToRSLabResults($pdo, $user, $data) {
    debugLog("=== SALVANDO DADOS ===");
    
    // Calcular dados
    $start_time = date('Y-m-d H:i:s', strtotime($data['startTime']));
    $end_time = date('Y-m-d H:i:s', strtotime($data['endTime']));
    $duration = strtotime($data['endTime']) - strtotime($data['startTime']);
    $final_score = calculateFinalScore($data);
    $formulas_used_json = json_encode($data['formulasUsed']);
    
    debugLog("Score: " . $final_score . " | Duração: " . $duration . "s");
    
    // Inserir dados principais
    $sql = "
        INSERT INTO rs_lab_results 
        (user_id, user_name, user_email, user_registration, user_course, user_turma,
         scenario_id, total_questions, correct_answers, final_score, formulas_used, 
         time_spent_seconds, start_time, end_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $user['id'],
        $data['name'],
        $data['email'],
        $data['registration'],
        $data['course'],
        $user['turma'] ?? '',
        $data['scenario_id'],
        $data['totalQuestions'],
        $data['correctAnswers'],
        $final_score,
        $formulas_used_json,
        $duration,
        $start_time,
        $end_time
    ]);
    
    $result_id = $pdo->lastInsertId();
    debugLog("Dados principais salvos - ID: " . $result_id);
    
    return [
        'success' => true,
        'message' => 'Laboratório R&S concluído com sucesso!',
        'result_id' => $result_id,
        'final_score' => $final_score,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * Calcular pontuação final
 */
function calculateFinalScore($data) {
    if (!isset($data['totalQuestions']) || $data['totalQuestions'] == 0) {
        return 0;
    }
    
    $base_score = ($data['correctAnswers'] / $data['totalQuestions']) * 100;
    return min(100, round($base_score, 2));
}

/**
 * Validar dados
 */
function validateRSLabData($data, $user) {
    $errors = [];
    
    if (empty($data['user_id']) || $data['user_id'] != $user['id']) {
        $errors[] = "ID do usuário não confere";
    }
    
    if (empty($data['name']) || strlen(trim($data['name'])) < 3) {
        $errors[] = "Nome inválido";
    }
    
    if (empty($data['scenario_id'])) {
        $errors[] = "Scenario ID obrigatório";
    }
    
    if (!isset($data['totalQuestions']) || $data['totalQuestions'] < 1) {
        $errors[] = "Número de questões inválido";
    }
    
    if (!isset($data['correctAnswers']) || $data['correctAnswers'] < 0) {
        $errors[] = "Número de respostas corretas inválido";
    }
    
    if (empty($data['startTime']) || empty($data['endTime'])) {
        $errors[] = "Horários obrigatórios";
    }
    
    return $errors;
}

// Endpoint para status (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['status'])) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'rs_lab_results'");
        $table_exists = $stmt->rowCount() > 0;
        
        $record_count = 0;
        if ($table_exists) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM rs_lab_results");
            $record_count = $stmt->fetchColumn();
        }
        
        if (function_exists('jsonResponse')) {
            jsonResponse([
                'module' => 'rs_lab',
                'status' => 'active',
                'table_exists' => $table_exists,
                'record_count' => $record_count,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'module' => 'rs_lab',
                'status' => 'active',
                'table_exists' => $table_exists,
                'record_count' => $record_count,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        
    } catch (Exception $e) {
        if (function_exists('jsonResponse')) {
            jsonResponse(['error' => $e->getMessage()], 500);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
?>