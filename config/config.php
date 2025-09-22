<?php
/**
 * Configurações Globais do Sistema de Atividades - ATUALIZADO
 * Professor Leandro Rodrigues
 * 
 * Este arquivo centraliza todas as configurações do sistema
 * VERSÃO ATUALIZADA: Inclui módulo de R&S (Laboratório de Recrutamento e Seleção)
 */

// Impedir acesso direto
if (!defined('SYSTEM_INIT')) {
    define('SYSTEM_INIT', true);
}

// Configurações do banco de dados
$db_config = [
    'host' => 'localhost',
    'dbname' => 'u906658109_atividades',
    'username' => 'u906658109_ativi_escolar',
    'password' => 'P@ncho2891.',
    'charset' => 'utf8mb4'
];

// Configurações gerais da aplicação
$app_config = [
    'timezone' => 'America/Sao_Paulo',
    'session_timeout' => 7200, // 2 horas
    'max_login_attempts' => 5,
    'professor_name' => 'Professor Leandro Rodrigues',
    'institution' => 'Curso de Informática',
    'system_version' => '2.1.0', // ATUALIZADO para incluir R&S
    'debug_mode' => false
];

// Configurações de segurança
$security_config = [
    'password_min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_special_chars' => false,
    'session_regenerate_interval' => 1800, // 30 minutos
    'admin_session_timeout' => 3600 // 1 hora para admin
];

// Credenciais do administrador (hardcoded para segurança)
define('ADMIN_USERNAME', 'Admin');
define('ADMIN_PASSWORD', 'Admin123.');
define('ADMIN_EMAIL', 'admin@sistema.local');

// Configurações de módulos/atividades - ATUALIZADO
$modules_config = [
    'ethics' => [
        'name' => 'Laboratório de Decisões Éticas',
        'description' => 'Dilemas éticos em TI',
        'enabled' => true,
        'path' => 'etica',
        'icon' => 'Ética',
        'duration' => '15-25 min',
        'difficulty' => 'medium',
        'table_prefix' => 'ethics_lab_',
        'target_courses' => ['informatica'],
        'module_type' => 'interactive_scenarios'
    ],
    'der_quiz' => [
        'name' => 'DER Quiz Interativo',
        'description' => 'Modelagem Entidade-Relacionamento',
        'enabled' => true,
        'path' => 'der_quiz',
        'icon' => 'DER',
        'duration' => '15-20 min',
        'difficulty' => 'easy',
        'table_prefix' => 'der_quiz_',
        'target_courses' => ['informatica'],
        'module_type' => 'quiz'
    ],
    'rs_lab' => [
        'name' => 'Laboratório de Recrutamento e Seleção',
        'description' => 'Excel aplicado ao RH - Fórmulas SOMA, SE, PROCV, MÉDIA',
        'enabled' => true,
        'path' => 'rs_lab',
        'icon' => 'R&S',
        'duration' => '30-40 min',
        'difficulty' => 'medium',
        'table_prefix' => 'rs_lab_',
        'target_courses' => ['recursos_humanos'],
        'module_type' => 'excel_lab',
        'required_formulas' => ['SOMA', 'SE', 'PROCV', 'MEDIA'],
        'scenarios' => ['techcorp_2024'],
        'total_questions' => 10,
        'max_score' => 175,
        'time_bonus' => true,
        'formula_tracking' => true
    ],
    'algorithms' => [
        'name' => 'Laboratório de Algoritmos',
        'description' => 'Programação e lógica',
        'enabled' => false,
        'path' => 'algoritmos',
        'icon' => 'Algo',
        'duration' => '30-45 min',
        'difficulty' => 'hard',
        'table_prefix' => 'algo_lab_',
        'target_courses' => ['informatica'],
        'module_type' => 'programming'
    ],
    'networks' => [
        'name' => 'Simulador de Redes',
        'description' => 'Configuração e diagnóstico',
        'enabled' => false,
        'path' => 'redes',
        'icon' => 'Redes',
        'duration' => '20-35 min',
        'difficulty' => 'medium',
        'table_prefix' => 'net_lab_',
        'target_courses' => ['informatica'],
        'module_type' => 'simulation'
    ]
];

// NOVA FUNÇÃO: Obter módulos por curso
function getModulesByCourse($course) {
    global $modules_config;
    $course_modules = [];
    
    foreach ($modules_config as $module_id => $config) {
        if ($config['enabled'] && in_array($course, $config['target_courses'])) {
            $course_modules[$module_id] = $config;
        }
    }
    
    return $course_modules;
}

// NOVA FUNÇÃO: Validar se usuário pode acessar módulo
function canUserAccessModule($user_course, $module_id) {
    global $modules_config;
    
    if (!isset($modules_config[$module_id])) {
        return false;
    }
    
    $module = $modules_config[$module_id];
    
    if (!$module['enabled']) {
        return false;
    }
    
    return in_array($user_course, $module['target_courses']);
}

// NOVA FUNÇÃO: Obter configuração específica do módulo
function getModuleConfig($module_id) {
    global $modules_config;
    return $modules_config[$module_id] ?? null;
}

// NOVA FUNÇÃO: Criar tabelas específicas do módulo R&S
function createRSLabTables() {
    $pdo = getConnection();
    
    // Detectar estrutura (nova ou antiga)
    $structure = detectRSLabStructure($pdo);
    
    if ($structure === 'new') {
        createRSLabNewStructure($pdo);
    } else {
        createRSLabOldStructure($pdo);
    }
}

function detectRSLabStructure($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'rs_lab_results'");
        return $stmt->rowCount() > 0 ? 'new' : 'old';
    } catch (Exception $e) {
        return 'old';
    }
}

function createRSLabNewStructure($pdo) {
    // Tabela principal de resultados
    $sql_results = "
        CREATE TABLE IF NOT EXISTS rs_lab_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            scenario_id VARCHAR(50) NOT NULL,
            total_questions INT NOT NULL,
            correct_answers INT NOT NULL,
            final_score DECIMAL(5,2) NOT NULL,
            formulas_used JSON NOT NULL,
            time_spent_seconds INT NOT NULL,
            time_bonus_points INT DEFAULT 0,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES system_users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_scenario_id (scenario_id),
            INDEX idx_final_score (final_score),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Tabela de respostas detalhadas
    $sql_answers = "
        CREATE TABLE IF NOT EXISTS rs_lab_answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            result_id INT NOT NULL,
            question_id VARCHAR(10) NOT NULL,
            question_type ENUM('calculation', 'formula', 'lookup', 'analysis', 'complex') NOT NULL,
            formula_type ENUM('SOMA', 'SE', 'PROCV', 'MEDIA', 'COMBINED') NOT NULL,
            user_formula TEXT,
            expected_formula TEXT NOT NULL,
            user_result DECIMAL(10,2),
            expected_result DECIMAL(10,2) NOT NULL,
            is_correct BOOLEAN NOT NULL,
            validation_type ENUM('formula_structure', 'result_value', 'both', 'concept') NOT NULL,
            time_spent INT NOT NULL,
            points_earned INT NOT NULL,
            hints_used INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (result_id) REFERENCES rs_lab_results(id) ON DELETE CASCADE,
            INDEX idx_result_id (result_id),
            INDEX idx_question_id (question_id),
            INDEX idx_formula_type (formula_type),
            INDEX idx_is_correct (is_correct)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Tabela de performance por fórmula
    $sql_formula_stats = "
        CREATE TABLE IF NOT EXISTS rs_lab_formula_performance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            result_id INT NOT NULL,
            formula_type ENUM('SOMA', 'SE', 'PROCV', 'MEDIA') NOT NULL,
            questions_count INT NOT NULL,
            correct_count INT NOT NULL,
            accuracy_rate DECIMAL(5,2) NOT NULL,
            avg_time_per_question INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (result_id) REFERENCES rs_lab_results(id) ON DELETE CASCADE,
            INDEX idx_result_id (result_id),
            INDEX idx_formula_type (formula_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $pdo->exec($sql_results);
        $pdo->exec($sql_answers);
        $pdo->exec($sql_formula_stats);
        return true;
    } catch (PDOException $e) {
        logActivity("Erro ao criar tabelas do R&S Lab: " . $e->getMessage(), 'ERROR', 'RS_LAB');
        return false;
    }
}

function createRSLabOldStructure($pdo) {
    // Estrutura antiga compatível
    $sql_students = "
        CREATE TABLE IF NOT EXISTS rs_lab_students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            name VARCHAR(255) NOT NULL,
            registration VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            course VARCHAR(100) NOT NULL,
            scenario_id VARCHAR(50) NOT NULL,
            total_questions INT NOT NULL,
            correct_answers INT NOT NULL,
            final_score DECIMAL(5,2) NOT NULL,
            formulas_used TEXT NOT NULL,
            time_spent_seconds INT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_registration (registration),
            INDEX idx_final_score (final_score),
            INDEX idx_scenario_id (scenario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $pdo->exec($sql_students);
        return true;
    } catch (PDOException $e) {
        logActivity("Erro ao criar tabelas antigas do R&S Lab: " . $e->getMessage(), 'ERROR', 'RS_LAB');
        return false;
    }
}

// Definir timezone
date_default_timezone_set($app_config['timezone']);

// Função para conectar ao banco de dados (MANTIDA ORIGINAL)
function getConnection() {
    global $db_config;
    
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
            $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            error_log("Erro de conexão com o banco: " . $e->getMessage());
            throw new Exception("Falha na conexão com o banco de dados");
        }
    }
    
    return $pdo;
}

// Função para criar tabelas do sistema (ATUALIZADA)
function createSystemTables() {
    $pdo = getConnection();
    
    // Tabela de usuários (MANTIDA ORIGINAL)
    $sql_users = "
        CREATE TABLE IF NOT EXISTS system_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            registration VARCHAR(100) NOT NULL UNIQUE,
            course VARCHAR(100) NOT NULL,
            turma VARCHAR(50),
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            login_attempts INT DEFAULT 0,
            last_login DATETIME NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_registration (registration),
            INDEX idx_status (status),
            INDEX idx_course (course),
            INDEX idx_turma (turma)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Tabela de sessões ativas (MANTIDA ORIGINAL)
    $sql_sessions = "
        CREATE TABLE IF NOT EXISTS system_sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT NULL,
            admin_session BOOLEAN DEFAULT FALSE,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            payload LONGTEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES system_users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_last_activity (last_activity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Tabela de logs do sistema (MANTIDA ORIGINAL)
    $sql_logs = "
        CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            module VARCHAR(50) NOT NULL,
            action VARCHAR(100) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            level ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') DEFAULT 'INFO',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES system_users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_module (module),
            INDEX idx_level (level),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Tabela de atividades dos usuários (MANTIDA ORIGINAL)
    $sql_activities = "
        CREATE TABLE IF NOT EXISTS system_user_activities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            module VARCHAR(50) NOT NULL,
            activity_type VARCHAR(100) NOT NULL,
            data JSON,
            score INT NULL,
            duration_seconds INT NULL,
            completed BOOLEAN DEFAULT FALSE,
            started_at DATETIME NOT NULL,
            completed_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES system_users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_module (module),
            INDEX idx_completed (completed),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $pdo->exec($sql_users);
        $pdo->exec($sql_sessions);
        $pdo->exec($sql_logs);
        $pdo->exec($sql_activities);
        
        // NOVO: Criar tabelas do módulo R&S se habilitado
        if ($GLOBALS['modules_config']['rs_lab']['enabled']) {
            createRSLabTables();
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar tabelas do sistema: " . $e->getMessage());
        return false;
    }
}

// TODAS AS OUTRAS FUNÇÕES MANTIDAS ORIGINAIS
function logActivity($message, $level = 'INFO', $module = 'SYSTEM', $user_id = null) {
    try {
        $pdo = getConnection();
        
        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (user_id, module, action, description, ip_address, user_agent, level) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $module,
            'ACTIVITY',
            $message,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $level
        ]);
        
        if ($GLOBALS['app_config']['debug_mode']) {
            $log_message = "[" . date('Y-m-d H:i:s') . "] [{$level}] [{$module}] {$message}" . PHP_EOL;
            error_log($log_message, 3, __DIR__ . '/../logs/system.log');
        }
        
    } catch (Exception $e) {
        error_log("Erro ao salvar log: " . $e->getMessage());
    }
}

function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule_set) {
        $value = $data[$field] ?? null;
        $rules_array = explode('|', $rule_set);
        
        foreach ($rules_array as $rule) {
            $rule_parts = explode(':', $rule);
            $rule_name = $rule_parts[0];
            $rule_param = $rule_parts[1] ?? null;
            
            switch ($rule_name) {
                case 'required':
                    if (empty($value)) {
                        $errors[$field][] = "Campo {$field} é obrigatório";
                    }
                    break;
                    
                case 'email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "E-mail inválido";
                    }
                    break;
                    
                case 'min':
                    if (!empty($value) && strlen($value) < (int)$rule_param) {
                        $errors[$field][] = "Campo {$field} deve ter pelo menos {$rule_param} caracteres";
                    }
                    break;
                    
                case 'max':
                    if (!empty($value) && strlen($value) > (int)$rule_param) {
                        $errors[$field][] = "Campo {$field} deve ter no máximo {$rule_param} caracteres";
                    }
                    break;
                    
                case 'password':
                    if (!empty($value)) {
                        $errors = array_merge($errors, validatePassword($value));
                    }
                    break;
            }
        }
    }
    
    return $errors;
}

function validatePassword($password) {
    global $security_config;
    $errors = [];
    
    if (strlen($password) < $security_config['password_min_length']) {
        $errors['password'][] = "Senha deve ter pelo menos {$security_config['password_min_length']} caracteres";
    }
    
    if ($security_config['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
        $errors['password'][] = "Senha deve conter pelo menos uma letra maiúscula";
    }
    
    if ($security_config['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
        $errors['password'][] = "Senha deve conter pelo menos uma letra minúscula";
    }
    
    if ($security_config['require_numbers'] && !preg_match('/\d/', $password)) {
        $errors['password'][] = "Senha deve conter pelo menos um número";
    }
    
    if ($security_config['require_special_chars'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors['password'][] = "Senha deve conter pelo menos um caractere especial";
    }
    
    return $errors;
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';");
}

function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function checkUserStatus($user_id) {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT status, login_attempts FROM system_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['valid' => false, 'reason' => 'Usuário não encontrado'];
    }
    
    if ($user['status'] === 'inactive') {
        return ['valid' => false, 'reason' => 'Conta inativa'];
    }
    
    if ($user['status'] === 'suspended') {
        return ['valid' => false, 'reason' => 'Conta suspensa'];
    }
    
    if ($user['login_attempts'] >= $GLOBALS['app_config']['max_login_attempts']) {
        return ['valid' => false, 'reason' => 'Muitas tentativas de login. Conta temporariamente bloqueada'];
    }
    
    return ['valid' => true];
}

function cleanExpiredSessions() {
    try {
        $pdo = getConnection();
        $timeout = $GLOBALS['app_config']['session_timeout'];
        
        $stmt = $pdo->prepare("
            DELETE FROM system_sessions 
            WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$timeout]);
        
        logActivity("Limpeza de sessões expiradas: " . $stmt->rowCount() . " sessões removidas", 'INFO', 'SYSTEM');
    } catch (Exception $e) {
        logActivity("Erro na limpeza de sessões: " . $e->getMessage(), 'ERROR', 'SYSTEM');
    }
}

function getSystemStats() {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'active' THEN 1 END) as active FROM system_users");
        $users = $stmt->fetch();
        
        $stmt = $pdo->query("SELECT COUNT(*) as today FROM system_user_activities WHERE DATE(created_at) = CURDATE()");
        $activities_today = $stmt->fetch()['today'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as week FROM system_user_activities WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $activities_week = $stmt->fetch()['week'];
        
        $active_modules = count(array_filter($GLOBALS['modules_config'], function($module) {
            return $module['enabled'];
        }));
        
        return [
            'users' => $users,
            'activities' => [
                'today' => $activities_today,
                'week' => $activities_week
            ],
            'modules' => [
                'active' => $active_modules,
                'total' => count($GLOBALS['modules_config'])
            ],
            'system' => [
                'version' => $GLOBALS['app_config']['system_version'],
                'uptime' => getSystemUptime()
            ]
        ];
    } catch (Exception $e) {
        logActivity("Erro ao obter estatísticas: " . $e->getMessage(), 'ERROR', 'SYSTEM');
        return null;
    }
}

function getSystemUptime() {
    $uptime_file = __DIR__ . '/../logs/system_start.log';
    
    if (!file_exists($uptime_file)) {
        file_put_contents($uptime_file, time());
        return '0 dias';
    }
    
    $start_time = (int)file_get_contents($uptime_file);
    $uptime_seconds = time() - $start_time;
    $days = floor($uptime_seconds / 86400);
    
    return $days . ' dias';
}

function initializeSystem() {
    $dirs = ['logs', 'backups', 'uploads'];
    foreach ($dirs as $dir) {
        $dir_path = __DIR__ . "/../{$dir}";
        if (!is_dir($dir_path)) {
            mkdir($dir_path, 0755, true);
        }
    }
    
    createSystemTables();
    
    if (rand(1, 100) <= 5) {
        cleanExpiredSessions();
    }
    
    logActivity("Sistema inicializado", 'INFO', 'SYSTEM');
}

// Auto-inicialização quando arquivo é incluído
if (defined('SYSTEM_INIT')) {
    initializeSystem();
}

// Variáveis globais para acesso fácil
$GLOBALS['db_config'] = $db_config;
$GLOBALS['app_config'] = $app_config;
$GLOBALS['security_config'] = $security_config;
$GLOBALS['modules_config'] = $modules_config;
?>