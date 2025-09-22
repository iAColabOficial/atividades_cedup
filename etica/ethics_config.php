<?php
/**
 * Configurações do Módulo de Laboratório de Ética
 * Professor Leandro Rodrigues
 * 
 * Este arquivo centraliza todas as configurações específicas
 * do laboratório de decisões éticas em TI
 */

// Impedir acesso direto
if (!defined('SYSTEM_INIT')) {
    die('Acesso direto não permitido');
}

// Configurações gerais do módulo
$ethics_config = [
    'module_name' => 'Laboratório de Decisões Éticas em TI',
    'module_key' => 'ethics_lab',
    'version' => '2.0.0',
    'enabled' => true,
    'icon' => '⚖️',
    'description' => 'Laboratório virtual para explorar dilemas éticos reais da área de TI',
    'duration_estimate' => '15-25 minutos',
    'difficulty_level' => 'Intermediário',
    'max_attempts_per_day' => 3,
    'min_completion_time' => 300, // 5 minutos
    'max_completion_time' => 7200, // 2 horas
    'passing_score' => 60,
    'excellent_score' => 80
];

// Configurações de pontuação
$ethics_scoring = [
    'initial_reputation' => 50,
    'min_reputation' => 0,
    'max_reputation' => 100,
    'score_categories' => [
        'excellent' => ['min' => 80, 'label' => 'Excelente', 'color' => '#27ae60'],
        'good' => ['min' => 60, 'label' => 'Bom', 'color' => '#3498db'],
        'average' => ['min' => 40, 'label' => 'Regular', 'color' => '#f39c12'],
        'poor' => ['min' => 0, 'label' => 'Insuficiente', 'color' => '#e74c3c']
    ]
];

// Textos de feedback baseados na pontuação final
$ethics_feedback = [
    'excellent' => "Excelente! Você demonstrou uma conduta ética exemplar ao longo do laboratório. Suas decisões mostram uma compreensão sólida dos princípios éticos em TI, priorizando sempre a transparência, responsabilidade e o bem-estar dos usuários. Continue mantendo esses altos padrões em sua carreira profissional.",
    
    'good' => "Bom trabalho! Você mostrou um bom entendimento dos conceitos éticos em TI. A maioria de suas decisões foi adequada, demonstrando consciência sobre os impactos de suas ações. Continue desenvolvendo sua sensibilidade ética e considere sempre as consequências de longo prazo de suas decisões.",
    
    'average' => "Sua performance indica a necessidade de maior reflexão sobre questões éticas em TI. Algumas decisões podem ter consequências negativas para usuários e organizações. Recomenda-se estudar mais sobre códigos de ética profissional e participar de discussões sobre dilemas éticos na área tecnológica.",
    
    'poor' => "É importante revisar seus conhecimentos sobre ética profissional em TI. Muitas de suas decisões podem levar a consequências graves, incluindo problemas legais e perda de confiança profissional. Recomenda-se fortemente buscar capacitação adicional em ética e responsabilidade profissional."
];

// Definição dos 10 dilemas éticos
$ethics_dilemmas = [
    [
        'id' => 1,
        'category' => 'Propriedade Intelectual',
        'title' => 'Software não licenciado',
        'description' => 'Você percebe que todos os computadores da empresa têm softwares piratas instalados. Como profissional de TI, você sabe dos riscos legais e éticos envolvidos.',
        'context' => 'Esta situação é comum em muitas empresas, especialmente pequenas e médias, onde o custo de licenças pode ser considerado alto.',
        'options' => [
            'Ignorar a situação e continuar seu trabalho normalmente.',
            'Alertar o chefe sobre os riscos legais e sugerir alternativas gratuitas.',
            'Denunciar anonimamente à autoridade competente.'
        ],
        'feedback' => [
            'Evita conflito imediato, mas aumenta risco legal para empresa e compromete sua integridade profissional.',
            'Demonstra responsabilidade ética e profissional, buscando soluções práticas e viáveis.',
            'Protege direitos legais, mas pode gerar tensão interna sem tentativa de resolução colaborativa.'
        ],
        'impact' => [-5, 10, 5],
        'references' => [
            'Lei de Direitos Autorais (Lei 9.610/98)',
            'Código de Ética do Profissional de Informática'
        ]
    ],
    [
        'id' => 2,
        'category' => 'Privacidade e Proteção de Dados',
        'title' => 'Dados confidenciais de clientes',
        'description' => 'Você encontra uma planilha com dados sensíveis de clientes (CPF, endereços, telefones) aberta e acessível na rede interna da empresa.',
        'context' => 'Com a LGPD em vigor, o tratamento inadequado de dados pessoais pode gerar multas significativas e processos judiciais.',
        'options' => [
            'Compartilhar com colegas para facilitar o trabalho da equipe.',
            'Manter sigilo absoluto e alertar imediatamente seu gestor.',
            'Usar os dados para testar um novo sistema sem autorização prévia.'
        ],
        'feedback' => [
            'Viola gravemente a privacidade e ética profissional, podendo gerar problemas legais sérios.',
            'Prática correta e ética, protegendo dados dos clientes conforme LGPD e boas práticas.',
            'Uso inadequado de dados sensíveis, mesmo com boa intenção técnica, viola princípios de privacidade.'
        ],
        'impact' => [-15, 10, -10],
        'references' => [
            'LGPD - Lei Geral de Proteção de Dados (Lei 13.709/18)',
            'Marco Civil da Internet (Lei 12.965/14)'
        ]
    ],
    [
        'id' => 3,
        'category' => 'Segurança da Informação',
        'title' => 'Falha de segurança descoberta',
        'description' => 'Você descobre uma falha grave no sistema que poderia expor dados de milhares de usuários. A correção demandaria recursos significativos.',
        'context' => 'Vulnerabilidades de segurança podem causar vazamentos massivos de dados, gerando prejuízos financeiros e de reputação enormes.',
        'options' => [
            'Ignorar para evitar problemas com a gestão.',
            'Relatar imediatamente ao responsável pelo sistema.',
            'Explorar a falha como prova de conceito antes de reportar.'
        ],
        'feedback' => [
            'Negligência grave que pode resultar em vazamento de dados e danos irreparáveis à empresa e usuários.',
            'Atitude correta e responsável, protege a empresa, usuários e demonstra profissionalismo ético.',
            'Ética questionável - explorar vulnerabilidades é arriscado mesmo com boa intenção, pode piorar a situação.'
        ],
        'impact' => [-20, 15, -5],
        'references' => [
            'ISO/IEC 27001 - Gestão de Segurança da Informação',
            'Marco Civil da Internet - Segurança de Dados'
        ]
    ],
    [
        'id' => 4,
        'category' => 'Responsabilidade Profissional',
        'title' => 'Pressão do cliente por solução não testada',
        'description' => 'Um cliente importante solicita urgentemente que você instale um software que não passou por todos os testes de segurança.',
        'context' => 'A pressão comercial muitas vezes conflita com boas práticas técnicas, criando dilemas entre satisfação do cliente e segurança.',
        'options' => [
            'Instalar imediatamente para não perder o cliente.',
            'Explicar os riscos e sugerir alternativas seguras.',
            'Recusar completamente a instalação e cancelar o serviço.'
        ],
        'feedback' => [
            'Compromete segurança e pode gerar responsabilidade profissional futura por danos causados.',
            'Equilibra atendimento ao cliente com responsabilidade técnica, demonstrando profissionalismo.',
            'Protege integridade do sistema, mas pode prejudicar relacionamento comercial desnecessariamente.'
        ],
        'impact' => [-10, 10, 5],
        'references' => [
            'Código de Ética Profissional do Desenvolvedor',
            'Boas Práticas de Desenvolvimento Seguro'
        ]
    ],
    [
        'id' => 5,
        'category' => 'Ética em IA e Monitoramento',
        'title' => 'Desenvolvimento de sistema de monitoramento',
        'description' => 'Você participa do desenvolvimento de um software de reconhecimento facial que será usado para monitorar funcionários no ambiente de trabalho.',
        'context' => 'Tecnologias de reconhecimento facial levantam questões sobre privacidade, direitos trabalhistas e possível uso abusivo.',
        'options' => [
            'Continuar o projeto sem questionar os aspectos éticos.',
            'Levantar discussões sobre privacidade e possíveis abusos.',
            'Recusar participar do projeto por questões morais.'
        ],
        'feedback' => [
            'Contribui para desenvolvimento de ferramenta com potencial uso antiético sem reflexão crítica.',
            'Demonstra responsabilidade social e ética profissional, buscando equilibrar tecnologia e direitos.',
            'Preserva integridade moral pessoal, mas não influencia positivamente o projeto nem oferece alternativas.'
        ],
        'impact' => [-5, 10, 8],
        'references' => [
            'Marco Civil da Internet - Privacidade',
            'Declaração Universal dos Direitos Humanos - Privacidade'
        ]
    ],
    [
        'id' => 6,
        'category' => 'Competição e Fair Play',
        'title' => 'Competição desleal em hackathon',
        'description' => 'Durante um hackathon, você descobre que outro participante planeja usar dados de teste de forma antiética para obter vantagem.',
        'context' => 'Competições de tecnologia devem ser baseadas em mérito e criatividade, não em práticas desleais ou antiéticas.',
        'options' => [
            'Ignorar e focar apenas no seu próprio projeto.',
            'Alertar discretamente os organizadores do evento.',
            'Confrontar diretamente o participante sobre suas práticas.'
        ],
        'feedback' => [
            'Permite que comportamento antiético prospere em ambiente competitivo, comprometendo a integridade do evento.',
            'Mantém ética da competição e fair play, protegendo todos os participantes de forma apropriada.',
            'Pode gerar conflito direto, mas defende princípios éticos, embora de forma menos diplomática.'
        ],
        'impact' => [-5, 10, 3],
        'references' => [
            'Códigos de Conduta de Competições Tecnológicas',
            'Ética em Competições Acadêmicas'
        ]
    ],
    [
        'id' => 7,
        'category' => 'Transparência e Auditoria',
        'title' => 'Manipulação de logs do sistema',
        'description' => 'Você percebe que logs de atividades de um sistema crítico foram deliberadamente manipulados para encobrir falhas operacionais.',
        'context' => 'Logs são fundamentais para auditoria, depuração e compliance. Sua manipulação compromete a transparência organizacional.',
        'options' => [
            'Ignorar a situação para evitar conflitos.',
            'Reportar imediatamente ao gestor responsável.',
            'Tentar corrigir os logs por conta própria.'
        ],
        'feedback' => [
            'Conivência com fraude compromete integridade do sistema, auditoria e pode violar regulamentações.',
            'Mantém transparência e conformidade com procedimentos, demonstrando responsabilidade profissional.',
            'Boa intenção, mas pode ser interpretado como interferência não autorizada e comprometer evidências.'
        ],
        'impact' => [-10, 12, -3],
        'references' => [
            'SOX - Sarbanes-Oxley Act (Transparência Corporativa)',
            'ITIL - Gestão de Incidentes e Logs'
        ]
    ],
    [
        'id' => 8,
        'category' => 'Qualidade e Testes',
        'title' => 'Pressão por atalhos em testes',
        'description' => 'Seu chefe solicita que você reduza drasticamente o tempo de testes de um sistema crítico, cortando etapas importantes de verificação.',
        'context' => 'Testes adequados são essenciais para qualidade e segurança de software. Atalhos podem causar falhas graves em produção.',
        'options' => [
            'Seguir as instruções para agradar a gestão.',
            'Explicar os riscos e insistir na execução completa dos testes.',
            'Executar testes parciais, tentando equilibrar risco e prazo.'
        ],
        'feedback' => [
            'Coloca usuários e empresa em risco por falhas não detectadas, comprometendo qualidade e segurança.',
            'Demonstra responsabilidade técnica e ética profissional, priorizando qualidade sobre pressões comerciais.',
            'Solução de compromisso, mas ainda mantém riscos significativos e pode não detectar problemas críticos.'
        ],
        'impact' => [-15, 10, -2],
        'references' => [
            'IEEE Standards for Software Testing',
            'ISTQB - Princípios de Teste de Software'
        ]
    ],
    [
        'id' => 9,
        'category' => 'Inteligência Artificial e Viés',
        'title' => 'IA para avaliação de funcionários',
        'description' => 'Você é solicitado a desenvolver uma IA para avaliar desempenho de funcionários com base em dados limitados e potencialmente enviesados.',
        'context' => 'Sistemas de IA podem perpetuar ou amplificar vieses existentes, levando a decisões injustas em recursos humanos.',
        'options' => [
            'Desenvolver o sistema conforme solicitado.',
            'Alertar sobre limitações, vieses e riscos do sistema.',
            'Recusar trabalhar neste projeto específico.'
        ],
        'feedback' => [
            'Sistema pode gerar decisões injustas e discriminatórias, violando princípios de equidade no trabalho.',
            'Responsabilidade ética em desenvolvimento de IA, buscando sistemas justos e transparentes.',
            'Protege integridade pessoal, mas não contribui para melhoria do projeto nem oferece soluções alternativas.'
        ],
        'impact' => [-8, 12, 5],
        'references' => [
            'IEEE Standards for Ethical AI Design',
            'Princípios de IA Responsável'
        ]
    ],
    [
        'id' => 10,
        'category' => 'Privacidade e Monetização',
        'title' => 'Proposta de venda de dados',
        'description' => 'Um colega sugere monetizar dados de usuários vendendo informações para empresas parceiras, argumentando que "todos fazem isso".',
        'context' => 'A monetização de dados pessoais sem consentimento explícito viola a LGPD e princípios fundamentais de privacidade.',
        'options' => [
            'Concordar com a ideia para gerar receita extra.',
            'Recusar firmemente e reportar a sugestão.',
            'Ignorar a conversa e não se envolver.'
        ],
        'feedback' => [
            'Violação grave da ética profissional, privacidade dos usuários e legislação vigente (LGPD).',
            'Conduta ética exemplar, protegendo direitos dos usuários e cumprindo a legislação.',
            'Evita responsabilidade direta, mas não impede prática antiética nem protege os usuários.'
        ],
        'impact' => [-25, 15, -5],
        'references' => [
            'LGPD - Consentimento para Tratamento de Dados',
            'Marco Civil da Internet - Proteção de Dados'
        ]
    ]
];

// Configurações de relatórios
$ethics_reports = [
    'individual_sections' => [
        'header' => 'Dossiê Profissional de Ética em TI',
        'score_display' => true,
        'choices_summary' => true,
        'detailed_analysis' => true,
        'recommendations' => true
    ],
    'group_analytics' => [
        'score_distribution' => true,
        'choice_patterns' => true,
        'time_analysis' => true,
        'improvement_areas' => true
    ]
];

// Configurações de interface
$ethics_interface = [
    'theme_colors' => [
        'primary' => '#2c3e50',
        'secondary' => '#3498db',
        'success' => '#27ae60',
        'warning' => '#f39c12',
        'danger' => '#e74c3c',
        'info' => '#17a2b8'
    ],
    'progress_animation' => true,
    'reputation_meter' => true,
    'feedback_delay' => 500, // ms
    'auto_advance_delay' => 2000 // ms
];

// Recursos educacionais complementares
$ethics_resources = [
    'recommended_reading' => [
        'Ética em Computação - IEEE Computer Society',
        'Código de Ética do Profissional em Informática - SBC',
        'LGPD - Guia Prático para Desenvolvedores',
        'Princípios de Privacidade by Design'
    ],
    'certification_paths' => [
        'Certified Information Privacy Professional (CIPP)',
        'Certified Information Security Manager (CISM)',
        'Professional Cloud Security Manager (PCSM)'
    ],
    'external_links' => [
        'acm_ethics' => 'https://www.acm.org/code-of-ethics',
        'ieee_ethics' => 'https://www.ieee.org/about/corporate/governance/p7-8.html',
        'sbc_ethics' => 'https://www.sbc.org.br/documentos-da-sbc/summary/131-estatutos-e-regulamentos/760-codigo-de-etica',
        'lgpd_guide' => 'https://www.gov.br/cidadania/pt-br/acesso-a-informacao/lgpd'
    ]
];

// Configurações de gamificação
$ethics_gamification = [
    'achievements' => [
        'first_completion' => [
            'name' => 'Primeiro Laboratório',
            'description' => 'Completou seu primeiro laboratório de ética',
            'icon' => '🎯',
            'score_required' => 0
        ],
        'ethical_champion' => [
            'name' => 'Campeão Ético',
            'description' => 'Obteve pontuação excelente (80+)',
            'icon' => '🏆',
            'score_required' => 80
        ],
        'perfect_score' => [
            'name' => 'Perfeição Ética',
            'description' => 'Obteve pontuação máxima (100)',
            'icon' => '👑',
            'score_required' => 100
        ],
        'consistent_performer' => [
            'name' => 'Performance Consistente',
            'description' => 'Completou 3 laboratórios com pontuação boa',
            'icon' => '⭐',
            'score_required' => 60
        ]
    ],
    'leaderboard' => [
        'enabled' => true,
        'anonymous' => true,
        'time_period' => 'monthly'
    ]
];

// Funções utilitárias do módulo
function getEthicsScoreCategory($score) {
    global $ethics_scoring;
    
    foreach ($ethics_scoring['score_categories'] as $category => $config) {
        if ($score >= $config['min']) {
            return $category;
        }
    }
    return 'poor';
}

function getEthicsFeedback($score) {
    global $ethics_feedback;
    $category = getEthicsScoreCategory($score);
    return $ethics_feedback[$category];
}

function getEthicsDilemmaById($id) {
    global $ethics_dilemmas;
    
    foreach ($ethics_dilemmas as $dilemma) {
        if ($dilemma['id'] == $id) {
            return $dilemma;
        }
    }
    return null;
}

function validateEthicsChoice($dilemma_id, $choice_index) {
    $dilemma = getEthicsDilemmaById($dilemma_id);
    
    if (!$dilemma) {
        return false;
    }
    
    return ($choice_index >= 0 && $choice_index < count($dilemma['options']));
}

function calculateEthicsProgress($choices_count) {
    return min(100, ($choices_count / 10) * 100);
}

function formatEthicsDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return round($seconds / 60) . 'min';
    } else {
        return round($seconds / 3600, 1) . 'h';
    }
}

function getEthicsModuleStats() {
    global $ethics_config;
    
    return [
        'module_info' => $ethics_config,
        'total_dilemmas' => count($GLOBALS['ethics_dilemmas']),
        'categories' => array_unique(array_column($GLOBALS['ethics_dilemmas'], 'category')),
        'difficulty_factors' => [
            'time_pressure' => 'Decisões sob pressão de tempo',
            'conflicting_interests' => 'Conflitos entre interesses',
            'legal_implications' => 'Implicações legais complexas',
            'stakeholder_impact' => 'Múltiplos stakeholders afetados'
        ]
    ];
}

// Validações específicas do módulo
function validateEthicsCompletion($data) {
    global $ethics_config;
    
    $errors = [];
    
    // Verificar número de escolhas
    if (count($data['choices']) !== 10) {
        $errors[] = "Número incorreto de dilemas completados";
    }
    
    // Verificar tempo mínimo e máximo
    $duration = strtotime($data['endTime']) - strtotime($data['startTime']);
    if ($duration < $ethics_config['min_completion_time']) {
        $errors[] = "Tempo de conclusão muito rápido (suspeito)";
    }
    if ($duration > $ethics_config['max_completion_time']) {
        $errors[] = "Tempo de conclusão excedeu o limite máximo";
    }
    
    // Verificar pontuação
    if ($data['reputation'] < $ethics_config['initial_reputation'] - 50 || 
        $data['reputation'] > 100) {
        $errors[] = "Pontuação final fora dos parâmetros esperados";
    }
    
    return $errors;
}

// Exportar configurações para uso global
$GLOBALS['ethics_config'] = $ethics_config;
$GLOBALS['ethics_scoring'] = $ethics_scoring;
$GLOBALS['ethics_feedback'] = $ethics_feedback;
$GLOBALS['ethics_dilemmas'] = $ethics_dilemmas;
$GLOBALS['ethics_reports'] = $ethics_reports;
$GLOBALS['ethics_interface'] = $ethics_interface;
$GLOBALS['ethics_resources'] = $ethics_resources;
$GLOBALS['ethics_gamification'] = $ethics_gamification;

// Log de inicialização do módulo
if (defined('SYSTEM_INIT')) {
    logActivity("Módulo de ética inicializado - Versão {$ethics_config['version']}", 'INFO', 'ETHICS_MODULE');
}
?>