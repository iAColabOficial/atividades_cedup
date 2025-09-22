<?php
/**
 * Página de Detalhes - Sistema Admin CORRIGIDA
 * Visualização detalhada de atividades concluídas
 * Versão robusta com tratamento completo de erros
 */

// Ativar relatório de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../config/config.php';
    require_once '../auth/auth.php';
} catch (Exception $e) {
    die("Erro ao carregar configurações: " . $e->getMessage());
}

// Verificar autenticação admin
try {
    $user = requireAuth();
    
    // Verificação robusta de admin
    $is_admin = false;
    if (isset($user['is_admin']) && $user['is_admin']) {
        $is_admin = true;
    } elseif (isset($user['id']) && $user['id'] === 'admin') {
        $is_admin = true;
    } elseif (isset($user['username']) && $user['username'] === 'Admin') {
        $is_admin = true;
    }
    
    if (!$is_admin) {
        header('Location: ../auth/login.html');
        exit;
    }
} catch (Exception $e) {
    die("Erro de autenticação: " . $e->getMessage());
}

// Obter e validar parâmetros
$module = $_GET['module'] ?? null;
$result_id = $_GET['id'] ?? null;

// Validação básica
if (!$module || !$result_id) {
    header('Location: index.php?error=missing_params');
    exit;
}

if (!is_numeric($result_id)) {
    header('Location: index.php?error=invalid_id');
    exit;
}

$valid_modules = ['rs_lab', 'ethics_lab', 'ethics', 'der_quiz'];
if (!in_array($module, $valid_modules)) {
    header('Location: index.php?error=invalid_module');
    exit;
}

// Inicializar variáveis
$result_data = null;
$detailed_answers = [];
$error_message = '';

try {
    // Carregar dados baseado no módulo
    $result_data = getResultData($module, $result_id);
    
    if (!$result_data) {
        header('Location: index.php?error=not_found');
        exit;
    }
    
    // Tentar carregar respostas detalhadas (opcional)
    $detailed_answers = getDetailedAnswers($module, $result_id);
    
} catch (Exception $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
    error_log("Erro em view_details.php: " . $e->getMessage());
}

/**
 * Buscar dados do resultado principal com verificação de tabela
 */
function getResultData($module, $result_id) {
    try {
        $pdo = getConnection();
        
        // Mapear módulos para tabelas
        $table_map = [
            'rs_lab' => 'rs_lab_results',
            'ethics_lab' => 'ethics_lab_results', 
            'ethics' => 'ethics_lab_results',
            'der_quiz' => 'der_quiz_students'
        ];
        
        if (!isset($table_map[$module])) {
            return null;
        }
        
        $table_name = $table_map[$module];
        
        // Verificar se tabela existe
        $stmt = $pdo->query("SHOW TABLES LIKE '$table_name'");
        if ($stmt->rowCount() == 0) {
            error_log("Tabela $table_name não encontrada para módulo $module");
            return null;
        }
        
        // Buscar dados
        $stmt = $pdo->prepare("SELECT * FROM $table_name WHERE id = ?");
        $stmt->execute([$result_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Erro em getResultData: " . $e->getMessage());
        return null;
    }
}

/**
 * Buscar respostas detalhadas (se disponíveis)
 */
function getDetailedAnswers($module, $result_id) {
    try {
        $pdo = getConnection();
        
        // Mapear módulos para tabelas de respostas
        $answer_tables = [
            'rs_lab' => 'rs_lab_answers',
            'ethics_lab' => 'ethics_lab_answers',
            'ethics' => 'ethics_lab_answers',
            'der_quiz' => 'der_quiz_answers'
        ];
        
        if (!isset($answer_tables[$module])) {
            return [];
        }
        
        $table_name = $answer_tables[$module];
        
        // Verificar se tabela existe
        $stmt = $pdo->query("SHOW TABLES LIKE '$table_name'");
        if ($stmt->rowCount() == 0) {
            return []; // Tabela não existe, mas não é erro
        }
        
        // Buscar respostas
        $stmt = $pdo->prepare("SELECT * FROM $table_name WHERE result_id = ? ORDER BY id");
        $stmt->execute([$result_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Erro em getDetailedAnswers: " . $e->getMessage());
        return [];
    }
}

/**
 * Obter configuração do módulo (verificação de existência)
 */
if (!function_exists('getModuleConfig')) {
    function getModuleConfig($module) {
        $configs = [
            'rs_lab' => [
                'name' => 'Laboratório de Recrutamento e Seleção',
                'icon' => '📈',
                'description' => 'Excel aplicado ao RH - Fórmulas SOMA, SE, PROCV, MÉDIA'
            ],
            'ethics_lab' => [
                'name' => 'Laboratório de Decisões Éticas',
                'icon' => '⚖️',
                'description' => 'Análise de cenários éticos em TI'
            ],
            'ethics' => [
                'name' => 'Laboratório de Decisões Éticas',
                'icon' => '⚖️',
                'description' => 'Análise de cenários éticos em TI'
            ],
            'der_quiz' => [
                'name' => 'DER Quiz Interativo',
                'icon' => '📊',
                'description' => 'Quiz sobre Modelagem Entidade-Relacionamento'
            ]
        ];
        
        return $configs[$module] ?? [
            'name' => 'Módulo Desconhecido',
            'icon' => '❓',
            'description' => 'Descrição não disponível'
        ];
    }
}

/**
 * Formatar tempo em segundos para legível (verificação de existência)
 */
if (!function_exists('formatDuration')) {
    function formatDuration($seconds) {
        if (empty($seconds) || !is_numeric($seconds)) {
            return 'N/A';
        }
        
        if ($seconds < 60) {
            return $seconds . 's';
        }
        
        $minutes = floor($seconds / 60);
        $remaining_seconds = $seconds % 60;
        
        if ($minutes < 60) {
            return $minutes . 'm ' . $remaining_seconds . 's';
        }
        
        $hours = floor($minutes / 60);
        $remaining_minutes = $minutes % 60;
        return $hours . 'h ' . $remaining_minutes . 'm ' . $remaining_seconds . 's';
    }
}

/**
 * Calcular estatísticas por fórmula (RS Lab) - verificação de existência
 */
if (!function_exists('calculateFormulaStats')) {
    function calculateFormulaStats($formulas_used) {
        if (empty($formulas_used)) {
            return [];
        }
        
        // Tentar decodificar JSON
        $data = null;
        if (is_string($formulas_used)) {
            $data = json_decode($formulas_used, true);
        } elseif (is_array($formulas_used)) {
            $data = $formulas_used;
        }
        
        if (!$data || !is_array($data)) {
            return [];
        }
        
        $stats = [];
        foreach ($data as $formula => $info) {
            if (is_array($info) && isset($info['count'], $info['correct'])) {
                $percentage = $info['count'] > 0 ? round(($info['correct'] / $info['count']) * 100) : 0;
                $stats[$formula] = [
                    'count' => $info['count'],
                    'correct' => $info['correct'],
                    'percentage' => $percentage,
                    'status' => $percentage >= 80 ? 'excellent' : ($percentage >= 60 ? 'good' : 'poor')
                ];
            }
        }
        
        return $stats;
    }
}

// Obter configuração do módulo
$module_config = getModuleConfig($module);
$formula_stats = [];

// Calcular estatísticas específicas do módulo
if ($module === 'rs_lab' && isset($result_data['formulas_used'])) {
    $formula_stats = calculateFormulaStats($result_data['formulas_used']);
}

// Função para obter nome do usuário (verificação de existência)
if (!function_exists('getUserDisplayName')) {
    function getUserDisplayName($result_data) {
        // Tentar diferentes campos de nome
        $name_fields = ['user_name', 'name', 'full_name'];
        foreach ($name_fields as $field) {
            if (!empty($result_data[$field])) {
                return $result_data[$field];
            }
        }
        
        // Tentar concatenar first_name + last_name
        if (!empty($result_data['first_name']) || !empty($result_data['last_name'])) {
            return trim(($result_data['first_name'] ?? '') . ' ' . ($result_data['last_name'] ?? ''));
        }
        
        return 'Usuário não identificado';
    }
}

$display_name = getUserDisplayName($result_data);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes - <?= htmlspecialchars($display_name) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            line-height: 1.6;
            color: #2c3e50;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-title {
            font-size: 2em;
            font-weight: 300;
        }
        
        .header-subtitle {
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .btn-back:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .details-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 5px solid #3498db;
        }
        
        .card-title {
            font-size: 1.3em;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .info-grid {
            display: grid;
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #7f8c8d;
        }
        
        .info-value {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .score-display {
            text-align: center;
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        .score-number {
            font-size: 3em;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        
        .score-label {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .formula-stats {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .stat-item.excellent {
            border-color: #27ae60;
            background: #f8fff8;
        }
        
        .stat-item.good {
            border-color: #f39c12;
            background: #fffcf0;
        }
        
        .stat-item.poor {
            border-color: #e74c3c;
            background: #fff5f5;
        }
        
        .stat-formula {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stat-percentage {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .excellent .stat-percentage {
            color: #27ae60;
        }
        
        .good .stat-percentage {
            color: #f39c12;
        }
        
        .poor .stat-percentage {
            color: #e74c3c;
        }
        
        .stat-count {
            font-size: 0.9em;
            color: #666;
        }
        
        .no-details {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
        }
        
        .no-details h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .module-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-left: 15px;
            backdrop-filter: blur(10px);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <?php if (!empty($error_message)): ?>
            <div class="error"><?= htmlspecialchars($error_message) ?></div>
            <a href="index.php" class="btn btn-primary">Voltar ao Dashboard</a>
        <?php else: ?>
            
            <!-- Header -->
            <div class="header">
                <div class="header-content">
                    <div>
                        <h1 class="header-title">
                            Detalhes da Atividade
                            <span class="module-badge"><?= htmlspecialchars($module_config['name']) ?></span>
                        </h1>
                        <p class="header-subtitle">
                            <?= htmlspecialchars($display_name) ?>
                            <?php if (!empty($result_data['user_registration'])): ?>
                                - Mat: <?= htmlspecialchars($result_data['user_registration']) ?>
                            <?php elseif (!empty($result_data['registration'])): ?>
                                - Mat: <?= htmlspecialchars($result_data['registration']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="index.php" class="btn-back">← Voltar ao Dashboard</a>
                </div>
            </div>

            <!-- Pontuação Principal -->
            <div class="score-display">
                <span class="score-number"><?= number_format($result_data['final_score'] ?? 0, 1) ?></span>
                <span class="score-label">pontos</span>
            </div>

            <!-- Informações Gerais -->
            <div class="details-grid">
                <!-- Dados do Usuário -->
                <div class="details-card">
                    <h3 class="card-title">Dados do Participante</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Nome:</span>
                            <span class="info-value"><?= htmlspecialchars($display_name) ?></span>
                        </div>
                        <?php if (!empty($result_data['user_email']) || !empty($result_data['email'])): ?>
                        <div class="info-item">
                            <span class="info-label">E-mail:</span>
                            <span class="info-value"><?= htmlspecialchars($result_data['user_email'] ?? $result_data['email']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($result_data['user_registration']) || !empty($result_data['registration'])): ?>
                        <div class="info-item">
                            <span class="info-label">Matrícula:</span>
                            <span class="info-value"><?= htmlspecialchars($result_data['user_registration'] ?? $result_data['registration']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($result_data['user_course']) || !empty($result_data['course'])): ?>
                        <div class="info-item">
                            <span class="info-label">Curso:</span>
                            <span class="info-value"><?= htmlspecialchars($result_data['user_course'] ?? $result_data['course']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label">Concluído em:</span>
                            <span class="info-value"><?= date('d/m/Y H:i', strtotime($result_data['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Resultados -->
                <div class="details-card">
                    <h3 class="card-title">Resultados</h3>
                    <div class="info-grid">
                        <?php if (!empty($result_data['correct_answers']) && !empty($result_data['total_questions'])): ?>
                        <div class="info-item">
                            <span class="info-label">Questões Corretas:</span>
                            <span class="info-value"><?= $result_data['correct_answers'] ?>/<?= $result_data['total_questions'] ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label">Pontuação Final:</span>
                            <span class="info-value"><?= number_format($result_data['final_score'] ?? 0, 1) ?>%</span>
                        </div>
                        <?php if (!empty($result_data['time_spent_seconds'])): ?>
                        <div class="info-item">
                            <span class="info-label">Tempo Gasto:</span>
                            <span class="info-value"><?= formatDuration($result_data['time_spent_seconds']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($result_data['scenario_id'])): ?>
                        <div class="info-item">
                            <span class="info-label">Cenário:</span>
                            <span class="info-value"><?= htmlspecialchars($result_data['scenario_id']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Estatísticas por Fórmula (RS Lab) -->
            <?php if (!empty($formula_stats)): ?>
            <div class="formula-stats">
                <h3 class="card-title">Performance por Tipo de Fórmula</h3>
                <div class="stats-grid">
                    <?php foreach ($formula_stats as $formula => $stats): ?>
                    <div class="stat-item <?= $stats['status'] ?>">
                        <div class="stat-formula"><?= htmlspecialchars($formula) ?></div>
                        <div class="stat-percentage"><?= $stats['percentage'] ?>%</div>
                        <div class="stat-count"><?= $stats['correct'] ?>/<?= $stats['count'] ?> corretas</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Aviso sobre Detalhes -->
            <?php if (empty($detailed_answers)): ?>
            <div class="no-details">
                <h4>Detalhes por Questão Não Disponíveis</h4>
                <p>As respostas individuais não foram salvas para esta atividade. Apenas dados gerais estão disponíveis.</p>
                <p><small>Para atividades futuras, os detalhes por questão serão salvos automaticamente.</small></p>
            </div>
            <?php endif; ?>

            <!-- Ações -->
            <div class="actions">
                <button onclick="window.print()" class="btn btn-primary">🖨️ Imprimir Relatório</button>
                <button onclick="exportData()" class="btn btn-secondary">💾 Exportar Dados</button>
            </div>
            
        <?php endif; ?>
    </div>

    <script>
        function exportData() {
            const data = {
                usuario: "<?= addslashes($display_name) ?>",
                email: "<?= addslashes($result_data['user_email'] ?? $result_data['email'] ?? '') ?>",
                matricula: "<?= addslashes($result_data['user_registration'] ?? $result_data['registration'] ?? '') ?>",
                curso: "<?= addslashes($result_data['user_course'] ?? $result_data['course'] ?? '') ?>",
                modulo: "<?= addslashes($module_config['name']) ?>",
                pontuacao_final: <?= $result_data['final_score'] ?? 0 ?>,
                questoes_corretas: "<?= ($result_data['correct_answers'] ?? 0) . '/' . ($result_data['total_questions'] ?? 0) ?>",
                tempo_gasto: "<?= isset($result_data['time_spent_seconds']) ? formatDuration($result_data['time_spent_seconds']) : 'N/A' ?>",
                data_conclusao: "<?= date('d/m/Y H:i', strtotime($result_data['created_at'])) ?>",
                <?php if (!empty($formula_stats)): ?>
                formulas: <?= json_encode($formula_stats) ?>,
                <?php endif; ?>
                exportado_em: new Date().toLocaleString('pt-BR')
            };
            
            const jsonString = JSON.stringify(data, null, 2);
            const blob = new Blob([jsonString], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `detalhes_<?= $module ?>_<?= $result_id ?>_<?= date('Y-m-d') ?>.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>