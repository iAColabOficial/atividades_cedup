<?php
require_once 'config.php';
require_once 'auth.php';

// Verificar se é administrador
$admin = requireAdmin();

// Configuração de paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtros
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$min_score = isset($_GET['min_score']) ? (int)$_GET['min_score'] : 0;
$max_score = isset($_GET['max_score']) ? (int)$_GET['max_score'] : 100;

try {
    $pdo = getConnection();
    
    // Construir query com filtros
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
    
    // Contar total de registros
    $count_sql = "SELECT COUNT(*) as total FROM ethics_lab_students s WHERE {$where_clause}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);
    
    // Buscar estudantes
    $sql = "
        SELECT s.*, 
               TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) as duration_minutes,
               u.email, u.course
        FROM ethics_lab_students s 
        LEFT JOIN ethics_lab_users u ON s.user_id = u.id
        WHERE {$where_clause}
        ORDER BY s.created_at DESC 
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    // Estatísticas gerais
    $stats_sql = "
        SELECT 
            COUNT(*) as total_students,
            AVG(final_score) as avg_score,
            MIN(final_score) as min_score,
            MAX(final_score) as max_score,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_count,
            COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_count,
            COUNT(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as month_count
        FROM ethics_lab_students
    ";
    $stats_stmt = $pdo->query($stats_sql);
    $stats = $stats_stmt->fetch();
    
    // Estatísticas de usuários
    $users_stats_sql = "
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
            COUNT(CASE WHEN last_login IS NOT NULL THEN 1 END) as users_logged
        FROM ethics_lab_users
    ";
    $users_stats_stmt = $pdo->query($users_stats_sql);
    $users_stats = $users_stats_stmt->fetch();
    
} catch (Exception $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Laboratório de Ética</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .admin-info {
            background: rgba(255,255,255,0.1);
            padding: 10px 20px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 16px;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
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
            background: linear-gradient(90deg, #3498db, #2980b9);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .reports-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .reports-section h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .report-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .report-btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: transform 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }
        
        .report-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52,152,219,0.3);
        }
        
        .report-btn.secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }
        
        .report-btn.success {
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .report-btn.warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .filters h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        
        .filter-group input, .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
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
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
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
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .score {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .score.excellent { background: #d4edda; color: #155724; }
        .score.good { background: #d1ecf1; color: #0c5460; }
        .score.average { background: #fff3cd; color: #856404; }
        .score.poor { background: #f8d7da; color: #721c24; }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
        }
        
        .pagination .current {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .report-buttons {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div>
                    <h1>📊 Painel Administrativo</h1>
                    <p>Laboratório Virtual de Decisões Éticas em TI - <?php echo $app_config['professor_name']; ?></p>
                </div>
                <div class="admin-info">
                    <span>🔧 <?php echo htmlspecialchars($admin['username']); ?></span>
                    <a href="#" onclick="logout()" class="logout-btn">🚪 Sair</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php else: ?>
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                    <div class="stat-label">Total de Laboratórios</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $users_stats['total_users']; ?></div>
                    <div class="stat-label">Usuários Cadastrados</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['avg_score'], 1); ?></div>
                    <div class="stat-label">Pontuação Média</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['today_count']; ?></div>
                    <div class="stat-label">Hoje</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['week_count']; ?></div>
                    <div class="stat-label">Esta Semana</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['month_count']; ?></div>
                    <div class="stat-label">Este Mês</div>
                </div>
            </div>
            
            <!-- Relatórios -->
            <div class="reports-section">
                <h3>📋 Relatórios e Ferramentas</h3>
                <div class="report-buttons">
                    <a href="export.php?<?php echo http_build_query($_GET); ?>" class="report-btn">
                        📥 Exportar Dados CSV
                    </a>
                    <a href="reports.php" class="report-btn secondary">
                        📊 Relatório Detalhado
                    </a>
                    <a href="backup.php" class="report-btn success">
                        💾 Sistema de Backup
                    </a>
                    <a href="analytics.php" class="report-btn warning">
                        📈 Analytics Avançado
                    </a>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filters">
                <h3>🔍 Filtros de Busca</h3>
                <form method="GET">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Buscar (Nome/Matrícula):</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Digite para buscar...">
                        </div>
                        <div class="filter-group">
                            <label>Data Inicial:</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="filter-group">
                            <label>Data Final:</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                    </div>
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Pontuação Mínima:</label>
                            <input type="number" name="min_score" value="<?php echo $min_score; ?>" min="0" max="100">
                        </div>
                        <div class="filter-group">
                            <label>Pontuação Máxima:</label>
                            <input type="number" name="max_score" value="<?php echo $max_score; ?>" min="0" max="100">
                        </div>
                        <div class="filter-group" style="display: flex; align-items: end; gap: 10px;">
                            <button type="submit" class="btn">Filtrar</button>
                            <a href="admin.php" class="btn btn-secondary">Limpar</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tabela de Estudantes -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Matrícula</th>
                            <th>E-mail</th>
                            <th>Curso</th>
                            <th>Pontuação</th>
                            <th>Duração</th>
                            <th>Data/Hora</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <?php
                                $score_class = 'poor';
                                if ($student['final_score'] >= 80) $score_class = 'excellent';
                                elseif ($student['final_score'] >= 60) $score_class = 'good';
                                elseif ($student['final_score'] >= 40) $score_class = 'average';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['registration']); ?></td>
                                <td><?php echo htmlspecialchars($student['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($student['course'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="score <?php echo $score_class; ?>">
                                        <?php echo $student['final_score']; ?>
                                    </span>
                                </td>
                                <td><?php echo $student['duration_minutes']; ?> min</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($student['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="student_detail.php?id=<?php echo $student['id']; ?>" class="btn">Ver Detalhes</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #666; padding: 40px;">
                                    Nenhum estudante encontrado com os filtros aplicados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginação -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($_GET); ?>">« Anterior</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&<?php echo http_build_query($_GET); ?>">Próxima »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin: 40px 0; color: #666;">
                <p>Total de <?php echo $total_records; ?> registros encontrados</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        async function logout() {
            if (confirm('Tem certeza que deseja sair do painel administrativo?')) {
                try {
                    const response = await fetch('auth.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ action: 'logout' })
                    });
                    
                    if (response.ok) {
                        window.location.href = 'login.html';
                    }
                } catch (error) {
                    console.error('Erro no logout:', error);
                    window.location.href = 'login.html';
                }
            }
        }
    </script>
</body>
</html>