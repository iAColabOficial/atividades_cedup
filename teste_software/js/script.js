/**
 * Teste de Software - Script Principal
 * Professor Leandro Rodrigues
 * Sistema de Atividades Educacionais
 */

class TesteSoftware {
    constructor() {
        this.currentUser = null;
        this.startTime = null;
        this.endTime = null;
        this.currentScenario = 1;
        this.totalScenarios = 4; // 3 cenários + reflexão
        this.answers = {
            login: '',
            carrinho: '',
            cadastro: '',
            reflexao: ''
        };
        this.scenarioStartTimes = {};
        this.scenarioEndTimes = {};
        
        this.init();
    }

    async init() {
        console.log('Inicializando Teste de Software...');
        
        // Verificar autenticação (opcional - compatível com sistema existente)
        await this.checkAuth();
        
        // Setup dos event listeners
        this.setupEventListeners();
        
        // Esconder loading e mostrar tela inicial
        setTimeout(() => {
            this.hideLoadingScreen();
        }, 1500);
    }

    async checkAuth() {
        try {
            // Tentar verificar se usuário está logado no sistema principal
            const response = await fetch('../auth/auth.php?check');
            if (response.ok) {
                const result = await response.json();
                if (result.authenticated) {
                    this.currentUser = result.user;
                    this.prefillUserData();
                }
            }
        } catch (error) {
            // Sistema funciona sem autenticação também
            console.log('Sistema funcionando em modo standalone');
        }
    }

    preillUserData() {
        if (this.currentUser) {
            const nameField = document.getElementById('studentName');
            const emailField = document.getElementById('studentEmail');
            const courseField = document.getElementById('studentCourse');
            
            if (nameField && this.currentUser.name) {
                nameField.value = this.currentUser.name;
            }
            if (emailField && this.currentUser.email) {
                emailField.value = this.currentUser.email;
            }
            if (courseField && this.currentUser.course) {
                courseField.value = this.currentUser.course;
            }
        }
    }

    setupEventListeners() {
        // Botão iniciar atividade
        const startBtn = document.getElementById('startActivityBtn');
        if (startBtn) {
            startBtn.addEventListener('click', () => this.showIdentificationScreen());
        }

        // Formulário de identificação
        const identForm = document.getElementById('identificationForm');
        if (identForm) {
            identForm.addEventListener('submit', (e) => this.handleIdentification(e));
        }

        // Botão enviar atividade
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.submitActivity());
        }

        // Auto-save das respostas (salvar while digitando)
        const textareas = ['answer1', 'answer2', 'answer3', 'answer4'];
        textareas.forEach(id => {
            const textarea = document.getElementById(id);
            if (textarea) {
                textarea.addEventListener('input', () => this.saveAnswer(id));
                textarea.addEventListener('focus', () => this.startScenarioTimer(id));
            }
        });

        // Validação em tempo real
        this.setupRealTimeValidation();
    }

    setupRealTimeValidation() {
        const requiredFields = ['studentName', 'studentRegistration'];
        
        requiredFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', () => this.validateField(field));
                field.addEventListener('blur', () => this.validateField(field));
            }
        });
    }

    validateField(field) {
        const value = field.value.trim();
        const isValid = value.length >= 2;
        
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
        }
        
        return isValid;
    }

    hideLoadingScreen() {
        const loadingScreen = document.getElementById('loadingScreen');
        if (loadingScreen) {
            loadingScreen.classList.add('hidden');
            setTimeout(() => {
                loadingScreen.style.display = 'none';
            }, 500);
        }
    }

    showWelcomeScreen() {
        this.hideAllScreens();
        const welcomeScreen = document.getElementById('welcomeScreen');
        if (welcomeScreen) {
            welcomeScreen.classList.add('active');
        }
    }

    showIdentificationScreen() {
        this.hideAllScreens();
        const identScreen = document.getElementById('identificationScreen');
        if (identScreen) {
            identScreen.classList.add('active');
        }
        
        // Focus no primeiro campo
        setTimeout(() => {
            const firstField = document.getElementById('studentName');
            if (firstField) firstField.focus();
        }, 300);
    }

    handleIdentification(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        // Validar campos obrigatórios
        const name = formData.get('studentName') || document.getElementById('studentName').value;
        const registration = formData.get('studentRegistration') || document.getElementById('studentRegistration').value;
        
        if (!this.validateIdentification(name, registration)) {
            return;
        }
        
        // Salvar dados do usuário
        this.currentUser = {
            id: registration, // Usar matrícula como ID
            name: name,
            registration: registration,
            email: document.getElementById('studentEmail').value || '',
            course: document.getElementById('studentCourse').value || ''
        };
        
        console.log('Usuário identificado:', this.currentUser);
        
        // Iniciar atividade
        this.startActivity();
    }

    validateIdentification(name, registration) {
        let isValid = true;
        
        const nameField = document.getElementById('studentName');
        const regField = document.getElementById('studentRegistration');
        
        if (!name || name.trim().length < 2) {
            nameField.classList.add('is-invalid');
            isValid = false;
        } else {
            nameField.classList.remove('is-invalid');
            nameField.classList.add('is-valid');
        }
        
        if (!registration || registration.trim().length < 3) {
            regField.classList.add('is-invalid');
            isValid = false;
        } else {
            regField.classList.remove('is-invalid');
            regField.classList.add('is-valid');
        }
        
        if (!isValid) {
            this.showAlert('Por favor, preencha corretamente os campos obrigatórios.', 'warning');
        }
        
        return isValid;
    }

    startActivity() {
        this.startTime = new Date();
        this.currentScenario = 1;
        
        console.log('Atividade iniciada às:', this.startTime);
        
        this.hideAllScreens();
        const activityScreen = document.getElementById('activityScreen');
        if (activityScreen) {
            activityScreen.classList.add('active');
        }
        
        this.updateProgress();
        this.showScenario(1);
    }

    hideAllScreens() {
        const screens = document.querySelectorAll('.screen');
        screens.forEach(screen => {
            screen.classList.remove('active');
        });
    }

    showScenario(scenarioNumber) {
        // Esconder todos os cenários
        const scenarios = document.querySelectorAll('.scenario-card');
        scenarios.forEach(scenario => {
            scenario.classList.remove('active');
        });
        
        // Mostrar cenário atual
        const currentScenarioCard = document.getElementById(`scenario${scenarioNumber}`);
        if (currentScenarioCard) {
            currentScenarioCard.classList.add('active');
        }
        
        this.currentScenario = scenarioNumber;
        this.updateProgress();
        
        // Auto-focus na textarea do cenário
        setTimeout(() => {
            const textarea = document.getElementById(`answer${scenarioNumber}`);
            if (textarea) {
                textarea.focus();
                this.startScenarioTimer(`answer${scenarioNumber}`);
            }
        }, 300);
    }

    nextScenario(nextNumber) {
        // Validar resposta atual
        const currentAnswer = document.getElementById(`answer${this.currentScenario}`);
        if (currentAnswer && !this.validateAnswer(currentAnswer.value, this.currentScenario)) {
            return;
        }
        
        // Salvar tempo do cenário atual
        this.endScenarioTimer(`answer${this.currentScenario}`);
        
        if (nextNumber <= this.totalScenarios) {
            this.showScenario(nextNumber);
        }
    }

    previousScenario(prevNumber) {
        if (prevNumber >= 1) {
            this.showScenario(prevNumber);
        }
    }

    validateAnswer(answer, scenarioNumber) {
        const minLength = 50; // Mínimo de 50 caracteres
        const trimmedAnswer = answer.trim();
        
        if (trimmedAnswer.length < minLength) {
            this.showAlert(`Por favor, escreva uma resposta mais detalhada para o cenário ${scenarioNumber}. Mínimo: ${minLength} caracteres.`, 'warning');
            return false;
        }
        
        return true;
    }

    saveAnswer(textareaId) {
        const textarea = document.getElementById(textareaId);
        if (!textarea) return;
        
        const answer = textarea.value;
        
        // Mapear textarea para resposta
        switch(textareaId) {
            case 'answer1':
                this.answers.login = answer;
                break;
            case 'answer2':
                this.answers.carrinho = answer;
                break;
            case 'answer3':
                this.answers.cadastro = answer;
                break;
            case 'answer4':
                this.answers.reflexao = answer;
                break;
        }
        
        // Salvar no localStorage como backup
        localStorage.setItem('testeSoftware_answers', JSON.stringify(this.answers));
        
        console.log('Resposta salva:', textareaId, answer.substring(0, 50) + '...');
    }

    startScenarioTimer(textareaId) {
        if (!this.scenarioStartTimes[textareaId]) {
            this.scenarioStartTimes[textareaId] = new Date();
        }
    }

    endScenarioTimer(textareaId) {
        if (this.scenarioStartTimes[textareaId]) {
            this.scenarioEndTimes[textareaId] = new Date();
        }
    }

    updateProgress() {
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        
        if (progressBar && progressText) {
            const percentage = (this.currentScenario / this.totalScenarios) * 100;
            progressBar.style.width = percentage + '%';
            progressText.textContent = `${this.currentScenario} de ${this.totalScenarios}`;
        }
    }

    async submitActivity() {
        // Validar todas as respostas
        if (!this.validateAllAnswers()) {
            return;
        }
        
        // Marcar fim da atividade
        this.endTime = new Date();
        this.endScenarioTimer('answer4');
        
        // Mostrar loading no botão
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
        }
        
        try {
            // Preparar dados para envio
            const resultData = this.prepareSubmissionData();
            
            // Enviar para o servidor
            const response = await fetch('save_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(resultData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                console.log('Atividade enviada com sucesso:', result);
                this.showResults(result);
            } else {
                throw new Error(result.message || 'Erro ao enviar atividade');
            }
            
        } catch (error) {
            console.error('Erro ao enviar atividade:', error);
            this.showAlert('Erro ao enviar atividade. Tente novamente.', 'danger');
            
            // Restaurar botão
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Enviar Respostas';
            }
        }
    }

    validateAllAnswers() {
        const scenarios = ['answer1', 'answer2', 'answer3', 'answer4'];
        let allValid = true;
        
        scenarios.forEach((id, index) => {
            const textarea = document.getElementById(id);
            if (textarea) {
                const answer = textarea.value.trim();
                if (!this.validateAnswer(answer, index + 1)) {
                    allValid = false;
                    // Scroll para o campo com erro
                    textarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    textarea.focus();
                }
            }
        });
        
        return allValid;
    }

    prepareSubmissionData() {
        const timeSpentSeconds = Math.round((this.endTime - this.startTime) / 1000);
        
        // Calcular pontuação baseada no tamanho e qualidade das respostas
        const finalScore = this.calculateScore();
        
        return {
            user_id: this.currentUser.id,
            user_name: this.currentUser.name,
            user_email: this.currentUser.email,
            user_registration: this.currentUser.registration,
            user_course: this.currentUser.course,
            user_turma: '', // Pode ser adicionado no formulário se necessário
            
            resposta_login: this.answers.login,
            resposta_carrinho: this.answers.carrinho,
            resposta_cadastro: this.answers.cadastro,
            resposta_reflexao: this.answers.reflexao,
            
            total_scenarios: 3,
            final_score: finalScore,
            time_spent_seconds: timeSpentSeconds,
            
            start_time: this.startTime.toISOString(),
            end_time: this.endTime.toISOString(),
            
            // Dados adicionais para análise
            scenario_times: this.calculateScenarioTimes(),
            answer_lengths: this.calculateAnswerLengths()
        };
    }

    calculateScore() {
        let totalScore = 0;
        const scenarios = ['login', 'carrinho', 'cadastro', 'reflexao'];
        
        scenarios.forEach(scenario => {
            const answer = this.answers[scenario];
            let scenarioScore = 0;
            
            // Pontuação baseada no tamanho da resposta
            if (answer.length >= 200) scenarioScore += 15;
            else if (answer.length >= 100) scenarioScore += 10;
            else if (answer.length >= 50) scenarioScore += 5;
            
            // Pontuação baseada em palavras-chave (análise simples)
            scenarioScore += this.analyzeKeywords(answer, scenario);
            
            totalScore += Math.min(scenarioScore, 25); // Máximo 25 pontos por cenário
        });
        
        return Math.min(totalScore, 100); // Máximo 100 pontos
    }

    analyzeKeywords(answer, scenario) {
        const keywords = {
            login: ['validação', 'senha', 'usuário', 'autenticação', 'segurança', 'erro', 'bloqueio'],
            carrinho: ['produto', 'quantidade', 'estoque', 'preço', 'total', 'finalizar', 'remover'],
            cadastro: ['cpf', 'email', 'validação', 'obrigatório', 'formato', 'duplicado', 'confirmar'],
            reflexao: ['qualidade', 'confiabilidade', 'usuário', 'defeito', 'custo', 'segurança']
        };
        
        const scenarioKeywords = keywords[scenario] || [];
        const lowerAnswer = answer.toLowerCase();
        let keywordScore = 0;
        
        scenarioKeywords.forEach(keyword => {
            if (lowerAnswer.includes(keyword)) {
                keywordScore += 1;
            }
        });
        
        return Math.min(keywordScore, 10); // Máximo 10 pontos por palavras-chave
    }

    calculateScenarioTimes() {
        const times = {};
        
        ['answer1', 'answer2', 'answer3', 'answer4'].forEach(id => {
            if (this.scenarioStartTimes[id] && this.scenarioEndTimes[id]) {
                times[id] = Math.round((this.scenarioEndTimes[id] - this.scenarioStartTimes[id]) / 1000);
            }
        });
        
        return times;
    }

    calculateAnswerLengths() {
        return {
            login: this.answers.login.length,
            carrinho: this.answers.carrinho.length,
            cadastro: this.answers.cadastro.length,
            reflexao: this.answers.reflexao.length
        };
    }

    showResults(resultData) {
        this.hideAllScreens();
        const resultsScreen = document.getElementById('resultsScreen');
        if (resultsScreen) {
            resultsScreen.classList.add('active');
        }
        
        // Gerar feedback automático
        this.generateFeedback(resultData);
        
        // Limpar localStorage
        localStorage.removeItem('testeSoftware_answers');
    }

    generateFeedback(resultData) {
        const feedbackContent = document.getElementById('feedbackContent');
        if (!feedbackContent) return;
        
        let feedbackHTML = `
            <div class="feedback-card">
                <div class="feedback-header">
                    <h5><i class="fas fa-chart-line text-success me-2"></i>Resumo da Sua Atividade</h5>
                </div>
                <div class="feedback-content">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Pontuação Final:</strong> ${resultData.final_score || 0}/100</p>
                            <p><strong>Tempo Total:</strong> ${this.formatTime(resultData.time_spent_seconds || 0)}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Cenários Completados:</strong> 3/3</p>
                            <p><strong>Reflexão Final:</strong> Concluída</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Feedback por cenário
        const scenarios = [
            { key: 'login', title: 'Formulário de Login', icon: 'fas fa-sign-in-alt' },
            { key: 'carrinho', title: 'Carrinho de Compras', icon: 'fas fa-shopping-cart' },
            { key: 'cadastro', title: 'Cadastro de Cliente', icon: 'fas fa-user-plus' }
        ];
        
        scenarios.forEach(scenario => {
            feedbackHTML += this.generateScenarioFeedback(scenario);
        });
        
        // Reflexão final
        feedbackHTML += `
            <div class="feedback-card reflection-feedback">
                <div class="feedback-header">
                    <h5><i class="fas fa-brain text-info me-2"></i>Reflexão Final</h5>
                </div>
                <div class="feedback-content">
                    <p>Sua reflexão sobre a importância dos testes de software demonstra compreensão do tema abordado.</p>
                    <div class="alert alert-info">
                        <strong>Lembre-se:</strong> Os testes são fundamentais para garantir a qualidade, reduzir custos e melhorar a experiência do usuário.
                    </div>
                </div>
            </div>
        `;
        
        feedbackContent.innerHTML = feedbackHTML;
    }

    generateScenarioFeedback(scenario) {
        const examples = this.getExampleAnswers(scenario.key);
        
        return `
            <div class="feedback-card scenario-feedback">
                <div class="feedback-header">
                    <h5><i class="${scenario.icon} text-primary me-2"></i>${scenario.title}</h5>
                </div>
                <div class="feedback-content">
                    <p><strong>Exemplos de casos de teste esperados:</strong></p>
                    ${examples.map(example => `
                        <div class="example-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            ${example}
                        </div>
                    `).join('')}
                    <div class="alert alert-light mt-3">
                        <small><strong>Dica:</strong> Compare suas respostas com os exemplos acima para aprimorar sua técnica de teste.</small>
                    </div>
                </div>
            </div>
        `;
    }

    getExampleAnswers(scenario) {
        const examples = {
            login: [
                'Testar login com credenciais válidas',
                'Testar login com senha incorreta',
                'Testar login com usuário inexistente',
                'Testar campos obrigatórios vazios',
                'Testar limite de tentativas de login',
                'Testar caracteres especiais na senha'
            ],
            carrinho: [
                'Adicionar produto ao carrinho',
                'Remover produto do carrinho',
                'Alterar quantidade de produtos',
                'Carrinho vazio na finalização',
                'Produtos fora de estoque',
                'Cálculo correto de totais e fretes'
            ],
            cadastro: [
                'Cadastro com dados válidos',
                'CPF já cadastrado no sistema',
                'Email com formato inválido',
                'Campos obrigatórios não preenchidos',
                'Senha fraca ou não confirmada',
                'CEP inexistente ou inválido'
            ]
        };
        
        return examples[scenario] || [];
    }

    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}m ${remainingSeconds}s`;
    }

    showAlert(message, type = 'info') {
        // Criar alert temporário
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Remover após 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 5000);
    }

    // Funções globais expostas para uso nos botões HTML
    static showWelcomeScreen() {
        if (window.testeSoftwareApp) {
            window.testeSoftwareApp.showWelcomeScreen();
        }
    }

    static nextScenario(number) {
        if (window.testeSoftwareApp) {
            window.testeSoftwareApp.nextScenario(number);
        }
    }

    static previousScenario(number) {
        if (window.testeSoftwareApp) {
            window.testeSoftwareApp.previousScenario(number);
        }
    }

    static submitActivity() {
        if (window.testeSoftwareApp) {
            window.testeSoftwareApp.submitActivity();
        }
    }
}

// Funções globais para compatibilidade com HTML
function showWelcomeScreen() {
    TesteSoftware.showWelcomeScreen();
}

function nextScenario(number) {
    TesteSoftware.nextScenario(number);
}

function previousScenario(number) {
    TesteSoftware.previousScenario(number);
}

function submitActivity() {
    TesteSoftware.submitActivity();
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM carregado, inicializando Teste de Software...');
    window.testeSoftwareApp = new TesteSoftware();
});

// Backup das respostas antes de sair da página
window.addEventListener('beforeunload', (event) => {
    if (window.testeSoftwareApp && window.testeSoftwareApp.answers) {
        localStorage.setItem('testeSoftware_answers', JSON.stringify(window.testeSoftwareApp.answers));
    }
});

// Restaurar respostas se a página for recarregada
window.addEventListener('load', () => {
    setTimeout(() => {
        const savedAnswers = localStorage.getItem('testeSoftware_answers');
        if (savedAnswers && window.testeSoftwareApp) {
            try {
                const answers = JSON.parse(savedAnswers);
                window.testeSoftwareApp.answers = answers;
                
                // Restaurar nos textareas
                Object.keys(answers).forEach((key, index) => {
                    const textarea = document.getElementById(`answer${index + 1}`);
                    if (textarea && answers[key]) {
                        textarea.value = answers[key];
                    }
                });
                
                console.log('Respostas restauradas do backup local');
            } catch (error) {
                console.log('Erro ao restaurar respostas:', error);
            }
        }
    }, 1000);
});