/**
 * DER Quiz - Sistema Interativo de Modelagem ER
 * Script principal para gerenciamento do quiz
 */

class DERQuiz {
    constructor() {
        this.currentUser = null;
        this.questions = [];
        this.currentQuestionIndex = 0;
        this.userAnswers = [];
        this.timeRemaining = 0;
        this.timerInterval = null;
        this.startTime = null;
        this.endTime = null;
        this.suspiciousActivity = [];
        this.tabSwitchCount = 0;
        this.copyAttempts = 0;
        
        this.init();
    }

    async init() {
        try {
            await this.loadUser();
            await this.loadQuestions();
            this.setupEventListeners();
            this.setupMonitoring();
            this.hideLoadingScreen();
        } catch (error) {
            console.error('Erro na inicialização:', error);
            this.showError('Erro ao carregar o quiz. Redirecionando para o hub...');
            setTimeout(() => {
                window.location.href = '../index.html';
            }, 2000);
        }
    }

    async loadUser() {
        try {
            const response = await fetch('../auth/auth.php?check', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Cache-Control': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (result.authenticated && result.user) {
                this.currentUser = result.user;
                // Suportar diferentes formatos de nome
                const userName = result.user.name || 
                               `${result.user.first_name || ''} ${result.user.last_name || ''}`.trim() ||
                               result.user.username || 'Usuário';
                               
                document.getElementById('userInfo').textContent = userName;
            } else {
                throw new Error('Usuário não autenticado');
            }
        } catch (error) {
            console.error('Erro ao carregar usuário:', error);
            alert('Sessão inválida ou expirada. Redirecionando para o hub...');
            window.location.href = '../index.html';
        }
    }

    async loadQuestions() {
        try {
            const response = await fetch('questions.json', {
                headers: {
                    'Cache-Control': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Erro ao carregar questões: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Embaralhar e selecionar 20 questões aleatórias
            this.questions = this.shuffleArray(data.questions).slice(0, 20);
            
            // Embaralhar as opções de cada questão
            this.questions.forEach(question => {
                if (question.options) {
                    question.options = this.shuffleArray(question.options);
                }
            });
            
            document.getElementById('totalQuestions').textContent = this.questions.length;
        } catch (error) {
            console.error('Erro ao carregar questões:', error);
            throw new Error('Falha ao carregar questões do quiz');
        }
    }

    shuffleArray(array) {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    }

    setupEventListeners() {
        // Botão iniciar quiz
        document.getElementById('startQuiz').addEventListener('click', () => {
            this.startQuiz();
        });

        // Botão submeter resposta
        document.getElementById('submitAnswer').addEventListener('click', () => {
            this.submitAnswer();
        });

        // Botões da tela de resultados
        document.getElementById('tryAgain').addEventListener('click', () => {
            this.resetQuiz();
        });

        document.getElementById('viewDetails').addEventListener('click', () => {
            this.showDetailedResults();
        });

        // Modal de aviso
        document.getElementById('closeWarning').addEventListener('click', () => {
            this.hideWarning();
        });

        document.getElementById('warningOk').addEventListener('click', () => {
            this.hideWarning();
        });

        // Prevenir ações durante o quiz
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardEvent(e);
        });

        document.addEventListener('contextmenu', (e) => {
            if (this.isQuizActive()) {
                e.preventDefault();
                this.logSuspiciousActivity('Context menu attempt');
                this.showWarning('Menu de contexto desabilitado durante o quiz');
            }
        });

        // Detectar mudanças de foco
        window.addEventListener('blur', () => {
            if (this.isQuizActive()) {
                this.tabSwitchCount++;
                this.logSuspiciousActivity('Tab switch detected');
                
                if (this.tabSwitchCount > 2) {
                    this.showWarning('Evite alternar entre abas durante o quiz');
                }
            }
        });
    }

    setupMonitoring() {
        // Monitorar possível abertura de DevTools
        setInterval(() => {
            if (this.isQuizActive()) {
                const heightDiff = window.outerHeight - window.innerHeight;
                const widthDiff = window.outerWidth - window.innerWidth;
                
                if (heightDiff > 200 || widthDiff > 200) {
                    this.logSuspiciousActivity('Possible DevTools open');
                }
            }
        }, 2000);
    }

    handleKeyboardEvent(e) {
        if (this.isQuizActive()) {
            // Bloquear combinações suspeitas
            if (e.ctrlKey) {
                switch (e.key.toLowerCase()) {
                    case 'c':
                        this.copyAttempts++;
                        this.logSuspiciousActivity('Copy attempt');
                        break;
                    case 'v':
                        e.preventDefault();
                        this.logSuspiciousActivity('Paste attempt blocked');
                        this.showWarning('Colar texto não é permitido durante o quiz');
                        break;
                    case 'a':
                        e.preventDefault();
                        this.logSuspiciousActivity('Select all blocked');
                        break;
                    case 'f':
                        e.preventDefault();
                        this.logSuspiciousActivity('Find blocked');
                        break;
                }
                
                if (e.shiftKey && e.key === 'I') {
                    e.preventDefault();
                    this.logSuspiciousActivity('DevTools shortcut blocked');
                }
            }
            
            if (e.key === 'F12') {
                e.preventDefault();
                this.logSuspiciousActivity('F12 blocked');
            }
        }
    }

    logSuspiciousActivity(activity) {
        this.suspiciousActivity.push({
            activity,
            timestamp: new Date().toISOString(),
            question: this.currentQuestionIndex + 1
        });
    }

    isQuizActive() {
        return document.getElementById('quizScreen').classList.contains('active');
    }

    hideLoadingScreen() {
        setTimeout(() => {
            document.getElementById('loadingScreen').classList.add('hidden');
        }, 1500);
    }

    startQuiz() {
        this.startTime = new Date();
        this.currentQuestionIndex = 0;
        this.userAnswers = [];
        this.suspiciousActivity = [];
        this.tabSwitchCount = 0;
        this.copyAttempts = 0;
        
        this.showScreen('quizScreen');
        this.displayQuestion();
    }

    displayQuestion() {
        const question = this.questions[this.currentQuestionIndex];
        const questionNumber = this.currentQuestionIndex + 1;
        
        // Atualizar informações do progresso
        document.getElementById('currentQuestion').textContent = questionNumber;
        this.updateProgressBar();
        
        // Atualizar tipo e dificuldade
        document.getElementById('questionType').textContent = 
            question.type === 'conceptual' ? 'Conceitual' : 
            question.type === 'interactive_choice' ? 'Interativo' : 'Visual';
        document.getElementById('questionDifficulty').textContent = 
            this.getDifficultyLabel(question.difficulty);
        
        // Atualizar título da questão
        document.getElementById('questionTitle').textContent = question.title;
        
        // Mostrar conteúdo apropriado
        if (question.type === 'image') {
            this.displayImageQuestion(question);
        } else {
            this.displayTextQuestion(question);
        }
        
        // Gerar opções
        this.displayOptions(question.options);
        
        // Resetar botão de submit
        this.resetSubmitButton();
        
        // Iniciar timer
        this.startTimer(question.timeLimit || 30);
    }

    displayTextQuestion(question) {
        document.getElementById('textQuestion').style.display = 'block';
        document.getElementById('imageQuestion').style.display = 'none';
        document.getElementById('questionDescription').textContent = question.description;
    }

    displayImageQuestion(question) {
        document.getElementById('textQuestion').style.display = 'none';
        document.getElementById('imageQuestion').style.display = 'block';
        document.getElementById('questionImage').src = question.imagePath;
        document.getElementById('imageDescription').textContent = question.description;
    }

    displayOptions(options) {
        const container = document.getElementById('optionsContainer');
        container.innerHTML = '';
        
        options.forEach((option, index) => {
            const optionElement = this.createOptionElement(option, index);
            container.appendChild(optionElement);
        });
    }

    createOptionElement(option, index) {
        const div = document.createElement('div');
        div.className = 'option';
        div.dataset.value = option.id;
        
        div.innerHTML = `
            <input type="radio" name="answer" value="${option.id}" id="option${index}">
            <label for="option${index}" class="option-label">
                <span class="option-marker"></span>
                <span class="option-text">${option.text}</span>
            </label>
        `;
        
        div.addEventListener('click', () => {
            this.selectOption(div, option.id);
        });
        
        return div;
    }

    selectOption(optionElement, value) {
        // Remover seleção anterior
        document.querySelectorAll('.option').forEach(opt => {
            opt.classList.remove('selected');
        });
        
        // Selecionar nova opção
        optionElement.classList.add('selected');
        optionElement.querySelector('input').checked = true;
        
        // Habilitar botão de submit
        document.getElementById('submitAnswer').disabled = false;
        document.getElementById('actionHint').textContent = 'Confirme sua resposta';
    }

    resetSubmitButton() {
        const submitBtn = document.getElementById('submitAnswer');
        submitBtn.disabled = true;
        document.getElementById('actionHint').textContent = 'Selecione uma opção';
    }

    startTimer(seconds) {
        this.timeRemaining = seconds;
        this.updateTimer();
        
        this.timerInterval = setInterval(() => {
            this.timeRemaining--;
            this.updateTimer();
            
            if (this.timeRemaining <= 0) {
                this.timeUp();
            }
        }, 1000);
    }

    updateTimer() {
        const timerElement = document.getElementById('timer');
        timerElement.textContent = `${this.timeRemaining}s`;
        
        // Aplicar classes de aviso
        timerElement.className = 'timer';
        if (this.timeRemaining <= 10) {
            timerElement.classList.add('danger');
        } else if (this.timeRemaining <= 20) {
            timerElement.classList.add('warning');
        }
    }

    timeUp() {
        clearInterval(this.timerInterval);
        
        // Registrar resposta vazia se nenhuma foi selecionada
        const selectedOption = document.querySelector('input[name="answer"]:checked');
        const answer = selectedOption ? selectedOption.value : null;
        
        this.recordAnswer(answer, true); // true = timeout
        this.nextQuestion();
    }

    submitAnswer() {
        const selectedOption = document.querySelector('input[name="answer"]:checked');
        
        if (!selectedOption) {
            this.showWarning('Por favor, selecione uma resposta');
            return;
        }
        
        clearInterval(this.timerInterval);
        this.recordAnswer(selectedOption.value, false);
        this.nextQuestion();
    }

    recordAnswer(answer, wasTimeout) {
        const question = this.questions[this.currentQuestionIndex];
        const timeSpent = (question.timeLimit || 30) - this.timeRemaining;
        
        this.userAnswers.push({
            questionId: question.id,
            questionTitle: question.title,
            selectedAnswer: answer,
            correctAnswer: question.correctAnswer,
            isCorrect: answer === question.correctAnswer,
            timeSpent: timeSpent,
            wasTimeout: wasTimeout,
            timestamp: new Date().toISOString()
        });
    }

    nextQuestion() {
        this.currentQuestionIndex++;
        
        if (this.currentQuestionIndex < this.questions.length) {
            setTimeout(() => {
                this.displayQuestion();
            }, 500);
        } else {
            this.finishQuiz();
        }
    }

    updateProgressBar() {
        const progress = ((this.currentQuestionIndex + 1) / this.questions.length) * 100;
        document.getElementById('progressFill').style.width = `${progress}%`;
    }

    async finishQuiz() {
        this.endTime = new Date();
        await this.saveResults();
        this.showResults();
    }

    async saveResults() {
        if (!this.currentUser) {
            console.error('Usuário não encontrado');
            return;
        }

        const results = {
            user_id: this.currentUser.id,
            name: this.currentUser.name || `${this.currentUser.first_name || ''} ${this.currentUser.last_name || ''}`.trim(),
            email: this.currentUser.email,
            registration: this.currentUser.registration,
            course: this.currentUser.course,
            startTime: this.startTime.toISOString(),
            endTime: this.endTime.toISOString(),
            totalQuestions: this.questions.length,
            correctAnswers: this.userAnswers.filter(a => a.isCorrect).length,
            answers: this.userAnswers,
            suspiciousActivity: this.suspiciousActivity,
            tabSwitchCount: this.tabSwitchCount,
            copyAttempts: this.copyAttempts
        };
        
        try {
            const response = await fetch('save_data.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Cache-Control': 'no-cache'
                },
                body: JSON.stringify(results)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const result = await response.json();
            
            if (!result.success) {
                console.error('Erro ao salvar:', result.message);
                this.showWarning('Aviso: Resultados podem não ter sido salvos corretamente.');
            }
        } catch (error) {
            console.error('Erro na requisição:', error);
            this.showWarning('Erro de conexão. Resultados podem não ter sido salvos.');
        }
    }

    showResults() {
        this.showScreen('resultsScreen');
        
        const correctCount = this.userAnswers.filter(a => a.isCorrect).length;
        const totalTime = this.endTime - this.startTime;
        const finalScore = Math.round((correctCount / this.questions.length) * 100);
        
        // Atualizar elementos da tela de resultados
        document.getElementById('finalScore').textContent = finalScore;
        document.getElementById('correctAnswers').textContent = 
            `${correctCount}/${this.questions.length}`;
        document.getElementById('totalTime').textContent = 
            this.formatTime(totalTime);
        document.getElementById('averageTime').textContent = 
            this.formatTime(totalTime / this.questions.length);
        
        // Mostrar análise de performance
        this.showPerformanceAnalysis(finalScore, correctCount);
    }

    showPerformanceAnalysis(score, correct) {
        const analysisContainer = document.getElementById('performanceAnalysis');
        let analysis = '';
        
        if (score >= 90) {
            analysis = '<h3>🏆 Excelente!</h3><p>Você demonstrou domínio excepcional em Modelagem ER. Parabéns!</p>';
        } else if (score >= 80) {
            analysis = '<h3>✅ Muito Bom!</h3><p>Você tem um bom entendimento de Modelagem ER. Continue praticando!</p>';
        } else if (score >= 70) {
            analysis = '<h3>👍 Bom!</h3><p>Você está no caminho certo. Revise alguns conceitos e tente novamente.</p>';
        } else if (score >= 60) {
            analysis = '<h3>⚠️ Regular</h3><p>É importante revisar os conceitos fundamentais de DER.</p>';
        } else {
            analysis = '<h3>📚 Precisa Estudar</h3><p>Recomendo revisar o material antes de tentar novamente.</p>';
        }
        
        // Adicionar estatísticas de suspeita se houver
        if (this.suspiciousActivity.length > 5 || this.tabSwitchCount > 3) {
            analysis += '<div style="margin-top: 1rem; padding: 1rem; background: #fff3cd; border-radius: 0.5rem; border: 1px solid #ffeaa7;"><strong>⚠️ Atividade Suspeita Detectada</strong><br>Este resultado pode ser revisado manualmente.</div>';
        }
        
        analysisContainer.innerHTML = analysis;
    }

    formatTime(ms) {
        const seconds = Math.floor(ms / 1000);
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        
        if (minutes > 0) {
            return `${minutes}m ${remainingSeconds}s`;
        } else {
            return `${remainingSeconds}s`;
        }
    }

    resetQuiz() {
        this.currentQuestionIndex = 0;
        this.userAnswers = [];
        this.suspiciousActivity = [];
        this.tabSwitchCount = 0;
        this.copyAttempts = 0;
        
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
        
        this.showScreen('welcomeScreen');
    }

    showDetailedResults() {
        // Implementar modal ou página com resultados detalhados
        alert('Funcionalidade em desenvolvimento: Resultados detalhados');
    }

    showScreen(screenId) {
        document.querySelectorAll('.screen').forEach(screen => {
            screen.classList.remove('active');
        });
        document.getElementById(screenId).classList.add('active');
    }

    showWarning(message) {
        document.getElementById('warningMessage').textContent = message;
        document.getElementById('warningModal').classList.add('active');
    }

    hideWarning() {
        document.getElementById('warningModal').classList.remove('active');
    }

    showError(message) {
        alert(`Erro: ${message}`);
    }

    getDifficultyLabel(difficulty) {
        const labels = {
            'easy': 'Fácil',
            'medium': 'Médio',
            'hard': 'Difícil'
        };
        return labels[difficulty] || 'Médio';
    }
}

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    new DERQuiz();
});

// Prevenir fechamento acidental durante o quiz
window.addEventListener('beforeunload', (e) => {
    if (document.getElementById('quizScreen').classList.contains('active')) {
        e.preventDefault();
        e.returnValue = 'Tem certeza que deseja sair? Seu progresso será perdido.';
        return e.returnValue;
    }
});