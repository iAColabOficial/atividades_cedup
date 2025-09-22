<?php
/**
 * Sistema de Exportação de Dados CORRIGIDO
 * Professor Leandro Rodrigues
 * 
 * Permite exportar dados de atividades de ética em diferentes formatos
 */

// Incluir configurações e autenticação
require_once '../config/config.php';
require_once '../auth/auth.php';

// Verificar se é administrador
$admin = requireAdmin();

// Parâmetros de exportação
$export_type = $_GET['type'] ?? 'ethics_activities'; // ethics_activities, users, logs, full
$format = $_GET['format'] ?? 'csv'; // csv, json
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$course_filter = $_GET['course_filter'] ?? '';
$turma_filter = $_GET['turma_filter'] ?? '';
$score_min = $_GET['score_min'] ?? 0;
$score_max = $_GET['score_max'] ?? 100;

// Validar parâmetros
$valid_types = ['ethics_activities', 'users', 'logs', 'full', 'ethics_detailed'];
$valid_formats = ['csv', 'json'];

if (!in_array($export_type, $valid_types)) {
    die('Tipo de exportação inválido');
}

if (!in_array($format, $valid_formats)) {
    die('Formato de exportação inválido');
}

try {
    $pdo = getConnection();
    
    // Log da exportação
    logActivity("Exportação iniciada - Tipo: {$export_type}, Formato: {$format}", 'INFO', 'ADMIN_EXPORT', $admin['id']);
    
    // Processar exportação baseada no tipo
    switch ($export_type) {
        case 'ethics_activities':
            exportEthicsActivities($pdo, $format, $date_from, $date_to, $course_filter, $turma_filter, $score_min, $score_max);
            break;
        case 'users':
            exportUsers($pdo, $format);
            break;
        case 'logs':
            exportLogs($pdo, $format, $date_from, $date_to);
            break;
        case 'ethics_detailed':
            exportEthicsDetailed($pdo, $format, $date_from, $date_to);
            break;
        case 'full':
            exportFullData($pdo, $format, $date_from, $date_to);
            break;
        default:
            die('Tipo de exportação não implementado');
    }
    
} catch (Exception $e) {
    logActivity("Erro na exportação: " . $e->getMessage(), 'ERROR', 'ADMIN_EXPORT', $admin['id']);
    die('Erro interno: ' . $e->getMessage());
}

/**
 * Exportar atividades de ética
 */
function exportEthicsActivities($pdo, $format, $date_from = '', $date_to = '', $course_filter = '', $turma_filter = '', $score_min = 0, $score_max = 100) {
    // Construir query com filtros
    $where_conditions = ["1=1"];
    $params = [];
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(es.created_at) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(es.created_at) <= ?";
        $params[] = $date_to;
    }
    
    if (!empty($course_filter)) {
        $where_conditions[] = "u.course = ?";
        $params[] = $course_filter;
    }
    
    if (!empty($turma_filter)) {
        $where_conditions[] = "u.turma = ?";
        $params[] = $turma_filter;
    }
    
    if ($score_min > 0) {
        $where_conditions[] = "es.final_score >= ?";
        $params[] = $score_min;
    }
    
    if ($score_max < 100) {
        $where_conditions[] = "es.final_score <= ?";
        $params[] = $score_max;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $sql = "
        SELECT 
            es.id,
            COALESCE(es.name, CONCAT(u.first_name, ' ', u.last_name)) as nome_completo,
            COALESCE(es.registration, u.registration) as matricula,
            u.email,
            u.course as curso,
            u.turma,
            es.final_score as pontuacao_final,
            TIMESTAMPDIFF(SECOND, es.start_time, es.end_time) as duracao_segundos,
            TIMESTAMPDIFF(MINUTE, es.start_time, es.end_time) as duracao_minutos,
            es.start_time as inicio,
            es.end_time as fim,
            es.created_at as criado_em
        FROM ethics_lab_students es
        LEFT JOIN system_users u ON es.user_id = u.id
        WHERE {$where_clause}
        ORDER BY es.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    // Processar dados para exportação
    $export_data = [];
    foreach ($data as $row) {
        $export_data[] = [
            'ID' => $row['id'],
            'Nome Completo' => $row['nome_completo'],
            'Matrícula' => $row['matricula'],
            'E-mail' => $row['email'] ?? 'Não informado',
            'Curso' => formatCourseName($row['curso']),
            'Turma' => formatTurmaName($row['turma']),
            'Pontuação Final' => $row['pontuacao_final'],
            'Duração (segundos)' => $row['duracao_segundos'],
            'Duração (minutos)' => $row['duracao_minutos'],
            'Classificação' => getScoreClassification($row['pontuacao_final']),
            'Início' => $row['inicio'],
            'Fim' => $row['fim'],
            'Data de Realização' => date('d/m/Y H:i:s', strtotime($row['criado_em']))
        ];
    }
    
    outputData($export_data, "atividades_etica_" . date('Y-m-d_H-i-s'), $format);
}

/**
 * Exportar dados detalhados de ética com escolhas
 */
function exportEthicsDetailed($pdo, $format, $date_from = '', $date_to = '') {
    $where_conditions = ["1=1"];
    $params = [];
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(es.created_at) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(es.created_at) <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Buscar resultados do laboratório
    $sql_results = "
        SELECT 
            es.*,
            u.first_name,
            u.last_name,
            u.email,
            u.course,
            u.turma
        FROM ethics_lab_students es
        LEFT JOIN system_users u ON es.user_id = u.id
        WHERE {$where_clause}
        ORDER BY es.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql_results);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    $export_data = [];
    
    foreach ($results as $result) {
        // Buscar escolhas para este resultado
        $sql_choices = "
            SELECT * FROM ethics_lab_choices 
            WHERE student_id = ? 
            ORDER BY dilemma_id
        ";
        $stmt_choices = $pdo->prepare($sql_choices);
        $stmt_choices->execute([$result['id']]);
        $choices = $stmt_choices->fetchAll();
        
        // Dados básicos
        $row_data = [
            'ID' => $result['id'],
            'Nome' => ($result['first_name'] ?? '') . ' ' . ($result['last_name'] ?? ''),
            'Nome Registrado' => $result['name'] ?? '',
            'E-mail' => $result['email'] ?? '',
            'Matrícula' => $result['registration'],
            'Curso' => formatCourseName($result['course']),
            'Turma' => formatTurmaName($result['turma']),
            'Pontuação Final' => $result['final_score'],
            'Classificação' => getScoreClassification($result['final_score']),
            'Duração Total (min)' => round((strtotime($result['end_time']) - strtotime($result['start_time'])) / 60, 2),
            'Início' => $result['start_time'],
            'Fim' => $result['end_time'],
            'Data de Criação' => $result['created_at']
        ];
        
        // Adicionar dados das escolhas (até 10 dilemas)
        for ($i = 1; $i <= 10; $i++) {
            $choice = array_filter($choices, function($c) use ($i) {
                return $c['dilemma_id'] == $i;
            });
            $choice = reset($choice);
            
            if ($choice) {
                $row_data["Dilema {$i} - Título"] = $choice['dilemma_title'];
                $row_data["Dilema {$i} - Escolha"] = $choice['choice_text'];
                $row_data["Dilema {$i} - Índice"] = $choice['choice_index'];
                $row_data["Dilema {$i} - Impacto"] = $choice['impact'];
                $row_data["Dilema {$i} - Horário"] = $choice['choice_timestamp'];
            } else {
                $row_data["Dilema {$i} - Título"] = 'Não respondido';
                $row_data["Dilema {$i} - Escolha"] = 'Não respondido';
                $row_data["Dilema {$i} - Índice"] = '';
                $row_data["Dilema {$i} - Impacto"] = 0;
                $row_data["Dilema {$i} - Horário"] = '';
            }
        }
        
        $export_data[] = $row_data;
    }
    
    outputData($export_data, "etica_detalhado_" . date('Y-m-d_H-i-s'), $format);
}

/**
 * Exportar usuários
 */
function exportUsers($pdo, $format) {
    $sql = "
        SELECT 
            u.*,
            COUNT(es.id) as total_atividades_etica,
            AVG(es.final_score) as media_pontuacao,
            MAX(es.created_at) as ultima_atividade
        FROM system_users u
        LEFT JOIN ethics_lab_students es ON u.id = es.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ";
    
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll();
    
    $export_data = [];
    foreach ($data as $row) {
        $export_data[] = [
            'ID' => $row['id'],
            'Nome' => $row['first_name'],
            'Sobrenome' => $row['last_name'],
            'E-mail' => $row['email'],
            'Matrícula' => $row['registration'],
            'Curso' => formatCourseName($row['course']),
            'Turma' => formatTurmaName($row['turma']),
            'Status' => $row['status'],
            'Tentativas de Login' => $row['login_attempts'],
            'Último Login' => $row['last_login'],
            'IP do Último Login' => $row['last_login_ip'],
            'Total Atividades Ética' => $row['total_atividades_etica'],
            'Média Pontuação Ética' => $row['media_pontuacao'] ? number_format($row['media_pontuacao'], 2) : 'N/A',
            'Última Atividade' => $row['ultima_atividade'],
            'Criado em' => $row['created_at'],
            'Atualizado em' => $row['updated_at']
        ];
    }
    
    outputData($export_data, "usuarios_" . date('Y-m-d_H-i-s'), $format);
}

/**
 * Exportar logs do sistema
 */
function exportLogs($pdo, $format, $date_from = '', $date_to = '') {
    $where_conditions = ["1=1"];
    $params = [];
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(l.created_at) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(l.created_at) <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $sql = "
        SELECT 
            l.*,
            u.first_name,
            u.last_name,
            u.email
        FROM system_logs l
        LEFT JOIN system_users u ON l.user_id = u.id
        WHERE {$where_clause}
        ORDER BY l.created_at DESC
        LIMIT 10000
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    $export_data = [];
    foreach ($data as $row) {
        $export_data[] = [
            'ID' => $row['id'],
            'Usuário' => $row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'Sistema',
            'E-mail' => $row['email'],
            'Módulo' => $row['module'],
            'Ação' => $row['action'],
            'Descrição' => $row['description'],
            'IP' => $row['ip_address'],
            'User Agent' => $row['user_agent'],
            'Nível' => $row['level'],
            'Data/Hora' => $row['created_at']
        ];
    }
    
    outputData($export_data, "logs_" . date('Y-m-d_H-i-s'), $format);
}

/**
 * Exportar todos os dados do sistema
 */
function exportFullData($pdo, $format, $date_from = '', $date_to = '') {
    $full_data = [
        'export_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'exported_by' => 'Administrador',
            'date_range' => $date_from && $date_to ? "{$date_from} a {$date_to}" : 'Todos os dados',
            'system_version' => $GLOBALS['app_config']['system_version']
        ]
    ];
    
    // Usuários
    $stmt = $pdo->query("SELECT * FROM system_users ORDER BY created_at DESC");
    $full_data['users'] = $stmt->fetchAll();
    
    // Atividades de ética
    $where_clause = "1=1";
    $params = [];
    
    if (!empty($date_from)) {
        $where_clause .= " AND DATE(created_at) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_clause .= " AND DATE(created_at) <= ?";
        $params[] = $date_to;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM ethics_lab_students WHERE {$where_clause} ORDER BY created_at DESC");
    $stmt->execute($params);
    $full_data['ethics_activities'] = $stmt->fetchAll();
    
    // Escolhas de ética
    $stmt = $pdo->query("SELECT * FROM ethics_lab_choices ORDER BY student_id, dilemma_id");
    $full_data['ethics_choices'] = $stmt->fetchAll();
    
    // Logs (limitado aos últimos 1000)
    $stmt = $pdo->prepare("SELECT * FROM system_logs WHERE {$where_clause} ORDER BY created_at DESC LIMIT 1000");
    $stmt->execute($params);
    $full_data['logs'] = $stmt->fetchAll();
    
    // Estatísticas do sistema
    $full_data['statistics'] = getSystemStats();
    
    outputData($full_data, "backup_completo_" . date('Y-m-d_H-i-s'), $format);
}

/**
 * Formatar nome do curso
 */
function formatCourseName($course) {
    if (!$course) return 'Não informado';
    return $course === 'informatica' ? 'Informática' : 'Recursos Humanos';
}

/**
 * Formatar nome da turma
 */
function formatTurmaName($turma) {
    if (!$turma) return 'Não informado';
    
    $turma_names = [
        '1_info' => '1º Info',
        '2_info_1' => '2º Info 1',
        '2_info_2' => '2º Info 2', 
        '3_info_7' => '3º Info 7',
        '3_info_8' => '3º Info 8',
        '2_rh' => '2º RH'
    ];
    
    return $turma_names[$turma] ?? $turma;
}

/**
 * Obter classificação da pontuação
 */
function getScoreClassification($score) {
    if ($score >= 90) return 'Excelente';
    if ($score >= 80) return 'Muito Bom';
    if ($score >= 70) return 'Bom';
    if ($score >= 60) return 'Satisfatório';
    if ($score >= 50) return 'Regular';
    if ($score >= 40) return 'Insuficiente';
    return 'Inadequado';
}

/**
 * Saída dos dados no formato especificado
 */
function outputData($data, $filename, $format) {
    switch ($format) {
        case 'csv':
            outputCSV($data, $filename);
            break;
        case 'json':
            outputJSON($data, $filename);
            break;
        default:
            die('Formato não suportado');
    }
}

/**
 * Saída em formato CSV
 */
function outputCSV($data, $filename) {
    if (empty($data)) {
        die('Nenhum dado para exportar');
    }
    
    // Headers para download CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Criar output stream
    $output = fopen('php://output', 'w');
    
    // Adicionar BOM para UTF-8 (para Excel reconhecer acentos)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Se é array associativo, usar as chaves como cabeçalhos
    if (is_array($data) && !empty($data) && is_array($data[0])) {
        $headers = array_keys($data[0]);
        fputcsv($output, $headers, ';');
        
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
    } else {
        // Para dados mais complexos, fazer uma representação simplificada
        fputcsv($output, ['Tipo', 'Dados'], ';');
        foreach ($data as $key => $value) {
            fputcsv($output, [$key, json_encode($value)], ';');
        }
    }
    
    fclose($output);
    exit;
}

/**
 * Saída em formato JSON
 */
function outputJSON($data, $filename) {
    // Headers para download JSON
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.json"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Obter estatísticas do sistema
function getSystemStats() {
    try {
        $pdo = getConnection();
        
        // Usuários
        $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'active' THEN 1 END) as active FROM system_users");
        $users = $stmt->fetch();
        
        // Atividades de ética
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ethics_lab_students");
        $ethics_total = $stmt->fetch()['total'];
        
        return [
            'users' => $users,
            'ethics_activities' => $ethics_total,
            'system_version' => $GLOBALS['app_config']['system_version'] ?? '2.0.0'
        ];
    } catch (Exception $e) {
        return [
            'users' => ['total' => 0, 'active' => 0],
            'ethics_activities' => 0,
            'system_version' => '2.0.0'
        ];
    }
}

// Se chegou até aqui sem processar exportação, mostrar formulário
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportação de Dados - Sistema de Atividades</title>
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
            font-weight: 300;
        }
        
        .content {
            padding: 40px;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        
        .form-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.3em;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group select, .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .export-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .export-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .export-card:hover {
            border-color: #3498db;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.2);
        }
        
        .export-card.selected {
            border-color: #3498db;
            background: #f0f8ff;
        }
        
        .export-icon {
            font-size: 3em;
            margin-bottom: 15px;
            display: block;
        }
        
        .export-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .export-description {
            color: #6c757d;
            font-size: 0.9em;
            line-height: 1.4;
        }
        
        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin: 10px 5px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }
        
        .btn-large {
            padding: 20px 40px;
            font-size: 18px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .export-options {
                grid-template-columns: 1fr;
            }
            
            .content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📥 Exportação de Dados</h1>
            <p>Sistema de Atividades - Laboratório de Ética</p>
        </div>
        
        <div class="content">
            <form method="GET" id="exportForm">
                <!-- Tipo de Exportação -->
                <div class="form-section">
                    <h3>Tipo de Dados para Exportar</h3>
                    <div class="export-options">
                        <div class="export-card" data-type="ethics_activities">
                            <span class="export-icon">⚖️</span>
                            <div class="export-title">Atividades de Ética</div>
                            <div class="export-description">Resultados do laboratório de decisões éticas</div>
                        </div>
                        
                        <div class="export-card" data-type="ethics_detailed">
                            <span class="export-icon">🔍</span>
                            <div class="export-title">Ética Detalhada</div>
                            <div class="export-description">Incluindo todas as escolhas por dilema</div>
                        </div>
                        
                        <div class="export-card" data-type="users">
                            <span class="export-icon">👥</span>
                            <div class="export-title">Usuários</div>
                            <div class="export-description">Informações dos usuários cadastrados</div>
                        </div>
                        
                        <div class="export-card" data-type="logs">
                            <span class="export-icon">📝</span>
                            <div class="export-title">Logs do Sistema</div>
                            <div class="export-description">Registros de atividades do sistema</div>
                        </div>
                        
                        <div class="export-card" data-type="full">
                            <span class="export-icon">💾</span>
                            <div class="export-title">Backup Completo</div>
                            <div class="export-description">Todos os dados para backup</div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="type" id="selectedType" value="ethics_activities">
                </div>
                
                <!-- Filtros -->
                <div class="form-section">
                    <h3>Filtros (Opcional)</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Curso:</label>
                            <select name="course_filter">
                                <option value="">Todos os cursos</option>
                                <option value="informatica">Informática</option>
                                <option value="recursos_humanos">Recursos Humanos</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Turma:</label>
                            <select name="turma_filter">
                                <option value="">Todas as turmas</option>
                                <option value="1_info">1º Info</option>
                                <option value="2_info_1">2º Info 1</option>
                                <option value="2_info_2">2º Info 2</option>
                                <option value="3_info_7">3º Info 7</option>
                                <option value="3_info_8">3º Info 8</option>
                                <option value="2_rh">2º RH</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Data Inicial:</label>
                            <input type="date" name="date_from">
                        </div>
                        
                        <div class="form-group">
                            <label>Data Final:</label>
                            <input type="date" name="date_to">
                        </div>
                    </div>
                </div>
                
                <!-- Formato -->
                <div class="form-section">
                    <h3>Formato de Exportação</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Formato:</label>
                            <select name="format">
                                <option value="csv">CSV (Excel)</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <button type="submit" class="btn btn-large">📥 Iniciar Exportação</button>
                    <a href="index.php" class="btn btn-secondary">Voltar ao Painel</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Seleção de tipo de exportação
        document.querySelectorAll('.export-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remover seleção anterior
                document.querySelectorAll('.export-card').forEach(c => c.classList.remove('selected'));
                
                // Selecionar atual
                this.classList.add('selected');
                
                // Atualizar input hidden
                document.getElementById('selectedType').value = this.dataset.type;
            });
        });
        
        // Selecionar primeiro tipo por padrão
        document.querySelector('.export-card').click();
        
        // Validação antes do envio
        document.getElementById('exportForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.textContent = '⏳ Processando...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>