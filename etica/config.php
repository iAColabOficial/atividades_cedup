<?php
// Configurações do banco de dados
$db_config = [
    'host' => 'localhost',
    'dbname' => 'u906658109_atividades',
    'username' => 'u906658109_ativi_escolar',
    'password' => 'P@ncho2891.',
    'charset' => 'utf8mb4'
];

// Função para conectar ao banco de dados
function getConnection() {
    global $db_config;
    
    try {
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erro de conexão com o banco: " . $e->getMessage());
        throw new Exception("Falha na conexão com o banco de dados");
    }
}

// Função para criar as tabelas necessárias
function createTables() {
    $pdo = getConnection();
    
    // Tabela para usuários (sistema de login)
    $sql_users = "
        CREATE TABLE IF NOT EXISTS ethics_lab_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            registration VARCHAR(100) NOT NULL UNIQUE,
            course VARCHAR(100) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_login DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_registration (registration),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Tabela para armazenar dados dos estudantes (resultados dos laboratórios)
    $sql_students = "
        CREATE TABLE IF NOT EXISTS ethics_lab_students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            name VARCHAR(255) NOT NULL,
            registration VARCHAR(100) NOT NULL,
            final_score INT NOT NULL,
            start_time DATETIME NOT NULL,
            end_time DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES ethics_lab_users(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_registration (registration),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Tabela para armazenar as escolhas individuais
    $sql_choices = "
        CREATE TABLE IF NOT EXISTS ethics_lab_choices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            dilemma_id INT NOT NULL,
            dilemma_title VARCHAR(255) NOT NULL,
            choice_index INT NOT NULL,
            choice_text TEXT NOT NULL,
            impact INT NOT NULL,
            choice_timestamp DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES ethics_lab_students(id) ON DELETE CASCADE,
            INDEX idx_student_id (student_id),
            INDEX idx_dilemma_id (dilemma_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    try {
        $pdo->exec($sql_users);
        $pdo->exec($sql_students);
        $pdo->exec($sql_choices);
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao criar tabelas: " . $e->getMessage());
        return false;
    }
}

// Configurações gerais da aplicação
$app_config = [
    'timezone' => 'America/Sao_Paulo',
    'max_attempts_per_day' => 3,
    'session_timeout' => 1800, // 30 minutos
    'professor_name' => 'Professor Leandro Rodrigues',
    'course_name' => 'Informática',
    'institution' => 'Instituição de Ensino'
];

// Definir timezone
date_default_timezone_set($app_config['timezone']);

// Função para log de atividades
function logActivity($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    error_log($log_message, 3, 'logs/activity.log');
}

// Função para validar dados de entrada
function validateInput($data) {
    $errors = [];
    
    if (empty($data['name']) || strlen(trim($data['name'])) < 3) {
        $errors[] = "Nome deve ter pelo menos 3 caracteres";
    }
    
    if (empty($data['registration']) || strlen(trim($data['registration'])) < 3) {
        $errors[] = "Matrícula deve ter pelo menos 3 caracteres";
    }
    
    if (!isset($data['choices']) || !is_array($data['choices']) || count($data['choices']) !== 10) {
        $errors[] = "Dados de escolhas inválidos";
    }
    
    return $errors;
}

// Função para sanitizar dados
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Função para verificar se já existe uma sessão ativa
function checkExistingSession($registration) {
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM ethics_lab_students 
        WHERE registration = ? 
        AND DATE(created_at) = CURDATE()
    ");
    
    $stmt->execute([$registration]);
    $result = $stmt->fetch();
    
    return $result['count'] >= 3; // Máximo 3 tentativas por dia
}

// Headers de segurança
function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Função para resposta JSON
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Inicializar banco de dados (criar tabelas se não existirem)
try {
    createTables();
} catch (Exception $e) {
    error_log("Erro na inicialização do banco: " . $e->getMessage());
}
?>