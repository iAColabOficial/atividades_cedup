<?php
/**
 * Sistema de Autenticação Centralizado
 * Professor Leandro Rodrigues
 * 
 * Gerencia login, logout, registro e verificação de sessões
 * para todos os módulos do sistema
 */

// Iniciar sistema
require_once '../config/config.php';

// Iniciar sessão segura
session_start();

// Definir headers de segurança
setSecurityHeaders();

// Verificar se está sendo chamado diretamente vs incluído
$is_direct_call = basename($_SERVER['SCRIPT_NAME']) === 'auth.php';

// Só processar requisições HTTP se chamado diretamente
if ($is_direct_call) {
    // Processar requisições baseadas no método HTTP
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        handlePostRequest();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleGetRequest();
    } else {
        jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
}

/**
 * Processar requisições POST (login, registro, logout)
 */
function handlePostRequest() {
    // Verificar Content-Type
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
        // Dados de formulário
        $input_data = $_POST;
    }
    
    $action = $input_data['action'] ?? '';
    
    try {
        switch ($action) {
            case 'register':
                handleRegister($input_data);
                break;
            case 'login':
                handleLogin($input_data);
                break;
            case 'admin_login':
                handleAdminLogin($input_data);
                break;
            case 'logout':
                handleLogout();
                break;
            default:
                jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
        }
    } catch (Exception $e) {
        logActivity("Erro de autenticação: " . $e->getMessage(), 'ERROR', 'AUTH');
        jsonResponse([
            'success' => false,
            'message' => 'Erro interno do servidor'
        ], 500);
    }
}

/**
 * Processar requisições GET (verificação de status)
 */
function handleGetRequest() {
    if (isset($_GET['check'])) {
        checkAuthenticationStatus();
    } elseif (isset($_GET['verify'])) {
        verifySession();
    } elseif (isset($_GET['status'])) {
        getSystemStatus();
    } else {
        jsonResponse(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
}

/**
 * Processar registro de novo usuário
 */
function handleRegister($data) {
    // Validar dados de entrada
    $validation_rules = [
        'firstName' => 'required|min:2|max:50',
        'lastName' => 'required|min:2|max:50',
        'email' => 'required|email|max:255',
        'registration' => 'required|min:3|max:100',
        'course' => 'required|max:100',
        'turma' => 'required|max:20',  // ADICIONADO: validação do campo turma
        'password' => 'required|min:8|password',
        'confirmPassword' => 'required'
    ];
    
    $validation_errors = validateInput($data, $validation_rules);
    
    // Verificar se senhas coincidem
    if (!empty($data['password']) && !empty($data['confirmPassword'])) {
        if ($data['password'] !== $data['confirmPassword']) {
            $validation_errors['confirmPassword'][] = 'Confirmação de senha não confere';
        }
    }
    
    // Verificar se aceitou termos
    if (empty($data['acceptTerms']) || $data['acceptTerms'] !== 'on') {
        $validation_errors['acceptTerms'][] = 'Você deve aceitar os termos de uso';
    }
    
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
        $stmt = $pdo->prepare("SELECT id FROM system_users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            jsonResponse([
                'success' => false,
                'message' => 'E-mail já cadastrado no sistema'
            ], 400);
        }
        
        // Verificar se matrícula já existe
        $stmt = $pdo->prepare("SELECT id FROM system_users WHERE registration = ?");
        $stmt->execute([$data['registration']]);
        if ($stmt->fetch()) {
            jsonResponse([
                'success' => false,
                'message' => 'Matrícula já cadastrada no sistema'
            ], 400);
        }
        
        // Hash da senha
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // CORRIGIDO: Inserir usuário incluindo campo turma
        $stmt = $pdo->prepare("
            INSERT INTO system_users 
            (first_name, last_name, email, registration, course, turma, password_hash, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $data['firstName'],
            $data['lastName'],
            $data['email'],
            $data['registration'],
            $data['course'],
            $data['turma'],  // ADICIONADO: campo turma
            $password_hash
        ]);
        
        $user_id = $pdo->lastInsertId();
        
        // Log da atividade incluindo turma
        logActivity("Novo usuário cadastrado: {$data['email']} ({$data['registration']}) - Curso: {$data['course']} - Turma: {$data['turma']}", 'INFO', 'AUTH');
        
        jsonResponse([
            'success' => true,
            'message' => 'Cadastro realizado com sucesso!',
            'user_id' => $user_id
        ]);
        
    } catch (PDOException $e) {
        logActivity("Erro de banco no cadastro: " . $e->getMessage(), 'ERROR', 'AUTH');
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
        
        // CORRIGIDO: Buscar usuário incluindo campo turma
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, email, registration, course, turma,
                   password_hash, status, login_attempts, last_login
            FROM system_users 
            WHERE (email = ? OR registration = ?)
        ");
        $stmt->execute([$data['email'], $data['email']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            logActivity("Tentativa de login com credencial inexistente: {$data['email']}", 'WARNING', 'AUTH');
            jsonResponse([
                'success' => false,
                'message' => 'Credenciais inválidas'
            ], 401);
        }
        
        // Verificar status do usuário
        $status_check = checkUserStatus($user['id']);
        if (!$status_check['valid']) {
            jsonResponse([
                'success' => false,
                'message' => $status_check['reason']
            ], 401);
        }
        
        // Verificar senha
        if (!password_verify($data['password'], $user['password_hash'])) {
            // Incrementar tentativas de login
            incrementLoginAttempts($user['id']);
            
            logActivity("Tentativa de login com senha incorreta: {$user['email']}", 'WARNING', 'AUTH');
            jsonResponse([
                'success' => false,
                'message' => 'Credenciais inválidas'
            ], 401);
        }
        
        // Login bem-sucedido - resetar tentativas
        resetLoginAttempts($user['id']);
        
        // Criar sessão
        createUserSession($user);
        
        // Atualizar último login
        updateLastLogin($user['id']);
        
        // Log de login bem-sucedido
        logActivity("Login realizado: {$user['email']} ({$user['registration']}) - {$user['course']} - {$user['turma']}", 'INFO', 'AUTH', $user['id']);
        
        jsonResponse([
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'user' => [
                'id' => $user['id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'registration' => $user['registration'],
                'course' => $user['course'],
                'turma' => $user['turma'],  // ADICIONADO: incluir turma na resposta
                'is_admin' => false
            ]
        ]);
        
    } catch (PDOException $e) {
        logActivity("Erro de banco no login: " . $e->getMessage(), 'ERROR', 'AUTH');
        jsonResponse([
            'success' => false,
            'message' => 'Erro interno do servidor'
        ], 500);
    }
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
        // Criar sessão administrativa
        createAdminSession();
        
        // Log de login administrativo
        logActivity("Login administrativo realizado", 'INFO', 'AUTH');
        
        jsonResponse([
            'success' => true,
            'message' => 'Login administrativo realizado com sucesso!',
            'user' => [
                'id' => 'admin',
                'name' => 'Administrador',
                'username' => ADMIN_USERNAME,
                'email' => ADMIN_EMAIL,
                'is_admin' => true
            ]
        ]);
    } else {
        // Log de tentativa de login admin inválida
        logActivity("Tentativa de login administrativo inválida: {$data['email']}", 'WARNING', 'AUTH');
        
        jsonResponse([
            'success' => false,
            'message' => 'Credenciais administrativas inválidas'
        ], 401);
    }
}

/**
 * Processar logout
 */
function handleLogout() {
    $user_info = getCurrentUser();
    
    if ($user_info) {
        if ($user_info['is_admin']) {
            logActivity("Logout administrativo realizado", 'INFO', 'AUTH');
        } else {
            logActivity("Logout realizado: {$user_info['email']}", 'INFO', 'AUTH', $user_info['id']);
        }
        
        // Remover sessão do banco de dados
        removeSessionFromDatabase();
    }
    
    // Destruir sessão
    session_destroy();
    
    jsonResponse([
        'success' => true,
        'message' => 'Logout realizado com sucesso'
    ]);
}

/**
 * Verificar status de autenticação
 */
function checkAuthenticationStatus() {
    $user = getCurrentUser();
    
    if ($user) {
        // Verificar se sessão não expirou
        if (isSessionExpired()) {
            session_destroy();
            jsonResponse([
                'authenticated' => false,
                'message' => 'Sessão expirada'
            ]);
        }
        
        // Regenerar ID da sessão se necessário
        regenerateSessionIfNeeded();
        
        jsonResponse([
            'authenticated' => true,
            'user' => $user
        ]);
    } else {
        jsonResponse([
            'authenticated' => false
        ]);
    }
}

/**
 * Verificar validade da sessão atual
 */
function verifySession() {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        jsonResponse([
            'valid' => false,
            'message' => 'Usuário não autenticado'
        ], 401);
    }
    
    if (isSessionExpired()) {
        session_destroy();
        jsonResponse([
            'valid' => false,
            'message' => 'Sessão expirada'
        ], 401);
    }
    
    jsonResponse([
        'valid' => true,
        'user' => getCurrentUser()
    ]);
}

/**
 * Obter status do sistema
 */
function getSystemStatus() {
    $stats = getSystemStats();
    jsonResponse([
        'status' => 'online',
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Criar sessão de usuário
 */
function createUserSession($user) {
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['user_registration'] = $user['registration'];
    $_SESSION['user_course'] = $user['course'];
    $_SESSION['user_turma'] = $user['turma'];  // ADICIONADO: salvar turma na sessão
    $_SESSION['is_admin'] = false;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Salvar sessão no banco de dados
    saveSessionToDatabase($user['id'], false);
}

/**
 * Criar sessão administrativa
 */
function createAdminSession() {
    session_regenerate_id(true);
    
    $_SESSION['admin_id'] = 'admin';
    $_SESSION['admin_username'] = ADMIN_USERNAME;
    $_SESSION['admin_email'] = ADMIN_EMAIL;
    $_SESSION['is_admin'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Salvar sessão no banco de dados
    saveSessionToDatabase(null, true);
}

/**
 * Salvar sessão no banco de dados
 */
function saveSessionToDatabase($user_id, $is_admin) {
    try {
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO system_sessions 
            (id, user_id, admin_session, ip_address, user_agent, payload) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            last_activity = CURRENT_TIMESTAMP,
            payload = VALUES(payload)
        ");
        
        $stmt->execute([
            session_id(),
            $user_id,
            $is_admin ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            json_encode($_SESSION)
        ]);
    } catch (Exception $e) {
        logActivity("Erro ao salvar sessão: " . $e->getMessage(), 'ERROR', 'AUTH');
    }
}

/**
 * Remover sessão do banco de dados
 */
function removeSessionFromDatabase() {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM system_sessions WHERE id = ?");
        $stmt->execute([session_id()]);
    } catch (Exception $e) {
        logActivity("Erro ao remover sessão: " . $e->getMessage(), 'ERROR', 'AUTH');
    }
}

/**
 * Verificar se sessão expirou
 */
function isSessionExpired() {
    if (!isset($_SESSION['last_activity'])) {
        return true;
    }
    
    $timeout = $_SESSION['is_admin'] ? 
        $GLOBALS['security_config']['admin_session_timeout'] : 
        $GLOBALS['app_config']['session_timeout'];
    
    return (time() - $_SESSION['last_activity']) > $timeout;
}

/**
 * Regenerar ID da sessão se necessário
 */
function regenerateSessionIfNeeded() {
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
        return;
    }
    
    $interval = $GLOBALS['security_config']['session_regenerate_interval'];
    if ((time() - $_SESSION['last_regeneration']) > $interval) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
        
        // Atualizar no banco de dados
        if (isset($_SESSION['user_id'])) {
            saveSessionToDatabase($_SESSION['user_id'], $_SESSION['is_admin']);
        }
    }
}

/**
 * Incrementar tentativas de login
 */
function incrementLoginAttempts($user_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE system_users SET login_attempts = login_attempts + 1 WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        logActivity("Erro ao incrementar tentativas de login: " . $e->getMessage(), 'ERROR', 'AUTH');
    }
}

/**
 * Resetar tentativas de login
 */
function resetLoginAttempts($user_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("UPDATE system_users SET login_attempts = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        logActivity("Erro ao resetar tentativas de login: " . $e->getMessage(), 'ERROR', 'AUTH');
    }
}

/**
 * Atualizar último login
 */
function updateLastLogin($user_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            UPDATE system_users 
            SET last_login = NOW(), last_login_ip = ? 
            WHERE id = ?
        ");
        $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '', $user_id]);
    } catch (Exception $e) {
        logActivity("Erro ao atualizar último login: " . $e->getMessage(), 'ERROR', 'AUTH');
    }
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
            'email' => $_SESSION['admin_email'],
            'is_admin' => true
        ];
    } elseif (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'registration' => $_SESSION['user_registration'],
            'course' => $_SESSION['user_course'],
            'turma' => $_SESSION['user_turma'] ?? '',  // ADICIONADO: incluir turma
            'is_admin' => false
        ];
    }
    
    return null;
}

/**
 * Verificar se usuário está autenticado (para uso em outras páginas)
 */
function requireAuth() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    $user = getCurrentUser();
    if (!$user) {
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => 'Não autenticado'], 401);
        } else {
            header('Location: /atividades/auth/login.html');
            exit;
        }
    }
    
    if (isSessionExpired()) {
        session_destroy();
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => 'Sessão expirada'], 401);
        } else {
            header('Location: /atividades/auth/login.html');
            exit;
        }
    }
    
    // Atualizar última atividade
    $_SESSION['last_activity'] = time();
    
    return $user;
}

/**
 * Verificar se é administrador
 */
function requireAdmin() {
    $user = requireAuth();
    
    if (!$user['is_admin']) {
        if (isAjaxRequest()) {
            jsonResponse(['success' => false, 'message' => 'Acesso negado'], 403);
        } else {
            header('Location: /atividades/auth/login.html');
            exit;
        }
    }
    
    return $user;
}

/**
 * Verificar se é uma requisição AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Atualizar última atividade se sessão ativa
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    $_SESSION['last_activity'] = time();
}
?>