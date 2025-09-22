<?php
require_once 'config.php';

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($student_id <= 0) {
    header('Location: admin.php');
    exit;
}

try {
    $pdo = getConnection();
    
    // Buscar dados do estudante
    $stmt_student = $pdo->prepare("
        SELECT s.*, 
               TIMESTAMPDIFF(MINUTE, s.start_time, s.end_time) as duration_minutes,
               u.email, u.course, u.first_name, u.last_name
        FROM ethics_lab_students s 
        LEFT JOIN ethics_lab_users u ON s.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt_student->execute([$student_id]);
    $student = $stmt_student->fetch();
    
    if (!$student) {
        header('Location: admin.php');
        exit;
    }
    
    // Buscar escolhas do estudante
    $stmt_choices = $pdo->prepare("
        SELECT * FROM ethics_lab_choices 
        WHERE student_id = ? 
        ORDER BY dilemma_id
    ");
    $stmt_choices->execute([$student_id]);
    $choices = $stmt_choices->fetchAll();
    
} catch (Exception $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
}

function getScoreClass($score) {
    if ($score >= 80) return 'excellent';
    if ($score >= 60) return 'good';
    if ($score >= 40) return 'average';
    return 'poor';
}

function getScoreDescription($score) {
    if ($score >= 80) return 'Excelente - Conduta ética exemplar';
    if ($score >= 60) return 'Bom - Bom entendimento ético';
    if ($score >= 40) return 'Regular - Necessita maior reflexão';
    return 'Insuficiente - Revisão urgente necessária';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Estudante - <?php echo htmlspecialchars($student['name'] ?? ''); ?></title>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            margin-bottom: 10px;
            display: inline-block;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .student-info {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .info-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1.1em;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .score-display {
            text-align: center;
            margin: 30px 0;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2em;
            font-weight: bold;
            color: white;
        }
        
        .score.excellent { background: #27ae60; }
        .score.good { background: #3498db; }
        .score.average { background: #f39c12; }
        .score.poor { background: #e74c3c; }
        
        .choices-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .choice-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .choice-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .choice-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .choice-meta {
            font-size: 0.9em;
            color: #666;
        }
        
        .choice-content {
            padding: 20px;
        }
        
        .choice-text {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }
        
        .impact-indicator {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
        }
        
        .impact-positive {
            background: #d4edda;
            color: #155724;
        }
        
        .impact-negative {
            background: #f8d7da;
            color: #721c24;
        }
        
        .impact-neutral {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: background 0.3s;
            margin: 5px;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-print {
            background: #27ae60;
        }
        
        .btn-print:hover {
            background: #229954;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .final-analysis {
            margin-top: 40px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #3498db;
        }
        
        @media print {
            .header, .btn, .back-link {
                display: none;
            }
            
            body {
                background: white;
            }
            
            .container {
                max-width: none;
                margin: 0;
                padding: 0;
            }
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
            
            .student-info, .choices-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <a href="admin.php" class="back-link">← Voltar ao Painel</a>
            <h1>📋 Relatório Individual de Ética</h1>
            <p><?php echo $app_config['professor_name']; ?></p>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($error_message)): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php else: ?>
            <!-- Informações do Estudante -->
            <div class="student-info">
                <h2>📊 Dossiê Profissional</h2>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Nome Completo</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Matrícula</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['registration']); ?></div>
                    </div>
                    <?php if ($student['email']): ?>
                    <div class="info-item">
                        <div class="info-label">E-mail</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($student['course']): ?>
                    <div class="info-item">
                        <div class="info-label">Curso</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['course']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <div class="info-label">Data/Hora</div>
                        <div class="info-value"><?php echo date('d/m/Y às H:i', strtotime($student['created_at'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Duração do Teste</div>
                        <div class="info-value"><?php echo $student['duration_minutes']; ?> minutos</div>
                    </div>
                </div>
                
                <div class="score-display">
                    <div class="score-circle score <?php echo getScoreClass($student['final_score']); ?>">
                        <?php echo $student['final_score']; ?>
                    </div>
                    <h3>Pontuação de Conduta Ética</h3>
                    <p><?php echo getScoreDescription($student['final_score']); ?></p>
                </div>
                
                <div style="text-align: center;">
                    <button onclick="window.print()" class="btn btn-print">🖨️ Imprimir Relatório</button>
                    <a href="admin.php" class="btn">📋 Voltar ao Painel</a>
                </div>
            </div>
            
            <!-- Escolhas Detalhadas -->
            <div class="choices-section">
                <h2>📝 Análise Detalhada das Decisões</h2>
                <p style="margin-bottom: 30px; color: #666;">
                    Abaixo estão listadas todas as decisões tomadas pelo estudante durante o laboratório virtual de ética.
                </p>
                
                <?php foreach ($choices as $choice): ?>
                    <div class="choice-item">
                        <div class="choice-header">
                            <div class="choice-title">
                                Dilema <?php echo $choice['dilemma_id']; ?>: <?php echo htmlspecialchars($choice['dilemma_title']); ?>
                            </div>
                            <div class="choice-meta">
                                Respondido em: <?php echo date('d/m/Y H:i:s', strtotime($choice['choice_timestamp'])); ?>
                            </div>
                        </div>
                        <div class="choice-content">
                            <div class="choice-text">
                                <strong>Decisão tomada:</strong><br>
                                <?php echo htmlspecialchars($choice['choice_text']); ?>
                            </div>
                            
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <span>Impacto na reputação:</span>
                                <?php
                                    $impact = $choice['impact'];
                                    $impact_class = 'impact-neutral';
                                    $impact_text = $impact;
                                    
                                    if ($impact > 0) {
                                        $impact_class = 'impact-positive';
                                        $impact_text = '+' . $impact;
                                    } elseif ($impact < 0) {
                                        $impact_class = 'impact-negative';
                                    }
                                ?>
                                <span class="impact-indicator <?php echo $impact_class; ?>">
                                    <?php echo $impact_text; ?> pontos
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Resumo Final -->
                <div class="final-analysis">
                    <h3 style="margin-bottom: 15px; color: #2c3e50;">💡 Análise Final</h3>
                    
                    <?php
                        $positive_choices = count(array_filter($choices, function($c) { return $c['impact'] > 0; }));
                        $negative_choices = count(array_filter($choices, function($c) { return $c['impact'] < 0; }));
                        $neutral_choices = count(array_filter($choices, function($c) { return $c['impact'] == 0; }));
                    ?>
                    
                    <p style="margin-bottom: 15px;">
                        <strong>Distribuição das decisões:</strong><br>
                        • Decisões positivas: <?php echo $positive_choices; ?> de 10<br>
                        • Decisões negativas: <?php echo $negative_choices; ?> de 10<br>
                        • Decisões neutras: <?php echo $neutral_choices; ?> de 10
                    </p>
                    
                    <?php if ($student['final_score'] >= 80): ?>
                        <p><strong>Parabéns!</strong> O estudante demonstrou excelente compreensão dos princípios éticos em TI, tomando decisões responsáveis que priorizam transparência, segurança e bem-estar dos usuários.</p>
                    <?php elseif ($student['final_score'] >= 60): ?>
                        <p>O estudante mostrou boa compreensão ética, mas há espaço para melhoria em algumas áreas. Recomenda-se discussão sobre as consequências de certas decisões.</p>
                    <?php elseif ($student['final_score'] >= 40): ?>
                        <p>Há necessidade de desenvolvimento adicional em ética profissional. O estudante deve estudar mais sobre responsabilidades éticas em TI e participar de discussões sobre dilemas morais.</p>
                    <?php else: ?>
                        <p><strong>Atenção:</strong> É fundamental que o estudante receba orientação adicional sobre ética profissional e responsabilidades em TI. Muitas decisões podem ter consequências graves.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center; margin: 40px 0; padding: 20px; background: white; border-radius: 10px; color: #666;">
            <p><strong>Laboratório Virtual de Decisões Éticas em TI</strong></p>
            <p><?php echo $app_config['professor_name']; ?> | <?php echo date('Y'); ?></p>
        </div>
    </div>
</body>
</html>