<?php
/**
 * Sistema de Salvamento Híbrido - Laboratório de Ética
 * Suporta tanto a estrutura antiga quanto a nova
 */

require_once '../config/config.php';
require_once '../auth/auth.php';

$user = requireAuth();
setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($content_type, 'application/json') === false) {
    jsonResponse(['success' => false, 'message' => 'Content-Type deve ser application/json'], 400);
}

try {
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['success' => false, 'message' => 'JSON inválido'], 400);
    }
    
    // Validar dados
    $validation_errors = validateLabData($data, $user);
    if (!empty($validation_errors)) {
        jsonResponse([
            'success' => false, 
            'message' => 'Dados inválidos',
            'errors' => $validation_errors
        ], 400);
    }
    
    $data = sanitizeInput($data);
    
    // Verificar limite diário
    if (checkDailyLimit($user['id'])) {
        jsonResponse([
            'success' => false, 
            'message' => 'Limite de tentativas diárias excedido (máximo 3 por dia)'
        ], 429);
    }
    
    $pdo = getConnection();
    
    // DETECTAR QUAL ESTRUTURA USAR
    $structure = detectTableStructure($pdo);
    
    $pdo->beginTransaction();
    
    try {
        if ($structure === 'new') {
            // Usar nova estrutura
            $result = saveToNewStructure($pdo, $user, $data);
        } else {
            // Usar estrutura antiga
            $result = saveToOldStructure($pdo, $user, $data);
        }
        
        $pdo->commit();
        
        logActivity(
            "Laboratório de ética concluído [{$structure}] - Score: {$data['reputation']}", 
            'INFO', 
            'ETHICS_LAB', 
            $user['id']
        );
        
        jsonResponse($result);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    logActivity("Erro no laboratório de ética: " . $e->getMessage(), 'ERROR', 'ETHICS_LAB', $user['id'] ?? null);
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
        // Tentar verificar se existe ethics_lab_results
        $stmt = $pdo->query("SHOW TABLES LIKE 'ethics_lab_results'");
        if ($stmt->rowCount() > 0) {
            return 'new';
        }
        return 'old';
    } catch (Exception $e) {
        return 'old'; // Fallback para estrutura antiga
    }
}

/**
 * Salvar usando nova estrutura (ethics_lab_results)
 */
function saveToNewStructure($pdo, $user, $data) {
    // Criar tabelas se não existirem
    createNewStructureTables($pdo);
    
    // Salvar na tabela system_user_activities
    $system_activity_id = saveSystemActivity($pdo, $user, $data);
    
    // Salvar dados específicos do laboratório
    $ethics_lab_id = saveEthicsLabResults($pdo, $user, $data, $system_activity_id);
    
    // Salvar escolhas individuais
    saveEthicsChoicesNew($pdo, $ethics_lab_id, $data['choices']);
    
    return [
        'success' => true,
        'message' => 'Dados salvos com sucesso (nova estrutura)',
        'lab_id' => $ethics_lab_id,
        'system_activity_id' => $system_activity_id,
        'final_score' => round($data['reputation']),
        'structure' => 'new'
    ];
}

/**
 * Salvar usando estrutura antiga (ethics_lab_students)
 */
function saveToOldStructure($pdo, $user, $data) {
    // Salvar na tabela ethics_lab_students
    $student_id = saveEthicsLabStudents($pdo, $user, $data);
    
    // Salvar escolhas na estrutura antiga
    saveEthicsChoicesOld($pdo, $student_id, $data['choices']);
    
    return [
        'success' => true,
        'message' => 'Dados salvos com sucesso (estrutura antiga)',
        'student_id' => $student_id,
        'final_score' => round($data['reputation']),
        'structure' => 'old'
    ];
}

/**
 * Criar tabelas da nova estrutura
 */
function createNewStructureTables($pdo) {
    $sql_results = "
        CREATE TABLE IF NOT EXISTS ethics_lab_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            system_activity_id INT NOT NULL,
            final_score INT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            duration_seconds INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES system_users(id) ON DELETE CASCADE,
            FOREIGN KEY (system_activity_id) REFERENCES system_user_activities(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $pdo->exec($sql_results);
        
        // Verificar se ethics_lab_choices tem a estrutura correta
        $stmt = $pdo->query("SHOW COLUMNS FROM ethics_lab_choices LIKE 'lab_result_id'");
        if ($stmt->rowCount() == 0) {
            // Ainda tem student_id, precisa atualizar
            $pdo->exec("ALTER TABLE ethics_lab_choices CHANGE student_id lab_result_id INT NOT NULL");
        }
    } catch (PDOException $e) {
        logActivity("Erro ao criar nova estrutura: " . $e->getMessage(), 'ERROR', 'ETHICS_LAB');
        throw $e;
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
        'final_score' => round($data['reputation']),
        'choices_count' => count($data['choices']),
        'start_time' => $start_time,
        'end_time' => $end_time,
        'user_name' => $data['name'],
        'user_email' => $data['email'],
        'user_registration' => $data['registration'],
        'user_course' => $data['course']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO system_user_activities 
        (user_id, module, activity_type, data, score, duration_seconds, completed, started_at, completed_at) 
        VALUES (?, 'ethics_lab', 'laboratory_completion', ?, ?, ?, TRUE, ?, ?)
    ");
    
    $stmt->execute([
        $user['id'],
        json_encode($activity_data),
        round($data['reputation']),
        $duration,
        $start_time,
        $end_time
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Salvar na tabela ethics_lab_results (nova estrutura)
 */
function saveEthicsLabResults($pdo, $user, $data, $system_activity_id) {
    $start_time = date('Y-m-d H:i:s', strtotime($data['startTime']));
    $end_time = date('Y-m-d H:i:s', strtotime($data['endTime']));
    $duration = strtotime($data['endTime']) - strtotime($data['startTime']);
    
    $stmt = $pdo->prepare("
        INSERT INTO ethics_lab_results 
        (user_id, system_activity_id, final_score, start_time, end_time, duration_seconds) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user['id'],
        $system_activity_id,
        round($data['reputation']),
        $start_time,
        $end_time,
        $duration
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Salvar na tabela ethics_lab_students (estrutura antiga)
 */
function saveEthicsLabStudents($pdo, $user, $data) {
    $start_time = date('Y-m-d H:i:s', strtotime($data['startTime']));
    $end_time = date('Y-m-d H:i:s', strtotime($data['endTime']));
    
    $stmt = $pdo->prepare("
        INSERT INTO ethics_lab_students 
        (user_id, name, registration, final_score, start_time, end_time) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user['id'],
        $data['name'],
        $data['registration'],
        round($data['reputation']),
        $start_time,
        $end_time
    ]);
    
    return $pdo->lastInsertId();
}

/**
 * Salvar escolhas (nova estrutura)
 */
function saveEthicsChoicesNew($pdo, $lab_result_id, $choices) {
    $stmt = $pdo->prepare("
        INSERT INTO ethics_lab_choices 
        (lab_result_id, dilemma_id, dilemma_title, choice_index, choice_text, impact, choice_timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($choices as $choice) {
        $choice_timestamp = date('Y-m-d H:i:s', strtotime($choice['timestamp']));
        
        $stmt->execute([
            $lab_result_id,
            $choice['dilemma_id'],
            $choice['dilemma_title'],
            $choice['choice_index'],
            $choice['choice_text'],
            $choice['impact'],
            $choice_timestamp
        ]);
    }
}

/**
 * Salvar escolhas (estrutura antiga)
 */
function saveEthicsChoicesOld($pdo, $student_id, $choices) {
    $stmt = $pdo->prepare("
        INSERT INTO ethics_lab_choices 
        (student_id, dilemma_id, dilemma_title, choice_index, choice_text, impact, choice_timestamp) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($choices as $choice) {
        $choice_timestamp = date('Y-m-d H:i:s', strtotime($choice['timestamp']));
        
        $stmt->execute([
            $student_id,
            $choice['dilemma_id'],
            $choice['dilemma_title'],
            $choice['choice_index'],
            $choice['choice_text'],
            $choice['impact'],
            $choice_timestamp
        ]);
    }
}

/**
 * Validação e outras funções (mantidas do código original)
 */
function validateLabData($data, $user) {
    $errors = [];
    
    if (empty($data['user_id']) || $data['user_id'] != $user['id']) {
        $errors[] = "ID do usuário não confere";
    }
    
    if (empty($data['name']) || strlen(trim($data['name'])) < 3) {
        $errors[] = "Nome deve ter pelo menos 3 caracteres";
    }
    
    if (!isset($data['reputation']) || !is_numeric($data['reputation']) || 
        $data['reputation'] < 0 || $data['reputation'] > 100) {
        $errors[] = "Pontuação de reputação inválida";
    }
    
    if (empty($data['startTime']) || empty($data['endTime'])) {
        $errors[] = "Horários de início e fim são obrigatórios";
    }
    
    if (!isset($data['choices']) || !is_array($data['choices']) || count($data['choices']) !== 10) {
        $errors[] = "Número incorreto de escolhas (esperado: 10)";
    }
    
    return $errors;
}

function checkDailyLimit($user_id, $limit = 3) {
    $pdo = getConnection();
    
    $count = 0;
    
    // Verificar na nova estrutura
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_user_activities WHERE user_id = ? AND module = 'ethics_lab' AND DATE(created_at) = CURDATE()");
        $stmt->execute([$user_id]);
        $count += $stmt->fetchColumn();
    } catch (Exception $e) {
        // Ignorar se tabela não existe
    }
    
    // Verificar na estrutura antiga
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ethics_lab_students WHERE user_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$user_id]);
        $count += $stmt->fetchColumn();
    } catch (Exception $e) {
        // Ignorar se tabela não existe
    }
    
    return $count >= $limit;
}
?>