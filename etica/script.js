// Script do Laboratório Virtual de Decisões Éticas em TI
// Professor Leandro Rodrigues

// Dados do estudante
let studentData = {
    user_id: null,
    name: '',
    registration: '',
    email: '',
    course: '',
    choices: [],
    reputation: 50,
    startTime: null,
    endTime: null
};

// Dilemas éticos
const dilemmas = [
    {
        id: 1,
        title: "Software não licenciado",
        description: "Você percebe que todos os computadores da empresa têm softwares piratas instalados. Como profissional de TI, você sabe dos riscos legais e éticos envolvidos.",
        options: [
            "Ignorar a situação e continuar seu trabalho normalmente.",
            "Alertar o chefe sobre os riscos legais e sugerir alternativas gratuitas.",
            "Denunciar anonimamente à autoridade competente."
        ],
        feedback: [
            "Evita conflito imediato, mas aumenta risco legal para empresa e compromete sua integridade profissional. (-5 reputação)",
            "Demonstra responsabilidade ética e profissional, buscando soluções práticas. (+10 reputação)",
            "Protege direitos legais, mas pode gerar tensão interna sem tentativa de resolução. (+5 reputação)"
        ],
        impact: [-5, 10, 5]
    },
    {
        id: 2,
        title: "Dados confidenciais de clientes",
        description: "Você encontra uma planilha com dados sensíveis de clientes (CPF, endereços, telefones) aberta e acessível na rede interna da empresa.",
        options: [
            "Compartilhar com colegas para facilitar o trabalho da equipe.",
            "Manter sigilo absoluto e alertar imediatamente seu gestor.",
            "Usar os dados para testar um novo sistema sem autorização prévia."
        ],
        feedback: [
            "Viola gravemente a privacidade e ética profissional, podendo gerar problemas legais sérios. (-15 reputação)",
            "Prática correta e ética, protegendo dados dos clientes conforme LGPD. (+10 reputação)",
            "Uso inadequado de dados sensíveis, mesmo com boa intenção técnica. (-10 reputação)"
        ],
        impact: [-15, 10, -10]
    },
    {
        id: 3,
        title: "Falha de segurança descoberta",
        description: "Você descobre uma falha grave no sistema que poderia expor dados de milhares de usuários. A correção demandaria recursos significativos.",
        options: [
            "Ignorar para evitar problemas com a gestão.",
            "Relatar imediatamente ao responsável pelo sistema.",
            "Explorar a falha como prova de conceito antes de reportar."
        ],
        feedback: [
            "Negligência grave que pode resultar em vazamento de dados e danos irreparáveis. (-20 reputação)",
            "Atitude correta e responsável, protege a empresa e os usuários. (+15 reputação)",
            "Ética questionável - explorar vulnerabilidades é arriscado mesmo com boa intenção. (-5 reputação)"
        ],
        impact: [-20, 15, -5]
    },
    {
        id: 4,
        title: "Pressão do cliente por solução não testada",
        description: "Um cliente importante solicita urgentemente que você instale um software que não passou por todos os testes de segurança.",
        options: [
            "Instalar imediatamente para não perder o cliente.",
            "Explicar os riscos e sugerir alternativas seguras.",
            "Recusar completamente a instalação e cancelar o serviço."
        ],
        feedback: [
            "Compromete segurança e pode gerar responsabilidade profissional futura. (-10 reputação)",
            "Equilibra atendimento ao cliente com responsabilidade técnica. (+10 reputação)",
            "Protege integridade do sistema, mas pode prejudicar relacionamento comercial. (+5 reputação)"
        ],
        impact: [-10, 10, 5]
    },
    {
        id: 5,
        title: "Desenvolvimento de sistema de monitoramento",
        description: "Você participa do desenvolvimento de um software de reconhecimento facial que será usado para monitorar funcionários no ambiente de trabalho.",
        options: [
            "Continuar o projeto sem questionar os aspectos éticos.",
            "Levantar discussões sobre privacidade e possíveis abusos.",
            "Recusar participar do projeto por questões morais."
        ],
        feedback: [
            "Contribui para desenvolvimento de ferramenta com potencial uso antiético. (-5 reputação)",
            "Demonstra responsabilidade social e ética profissional. (+10 reputação)",
            "Preserva integridade moral pessoal, mas não influencia positivamente o projeto. (+8 reputação)"
        ],
        impact: [-5, 10, 8]
    },
    {
        id: 6,
        title: "Competição desleal em hackathon",
        description: "Durante um hackathon, você descobre que outro participante planeja usar dados de teste de forma antiética para obter vantagem.",
        options: [
            "Ignorar e focar apenas no seu próprio projeto.",
            "Alertar discretamente os organizadores do evento.",
            "Confrontar diretamente o participante sobre suas práticas."
        ],
        feedback: [
            "Permite que comportamento antiético prospere em ambiente competitivo. (-5 reputação)",
            "Mantém ética da competição e fair play. (+10 reputação)",
            "Pode gerar conflito direto, mas defende princípios éticos. (+3 reputação)"
        ],
        impact: [-5, 10, 3]
    },
    {
        id: 7,
        title: "Manipulação de logs do sistema",
        description: "Você percebe que logs de atividades de um sistema crítico foram deliberadamente manipulados para encobrir falhas operacionais.",
        options: [
            "Ignorar a situação para evitar conflitos.",
            "Reportar imediatamente ao gestor responsável.",
            "Tentar corrigir os logs por conta própria."
        ],
        feedback: [
            "Conivência com fraude compromete integridade do sistema e auditoria. (-10 reputação)",
            "Mantém transparência e conformidade com procedimentos. (+12 reputação)",
            "Boa intenção, mas pode ser interpretado como interferência não autorizada. (-3 reputação)"
        ],
        impact: [-10, 12, -3]
    },
    {
        id: 8,
        title: "Pressão por atalhos em testes",
        description: "Seu chefe solicita que você reduza drasticamente o tempo de testes de um sistema crítico, cortando etapas importantes de verificação.",
        options: [
            "Seguir as instruções para agradar a gestão.",
            "Explicar os riscos e insistir na execução completa dos testes.",
            "Executar testes parciais, tentando equilibrar risco e prazo."
        ],
        feedback: [
            "Coloca usuários e empresa em risco por falhas não detectadas. (-15 reputação)",
            "Demonstra responsabilidade técnica e ética profissional. (+10 reputação)",
            "Solução de compromisso, mas ainda mantém riscos significativos. (-2 reputação)"
        ],
        impact: [-15, 10, -2]
    },
    {
        id: 9,
        title: "IA para avaliação de funcionários",
        description: "Você é solicitado a desenvolver uma IA para avaliar desempenho de funcionários com base em dados limitados e potencialmente enviesados.",
        options: [
            "Desenvolver o sistema conforme solicitado.",
            "Alertar sobre limitações, vieses e riscos do sistema.",
            "Recusar trabalhar neste projeto específico."
        ],
        feedback: [
            "Sistema pode gerar decisões injustas e discriminatórias. (-8 reputação)",
            "Responsabilidade ética em desenvolvimento de IA. (+12 reputação)",
            "Protege integridade pessoal, mas não contribui para melhoria do projeto. (+5 reputação)"
        ],
        impact: [-8, 12, 5]
    },
    {
        id: 10,
        title: "Proposta de venda de dados",
        description: "Um colega sugere monetizar dados de usuários vendendo informações para empresas parceiras, argumentando que 'todos fazem isso'.",
        options: [
            "Concordar com a ideia para gerar receita extra.",
            "Recusar firmemente e reportar a sugestão.",
            "Ignorar a conversa e não se envolver."
        ],
        feedback: [
            "Violação grave da ética profissional, privacidade e legislação vigente. (-25 reputação)",
            "Conduta ética exemplar, protegendo direitos dos usuários. (+15 reputação)",
            "Evita responsabilidade direta, mas não impede prática antiética. (-5 reputação)"
        ],
        impact: [-25, 15, -5]
    }
];

let currentDilemma = 0;

// Verificar autenticação ao carregar página
document.addEventListener('DOMContentLoaded', function() {
    checkAuthentication();
});

// Função para verificar autenticação
async function checkAuthentication() {
    try {
        const response = await fetch('../auth/auth.php?check');
        const result = await response.json();
        
        if (result.authenticated) {
            // Usuário autenticado - preencher dados
            studentData.user_id = result.user.id;
            studentData.name = result.user.name;
            studentData.registration = result.user.registration || 'N/A';
            studentData.email = result.user.email;
            studentData.course = result.user.course || 'N/A';
            
            // Mostrar informações do usuário
            showUserInfo();
        } else {
            // Não autenticado - redirecionar para login
            alert('Você precisa estar logado para acessar este laboratório.');
            window.location.href = '../auth/login.html';
        }
    } catch (error) {
        console.error('Erro ao verificar autenticação:', error);
        alert('Erro de conexão. Redirecionando para login...');
        window.location.href = '../auth/login.html';
    }
}

// Função para mostrar informações do usuário
function showUserInfo() {
    const userDetails = document.getElementById('userDetails');
    userDetails.innerHTML = `
        <p><strong>Nome:</strong> ${studentData.name}</p>
        <p><strong>E-mail:</strong> ${studentData.email}</p>
        <p><strong>Matrícula:</strong> ${studentData.registration}</p>
        <p><strong>Curso:</strong> ${studentData.course}</p>
    `;
}

// Função para iniciar o laboratório
function startLab() {
    studentData.startTime = new Date();
    
    document.getElementById('welcomeScreen').classList.add('hidden');
    document.getElementById('progressContainer').classList.add('show');
    document.getElementById('dilemmaScreen').classList.add('show');
    
    loadDilemma(0);
}

// Função para carregar um dilema
function loadDilemma(index) {
    if (index >= dilemmas.length) {
        showFinalReport();
        return;
    }
    
    const dilemma = dilemmas[index];
    
    document.getElementById('dilemmaTitle').textContent = dilemma.title;
    document.getElementById('dilemmaDescription').textContent = dilemma.description;
    
    const optionsContainer = document.getElementById('optionsContainer');
    optionsContainer.innerHTML = '';
    
    dilemma.options.forEach((option, i) => {
        const optionDiv = document.createElement('div');
        optionDiv.className = 'option';
        optionDiv.innerHTML = `<span class="option-letter">${String.fromCharCode(97 + i)})</span> ${option}`;
        optionDiv.onclick = () => selectOption(i);
        optionsContainer.appendChild(optionDiv);
    });
    
    updateProgress(index + 1);
    
    // Reset feedback and button
    document.getElementById('feedback').classList.remove('show');
    document.getElementById('nextBtn').classList.remove('show');
}

// Função para selecionar uma opção
function selectOption(optionIndex) {
    const options = document.querySelectorAll('.option');
    options.forEach(opt => opt.classList.remove('selected'));
    options[optionIndex].classList.add('selected');
    
    const dilemma = dilemmas[currentDilemma];
    const impact = dilemma.impact[optionIndex];
    
    studentData.choices.push({
        dilemma_id: dilemma.id,
        dilemma_title: dilemma.title,
        choice_index: optionIndex,
        choice_text: dilemma.options[optionIndex],
        impact: impact,
        timestamp: new Date()
    });
    
    studentData.reputation += impact;
    if (studentData.reputation < 0) studentData.reputation = 0;
    if (studentData.reputation > 100) studentData.reputation = 100;
    
    updateReputationBar();
    
    document.getElementById('feedbackText').textContent = dilemma.feedback[optionIndex];
    document.getElementById('feedback').classList.add('show');
    document.getElementById('nextBtn').classList.add('show');
}

// Função para próximo dilema
function nextDilemma() {
    currentDilemma++;
    loadDilemma(currentDilemma);
}

// Função para atualizar barra de progresso
function updateProgress(current) {
    const percentage = (current / dilemmas.length) * 100;
    document.getElementById('progressFill').style.width = percentage + '%';
    document.getElementById('progressText').textContent = `Dilema ${current} de ${dilemmas.length}`;
}

// Função para atualizar barra de reputação
function updateReputationBar() {
    document.getElementById('reputationFill').style.width = studentData.reputation + '%';
    document.getElementById('reputationScore').textContent = Math.round(studentData.reputation);
}

// Função para mostrar relatório final
function showFinalReport() {
    studentData.endTime = new Date();
    
    document.getElementById('dilemmaScreen').classList.remove('show');
    document.getElementById('progressContainer').classList.remove('show');
    document.getElementById('finalReport').classList.add('show');
    
    document.getElementById('studentInfo').innerHTML = `
        <strong>${studentData.name}</strong><br>
        ${studentData.email}<br>
        Matrícula: ${studentData.registration}
    `;
    
    const finalScore = Math.round(studentData.reputation);
    document.getElementById('finalScore').textContent = finalScore;
    
    // Gerar resumo das decisões
    generateDecisionSummary();
    
    // Gerar análise final
    generateFinalAnalysis(finalScore);
    
    // Salvar dados no banco
    saveToDatabase();
}

// Função para gerar resumo das decisões
function generateDecisionSummary() {
    const summaryContainer = document.getElementById('decisionSummary');
    summaryContainer.innerHTML = '';
    
    studentData.choices.forEach((choice, index) => {
        const itemDiv = document.createElement('div');
        itemDiv.className = 'decision-item';
        itemDiv.innerHTML = `
            <div class="decision-dilemma">${index + 1}. ${choice.dilemma_title}</div>
            <div class="decision-choice">Escolha: ${choice.choice_text}</div>
        `;
        summaryContainer.appendChild(itemDiv);
    });
}

// Função para gerar análise final
function generateFinalAnalysis(score) {
    let analysis = '';
    
    if (score >= 80) {
        analysis = `Excelente! Você demonstrou uma conduta ética exemplar ao longo do laboratório. 
        Suas decisões mostram uma compreensão sólida dos princípios éticos em TI, priorizando 
        sempre a transparência, responsabilidade e o bem-estar dos usuários. Continue mantendo 
        esses altos padrões em sua carreira profissional.`;
    } else if (score >= 60) {
        analysis = `Bom trabalho! Você mostrou um bom entendimento dos conceitos éticos em TI. 
        A maioria de suas decisões foi adequada, demonstrando consciência sobre os impactos 
        de suas ações. Continue desenvolvendo sua sensibilidade ética e considere sempre as 
        consequências de longo prazo de suas decisões.`;
    } else if (score >= 40) {
        analysis = `Sua performance indica a necessidade de maior reflexão sobre questões éticas 
        em TI. Algumas decisões podem ter consequências negativas para usuários e organizações. 
        Recomenda-se estudar mais sobre códigos de ética profissional e participar de discussões 
        sobre dilemas éticos na área tecnológica.`;
    } else {
        analysis = `É importante revisar seus conhecimentos sobre ética profissional em TI. 
        Muitas de suas decisões podem levar a consequências graves, incluindo problemas legais 
        e perda de confiança profissional. Recomenda-se fortemente buscar capacitação adicional 
        em ética e responsabilidade profissional.`;
    }
    
    document.getElementById('finalAnalysis').textContent = analysis;
}

// Função para salvar dados no banco
async function saveToDatabase() {
    showLoading(true);
    
    try {
        const response = await fetch('save_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: studentData.user_id,
                name: studentData.name,
                email: studentData.email,
                registration: studentData.registration,
                course: studentData.course,
                choices: studentData.choices,
                reputation: studentData.reputation,
                startTime: studentData.startTime,
                endTime: studentData.endTime
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('Dados salvos com sucesso!');
            showMessage('Dados salvos com sucesso!', 'success');
        } else {
            console.error('Erro ao salvar:', result.message);
            showMessage('Aviso: Dados podem não ter sido salvos. Entre em contato com o professor.', 'error');
        }
    } catch (error) {
        console.error('Erro na comunicação com o servidor:', error);
        showMessage('Erro de conexão. Dados podem não ter sido salvos.', 'error');
    } finally {
        showLoading(false);
    }
}

// Função para mostrar/ocultar loading
function showLoading(show) {
    const spinner = document.getElementById('loadingSpinner');
    if (show) {
        spinner.classList.add('show');
    } else {
        spinner.classList.remove('show');
    }
}

// Função para mostrar mensagens
function showMessage(message, type) {
    const existingMessage = document.querySelector('.message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    
    const content = document.querySelector('.content');
    content.insertBefore(messageDiv, content.firstChild);
    
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 5000);
}

// Função para reiniciar o laboratório
function restartLab() {
    if (confirm('Tem certeza que deseja refazer o laboratório? Seus dados atuais serão perdidos.')) {
        // Reset dos dados
        studentData.choices = [];
        studentData.reputation = 50;
        studentData.startTime = null;
        studentData.endTime = null;
        
        currentDilemma = 0;
        
        // Reset da interface
        document.getElementById('finalReport').classList.remove('show');
        document.getElementById('welcomeScreen').classList.remove('hidden');
        document.getElementById('progressContainer').classList.remove('show');
        document.getElementById('dilemmaScreen').classList.remove('show');
        
        // Reset barra de reputação
        document.getElementById('reputationFill').style.width = '50%';
        document.getElementById('reputationScore').textContent = '50';
    }
}

// Função para logout
async function logout() {
    if (confirm('Tem certeza que deseja sair do laboratório? Progresso não salvo será perdido.')) {
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
            // Mesmo com erro, redirecionar
            window.location.href = '../index.html';
        }
    }
}

// Função utilitária para formatação de data
function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

// Prevenir saída acidental durante o laboratório
window.addEventListener('beforeunload', function(e) {
    if (currentDilemma > 0 && currentDilemma < dilemmas.length) {
        e.preventDefault();
        e.returnValue = 'Você tem progresso não salvo. Tem certeza que deseja sair?';
        return e.returnValue;
    }
});

// Atalhos de teclado para acessibilidade
document.addEventListener('keydown', function(e) {
    // Teclas 1, 2, 3 para selecionar opções
    if (e.key >= '1' && e.key <= '3') {
        const optionIndex = parseInt(e.key) - 1;
        const options = document.querySelectorAll('.option');
        if (options[optionIndex] && document.getElementById('dilemmaScreen').classList.contains('show')) {
            selectOption(optionIndex);
        }
    }
    
    // Enter para próximo dilema
    if (e.key === 'Enter' && document.getElementById('nextBtn').classList.contains('show')) {
        nextDilemma();
    }
    
    // Escape para voltar ao hub
    if (e.key === 'Escape') {
        if (confirm('Deseja voltar ao hub de atividades?')) {
            window.location.href = '../index.html';
        }
    }
});

// Log de atividade do usuário para análise
function logUserActivity(action, data = {}) {
    console.log('User Activity:', {
        timestamp: new Date().toISOString(),
        user_id: studentData.user_id,
        action: action,
        data: data
    });
}

// Rastrear engajamento
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('option')) {
        logUserActivity('option_clicked', {
            dilemma: currentDilemma + 1,
            option: e.target.textContent
        });
    }
});

// Verificar se o usuário ficou inativo por muito tempo
let inactivityTimer;
function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(() => {
        if (currentDilemma > 0 && currentDilemma < dilemmas.length) {
            if (confirm('Você ficou inativo por um tempo. Deseja continuar o laboratório?')) {
                resetInactivityTimer();
            } else {
                window.location.href = '../index.html';
            }
        }
    }, 600000); // 10 minutos
}

// Iniciar timer de inatividade
document.addEventListener('mousemove', resetInactivityTimer);
document.addEventListener('keypress', resetInactivityTimer);
resetInactivityTimer();