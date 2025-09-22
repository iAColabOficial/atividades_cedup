<?php
/**
 * Setup/Instalador do Laboratório Virtual de Decisões Éticas em TI
 * Professor Leandro Rodrigues
 * 
 * Este arquivo deve ser executado apenas uma vez para configurar o sistema
 * Após a instalação, delete este arquivo por segurança
 */

session_start();

// Configurações de segurança
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['setup_token'] ?? '', $_POST['setup_token'] ?? '')) {
    die('Token de segurança inválido');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $_SESSION['setup_token'] = bin2hex(random_bytes(32));
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success_messages = [];

// Função para verificar requisitos
function checkRequirements() {
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'JSON Extension' => extension_loaded('json'),
        'Logs Directory Writable' => is_writable(dirname(__FILE__)) || mkdir('logs', 0755, true)
    ];
    
    return $requirements;
}

// Função para testar conexão com banco
function testDatabaseConnection($config) {
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return ['success' => true, 'message' => 'Conexão estabelecida com sucesso!'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
    }
}

// Função para criar tabelas
function createTables($config) {
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Tabela de usuários
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
        
        // Tabela de estudantes
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
        
        // Tabela de escolhas
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
        
        $pdo->exec($sql_users);
        $pdo->exec($sql_students);
        $pdo->exec($sql_choices);
        
        return ['success' => true, 'message' => 'Tabelas criadas com sucesso!'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erro ao criar tabelas: ' . $e->getMessage()];
    }
}

// Configuração padrão do banco
$db_config = [
    'host' => 'localhost',
    'dbname' => 'u906658109_atividades',
    'username' => 'u906658109_atividades',
    'password' => 'P@ncho2891.'
];

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        // Testar conexão
        $test_result = testDatabaseConnection($db_config);
        if ($test_result['success']) {
            $success_messages[] = $test_result['message'];
        } else {
            $errors[] = $test_result['message'];
        }
    } elseif ($step == 3) {
        // Criar tabelas
        $create_result = createTables($db_config);
        if ($create_result['success']) {
            $success_messages[] = $create_result['message'];
        } else {
            $errors[] = $create_result['message'];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Laboratório Virtual de Ética</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .professor-info {
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            margin-top: 15px;
        }
        
        .content {
            padding: 40px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            position: relative;
        }
        
        .step.completed {
            background: #27ae60;
            color: white;
        }
        
        .step.current {
            background: #3498db;
            color: white;
        }
        
        .step.pending {
            background: #ecf0f1;
            color: #95a5a6;
        }
        
        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 20px;
            height: 2px;
            background: #ecf0f1;
            transform: translateY(-50%);
        }
        
        .requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .requirement:last-child {
            border-bottom: none;
        }
        
        .requirement .status {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: white;
        }
        
        .requirement .status.ok {
            background: #27ae60;
        }
        
        .requirement .status.error {
            background: #e74c3c;
        }
        
        .config-info {
            background: #e8f4f8;
            border: 1px solid #bee5eb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .config-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #bee5eb;
        }
        
        .config-item:last-child {
            border-bottom: none;
        }
        
        .config-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .config-value {
            color: #495057;
            font-family: monospace;
        }
        
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 10px 5px;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #27ae60;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-warning {
            background: #f39c12;
        }
        
        .btn-warning:hover {
            background: #e67e22;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .message.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .message.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .final-actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            color: #856404;
        }
        
        .warning-box strong {
            color: #d63031;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛠️ Setup do Sistema</h1>
            <p>Laboratório Virtual de Decisões Éticas em TI</p>
            <div class="professor-info">
                <span>👨‍🏫 Professor Leandro Rodrigues</span>
            </div>
        </div>
        
        <div class="content">
            <!-- Indicador de passos -->
            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? ($step > 1 ? 'completed' : 'current') : 'pending'; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? ($step > 2 ? 'completed' : 'current') : 'pending'; ?>">2</div>
                <div class="step <?php echo $step >= 3 ? ($step > 3 ? 'completed' : 'current') : 'pending'; ?>">3</div>
                <div class="step <?php echo $step >= 4 ? 'current' : 'pending'; ?>">4</div>
            </div>
            
            <!-- Mensagens -->
            <?php foreach ($errors as $error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
            
            <?php foreach ($success_messages as $message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endforeach; ?>
            
            <?php if ($step == 1): ?>
                <!-- Passo 1: Verificar Requisitos -->
                <h2>📋 Verificação de Requisitos</h2>
                <p>Verificando se o servidor atende aos requisitos mínimos do sistema:</p>
                
                <div class="requirements">
                    <?php
                    $requirements = checkRequirements();
                    $all_ok = true;
                    foreach ($requirements as $name => $status):
                        if (!$status) $all_ok = false;
                    ?>
                        <div class="requirement">
                            <div class="status <?php echo $status ? 'ok' : 'error'; ?>">
                                <?php echo $status ? '✓' : '✗'; ?>
                            </div>
                            <span><?php echo $name; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($all_ok): ?>
                    <div class="message success">
                        ✅ Todos os requisitos foram atendidos! Você pode prosseguir com a instalação.
                    </div>
                    <a href="?step=2" class="btn btn-success">Continuar para Configuração do Banco</a>
                <?php else: ?>
                    <div class="message error">
                        ❌ Alguns requisitos não foram atendidos. Entre em contato com o suporte do servidor.
                    </div>
                    <button onclick="location.reload()" class="btn">Verificar Novamente</button>
                <?php endif; ?>
                
            <?php elseif ($step == 2): ?>
                <!-- Passo 2: Configuração do Banco -->
                <h2>🗄️ Configuração do Banco de Dados</h2>
                <p>Configurações do banco de dados MySQL:</p>
                
                <div class="config-info">
                    <div class="config-item">
                        <span class="config-label">Host:</span>
                        <span class="config-value"><?php echo htmlspecialchars($db_config['host']); ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Banco:</span>
                        <span class="config-value"><?php echo htmlspecialchars($db_config['dbname']); ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Usuário:</span>
                        <span class="config-value"><?php echo htmlspecialchars($db_config['username']); ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Senha:</span>
                        <span class="config-value">••••••••••</span>
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="setup_token" value="<?php echo $_SESSION['setup_token']; ?>">
                    <button type="submit" class="btn">Testar Conexão</button>
                </form>
                
                <?php if (empty($errors) && !empty($success_messages)): ?>
                    <a href="?step=3" class="btn btn-success">Continuar para Criação das Tabelas</a>
                <?php endif; ?>
                
            <?php elseif ($step == 3): ?>
                <!-- Passo 3: Criar Tabelas -->
                <h2>📊 Criação das Tabelas</h2>
                <p>Criando as tabelas necessárias no banco de dados:</p>
                
                <div class="config-info">
                    <h4>Tabelas que serão criadas:</h4>
                    <ul style="margin: 15px 0 0 20px;">
                        <li><strong>ethics_lab_users</strong> - Usuários do sistema</li>
                        <li><strong>ethics_lab_students</strong> - Dados dos estudantes</li>
                        <li><strong>ethics_lab_choices</strong> - Escolhas individuais dos dilemas</li>
                    </ul>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="setup_token" value="<?php echo $_SESSION['setup_token']; ?>">
                    <button type="submit" class="btn">Criar Tabelas</button>
                </form>
                
                <?php if (empty($errors) && !empty($success_messages)): ?>
                    <a href="?step=4" class="btn btn-success">Finalizar Instalação</a>
                <?php endif; ?>
                
            <?php elseif ($step == 4): ?>
                <!-- Passo 4: Finalização -->
                <h2>🎉 Instalação Concluída!</h2>
                <p>O sistema foi instalado com sucesso e está pronto para uso.</p>
                
                <div class="message success">
                    ✅ Todas as configurações foram aplicadas corretamente!
                </div>
                
                <div class="warning-box">
                    <strong>⚠️ IMPORTANTE:</strong><br>
                    Por motivos de segurança, delete este arquivo (setup.php) após a instalação.<br>
                    Mantenha-o apenas se precisar reinstalar o sistema.
                </div>
                
                <div class="final-actions">
                    <h3>🚀 Próximos Passos:</h3>
                    <p style="margin: 20px 0;">Acesse os links abaixo para usar o sistema:</p>
                    
                    <a href="login.html" class="btn btn-success">
                        🎯 Acessar Sistema de Login
                    </a>
                    
                    <a href="admin.php" class="btn">
                        📊 Painel Administrativo
                    </a>
                    
                    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4>📁 URLs de Acesso:</h4>
                        <p><strong>Login/Laboratório:</strong> https://proleandro.com.br/atividades/etica/login.html</p>
                        <p><strong>Painel Admin:</strong> https://proleandro.com.br/atividades/etica/admin.php</p>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</body>
</html>