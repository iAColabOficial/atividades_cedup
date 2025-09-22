<?php
/**
 * Sistema de Autenticação com Acesso Admin
 * Laboratório Virtual de Decisões Éticas em TI
 * Professor Leandro Rodrigues
 */

require_once 'config.php';

// Iniciar sessão
session_start();

// Definir headers de segurança
setSecurityHeaders();

// Credenciais do administrador (hardcoded para segurança)
define('ADMIN_USERNAME', 'Admin');
define('ADMIN_PASSWORD', 'Admin123.');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

// CORREÇÃO: Aceitar tanto JSON quanto form data
$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
$input_data = [];

if (strpos($content_type, 'application/json') !== false) {
    // Dados JSON
    $json_input = file_get_contents('php://input');
    $input_data = json_decode($json_input, true) ?? [];
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['success' => false, 'message' => 'JSON inválido'], 400);
    }
} else {
    // Dados de formulário (POST padrão)
    $input_data = $_POST;
}

try {
    // CORREÇÃO: Usar dados já processados
    $data = $input_data;
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'register':
            handleRegister($data);
            break;
        case 'login':
            handleLogin($data);
            break;
        case 'admin_login':
            handleAdminLogin($data);
            break;
        case 'logout':
            handleLogout();
            break;
        case 'verify':
            handleVerify();
            break;
        case 'check_login':
            handleVerify();
            break;
        case 'check_admin':
            handleVerify();
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
    }
    
} catch (Exception $e) {
    logActivity("Erro de autenticação: " . $e->getMessage(), 'ERROR');
    jsonResponse([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ], 500);
}

/**
 * Processar login administrativo
 */
function handleAdminLogin($data) {
    // Validar dados de entrada
    if (empty($data['email']) || empty($data['password'])) {
        jsonResponse([
            'success' => false,
            'message' => 'Login e senha são obrigatórios'
        ], 400);
    }
    
    // Verificar credenciais admin
    if ($data['email'] === ADMIN_USERNAME && $data['password'] === ADMIN_PASSWORD) {
        // Login administrativo bem-sucedido
        $_SESSION['admin_id'] = 'admin';
        $_SESSION['admin_username'] = ADMIN_USERNAME;
        $_SESSION['is_admin'] = true;
        $_SESSION['login_time'] = time();
        
        // Log de login administrativo
        logActivity("Login administrativo realizado por: " . ADMIN_USERNAME);
        
        jsonResponse([
            'success' => true,
            'message' => 'Login administrativo realizado com sucesso!',
            'user' => [
                'id' => 'admin',
                'name' => 'Administrador',
                'username' => ADMIN_USERNAME,
                'is_admin' => true
            ]
        ]);
    } else {
        // Log de tentativa de login admin inválida
        logActivity("Tentativa de login administrativo inválida: {$data['email']}", 'WARNING');
        
        jsonResponse([
            'success' => false,
            'message' => 'Credenciais administrativas inválidas'
        ], 401);
    }
}

/**
 * Processar cadastro de usuário
 */
function handleRegister($data) {
    // Validar dados de entrada
    $validation_errors = validateRegistrationData($data);
    if (!empty($validation_errors)) {
        jsonResponse([
            'success' => false, 
            'message' => 'Dados inválidos',
            'errors' => $validation_errors
        ], 400);
    }
    
    // Sanitizar dados
    $data = sanitizeInput($data);
    
    try {
        $pdo = getConnection();
        
        // Verificar se email já existe
        $stmt = $pdo->prepare("SELECT id FROM ethics_lab_users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            jsonResponse([
                'success' => false,
                'message' => 'E-mail já cadastrado no sistema'
            ], 400);
        }
        
        // Verificar se matrícula já existe
        $stmt = $pdo->prepare("SELECT id FROM ethics_lab_users WHERE registration = ?");
        $stmt->execute([$data['registration']]);
        if ($stmt->fetch()) {
            jsonResponse([
                'success' => false,
                'message' => 'Matrícula já cadastrada no sistema'
            ], 400);
        }
        
        // Hash da senha
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Inserir usuário
        $stmt = $pdo->prepare("
            INSERT INTO ethics_lab_users 
            (first_name, last_name, email, registration, course, password_hash, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $data['registration'],
            $data['course'],
            $password_hash
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Log da atividade
        logActivity("Novo usuário cadastrado: {$data['email']} ({$data['registration']})");
        
        jsonResponse([
            'success' => true,
            'message' => 'Cadastro realizado com sucesso!',
            'user_id' => $user_id
        ]);
        
    } catch (PDOException $e) {
        logActivity("Erro de banco no cadastro: " . $e->getMessage(), 'ERROR');
        jsonResponse([
            'success' => false,
            'message' => 'Erro ao cadastrar usuário'
        ], 500);
    }
}

/**
 * Processar login de usuário
 */
function handleLogin($data) {
    // Validar dados de entrada
    if (empty($data['email']) || empty($data['password'])) {
        jsonResponse([
            'success' => false,
            'message' => 'E-mail e senha são obrigatórios'
        ], 400);
    }
    
    try {
        $pdo = getConnection();
        
        // Buscar usuário por email ou matrícula
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, email, registration, course, password_hash, status, last_login
            FROM ethics_lab_users 
            WHERE (email = ? OR registration = ?) AND status = 'active'
        ");
        $stmt->execute([$data['email'], $data['email']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Log de tentativa de login inválida
            logActivity("Tentativa de login com credencial inexistente: {$data['email']}", 'WARNING');
            
            jsonResponse([
                'success' => false,
                'message' => 'Credenciais inválidas'
            ], 401);
        }
        
        // Verificar senha
        if (!password_verify($data['password'], $user['password_hash'])) {
            // Log de tentativa de senha incorreta
            logActivity("Tentativa de login com senha incorreta: {$user['email']}", 'WARNING');
            
            jsonResponse([
                'success' => false,
                'message' => 'Credenciais inválidas'
            ], 401);
        }
        
        // Login bem-sucedido - criar sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_registration'] = $user['registration'];
        $_SESSION['user_course'] = $user['course'];
        $_SESSION['is_admin'] = false;
        $_SESSION['login_time'] = time();
        
        // Atualizar último login
        $stmt = $pdo->prepare("UPDATE ethics_lab_users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Log de login bem-sucedido
        logActivity("Login realizado: {$user['email']} ({$user['registration']})");
        
        jsonResponse([
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'user' => [
                'id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'registration' => $user['registration'],
                'course' => $user['course'],
                'is_admin' => false
            ]
        ]);
        
    } catch (PDOException $e) {
        logActivity("Erro de banco no login: " . $e->getMessage(), 'ERROR');
        jsonResponse([
            'success' => false,
            'message' => 'Erro interno do servidor'
        ], 500);
    }
}

/**
 * Processar logout
 */
function handleLogout() {
    if (isset($_SESSION['user_email'])) {
        logActivity("Logout realizado: {$_SESSION['user_email']}");
    } elseif (isset($_SESSION['admin_username'])) {
        logActivity("Logout administrativo realizado: {$_SESSION['admin_username']}");
    }
    
    // Destruir sessão
    session_destroy();
    
    jsonResponse([
        'success' => true,
        'message' => 'Logout realizado com sucesso'
    ]);
}

/**
 * Verificar se usuário está logado
 */
function handleVerify() {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        jsonResponse([
            'success' => false,
            'message' => 'Usuário não autenticado'
        ], 401);
    }
    
    // Verificar se sessão não expirou (2 horas)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
        session_destroy();
        jsonResponse([
            'success' => false,
            'message' => 'Sessão expirada'
        ], 401);
    }
    
    // Retornar dados do usuário
    if (isset($_SESSION['admin_id'])) {
        jsonResponse([
            'success' => true,
            'message' => 'Administrador autenticado',
            'user' => [
                'id' => $_SESSION['admin_id'],
                'name' => 'Administrador',
                'username' => $_SESSION['admin_username'],
                'is_admin' => true
            ]
        ]);
    } else {
        jsonResponse([
            'success' => true,
            'message' => 'Usuário autenticado',
            'user' => [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'registration' => $_SESSION['user_registration'],
                'course' => $_SESSION['user_course'],
                'is_admin' => false
            ]
        ]);
    }
}

/**
 * Validar dados de cadastro
 */
function validateRegistrationData($data) {
    $errors = [];
    
    // Nome
    if (empty($data['firstName']) || strlen(trim($data['firstName'])) < 2) {
        $errors[] = "Nome deve ter pelo menos 2 caracteres";
    }
    
    // Sobrenome
    if (empty($data['lastName']) || strlen(trim($data['lastName'])) < 2) {
        $errors[] = "Sobrenome deve ter pelo menos 2 caracteres";
    }
    
    // Email
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "E-mail inválido";
    }
    
    // Matrícula
    if (empty($data['registration']) || strlen(trim($data['registration'])) < 3) {
        $errors[] = "Matrícula deve ter pelo menos 3 caracteres";
    }
    
    // Curso
    $valid_courses = ['informatica', 'sistemas', 'redes', 'seguranca', 'outro'];
    if (empty($data['course']) || !in_array($data['course'], $valid_courses)) {
        $errors[] = "Curso inválido";
    }
    
    // Senha
    if (empty($data['password'])) {
        $errors[] = "Senha é obrigatória";
    } else {
        $password = $data['password'];
        
        if (strlen($password) < 8) {
            $errors[] = "Senha deve ter pelo menos 8 caracteres";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Senha deve conter pelo menos uma letra maiúscula";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Senha deve conter pelo menos uma letra minúscula";
        }
        
        if (!preg_match('/\d/', $password)) {
            $errors[] = "Senha deve conter pelo menos um número";
        }
    }
    
    // Confirmação de senha
    if (empty($data['confirmPassword']) || $data['password'] !== $data['confirmPassword']) {
        $errors[] = "Confirmação de senha não confere";
    }
    
    // Aceitar termos
    if (empty($data['acceptTerms']) || $data['acceptTerms'] !== 'on') {
        $errors[] = "Você deve aceitar os termos de uso";
    }
    
    return $errors;
}

/**
 * Verificar se usuário está autenticado (para uso em outras páginas)
 */
function requireAuth() {
    session_start();
    
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        header('Location: login.html');
        exit;
    }
    
    // Verificar se sessão não expirou
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
        session_destroy();
        header('Location: login.html');
        exit;
    }
    
    if (isset($_SESSION['admin_id'])) {
        return [
            'id' => $_SESSION['admin_id'],
            'name' => 'Administrador',
            'username' => $_SESSION['admin_username'],
            'is_admin' => true
        ];
    } else {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'registration' => $_SESSION['user_registration'],
            'course' => $_SESSION['user_course'],
            'is_admin' => false
        ];
    }
}

/**
 * Verificar se é administrador
 */
function requireAdmin() {
    session_start();
    
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.html');
        exit;
    }
    
    // Verificar se sessão não expirou
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 7200) {
        session_destroy();
        header('Location: login.html');
        exit;
    }
    
    return [
        'id' => $_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'],
        'is_admin' => true
    ];
}

/**
 * Obter dados do usuário atual
 */
function getCurrentUser() {
    if (isset($_SESSION['admin_id'])) {
        return [
            'id' => $_SESSION['admin_id'],
            'name' => 'Administrador',
            'username' => $_SESSION['admin_username'],
            'is_admin' => true
        ];
    } elseif (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'registration' => $_SESSION['user_registration'],
            'course' => $_SESSION['user_course'],
            'is_admin' => false
        ];
    }
    
    return null;
}

// Endpoint para verificar status de autenticação (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check'])) {
    session_start();
    
    if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
        jsonResponse([
            'authenticated' => true,
            'user' => getCurrentUser()
        ]);
    } else {
        jsonResponse([
            'authenticated' => false
        ]);
    }
}
?>