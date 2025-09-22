<?php
/**
 * Sistema de Salvamento de Dados - DER Quiz
 * Professor Leandro Rodrigues
 * 
 * Processa e salva os resultados do quiz de DER no banco de dados
 * Versão híbrida compatível com estruturas antiga e nova
 */

// Incluir configurações globais com verificação de caminhos
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
        break;
    }
}

if (!$config_loaded) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Configuração não encontrada']));
}

// Incluir autenticação com verificação de caminhos
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
        break;
    }
}

if (!$auth_loaded) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Sistema de autenticação não encontrado']));
}

// Verificar autenticação
try {
    $user = requireAuth();
} catch (Exception $e) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Usuário não autenticado']));
}

// Definir headers de segurança
setSecurityHeaders();

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

// Verificar Content-Type
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($content_type, 'application/json') === false) {
    jsonResponse(['success' => false, 'message' => 'Content-Type deve ser application/json'], 400);
}

try {
    // Obter dados JSON da requisição
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['success' => false, 'message' => 'JSON inválido'], 400);
    }
    
    // Validar dados de entrada
    $validation_errors = validateQuizData($data, $user);
    if (!empty($validation_errors)) {
        jsonResponse([
            'success' => false, 
            'message' => 'Dados inválidos',
            'errors' => $validation_errors
        ], 400);
    }
    
    // Sanitizar dados
    $data = sanitizeInput($data);
    
    // Verificar limite de tentativas diárias
    if (checkDailyLimit($user['id'])) {
        jsonResponse([
            'success' => false, 
            'message' => 'Limite de tentativas diárias excedido (máximo 3 por dia)'
        ], 429);
    }
    
    // Conectar ao banco
    $pdo = getConnection();
    
    // Detectar qual estrutura usar
    $structure = detectTableStructure($pdo);
    
    // Verificar/criar tabelas específicas do módulo DER
    createDerQuizTables($pdo);
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    try {
        if ($structure === 'new') {
            // Usar nova estrutura
            $result = saveToNewStructure($pdo, $user, $data);
        } else {
            // Usar estrutura antiga
            $result = saveToOldStructure($pdo, $user, $data);
        }
        
        // Confirmar transação
        $pdo->commit();
        
        // Log da atividade
        logActivity(
            "DER Quiz concluído [{$structure}] - Score: {$data['correctAnswers']}/{$data['totalQuestions']}", 
            'INFO', 
            'DER_QUIZ', 
            $user['id']
        );
        
        // Resposta de sucesso
        jsonResponse($result);
        
    } catch (PDOException $e) {
        // Reverter transação em caso de erro
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    logActivity("Erro de banco no DER Quiz: " . $e->getMessage(), 'ERROR', 'DER_QUIZ', $user['id'] ?? null);
    jsonResponse([
        'success' => false,
        'message' => 'Erro interno do servidor - banco de dados'
    ], 500);
    
} catch (Exception $e) {
    logActivity("Erro geral no DER Quiz: " . $e->getMessage(), 'ERROR', 'DER_QUIZ', $user['id'] ?? null);
    jsonResponse([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ], 500);
}

/**
 * Detectar qual estrutura de tabela usar
 */
function detectTableStructure($pdo) {
    try {
        // Tentar verificar se existe der_quiz_results (nova estrutura)
        $stmt = $pdo->query("SHOW TABLES LIKE 'der_quiz_results'");
        if ($stmt->rowCount() > 0) {
            return 'new';
        }
        return 'old';
    } catch (Exception $e) {
        return 'old'; // Fallback para estrutura antiga
    }
}

/**
 * Salvar usando nova estrutura
 */
function saveToNewStructure($pdo, $user, $data) {
    // Salvar na tabela system_user_activities
    $system_activity_id = saveSystemActivity($pdo, $user, $data);
    
    // Salvar dados específicos do DER Quiz
    $der_quiz_id = saveDerQuizResults($pdo, $user, $data, $system_activity_id);
    
    // Salvar respostas individuais
    saveDerQuizAnswers($pdo, $der_quiz_id, $data['answers']);
    
    // Salvar atividades suspeitas se houver
    if (!empty($data['suspiciousActivity'])) {
        saveSuspiciousActivity($pdo, $der_quiz_id, $data['suspiciousActivity']);
    }
    
    return [
        'success' => true,
        'message' => 'Quiz concluído com sucesso!',
        'quiz_id' => $der_quiz_id,
        'system_activity_id' => $system_activity_id,
        'final_score' => calculateScore($data),
        'structure' => 'new'
    ];
}

/**
 * Salvar usando estrutura antiga
 */
function saveToOldStructure($pdo, $user, $data) {
    // Salvar na tabela der_quiz_students (estrutura antiga)
    $student_id = saveDerQuizStudents($pdo, $user, $data);
    
    // Salvar respostas na estrutura antiga
    saveDerQuizAnswersOld($pdo, $student_id, $data['answers']);
    
    return [
        'success' => true,
        'message' => 'Quiz concluído com sucesso!',
        'student_id' => $student_id,
        'final_score' => calculateScore($data),
        'structure' => 'old'
    ];
}

/**
 * Criar tabelas específicas do DER Quiz
 */
function createDerQuizTables($pdo) {
    // Verificar se usar nova estrutura
    if (detectTableStructure($pdo) === 'new') {
        createNewStructureTables($pdo);
    } else {
        createOldStructureTables($pdo);
    }
}

/**
 * Criar tabelas da nova estrutura
 */
function createNewStructureTables($pdo) {
    // Tabela para resultados do DER Quiz
    $sql_results = "
        CREATE TABLE IF NOT EXISTS der_quiz_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            system_activity_id INT NOT NULL,
            total_questions INT NOT NULL,
            correct_answers INT NOT NULL,
            final_score DECIMAL(5,2) NOT NULL,
            time_spent_seconds INT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            tab_switches INT DEFAULT 0,
            copy_attempts INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES system_users(id) ON DELETE CASCADE,
            FOREIGN KEY (system_activity_id) REFERENCES system_user_activities(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_final_score (final_score),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Tabela para respostas individuais
    $sql_answers = "
        CREATE TABLE IF NOT EXISTS der_quiz_answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quiz_result_id INT NOT NULL,
            question_id VARCHAR(10) NOT NULL,
            question_title VARCHAR(255) NOT NULL,
            selected_answer VARCHAR(10),
            correct_answer VARCHAR(10) NOT NULL,
            is_correct BOOLEAN NOT NULL,
            time_spent INT NOT NULL,
            was_timeout BOOLEAN DEFAULT FALSE,
            answer_timestamp DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quiz_result_id) REFERENCES der_quiz_results(id) ON DELETE CASCADE,
            INDEX idx_quiz_result_id (quiz_result_id),
            INDEX idx_question_id (question_id),
            INDEX idx_is_correct (is_correct)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Tabela para atividades suspeitas
    $sql_suspicious = "
        CREATE TABLE IF NOT EXISTS der_quiz_suspicious_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quiz_result_id INT NOT NULL,
            activity_type VARCHAR(100) NOT NULL,
            description TEXT,
            question_number INT,
            activity_timestamp DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (quiz_result_id) REFERENCES der_quiz_results(id) ON DELETE CASCADE,
            INDEX idx_quiz_result_id (quiz_result_id),
            INDEX idx_activity_type (activity_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $pdo->exec($sql_results);
        $pdo->exec($sql_answers);
        $pdo->exec($sql_suspicious);
    } catch (PDOException $e) {
        logActivity("Erro ao criar tabelas do DER Quiz: " . $e->getMessage(), 'ERROR', 'DER_QUIZ');
        throw new Exception("Falha na criação das tabelas do DER Quiz");
    }
}

/**
 * Criar tabelas da estrutura antiga
 */
function createOldStructureTables($pdo) {
    // Tabela para estudantes (estrutura antiga)
    $sql_students = "
        CREATE TABLE IF NOT EXISTS der_quiz_students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            name VARCHAR(255) NOT NULL,
            registration VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            course VARCHAR(100) NOT NULL,
            total_questions INT NOT NULL,
            correct_answers INT NOT NULL,
            final_score DECIMAL(5,2) NOT NULL,
            time_spent_seconds INT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_registration (registration),
            INDEX idx_final_score (final_score)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Tabela para respostas (estrutura antiga)
    $sql_answers_old = "
        CREATE TABLE IF NOT EXISTS der_quiz_answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            question_id VARCHAR(10) NOT NULL,
            question_title VARCHAR(255) NOT NULL,
            selected_answer VARCHAR(10),
            correct_answer VARCHAR(10) NOT NULL,
            is_correct BOOLEAN NOT NULL,
            time_spent INT NOT NULL,
            was_timeout BOOLEAN DEFAULT FALSE,
            answer_timestamp DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES der_quiz_students(id) ON DELETE CASCADE,
            INDEX idx_student_id (student_id),
            INDEX idx_question_id (question_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $pdo->exec($sql_students);
        $pdo->exec($sql_answers_old);
    } catch (PDOException $e) {
        logActivity("Erro ao criar tabelas antigas do DER Quiz: " . $e->getMessage(), 'ERROR', 'DER_QUIZ');
        throw new Exception("Falha na criação das tabelas do DER Quiz");
    }
}

/**
 * Salvar na tabela system_user_activities (nova estrutura)
 */
function saveSystemActivity($pdo, $user, $data) {
    $start_time = date('Y-m-d H:i:s', strtotime($data['startTime']));
    $end_time = date('Y-m-d H:i:s', strtotime($data['endTime']));
    $duration = strtotime($data['endTime']) - strtotime($data['startTime']);
    
    $activity_data = [
        'total_questions' => $data['totalQuestions'],
        'correct_answers' => $data['correctAnswers'],
        'final_score' => calculateScore($data),
        'start_time' => $start_time,
        'end_time' => $end_time,
        'user_name' => $data['name'],
        'user_email' => $data['email'],
        'user_registration' => $data['registration'],
        'user_course' => $data['course'],
        'suspicious_activity_count' => count($data['suspiciousActivity'] ?? []),
        'tab_switches' => $data['tabSwitchCount'] ?? 0,
        'copy_attempts' => $data['copyAttempts'] ?? 0
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO system_user_activities 
        (user_id, module, activity_type, data, score, duration_seconds, completed, started_at, completed_at) 
        VALUES (?, 'der_quiz', 'quiz_completion', ?, ?, ?, TRUE, ?, ?)
    ");
    
    $stmt->execute([
        $user['id'],
        json_encode($activity_data),
        calculateScore($data),
        $duration,
        $start_time,
        $end_time
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Salvar na tabela der_quiz_results (nova estrutura)
 */
function saveDerQuizResults($pdo, $user, $data, $system_activity_id) {
    $start_time = date('Y-m-d H:i:s', strtotime($data['startTime']));
    $end_time = date('Y-m-d H:i:s', strtotime($data['endTime']));
    $duration = strtotime($data['endTime']) - strtotime($data['startTime']);
    
    $stmt = $pdo->prepare("
        INSERT INTO der_quiz_results 
        (user_id, system_activity_id, total_questions, correct_answers, final_score, 
         time_spent_seconds, start_time, end_time, tab_switches, copy_attempts) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user['id'],
        $system_activity_id,
        $data['totalQuestions'],
        $data['correctAnswers'],
        calculateScore($data),
        $duration,
        $start_time,
        $end_time,
        $data['tabSwitchCount'] ?? 0,
        $data['copyAttempts'] ?? 0
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Salvar na tabela der_quiz_students (estrutura antiga)
 */
function saveDerQuizStudents($pdo, $user, $data) {
    $start_time = date('Y-m-d H:i:s', strtotime($data['startTime']));
    $end_time = date('Y-m-d H:i:s', strtotime($data['endTime']));
    $duration = strtotime($data['endTime']) - strtotime($data['startTime']);
    
    $stmt = $pdo->prepare("
        INSERT INTO der_quiz_students 
        (user_id, name, registration, email, course, total_questions, correct_answers, 
         final_score, time_spent_seconds, start_time, end_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user['id'],
        $data['name'],
        $data['registration'],
        $data['email'],
        $data['course'],
        $data['totalQuestions'],
        $data['correctAnswers'],
        calculateScore($data),
        $duration,
        $start_time,
        $end_time
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Salvar respostas individuais (nova estrutura)
 */
function saveDerQuizAnswers($pdo, $quiz_result_id, $answers) {
    $stmt = $pdo->prepare("
        INSERT INTO der_quiz_answers 
        (quiz_result_id, question_id, question_title, selected_answer, correct_answer, 
         is_correct, time_spent, was_timeout, answer_timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($answers as $answer) {
        $answer_timestamp = date('Y-m-d H:i:s', strtotime($answer['timestamp']));
        
        $stmt->execute([
            $quiz_result_id,
            $answer['questionId'],
            $answer['questionTitle'],
            $answer['selectedAnswer'],
            $answer['correctAnswer'],
            $answer['isCorrect'] ? 1 : 0,
            $answer['timeSpent'],
            $answer['wasTimeout'] ? 1 : 0,
            $answer_timestamp
        ]);
    }
}

/**
 * Salvar respostas individuais (estrutura antiga)
 */
function saveDerQuizAnswersOld($pdo, $student_id, $answers) {
    $stmt = $pdo->prepare("
        INSERT INTO der_quiz_answers 
        (student_id, question_id, question_title, selected_answer, correct_answer, 
         is_correct, time_spent, was_timeout, answer_timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($answers as $answer) {
        $answer_timestamp = date('Y-m-d H:i:s', strtotime($answer['timestamp']));
        
        $stmt->execute([
            $student_id,
            $answer['questionId'],
            $answer['questionTitle'],
            $answer['selectedAnswer'],
            $answer['correctAnswer'],
            $answer['isCorrect'] ? 1 : 0,
            $answer['timeSpent'],
            $answer['wasTimeout'] ? 1 : 0,
            $answer_timestamp
        ]);
    }
}

/**
 * Salvar atividades suspeitas
 */
function saveSuspiciousActivity($pdo, $quiz_result_id, $activities) {
    $stmt = $pdo->prepare("
        INSERT INTO der_quiz_suspicious_activity 
        (quiz_result_id, activity_type, description, question_number, activity_timestamp) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($activities as $activity) {
        $activity_timestamp = date('Y-m-d H:i:s', strtotime($activity['timestamp']));
        
        $stmt->execute([
            $quiz_result_id,
            $activity['activity'],
            json_encode($activity),
            $activity['question'] ?? null,
            $activity_timestamp
        ]);
    }
}

/**
 * Calcular pontuação final
 */
function calculateScore($data) {
    if ($data['totalQuestions'] == 0) return 0;
    return round(($data['correctAnswers'] / $data['totalQuestions']) * 100, 2);
}

/**
 * Validar dados do quiz
 */
function validateQuizData($data, $user) {
    $errors = [];
    
    // Verificar se user_id confere
    if (empty($data['user_id']) || $data['user_id'] != $user['id']) {
        $errors[] = "ID do usuário não confere";
    }
    
    // Verificar dados básicos
    if (empty($data['name']) || strlen(trim($data['name'])) < 3) {
        $errors[] = "Nome deve ter pelo menos 3 caracteres";
    }
    
    // Verificar questões
    if (!isset($data['totalQuestions']) || !is_numeric($data['totalQuestions']) || 
        $data['totalQuestions'] < 1 || $data['totalQuestions'] > 50) {
        $errors[] = "Número de questões inválido";
    }
    
    // Verificar respostas corretas
    if (!isset($data['correctAnswers']) || !is_numeric($data['correctAnswers']) || 
        $data['correctAnswers'] < 0 || $data['correctAnswers'] > $data['totalQuestions']) {
        $errors[] = "Número de respostas corretas inválido";
    }
    
    // Verificar horários
    if (empty($data['startTime']) || empty($data['endTime'])) {
        $errors[] = "Horários de início e fim são obrigatórios";
    }
    
    // Verificar se as datas fazem sentido
    if (!empty($data['startTime']) && !empty($data['endTime'])) {
        $start = strtotime($data['startTime']);
        $end = strtotime($data['endTime']);
        
        if ($start === false || $end === false) {
            $errors[] = "Formato de data/hora inválido";
        } elseif ($end <= $start) {
            $errors[] = "Data de fim deve ser posterior à data de início";
        } elseif (($end - $start) > 3600) { // Máximo 1 hora
            $errors[] = "Duração muito longa (máximo 1 hora)";
        }
    }
    
    // Verificar respostas
    if (!isset($data['answers']) || !is_array($data['answers'])) {
        $errors[] = "Dados de respostas inválidos";
    } elseif (count($data['answers']) !== $data['totalQuestions']) {
        $errors[] = "Número de respostas não confere com número de questões";
    }
    
    return $errors;
}

/**
 * Verificar limite diário de tentativas
 */
function checkDailyLimit($user_id, $limit = 3) {
    $pdo = getConnection();
    
    $count = 0;
    
    // Verificar na nova estrutura
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM system_user_activities 
            WHERE user_id = ? AND module = 'der_quiz' AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$user_id]);
        $count += $stmt->fetchColumn();
    } catch (Exception $e) {
        // Ignorar se tabela não existe
    }
    
    // Verificar na estrutura antiga
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM der_quiz_students 
            WHERE user_id = ? AND DATE(created_at) = CURDATE()
        ");
        $stmt->execute([$user_id]);
        $count += $stmt->fetchColumn();
    } catch (Exception $e) {
        // Ignorar se tabela não existe
    }
    
    return $count >= $limit;
}

/**
 * Endpoint para verificar status do sistema (GET request)
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['status'])) {
    $pdo = getConnection();
    $structure = detectTableStructure($pdo);
    
    jsonResponse([
        'module' => 'der_quiz',
        'status' => 'active',
        'structure' => $structure,
        'database_connected' => true,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>