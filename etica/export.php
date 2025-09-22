<?php
require_once 'config.php';

// Verificar se é uma requisição válida
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: admin.php');
    exit;
}

// Filtros (mesmos do admin.php)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$min_score = isset($_GET['min_score']) ? (int)$_GET['min_score'] : 0;
$max_score = isset($_GET['max_score']) ? (int)$_GET['max_score'] : 100;

try {
    $pdo = getConnection();
    
    // Construir query com filtros (igual ao admin.php)
    $where_conditions = ["1=1"];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(s.name LIKE ? OR s.registration LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(s.created_at) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(s.created_at) <= ?";
        $params[] = $date_to;
    }
    
    if ($min_score > 0) {
        $where_conditions[] = "s.final_score >= ?";
        $params[] = $min_score;
    }
    
    if ($max_score < 100) {
        $where_conditions[] = "s.final_score <= ?";
        $params[] = $max_score;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Buscar dados completos para exportação
    $sql = "
        SELECT 
            s.id,
            s.name,
            s.registration,
            s.final_score,
            s.start_time,
            s.end_time,
            s.created_at,
            TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) as duration_minutes,
            u.email,
            u.course,
            u.first_name,
            u.last_name
        FROM ethics_lab_students s 
        LEFT JOIN ethics_lab_users u ON s.user_id = u.id
        WHERE {$where_clause}
        ORDER BY s.created_at DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    // Buscar escolhas detalhadas para cada estudante
    $detailed_data = [];
    foreach ($students as $student) {
        $choices_sql = "
            SELECT dilemma_id, dilemma_title, choice_index, choice_text, impact 
            FROM ethics_lab_choices 
            WHERE student_id = ? 
            ORDER BY dilemma_id
        ";
        $choices_stmt = $pdo->prepare($choices_sql);
        $choices_stmt->execute([$student['id']]);
        $choices = $choices_stmt->fetchAll();
        
        $student['choices'] = $choices;
        $detailed_data[] = $student;
    }
    
    // Definir nome do arquivo
    $filename = 'laboratorio_etica_' . date('Y-m-d_H-i-s') . '.csv';
    
    // Headers para download CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Criar output stream
    $output = fopen('php://output', 'w');
    
    // Adicionar BOM para UTF-8 (para Excel reconhecer acentos)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalhos básicos
    $basic_headers = [
        'ID',
        'Nome',
        'Matrícula', 
        'E-mail',
        'Curso',
        'Pontuação Final',
        'Duração (min)',
        'Data Início',
        'Data Fim',
        'Data Criação'
    ];
    
    // Adicionar cabeçalhos para cada dilema
    for ($i = 1; $i <= 10; $i++) {
        $basic_headers[] = "Dilema {$i} - Escolha";
        $basic_headers[] = "Dilema {$i} - Impacto";
    }
    
    // Escrever cabeçalhos
    fputcsv($output, $basic_headers, ';');
    
    // Escrever dados
    foreach ($detailed_data as $student) {
        $row = [
            $student['id'],
            $student['name'],
            $student['registration'],
            $student['email'] ?? 'N/A',
            $student['course'] ?? 'N/A',
            $student['final_score'],
            $student['duration_minutes'],
            $student['start_time'],
            $student['end_time'],
            $student['created_at']
        ];
        
        // Organizar escolhas por dilema_id
        $choices_by_dilemma = [];
        foreach ($student['choices'] as $choice) {
            $choices_by_dilemma[$choice['dilemma_id']] = $choice;
        }
        
        // Adicionar dados dos dilemas
        for ($i = 1; $i <= 10; $i++) {
            if (isset($choices_by_dilemma[$i])) {
                $choice = $choices_by_dilemma[$i];
                $row[] = $choice['choice_text'];
                $row[] = $choice['impact'];
            } else {
                $row[] = 'Não respondido';
                $row[] = '0';
            }
        }
        
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    // Em caso de erro, redirecionar para admin com mensagem
    header('Location: admin.php?error=' . urlencode('Erro ao exportar dados: ' . $e->getMessage()));
    exit;
}
?>