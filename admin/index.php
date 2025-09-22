<?php
/**
 * Painel Administrativo COMPLETO - CORRIGIDO
 * Professor Leandro Rodrigues
 * 
 * Dashboard centralizado para TODOS os módulos: Ética + DER Quiz + R&S Lab
 * Versão corrigida com melhor tratamento de erros e links funcionais
 */

// Ativar relatório de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configurações e autenticação
require_once '../config/config.php';
require_once '../auth/auth.php';

// Verificar se é administrador
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
    
    $admin = $user; // Para compatibilidade com o código existente
    
} catch (Exception $e) {
    die("Erro de autenticação: " . $e->getMessage());
}

// Configuração de paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$score_min = isset($_GET['score_min']) ? (int)$_GET['score_min'] : 0;
$score_max = isset($_GET['score_max']) ? (int)$_GET['score_max'] : 100;
$course_filter = isset($_GET['course_filter']) ? $_GET['course_filter'] : '';
$turma_filter = isset($_GET['turma_filter']) ? $_GET['turma_filter'] : '';
$module_filter = isset($_GET['module_filter']) ? $_GET['module_filter'] : '';

// Inicializar variáveis
$available_structures = [];
$system_stats = [];
$module_stats = [];
$activities = [];
$total_records = 0;
$total_pages = 1;
$top_users = [];
$course_turma_stats = [];
$error_message = '';

try {
    $pdo = getConnection();
    
    // DETECTAR TODAS AS ESTRUTURAS DISPONÍVEIS
    $available_structures = detectAllStructures($pdo);
    
    // Obter estatísticas gerais do sistema
    $system_stats = getAdminSystemStats($pdo);
    
    // Obter estatísticas de TODOS os 3 módulos
    $module_stats = getAllModuleStats($pdo, $available_structures);
    
    // Obter atividades de TODOS os 3 módulos
    $activities_data = getAllActivities($pdo, $available_structures, $search, $date_from, $date_to, $score_min, $score_max, $course_filter, $turma_filter, $module_filter, $limit, $offset);
    $activities = $activities_data['activities'];
    $total_records = $activities_data['total'];
    $total_pages = ceil($total_records / $limit);
    
    // Obter usuários mais ativos (todos os módulos)
    $top_users = getTopUsersAllModules($pdo, $available_structures);
    
    // Obter estatísticas por curso e turma (todos os módulos)
    $course_turma_stats = getCourseStatsAllModules($pdo, $available_structures);
    
} catch (Exception $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
    error_log("Erro no painel admin: " . $e->getMessage());
}

/**
 * DETECTAR TODAS AS 3 ESTRUTURAS DISPONÍVEIS
 */
function detectAllStructures($pdo) {
    $structures = [
        'ethics' => ['old' => false, 'new' => false],
        'der_quiz' => ['old' => false, 'new' => false],
        'rs_lab' => ['old' => false, 'new' => false]
    ];
    
    $tables_to_check = [
        'ethics_lab_students' => ['ethics', 'old'],
        'ethics_lab_results' => ['ethics', 'new'],
        'der_quiz_students' => ['der_quiz', 'old'],
        'der_quiz_results' => ['der_quiz', 'new'],
        'rs_lab_students' => ['rs_lab', 'old'],
        'rs_lab_results' => ['rs_lab', 'new']
    ];
    
    foreach ($tables_to_check as $table => $config) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $structures[$config[0]][$config[1]] = true;
            }
        } catch (Exception $e) {
            // Ignorar erro de tabela não encontrada
        }
    }
    
    return $structures;
}

/**
 * OBTER ESTATÍSTICAS GERAIS DO SISTEMA - ADMIN
 */
function getAdminSystemStats($pdo) {
    $stats = [
        'users' => ['total' => 0, 'active' => 0]
    ];
    
    try {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active
            FROM system_users
        ");
        $result = $stmt->fetch();
        if ($result) {
            $stats['users'] = $result;
        }
    } catch (Exception $e) {
        // Tabela system_users pode não existir
    }
    
    return $stats;
}

/**
 * OBTER ESTATÍSTICAS DE TODOS OS 3 MÓDULOS
 */
function getAllModuleStats($pdo, $structures) {
    $stats = [
        'ethics' => getModuleStats($pdo, 'ethics', $structures['ethics']),
        'der_quiz' => getModuleStats($pdo, 'der_quiz', $structures['der_quiz']),
        'rs_lab' => getModuleStats($pdo, 'rs_lab', $structures['rs_lab'])
    ];
    
    return $stats;
}

/**
 * OBTER ESTATÍSTICAS DE UM MÓDULO ESPECÍFICO
 */
function getModuleStats($pdo, $module, $module_structures) {
    $stats = [
        'total_activities' => 0,
        'unique_users' => 0,
        'avg_score' => 0,
        'today_count' => 0,
        'week_count' => 0,
        'excellent_count' => 0,
        'good_count' => 0,
        'average_count' => 0,
        'poor_count' => 0
    ];
    
    $tables = [
        'ethics' => ['old' => 'ethics_lab_students', 'new' => 'ethics_lab_results'],
        'der_quiz' => ['old' => 'der_quiz_students', 'new' => 'der_quiz_results'],
        'rs_lab' => ['old' => 'rs_lab_students', 'new' => 'rs_lab_results']
    ];
    
    $total_score = 0;
    $score_count = 0;
    $all_user_ids = [];
    
    // Processar estrutura antiga
    if ($module_structures['old'] && isset($tables[$module]['old'])) {
        $table = $tables[$module]['old'];
        try {
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    AVG(final_score) as avg_score,
                    SUM(final_score) as sum_score,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today,
                    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week,
                    COUNT(CASE WHEN final_score >= 80 THEN 1 END) as excellent,
                    COUNT(CASE WHEN final_score >= 60 AND final_score < 80 THEN 1 END) as good,
                    COUNT(CASE WHEN final_score >= 40 AND final_score < 60 THEN 1 END) as average,
                    COUNT(CASE WHEN final_score < 40 THEN 1 END) as poor
                FROM $table
            ");
            $old_stats = $stmt->fetch();
            
            if ($old_stats && $old_stats['total'] > 0) {
                $stats['total_activities'] += $old_stats['total'];
                $stats['today_count'] += $old_stats['today'];
                $stats['week_count'] += $old_stats['week'];
                $stats['excellent_count'] += $old_stats['excellent'];
                $stats['good_count'] += $old_stats['good'];
                $stats['average_count'] += $old_stats['average'];
                $stats['poor_count'] += $old_stats['poor'];
                
                if ($old_stats['sum_score'] > 0) {
                    $total_score += $old_stats['sum_score'];
                    $score_count += $old_stats['total'];
                }
            }
            
            // Usuários únicos da estrutura antiga
            $stmt = $pdo->query("SELECT DISTINCT user_id FROM $table WHERE user_id IS NOT NULL");
            while ($row = $stmt->fetch()) {
                $all_user_ids[] = $row['user_id'];
            }
        } catch (Exception $e) {
            // Ignorar erro
        }
    }
    
    // Processar estrutura nova
    if ($module_structures['new'] && isset($tables[$module]['new'])) {
        $table = $tables[$module]['new'];
        try {
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    AVG(final_score) as avg_score,
                    SUM(final_score) as sum_score,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today,
                    COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week,
                    COUNT(CASE WHEN final_score >= 80 THEN 1 END) as excellent,
                    COUNT(CASE WHEN final_score >= 60 AND final_score < 80 THEN 1 END) as good,
                    COUNT(CASE WHEN final_score >= 40 AND final_score < 60 THEN 1 END) as average,
                    COUNT(CASE WHEN final_score < 40 THEN 1 END) as poor
                FROM $table
            ");
            $new_stats = $stmt->fetch();
            
            if ($new_stats && $new_stats['total'] > 0) {
                $stats['total_activities'] += $new_stats['total'];
                $stats['today_count'] += $new_stats['today'];
                $stats['week_count'] += $new_stats['week'];
                $stats['excellent_count'] += $new_stats['excellent'];
                $stats['good_count'] += $new_stats['good'];
                $stats['average_count'] += $new_stats['average'];
                $stats['poor_count'] += $new_stats['poor'];
                
                if ($new_stats['sum_score'] > 0) {
                    $total_score += $new_stats['sum_score'];
                    $score_count += $new_stats['total'];
                }
            }
            
            // Usuários únicos da estrutura nova
            $stmt = $pdo->query("SELECT DISTINCT user_id FROM $table WHERE user_id IS NOT NULL");
            while ($row = $stmt->fetch()) {
                $all_user_ids[] = $row['user_id'];
            }
        } catch (Exception $e) {
            // Ignorar erro
        }
    }
    
    // Calcular valores finais
    $stats['unique_users'] = count(array_unique($all_user_ids));
    $stats['avg_score'] = $score_count > 0 ? ($total_score / $score_count) : 0;
    
    return $stats;
}

/**
 * OBTER ATIVIDADES DE TODOS OS 3 MÓDULOS
 */
function getAllActivities($pdo, $structures, $search, $date_from, $date_to, $score_min, $score_max, $course_filter, $turma_filter, $module_filter, $limit, $offset) {
    $all_activities = [];
    
    // Módulos a processar
    $modules_to_process = [];
    if (empty($module_filter)) {
        $modules_to_process = ['ethics', 'der_quiz', 'rs_lab'];
    } else {
        $modules_to_process = [$module_filter];
    }
    
    foreach ($modules_to_process as $module) {
        if (isset($structures[$module])) {
            $module_activities = getModuleActivities($pdo, $module, $structures[$module], $search, $date_from, $date_to, $score_min, $score_max, $course_filter, $turma_filter);
            $all_activities = array_merge($all_activities, $module_activities);
        }
    }
    
    // Ordenar por data (mais recente primeiro)
    usort($all_activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Aplicar paginação
    $total_records = count($all_activities);
    $activities = array_slice($all_activities, $offset, $limit);
    
    return [
        'activities' => $activities,
        'total' => $total_records
    ];
}

/**
 * OBTER ATIVIDADES DE UM MÓDULO ESPECÍFICO
 */
function getModuleActivities($pdo, $module, $module_structures, $search, $date_from, $date_to, $score_min, $score_max, $course_filter, $turma_filter) {
    $activities = [];
    
    $tables = [
        'ethics' => ['old' => 'ethics_lab_students', 'new' => 'ethics_lab_results'],
        'der_quiz' => ['old' => 'der_quiz_students', 'new' => 'der_quiz_results'],
        'rs_lab' => ['old' => 'rs_lab_students', 'new' => 'rs_lab_results']
    ];
    
    $module_configs = [
        'ethics' => ['icon' => '⚖️', 'name' => 'Laboratório de Ética'],
        'der_quiz' => ['icon' => '📊', 'name' => 'DER Quiz'],
        'rs_lab' => ['icon' => '📈', 'name' => 'Laboratório de R&S']
    ];
    
    $module_icon = $module_configs[$module]['icon'];
    $module_name = $module_configs[$module]['name'];
    
    // Buscar na estrutura antiga
    if ($module_structures['old'] && isset($tables[$module]['old'])) {
        $table = $tables[$module]['old'];
        try {
            $where_conditions = ["1=1"];
            $params = [];
            
            if (!empty($search)) {
                $where_conditions[] = "(s.name LIKE ? OR s.registration LIKE ? OR s.email LIKE ?)";
                $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
            }
            
            if (!empty($date_from)) {
                $where_conditions[] = "DATE(s.created_at) >= ?";
                $params[] = $date_from;
            }
            
            if (!empty($date_to)) {
                $where_conditions[] = "DATE(s.created_at) <= ?";
                $params[] = $date_to;
            }
            
            if ($score_min > 0) {
                $where_conditions[] = "s.final_score >= ?";
                $params[] = $score_min;
            }
            
            if ($score_max < 100) {
                $where_conditions[] = "s.final_score <= ?";
                $params[] = $score_max;
            }
            
            $where_clause = implode(" AND ", $where_conditions);
            
            $sql = "
                SELECT 
                    s.*,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.course,
                    u.turma,
                    u.registration,
                    CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as full_name,
                    TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) as duration_minutes,
                    'old' as source_structure,
                    '{$module}' as module_type,
                    '{$module_icon}' as module_icon,
                    '{$module_name}' as module_name
                FROM {$table} s
                LEFT JOIN system_users u ON s.user_id = u.id
                WHERE {$where_clause}
                ORDER BY s.created_at DESC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $old_activities = $stmt->fetchAll();
            
            // Processar resultados para padronizar nomes
            foreach ($old_activities as &$activity) {
                if (empty($activity['full_name']) || trim($activity['full_name']) == '') {
                    $activity['display_name'] = $activity['name'] ?? 'Nome não informado';
                } else {
                    $activity['display_name'] = trim($activity['full_name']);
                }
            }
            
            $activities = array_merge($activities, $old_activities);
        } catch (Exception $e) {
            error_log("Erro ao buscar atividades antigas do {$module}: " . $e->getMessage());
        }
    }
    
    // Buscar na estrutura nova
    if ($module_structures['new'] && isset($tables[$module]['new'])) {
        $table = $tables[$module]['new'];
        try {
            $where_conditions = ["1=1"];
            $params = [];
            
            if (!empty($search)) {
                $where_conditions[] = "(r.user_name LIKE ? OR r.user_registration LIKE ? OR r.user_email LIKE ?)";
                $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
            }
            
            if (!empty($date_from)) {
                $where_conditions[] = "DATE(r.created_at) >= ?";
                $params[] = $date_from;
            }
            
            if (!empty($date_to)) {
                $where_conditions[] = "DATE(r.created_at) <= ?";
                $params[] = $date_to;
            }
            
            if ($score_min > 0) {
                $where_conditions[] = "r.final_score >= ?";
                $params[] = $score_min;
            }
            
            if ($score_max < 100) {
                $where_conditions[] = "r.final_score <= ?";
                $params[] = $score_max;
            }
            
            $where_clause = implode(" AND ", $where_conditions);
            
            $sql = "
                SELECT 
                    r.*,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.course,
                    u.turma,
                    u.registration,
                    r.user_name as display_name,
                    TIMESTAMPDIFF(MINUTE, r.start_time, r.end_time) as duration_minutes,
                    'new' as source_structure,
                    '{$module}' as module_type,
                    '{$module_icon}' as module_icon,
                    '{$module_name}' as module_name
                FROM {$table} r
                LEFT JOIN system_users u ON r.user_id = u.id
                WHERE {$where_clause}
                ORDER BY r.created_at DESC
            ";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $new_activities = $stmt->fetchAll();
            
            $activities = array_merge($activities, $new_activities);
        } catch (Exception $e) {
            error_log("Erro ao buscar atividades novas do {$module}: " . $e->getMessage());
        }
    }
    
    return $activities;
}

/**
 * OBTER TOP USUÁRIOS DE TODOS OS 3 MÓDULOS
 */
function getTopUsersAllModules($pdo, $structures, $limit = 10) {
    $all_users = [];
    
    foreach (['ethics', 'der_quiz', 'rs_lab'] as $module) {
        if (isset($structures[$module])) {
            $module_users = getTopModuleUsers($pdo, $module, $structures[$module], $limit);
            $all_users = array_merge($all_users, $module_users);
        }
    }
    
    // Ordenar e limitar
    usort($all_users, function($a, $b) {
        if ($a['final_score'] == $b['final_score']) {
            return strtotime($b['activity_date']) - strtotime($a['activity_date']);
        }
        return $b['final_score'] - $a['final_score'];
    });
    
    return array_slice($all_users, 0, $limit);
}

/**
 * OBTER TOP USUÁRIOS DE UM MÓDULO ESPECÍFICO
 */
function getTopModuleUsers($pdo, $module, $module_structures, $limit = 10) {
    $users = [];
    
    $tables = [
        'ethics' => ['old' => 'ethics_lab_students', 'new' => 'ethics_lab_results'],
        'der_quiz' => ['old' => 'der_quiz_students', 'new' => 'der_quiz_results'],
        'rs_lab' => ['old' => 'rs_lab_students', 'new' => 'rs_lab_results']
    ];
    
    $module_configs = [
        'ethics' => ['icon' => '⚖️', 'name' => 'Laboratório de Ética'],
        'der_quiz' => ['icon' => '📊', 'name' => 'DER Quiz'],
        'rs_lab' => ['icon' => '📈', 'name' => 'Laboratório de R&S']
    ];
    
    // Processar ambas as estruturas se disponíveis
    foreach (['old', 'new'] as $structure_type) {
        if ($module_structures[$structure_type] && isset($tables[$module][$structure_type])) {
            $table = $tables[$module][$structure_type];
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        COALESCE(u.first_name, s.name, s.user_name, 'Anônimo') as first_name,
                        COALESCE(u.last_name, '', '') as last_name,
                        COALESCE(u.email, s.email, s.user_email, '') as email,
                        COALESCE(u.course, s.user_course, '') as course,
                        COALESCE(u.turma, s.user_turma, '') as turma,
                        s.final_score,
                        s.created_at as activity_date,
                        TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) as duration_minutes,
                        '{$structure_type}' as source_structure,
                        '{$module_configs[$module]['icon']}' as module_icon,
                        '{$module_configs[$module]['name']}' as module_name
                    FROM {$table} s
                    LEFT JOIN system_users u ON s.user_id = u.id
                    ORDER BY s.final_score DESC, s.created_at DESC
                    LIMIT ?
                ");
                $stmt->execute([$limit]);
                $structure_users = $stmt->fetchAll();
                $users = array_merge($users, $structure_users);
            } catch (Exception $e) {
                // Ignorar erro
            }
        }
    }
    
    return $users;
}

/**
 * OBTER ESTATÍSTICAS POR CURSO DE TODOS OS 3 MÓDULOS
 */
function getCourseStatsAllModules($pdo, $structures) {
    $stats = [];
    
    try {
        // Obter base de cursos/turmas
        $stmt = $pdo->query("
            SELECT 
                u.course,
                u.turma,
                COUNT(DISTINCT u.id) as total_users,
                COUNT(DISTINCT CASE WHEN u.status = 'active' THEN u.id END) as active_users
            FROM system_users u
            WHERE u.course IS NOT NULL AND u.turma IS NOT NULL
            GROUP BY u.course, u.turma
            ORDER BY u.course, u.turma
        ");
        $base_stats = $stmt->fetchAll();
        
        // Para cada curso/turma, contar atividades
        foreach ($base_stats as $base_stat) {
            $base_stat['completed_ethics'] = countModuleActivities($pdo, 'ethics', $structures['ethics'], $base_stat['course'], $base_stat['turma']);
            $base_stat['completed_der_quiz'] = countModuleActivities($pdo, 'der_quiz', $structures['der_quiz'], $base_stat['course'], $base_stat['turma']);
            $base_stat['completed_rs_lab'] = countModuleActivities($pdo, 'rs_lab', $structures['rs_lab'], $base_stat['course'], $base_stat['turma']);
            $base_stat['total_activities'] = $base_stat['completed_ethics'] + $base_stat['completed_der_quiz'] + $base_stat['completed_rs_lab'];
            $stats[] = $base_stat;
        }
    } catch (Exception $e) {
        // Retornar array vazio se houver erro
    }
    
    return $stats;
}

/**
 * CONTAR ATIVIDADES DE UM MÓDULO POR CURSO/TURMA
 */
function countModuleActivities($pdo, $module, $module_structures, $course, $turma) {
    $count = 0;
    
    $tables = [
        'ethics' => ['old' => 'ethics_lab_students', 'new' => 'ethics_lab_results'],
        'der_quiz' => ['old' => 'der_quiz_students', 'new' => 'der_quiz_results'],
        'rs_lab' => ['old' => 'rs_lab_students', 'new' => 'rs_lab_results']
    ];
    
    foreach (['old', 'new'] as $structure_type) {
        if ($module_structures[$structure_type] && isset($tables[$module][$structure_type])) {
            $table = $tables[$module][$structure_type];
            try {
                $stmt = $pdo->prepare("
                    SELECT COUNT(s.id) as count
                    FROM {$table} s
                    LEFT JOIN system_users u ON s.user_id = u.id
                    WHERE (u.course = ? AND u.turma = ?) 
                       OR (s.user_course = ? AND s.user_turma = ?)
                ");
                $stmt->execute([$course, $turma, $course, $turma]);
                $count += $stmt->fetchColumn();
            } catch (Exception $e) {
                // Ignorar erro
            }
        }
    }
    
    return $count;
}

// Funções auxiliares - com verificação de existência
if (!function_exists('getScoreClass')) {
    function getScoreClass($score) {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'average';
        return 'poor';
    }
}

if (!function_exists('formatDuration')) {
    function formatDuration($minutes) {
        if ($minutes < 60) {
            return $minutes . 'min';
        } else {
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $hours . 'h ' . $mins . 'min';
        }
    }
}

if (!function_exists('formatTurmaName')) {
    function formatTurmaName($turma) {
        $turma_names = [
            '1_info' => '1º Info',
            '2_info_1' => '2º Info 1',
            '2_info_2' => '2º Info 2', 
            '3_info_7' => '3º Info 7',
            '3_info_8' => '3º Info 8',
            '2_rh' => '2º RH',
            '2_eh' => '2º RH'
        ];
        
        return $turma_names[$turma] ?? $turma;
    }
}

if (!function_exists('formatCourseName')) {
    function formatCourseName($course) {
        return $course === 'informatica' ? 'Informática' : 'Recursos Humanos';
    }
}

if (!function_exists('getUniqueTurmas')) {
    function getUniqueTurmas() {
        try {
            $pdo = getConnection();
            $stmt = $pdo->query("SELECT DISTINCT turma FROM system_users WHERE turma IS NOT NULL ORDER BY turma");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }
}

// Definir algumas configurações padrão se não existirem
if (!isset($app_config)) {
    $app_config = [
        'professor_name' => 'Professor Leandro Rodrigues'
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Sistema Completo de Atividades</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 20px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 2.2em;
            margin-bottom: 5px;
            font-weight: 300;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .admin-info {
            background: rgba(255,255,255,0.1);
            padding: 15px 25px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(10px);
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }
        
        .main-content {
            padding: 30px 0;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #e74c3c, #c0392b);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.95em;
            margin-bottom: 10px;
        }
        
        .stat-change {
            font-size: 0.85em;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 600;
        }
        
        .stat-change.positive {
            background: #d4edda;
            color: #155724;
        }
        
        .stat-change.neutral {
            background: #f8f9fa;
            color: #6c757d;
        }
        
        .content-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .module-card {
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .module-card.ethics {
            background: linear-gradient(135deg, #fff5f5, #ffe6e6);
            border: 2px solid #e74c3c;
        }
        
        .module-card.der_quiz {
            background: linear-gradient(135deg, #f0f8ff, #e6f3ff);
            border: 2px solid #3498db;
        }
        
        .module-card.rs_lab {
            background: linear-gradient(135deg, #f0fff4, #e6ffed);
            border: 2px solid #27ae60;
        }
        
        .module-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .module-icon {
            font-size: 2.5em;
            margin-right: 15px;
        }
        
        .module-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .module-status {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 600;
            background: #d4edda;
            color: #155724;
        }
        
        .module-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .module-stat {
            text-align: center;
            padding: 15px;
            background: rgba(255,255,255,0.7);
            border-radius: 10px;
        }
        
        .module-stat-number {
            font-size: 1.8em;
            font-weight: bold;
        }
        
        .module-stat-number.ethics {
            color: #e74c3c;
        }
        
        .module-stat-number.der_quiz {
            color: #3498db;
        }
        
        .module-stat-number.rs_lab {
            color: #27ae60;
        }
        
        .module-stat-label {
            font-size: 0.85em;
            color: #6c757d;
        }
        
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 0.9em;
        }
        
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: end;
            grid-column: 1 / -1;
            justify-content: center;
        }
        
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #95a5a6;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9em;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .score {
            font-weight: bold;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
        }
        
        .score.excellent { background: #d4edda; color: #155724; }
        .score.good { background: #d1ecf1; color: #0c5460; }
        .score.average { background: #fff3cd; color: #856404; }
        .score.poor { background: #f8d7da; color: #721c24; }
        
        .turma-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .turma-info { background: #cce5ff; color: #0056b3; }
        .turma-rh { background: #ffebcc; color: #cc6600; }
        
        .module-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .module-badge.ethics {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .module-badge.der_quiz {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .module-badge.rs_lab {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .structure-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7em;
            font-weight: 600;
            margin-left: 5px;
        }
        
        .structure-badge.old {
            background: #fff3cd;
            color: #856404;
        }
        
        .structure-badge.new {
            background: #d4edda;
            color: #155724;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 25px 0;
        }
        
        .pagination a, .pagination span {
            padding: 10px 15px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .pagination .current {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 1.5em;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .empty-state p {
            font-size: 1.1em;
            line-height: 1.6;
        }
        
        .multi-module-alert {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
            }
            
            .module-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filter-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div>
                    <h1>Painel Administrativo Completo</h1>
                    <p>Sistema com 3 Módulos - <?php echo htmlspecialchars($app_config['professor_name']); ?></p>
                </div>
                <div class="admin-info">
                    <span><?php echo htmlspecialchars($admin['username'] ?? $admin['name'] ?? 'Admin'); ?></span>
                    <a href="../index.html" class="logout-btn">Hub</a>
                    <a href="#" onclick="logout()" class="logout-btn">Sair</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="main-content">
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php else: ?>
                
                <!-- Alert indicando os 3 módulos -->
                <div class="multi-module-alert">
                    <strong>Sistema com 3 Módulos Ativos:</strong> 
                    Ética: <?php echo ($available_structures['ethics']['old'] || $available_structures['ethics']['new']) ? 'Disponível' : 'Não disponível'; ?> | 
                    DER Quiz: <?php echo ($available_structures['der_quiz']['old'] || $available_structures['der_quiz']['new']) ? 'Disponível' : 'Não disponível'; ?> | 
                    R&S Lab: <?php echo ($available_structures['rs_lab']['old'] || $available_structures['rs_lab']['new']) ? 'Disponível' : 'Não disponível'; ?>
                </div>
                
                <!-- Estatísticas Gerais -->
                <div class="stats-overview">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $system_stats['users']['total']; ?></div>
                        <div class="stat-label">Usuários Cadastrados</div>
                        <div class="stat-change positive">
                            <?php echo $system_stats['users']['active']; ?> ativos
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $module_stats['ethics']['total_activities'] + $module_stats['der_quiz']['total_activities'] + $module_stats['rs_lab']['total_activities']; ?></div>
                        <div class="stat-label">Total de Atividades</div>
                        <div class="stat-change neutral">
                            <?php echo $module_stats['ethics']['today_count'] + $module_stats['der_quiz']['today_count'] + $module_stats['rs_lab']['today_count']; ?> hoje
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $module_stats['ethics']['unique_users'] + $module_stats['der_quiz']['unique_users'] + $module_stats['rs_lab']['unique_users']; ?></div>
                        <div class="stat-label">Usuários Participantes</div>
                        <div class="stat-change positive">
                            <?php echo $module_stats['ethics']['week_count'] + $module_stats['der_quiz']['week_count'] + $module_stats['rs_lab']['week_count']; ?> esta semana
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php 
                            $total_avg = 0;
                            $count = 0;
                            if ($module_stats['ethics']['avg_score'] > 0) { $total_avg += $module_stats['ethics']['avg_score']; $count++; }
                            if ($module_stats['der_quiz']['avg_score'] > 0) { $total_avg += $module_stats['der_quiz']['avg_score']; $count++; }
                            if ($module_stats['rs_lab']['avg_score'] > 0) { $total_avg += $module_stats['rs_lab']['avg_score']; $count++; }
                            echo $count > 0 ? number_format($total_avg / $count, 1) : '0'; 
                        ?></div>
                        <div class="stat-label">Pontuação Média Geral</div>
                        <div class="stat-change neutral">
                            Todos os 3 módulos
                        </div>
                    </div>
                </div>

                <!-- Módulos Ativos -->
                <div class="modules-grid">
                    <!-- Módulo de Ética -->
                    <div class="module-card ethics">
                        <div class="module-header">
                            <div class="module-icon">⚖️</div>
                            <div>
                                <div class="module-title">Laboratório de Decisões Éticas</div>
                                <div class="module-status">
                                    <?php echo ($available_structures['ethics']['old'] || $available_structures['ethics']['new']) ? 'Ativo' : 'Inativo'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="module-stats">
                            <div class="module-stat">
                                <div class="module-stat-number ethics"><?php echo $module_stats['ethics']['total_activities']; ?></div>
                                <div class="module-stat-label">Atividades</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number ethics"><?php echo $module_stats['ethics']['unique_users']; ?></div>
                                <div class="module-stat-label">Usuários</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number ethics"><?php echo number_format($module_stats['ethics']['avg_score'], 1); ?></div>
                                <div class="module-stat-label">Média</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number ethics"><?php echo $module_stats['ethics']['excellent_count']; ?></div>
                                <div class="module-stat-label">Excelentes</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number ethics"><?php echo $module_stats['ethics']['today_count']; ?></div>
                                <div class="module-stat-label">Hoje</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Módulo DER Quiz -->
                    <div class="module-card der_quiz">
                        <div class="module-header">
                            <div class="module-icon">📊</div>
                            <div>
                                <div class="module-title">DER Quiz Interativo</div>
                                <div class="module-status">
                                    <?php echo ($available_structures['der_quiz']['old'] || $available_structures['der_quiz']['new']) ? 'Ativo' : 'Inativo'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="module-stats">
                            <div class="module-stat">
                                <div class="module-stat-number der_quiz"><?php echo $module_stats['der_quiz']['total_activities']; ?></div>
                                <div class="module-stat-label">Atividades</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number der_quiz"><?php echo $module_stats['der_quiz']['unique_users']; ?></div>
                                <div class="module-stat-label">Usuários</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number der_quiz"><?php echo number_format($module_stats['der_quiz']['avg_score'], 1); ?></div>
                                <div class="module-stat-label">Média</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number der_quiz"><?php echo $module_stats['der_quiz']['excellent_count']; ?></div>
                                <div class="module-stat-label">Excelentes</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number der_quiz"><?php echo $module_stats['der_quiz']['today_count']; ?></div>
                                <div class="module-stat-label">Hoje</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Módulo R&S -->
                    <div class="module-card rs_lab">
                        <div class="module-header">
                            <div class="module-icon">📈</div>
                            <div>
                                <div class="module-title">Laboratório de R&S</div>
                                <div class="module-status">
                                    <?php echo ($available_structures['rs_lab']['old'] || $available_structures['rs_lab']['new']) ? 'Ativo' : 'Inativo'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="module-stats">
                            <div class="module-stat">
                                <div class="module-stat-number rs_lab"><?php echo $module_stats['rs_lab']['total_activities']; ?></div>
                                <div class="module-stat-label">Atividades</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number rs_lab"><?php echo $module_stats['rs_lab']['unique_users']; ?></div>
                                <div class="module-stat-label">Usuários</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number rs_lab"><?php echo number_format($module_stats['rs_lab']['avg_score'], 1); ?></div>
                                <div class="module-stat-label">Média</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number rs_lab"><?php echo $module_stats['rs_lab']['excellent_count']; ?></div>
                                <div class="module-stat-label">Excelentes</div>
                            </div>
                            <div class="module-stat">
                                <div class="module-stat-number rs_lab"><?php echo $module_stats['rs_lab']['today_count']; ?></div>
                                <div class="module-stat-label">Hoje</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Atividades Recentes -->
                <div class="content-section">
                    <h2 class="section-title">🎯 Todas as Atividades Realizadas (3 Módulos)</h2>
                    
                    <!-- Filtros -->
                    <form method="GET" class="filters">
                        <div class="filter-group">
                            <label>Buscar:</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nome, matrícula, e-mail...">
                        </div>
                        <div class="filter-group">
                            <label>Módulo:</label>
                            <select name="module_filter">
                                <option value="">Todos os módulos</option>
                                <option value="ethics" <?php echo $module_filter === 'ethics' ? 'selected' : ''; ?>>⚖️ Laboratório de Ética</option>
                                <option value="der_quiz" <?php echo $module_filter === 'der_quiz' ? 'selected' : ''; ?>>📊 DER Quiz</option>
                                <option value="rs_lab" <?php echo $module_filter === 'rs_lab' ? 'selected' : ''; ?>>📈 Laboratório de R&S</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Curso:</label>
                            <select name="course_filter">
                                <option value="">Todos os cursos</option>
                                <option value="informatica" <?php echo $course_filter === 'informatica' ? 'selected' : ''; ?>>Informática</option>
                                <option value="recursos_humanos" <?php echo $course_filter === 'recursos_humanos' ? 'selected' : ''; ?>>Recursos Humanos</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Turma:</label>
                            <select name="turma_filter">
                                <option value="">Todas as turmas</option>
                                <?php foreach (getUniqueTurmas() as $turma): ?>
                                    <option value="<?php echo htmlspecialchars($turma); ?>" <?php echo $turma_filter === $turma ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(formatTurmaName($turma)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Data Inicial:</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="filter-group">
                            <label>Data Final:</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="filter-group">
                            <label>Pontuação Mín.:</label>
                            <input type="number" name="score_min" value="<?php echo $score_min; ?>" min="0" max="100">
                        </div>
                        <div class="filter-group">
                            <label>Pontuação Máx.:</label>
                            <input type="number" name="score_max" value="<?php echo $score_max; ?>" min="0" max="100">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn">Filtrar</button>
                            <a href="index.php" class="btn btn-secondary">Limpar</a>
                        </div>
                    </form>
                    
                    <!-- Tabela de Atividades -->
                    <?php if (empty($activities)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🎯</div>
                            <h3>Nenhuma atividade realizada ainda</h3>
                            <p>Quando os alunos começarem a fazer as atividades, os resultados aparecerão aqui.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Usuário</th>
                                        <th>Módulo</th>
                                        <th>Curso/Turma</th>
                                        <th>Pontuação Final</th>
                                        <th>Duração</th>
                                        <th>Data</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($activity['display_name'] ?? 'Nome não informado'); ?></strong><br>
                                                <small><?php echo htmlspecialchars($activity['email'] ?? $activity['user_email'] ?? ''); ?></small><br>
                                                <small>Mat: <?php echo htmlspecialchars($activity['registration'] ?? $activity['user_registration'] ?? 'N/A'); ?></small>
                                            </td>
                                            <td>
                                                <span class="module-badge <?php echo htmlspecialchars($activity['module_type']); ?>">
                                                    <?php echo htmlspecialchars($activity['module_icon']); ?> <?php echo htmlspecialchars($activity['module_name']); ?>
                                                </span>
                                                <br>
                                                <span class="structure-badge <?php echo htmlspecialchars($activity['source_structure']); ?>">
                                                    <?php echo $activity['source_structure'] === 'old' ? 'Estrutura Antiga' : 'Nova Estrutura'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($activity['course'])): ?>
                                                    <span style="font-weight: 600; color: #2c3e50;">
                                                        <?php echo htmlspecialchars(formatCourseName($activity['course'])); ?>
                                                    </span>
                                                    <br>
                                                    <span class="turma-badge <?php echo strpos($activity['turma'], 'info') !== false ? 'turma-info' : 'turma-rh'; ?>">
                                                        <?php echo htmlspecialchars(formatTurmaName($activity['turma'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #6c757d;">Não informado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="score <?php echo getScoreClass($activity['final_score']); ?>">
                                                    <?php echo number_format($activity['final_score'], 1); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDuration($activity['duration_minutes'] ?? 0); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></td>
                                            <td>
                                                <a href="view_details.php?id=<?php echo $activity['id']; ?>&module=<?php echo $activity['module_type']; ?>" class="btn">Ver Detalhes</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginação -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page-1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">« Anterior</a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="current"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page+1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">Próxima »</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
            <?php endif; ?>
        </div>
    </div>

    <script>
        async function logout() {
            if (confirm('Tem certeza que deseja sair do painel administrativo?')) {
                try {
                    const response = await fetch('../auth/auth.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ action: 'logout' })
                    });
                    
                    if (response.ok) {
                        window.location.href = '../index.html';
                    }
                } catch (error) {
                    console.error('Erro no logout:', error);
                    window.location.href = '../index.html';
                }
            }
        }
    </script>
</body>
</html>