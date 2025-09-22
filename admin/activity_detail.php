<?php
/**
 * Detalhes da Atividade de Ética
 * Professor Leandro Rodrigues
 * 
 * Mostra informações detalhadas sobre uma atividade específica
 */

// Incluir configurações e autenticação
require_once '../config/config.php';
require_once '../auth/auth.php';

// Verificar se é administrador
$admin = requireAdmin();

// Obter ID da atividade
$activity_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$activity_id) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = getConnection();
    
    // Buscar dados da atividade
    $activity_sql = "
        SELECT 
            es.*,
            u.first_name,
            u.last_name,
            u.email,
            u.course,
            u.turma,
            TIMESTAMPDIFF(MINUTE, es.start_time, es.end_time) as duration_minutes,
            TIMESTAMPDIFF(SECOND, es.start_time, es.end_time) as duration_seconds
        FROM ethics_lab_students es
        LEFT JOIN system_users u ON es.user_id = u.id
        WHERE es.id = ?
    ";
    
    $stmt = $pdo->prepare($activity_sql);
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch();
    
    if (!$activity) {
        header('Location: index.php');
        exit;
    }
    
    // Buscar todas as escolhas desta atividade
    $choices_sql = "
        SELECT *
        FROM ethics_lab_choices
        WHERE student_id = ?
        ORDER BY dilemma_id
    ";
    
    $stmt = $pdo->prepare($choices_sql);
    $stmt->execute([$activity_id]);
    $choices = $stmt->fetchAll();
    
    // Organizar escolhas por dilema
    $choices_by_dilemma = [];
    foreach ($choices as $choice) {
        $choices_by_dilemma[$choice['dilemma_id']] = $choice;
    }
    
} catch (Exception $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
    logActivity("Erro ao carregar detalhes da atividade: " . $e->getMessage(), 'ERROR', 'ADMIN', $admin['id']);
}

/**
 * Formatar nome da turma para exibição
 */
function formatTurmaName($turma) {
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
 * Formatar nome do curso
 */
function formatCourseName($course) {
    return $course === 'informatica' ? 'Informática' : 'Recursos Humanos';
}

/**
 * Obter classe CSS baseada na pontuação
 */
function getScoreClass($score) {
    if ($score >= 80) return 'excellent';
    if ($score >= 60) return 'good';
    if ($score >= 40) return 'average';
    return 'poor';
}

/**
 * Formatar duração em texto legível
 */
function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return $minutes . 'min ' . $secs . 's';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . $minutes . 'min';
    }
}

/**
 * Obter interpretação da pontuação
 */
function getScoreInterpretation($score) {
    if ($score >= 90) return 'Excelente compreensão ética';
    if ($score >= 80) return 'Boa compreensão ética';
    if ($score >= 70) return 'Compreensão ética satisfatória';
    if ($score >= 60) return 'Compreensão ética adequada';
    if ($score >= 50) return 'Compreensão ética básica';
    if ($score >= 40) return 'Compreensão ética em desenvolvimento';
    return 'Necessita reforço na compreensão ética';
}

/**
 * Obter cor do impacto
 */
function getImpactColor($impact) {
    if ($impact >= 8) return '#27ae60'; // Verde
    if ($impact >= 6) return '#f39c12'; // Amarelo
    if ($impact >= 4) return '#e67e22'; // Laranja
    return '#e74c3c'; // Vermelho
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Atividade - Sistema de Atividades</title>
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
            font-weight: 300;
        }
        
        .header p {
            opacity: 0.9;
        }
        
        .back-btn {
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
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-1px);
        }
        
        .main-content {
            padding: 30px 0;
        }
        
        .activity-overview {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }
        
        .student-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5em;
            margin-right: 20px;
        }
        
        .student-info h2 {
            color: #2c3e50;
            font-size: 1.8em;
            margin-bottom: 5px;
        }
        
        .student-meta {
            color: #6c757d;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .student-meta span {
            background: #f8f9fa;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            border-left: 4px solid #e74c3c;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .score-card {
            background: linear-gradient(135deg, #fff5f5, #ffe6e6);
            border: 2px solid #e74c3c;
        }
        
        .score-value {
            color: #e74c3c;
        }
        
        .choices-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
        }
        
        .section-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dilemma-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .dilemma-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .dilemma-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .dilemma-number {
            background: #e74c3c;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .dilemma-title {
            flex: 1;
            margin: 0 15px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .impact-badge {
            padding: 6px 12px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            font-size: 0.85em;
        }
        
        .choice-content {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #e74c3c;
        }
        
        .choice-text {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .choice-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85em;
            color: #6c757d;
        }
        
        .no-choice {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            font-style: italic;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .score-interpretation {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            border: 1px solid #c3e6cb;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .interpretation-title {
            font-weight: 600;
            color: #155724;
            margin-bottom: 10px;
        }
        
        .interpretation-text {
            color: #155724;
        }
        
        .timeline-item {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .student-header {
                flex-direction: column;
                text-align: center;
            }
            
            .student-meta {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dilemma-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .choice-meta {
                flex-direction: column;
                gap: 5px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div>
                    <h1>Detalhes da Atividade de Ética</h1>
                    <p>Análise completa do desempenho do estudante</p>
                </div>
                <a href="index.php" class="back-btn">← Voltar ao Painel</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="main-content">
            <?php if (isset($error_message)): ?>
                <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php else: ?>
                
                <!-- Visão Geral da Atividade -->
                <div class="activity-overview">
                    <div class="student-header">
                        <div class="student-avatar">
                            <?php echo strtoupper(substr($activity['name'] ?? $activity['first_name'], 0, 1)); ?>
                        </div>
                        <div class="student-info">
                            <h2><?php echo htmlspecialchars($activity['name'] ?? ($activity['first_name'] . ' ' . $activity['last_name'])); ?></h2>
                            <div class="student-meta">
                                <span>📧 <?php echo htmlspecialchars($activity['email'] ?? 'Não informado'); ?></span>
                                <span>🎓 Mat: <?php echo htmlspecialchars($activity['registration']); ?></span>
                                <?php if ($activity['course']): ?>
                                    <span>📚 <?php echo formatCourseName($activity['course']); ?></span>
                                    <span>👥 <?php echo formatTurmaName($activity['turma']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stats-grid">
                        <div class="stat-card score-card">
                            <div class="stat-value score-value"><?php echo $activity['final_score']; ?></div>
                            <div class="stat-label">Pontuação Final</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo formatDuration($activity['duration_seconds']); ?></div>
                            <div class="stat-label">Tempo Total</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo count($choices); ?></div>
                            <div class="stat-label">Dilemas Respondidos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo date('d/m/Y', strtotime($activity['created_at'])); ?></div>
                            <div class="stat-label">Data de Realização</div>
                        </div>
                    </div>
                    
                    <!-- Horários detalhados -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                            <strong>Início:</strong><br>
                            <?php echo date('d/m/Y H:i:s', strtotime($activity['start_time'])); ?>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center;">
                            <strong>Fim:</strong><br>
                            <?php echo date('d/m/Y H:i:s', strtotime($activity['end_time'])); ?>
                        </div>
                    </div>
                    
                    <!-- Interpretação da pontuação -->
                    <div class="score-interpretation">
                        <div class="interpretation-title">Interpretação do Desempenho:</div>
                        <div class="interpretation-text"><?php echo getScoreInterpretation($activity['final_score']); ?></div>
                    </div>
                </div>
                
                <!-- Detalhes das Escolhas -->
                <div class="choices-section">
                    <h2 class="section-title">⚖️ Análise Detalhada das Decisões Éticas</h2>
                    
                    <?php if (empty($choices)): ?>
                        <div class="no-choice">
                            <h3>Nenhuma escolha registrada</h3>
                            <p>Não foram encontradas decisões registradas para esta atividade.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($choices_by_dilemma as $dilemma_id => $choice): ?>
                            <div class="dilemma-card">
                                <div class="dilemma-header">
                                    <div class="dilemma-number"><?php echo $dilemma_id; ?></div>
                                    <div class="dilemma-title"><?php echo htmlspecialchars($choice['dilemma_title']); ?></div>
                                    <div class="impact-badge" style="background-color: <?php echo getImpactColor($choice['impact']); ?>">
                                        Impacto: <?php echo $choice['impact']; ?>/10
                                    </div>
                                </div>
                                
                                <div class="choice-content">
                                    <div class="choice-text">
                                        <strong>Decisão tomada:</strong> <?php echo htmlspecialchars($choice['choice_text']); ?>
                                    </div>
                                    <div class="choice-meta">
                                        <span>Escolha #<?php echo $choice['choice_index']; ?></span>
                                        <span>⏱️ <?php echo date('H:i:s', strtotime($choice['choice_timestamp'])); ?></span>
                                    </div>
                                    
                                    <?php
                                    // Calcular tempo desde o início da atividade
                                    $choice_time = strtotime($choice['choice_timestamp']);
                                    $start_time = strtotime($activity['start_time']);
                                    $time_elapsed = $choice_time - $start_time;
                                    ?>
                                    <div class="timeline-item">
                                        Respondido após <?php echo formatDuration($time_elapsed); ?> do início da atividade
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Resumo das escolhas -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin-top: 30px;">
                            <h3 style="color: #2c3e50; margin-bottom: 15px;">📊 Resumo das Decisões</h3>
                            
                            <?php
                            $impacts = array_column($choices, 'impact');
                            $avg_impact = count($impacts) > 0 ? round(array_sum($impacts) / count($impacts), 1) : 0;
                            $high_impact = count(array_filter($impacts, function($i) { return $i >= 8; }));
                            $medium_impact = count(array_filter($impacts, function($i) { return $i >= 6 && $i < 8; }));
                            $low_impact = count(array_filter($impacts, function($i) { return $i < 6; }));
                            ?>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                    <div style="font-size: 1.5em; font-weight: bold; color: #e74c3c;"><?php echo $avg_impact; ?></div>
                                    <div style="color: #6c757d; font-size: 0.9em;">Impacto Médio</div>
                                </div>
                                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                    <div style="font-size: 1.5em; font-weight: bold; color: #27ae60;"><?php echo $high_impact; ?></div>
                                    <div style="color: #6c757d; font-size: 0.9em;">Alto Impacto (8-10)</div>
                                </div>
                                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                    <div style="font-size: 1.5em; font-weight: bold; color: #f39c12;"><?php echo $medium_impact; ?></div>
                                    <div style="color: #6c757d; font-size: 0.9em;">Médio Impacto (6-7)</div>
                                </div>
                                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                                    <div style="font-size: 1.5em; font-weight: bold; color: #e74c3c;"><?php echo $low_impact; ?></div>
                                    <div style="color: #6c757d; font-size: 0.9em;">Baixo Impacto (0-5)</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</body>
</html>