/**
 * Laboratório de R&S - Sistema Interativo de Excel para RH
 * Script principal para gerenciamento do laboratório - VERSÃO CORRIGIDA
 */

class RSLab {
    constructor() {
        this.currentUser = null;
        this.scenarios = [];
        this.questions = [];
        this.currentQuestionIndex = 0;
        this.userAnswers = [];
        this.timeRemaining = 0;
        this.timerInterval = null;
        this.startTime = null;
        this.endTime = null;
        this.formulasUsed = {
            'SOMA': { count: 0, correct: 0 },
            'SE': { count: 0, correct: 0 },
            'PROCV': { count: 0, correct: 0 },
            'MEDIA': { count: 0, correct: 0 }
        };
        this.currentTableData = null;
        this.selectedCell = null;
        
        this.init();
    }

    async init() {
        try {
            await this.loadUser();
            await this.loadScenarios();
            await this.loadQuestions();
            this.setupEventListeners();
            this.hideLoadingScreen();
        } catch (error) {
            console.error('Erro na inicialização:', error);
            this.showError('Erro ao carregar o laboratório. Redirecionando para o hub...');
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

    async loadScenarios() {
        try {
            const response = await fetch('scenarios.json', {
                headers: {
                    'Cache-Control': 'no-cache'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Erro ao carregar cenários: ${response.status}`);
            }
            
            const data = await response.json();
            this.scenarios = data.scenarios;
        } catch (error) {
            console.error('Erro ao carregar cenários:', error);
            throw new Error('Falha ao carregar dados do laboratório');
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
            this.questions = data.questions;
            
            document.getElementById('totalQuestions').textContent = this.questions.length;
        } catch (error) {
            console.error('Erro ao carregar questões:', error);
            throw new Error('Falha ao carregar questões do laboratório');
        }
    }

    setupEventListeners() {
        // Botão iniciar laboratório
        document.getElementById('startLab').addEventListener('click', () => {
            this.startLab();
        });

        // Botão submeter resposta
        document.getElementById('submitAnswer').addEventListener('click', () => {
            this.submitAnswer();
        });

        // Campo de resposta
        document.getElementById('userAnswer').addEventListener('input', (e) => {
            this.validateFormulaInput(e.target.value);
        });

        // Botão de dica
        document.getElementById('showHint').addEventListener('click', () => {
            this.showHint();
        });

        // Botões da tela de resultados
        document.getElementById('tryAgain').addEventListener('click', () => {
            this.resetLab();
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

        // Campo de fórmula no toolbar
        document.getElementById('formulaInput').addEventListener('focus', () => {
            const userAnswer = document.getElementById('userAnswer');
            userAnswer.focus();
        });

        // Eventos de teclado
        document.addEventListener('keydown', (e) => {
            if (this.isLabActive()) {
                this.handleKeyboardEvent(e);
            }
        });

        // Detectar mudanças de foco (controle de integridade)
        window.addEventListener('blur', () => {
            if (this.isLabActive()) {
                this.logSuspiciousActivity('Tab switch detected');
            }
        });
    }

    hideLoadingScreen() {
        setTimeout(() => {
            document.getElementById('loadingScreen').classList.add('hidden');
        }, 1500);
    }

    startLab() {
        this.startTime = new Date();
        this.currentQuestionIndex = 0;
        this.userAnswers = [];
        this.formulasUsed = {
            'SOMA': { count: 0, correct: 0 },
            'SE': { count: 0, correct: 0 },
            'PROCV': { count: 0, correct: 0 },
            'MEDIA': { count: 0, correct: 0 }
        };
        
        this.showScreen('labScreen');
        this.displayQuestion();
    }

    displayQuestion() {
        const question = this.questions[this.currentQuestionIndex];
        const questionNumber = this.currentQuestionIndex + 1;
        
        // Atualizar informações do progresso
        document.getElementById('currentQuestion').textContent = questionNumber;
        this.updateProgressBar();
        
        // Atualizar detalhes da questão
        document.getElementById('questionType').textContent = this.getTypeLabel(question.type);
        document.getElementById('questionDifficulty').textContent = this.getDifficultyLabel(question.difficulty);
        document.getElementById('questionTitle').textContent = question.title;
        document.getElementById('questionDescription').textContent = question.description;
        document.getElementById('questionInstruction').textContent = question.instruction;
        
        // Gerar planilha Excel para a questão
        this.generateExcelTable(question);
        
        // Resetar interface
        this.resetAnswerInterface();
        
        // Iniciar timer
        this.startTimer(question.time_limit || 180);
    }

    generateExcelTable(question) {
        const tableBody = document.getElementById('excelTableBody');
        const sheetTitle = document.getElementById('sheetTitle');
        
        // Atualizar título da planilha
        sheetTitle.textContent = `Planilha: ${question.title}`;
        
        // Limpar tabela anterior
        tableBody.innerHTML = '';
        
        // Obter dados da tabela
        const tableData = question.table_data;
        this.currentTableData = tableData;
        
        if (tableData.reference_table) {
            // Questão com tabela de referência (PROCV)
            this.generateReferenceTable(tableData.reference_table, tableBody);
        } else {
            // Questão normal
            this.generateNormalTable(tableData, tableBody);
        }
        
        // Atualizar barra de fórmulas se houver instrução
        if (tableData.formula_instruction) {
            document.getElementById('formulaInput').placeholder = tableData.formula_instruction;
        }
    }

    generateNormalTable(tableData, tableBody) {
        // Cabeçalhos
        if (tableData.headers) {
            const headerRow = document.createElement('tr');
            tableData.headers.forEach(header => {
                const th = document.createElement('td');
                th.textContent = header;
                th.style.fontWeight = 'bold';
                th.style.backgroundColor = '#f8f9fa';
                headerRow.appendChild(th);
            });
            tableBody.appendChild(headerRow);
        }
        
        // Dados
        if (tableData.rows) {
            tableData.rows.forEach((row, rowIndex) => {
                const tr = document.createElement('tr');
                row.forEach((cell, cellIndex) => {
                    const td = document.createElement('td');
                    td.textContent = cell;
                    td.dataset.row = rowIndex;
                    td.dataset.col = cellIndex;
                    
                    // Célula de destino para fórmula
                    if (tableData.target_cell) {
                        const targetCell = this.parseExcelCell(tableData.target_cell);
                        if (targetCell.row === rowIndex && targetCell.col === cellIndex) {
                            td.classList.add('formula-result');
                            td.textContent = '?';
                        }
                    }
                    
                    td.addEventListener('click', () => this.selectCell(td));
                    tr.appendChild(td);
                });
                tableBody.appendChild(tr);
            });
        }
    }

    generateReferenceTable(refTable, tableBody) {
        // Título da tabela de referência
        const titleRow = document.createElement('tr');
        const titleCell = document.createElement('td');
        titleCell.colSpan = refTable.headers.length;
        titleCell.textContent = refTable.name;
        titleCell.style.fontWeight = 'bold';
        titleCell.style.textAlign = 'center';
        titleCell.style.backgroundColor = '#e3f2fd';
        titleRow.appendChild(titleCell);
        tableBody.appendChild(titleRow);
        
        // Cabeçalhos
        const headerRow = document.createElement('tr');
        refTable.headers.forEach(header => {
            const th = document.createElement('td');
            th.textContent = header;
            th.style.fontWeight = 'bold';
            th.style.backgroundColor = '#f8f9fa';
            headerRow.appendChild(th);
        });
        tableBody.appendChild(headerRow);
        
        // Dados da tabela de referência
        refTable.rows.forEach((row, rowIndex) => {
            const tr = document.createElement('tr');
            row.forEach((cell, cellIndex) => {
                const td = document.createElement('td');
                td.textContent = cell;
                td.dataset.row = rowIndex + 1; // +1 por causa do header
                td.dataset.col = cellIndex;
                td.addEventListener('click', () => this.selectCell(td));
                tr.appendChild(td);
            });
            tableBody.appendChild(tr);
        });
    }

    selectCell(cell) {
        // Remover seleção anterior
        if (this.selectedCell) {
            this.selectedCell.classList.remove('selected');
        }
        
        // Selecionar nova célula
        cell.classList.add('selected');
        this.selectedCell = cell;
        
        // Atualizar barra de fórmulas
        const cellRef = this.getCellReference(cell);
        document.getElementById('formulaInput').value = `Célula ${cellRef}: ${cell.textContent}`;
    }

    getCellReference(cell) {
        const col = parseInt(cell.dataset.col);
        const row = parseInt(cell.dataset.row) + 1; // +1 para Excel (começa em 1)
        const colLetter = String.fromCharCode(65 + col); // A, B, C...
        return `${colLetter}${row}`;
    }

    parseExcelCell(cellRef) {
        // Converter referência como "A1" para {row: 0, col: 0}
        const match = cellRef.match(/([A-Z]+)(\d+)/);
        if (match) {
            const col = match[1].charCodeAt(0) - 65;
            const row = parseInt(match[2]) - 1;
            return { row, col };
        }
        return { row: 0, col: 0 };
    }

    validateFormulaInput(formula) {
        const submitBtn = document.getElementById('submitAnswer');
        const actionHint = document.getElementById('actionHint');
        
        if (formula.trim() === '') {
            submitBtn.disabled = true;
            actionHint.textContent = 'Digite uma fórmula válida';
            return;
        }
        
        // Validação básica de fórmula Excel
        if (formula.startsWith('=')) {
            submitBtn.disabled = false;
            actionHint.textContent = 'Fórmula detectada - pode confirmar';
        } else {
            submitBtn.disabled = true;
            actionHint.textContent = 'Fórmulas devem começar com =';
        }
        
        // Atualizar barra de fórmulas
        document.getElementById('formulaInput').value = formula;
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
        } else if (this.timeRemaining <= 30) {
            timerElement.classList.add('warning');
        }
    }

    timeUp() {
        clearInterval(this.timerInterval);
        
        const userFormula = document.getElementById('userAnswer').value;
        this.recordAnswer(userFormula, true); // true = timeout
        this.nextQuestion();
    }

    submitAnswer() {
        const userFormula = document.getElementById('userAnswer').value;
        
        if (!userFormula.trim()) {
            this.showWarning('Por favor, digite uma fórmula válida');
            return;
        }
        
        clearInterval(this.timerInterval);
        this.recordAnswer(userFormula, false);
        this.nextQuestion();
    }

    recordAnswer(userFormula, wasTimeout) {
        const question = this.questions[this.currentQuestionIndex];
        const timeSpent = (question.time_limit || 180) - this.timeRemaining;
        
        // Validar resposta
        const validation = this.validateAnswer(userFormula, question);
        
        // Rastrear uso de fórmulas
        this.trackFormulaUsage(question.formula_type, validation.isCorrect);
        
        this.userAnswers.push({
            questionId: question.id,
            questionTitle: question.title,
            formulaType: question.formula_type,
            userFormula: userFormula,
            expectedFormula: question.expected_formula,
            userResult: validation.userResult,
            expectedResult: question.expected_result,
            isCorrect: validation.isCorrect,
            timeSpent: timeSpent,
            wasTimeout: wasTimeout,
            pointsEarned: validation.isCorrect ? question.points : 0,
            timestamp: new Date().toISOString()
        });
    }

    validateAnswer(userFormula, question) {
        const result = {
            isCorrect: false,
            userResult: null,
            explanation: ''
        };
        
        // Normalizar fórmulas para comparação
        const normalizedUser = this.normalizeFormula(userFormula);
        const normalizedExpected = this.normalizeFormula(question.expected_formula);
        
        // Validação por tipo
        switch (question.validation_type) {
            case 'formula_structure':
                result.isCorrect = this.compareFormulaStructure(normalizedUser, normalizedExpected);
                break;
            case 'result_value':
                result.userResult = this.calculateFormulaResult(userFormula, question);
                result.isCorrect = Math.abs(result.userResult - question.expected_result) < 0.01;
                break;
            case 'both':
                const structureMatch = this.compareFormulaStructure(normalizedUser, normalizedExpected);
                result.userResult = this.calculateFormulaResult(userFormula, question);
                const resultMatch = Math.abs(result.userResult - question.expected_result) < 0.01;
                result.isCorrect = structureMatch && resultMatch; // CORRIGIDO: AND em vez de OR
                break;
            case 'concept':
                // Para questões conceituais complexas, validar principalmente estrutura
                result.isCorrect = this.compareFormulaStructure(normalizedUser, normalizedExpected);
                break;
            default:
                result.isCorrect = normalizedUser === normalizedExpected;
        }
        
        return result;
    }

    normalizeFormula(formula) {
        return formula
            .toUpperCase()
            .replace(/\s+/g, '')
            .replace(/;/g, ','); // Excel brasileiro usa ; em vez de ,
    }

    compareFormulaStructure(user, expected) {
        // Comparação exata primeiro
        if (user === expected) {
            return true;
        }
        
        // Comparação flexível para estruturas similares
        const userPattern = user.replace(/[0-9]+/g, 'X').replace(/[A-Z]+[0-9]+/g, 'CELL');
        const expectedPattern = expected.replace(/[0-9]+/g, 'X').replace(/[A-Z]+[0-9]+/g, 'CELL');
        
        return userPattern === expectedPattern;
    }

    calculateFormulaResult(formula, question) {
        // FUNÇÃO CORRIGIDA - Simulação rigorosa de cálculo Excel
        try {
            const normalized = this.normalizeFormula(formula);
            const questionType = question.formula_type;
            
            // CORREÇÃO PRINCIPAL: Verificar se tipo de fórmula corresponde ao tipo da questão
            
            if (normalized.includes('SOMA') || normalized.includes('SUM')) {
                // Só aceitar SOMA em questões tipo SOMA
                if (questionType === 'SOMA') {
                    return this.simulateSum(formula, question);
                }
                // Rejeitar fórmula SOMA em questões de outros tipos
                return -999999;
            }
            
            if (normalized.includes('MEDIA') || normalized.includes('AVERAGE')) {
                // Só aceitar MÉDIA em questões tipo MEDIA
                if (questionType === 'MEDIA') {
                    return this.simulateAverage(formula, question);
                }
                // Rejeitar fórmula MÉDIA em questões de outros tipos
                return -999999;
            }
            
            if (normalized.includes('PROCV') || normalized.includes('VLOOKUP')) {
                // Só aceitar PROCV em questões tipo PROCV
                if (questionType === 'PROCV') {
                    return this.simulateProcv(formula, question);
                }
                // Rejeitar fórmula PROCV em questões de outros tipos
                return -999999;
            }
            
            if (normalized.includes('SE') || normalized.includes('IF')) {
                // Só aceitar SE em questões tipo SE
                if (questionType === 'SE') {
                    return this.simulateSe(formula, question);
                }
                // Rejeitar fórmula SE em questões de outros tipos
                return -999999;
            }
            
            // Para questões complexas (COMBINED), permitir múltiplos tipos
            if (questionType === 'COMBINED') {
                // Questões complexas podem usar qualquer fórmula
                if (normalized.includes('SOMA') || normalized.includes('SUM')) {
                    return this.simulateSum(formula, question);
                }
                if (normalized.includes('MEDIA') || normalized.includes('AVERAGE')) {
                    return this.simulateAverage(formula, question);
                }
                if (normalized.includes('PROCV') || normalized.includes('VLOOKUP')) {
                    return this.simulateProcv(formula, question);
                }
                if (normalized.includes('SE') || normalized.includes('IF')) {
                    return this.simulateSe(formula, question);
                }
            }
            
            // Fórmula não reconhecida ou tipo incompatível
            return -999999;
            
        } catch (error) {
            return -999999; // Valor de erro
        }
    }

    simulateSum(formula, question) {
        // Simular SOMA apenas se a estrutura da fórmula estiver correta
        const normalized = this.normalizeFormula(formula);
        
        // Verificar se tem estrutura básica correta de SOMA
        if (normalized.includes('SOMA(') && normalized.includes(':')) {
            // Simular soma baseada nos dados da tabela
            if (question.table_data && question.table_data.rows) {
                const numericValues = question.table_data.rows
                    .flat()
                    .map(v => parseFloat(v.replace(/[^\d.-]/g, '')))
                    .filter(v => !isNaN(v));
                return numericValues.reduce((sum, val) => sum + val, 0);
            }
            return question.expected_result;
        }
        
        return -999999; // Estrutura incorreta
    }

    simulateAverage(formula, question) {
        // Simular MÉDIA apenas se a estrutura da fórmula estiver correta
        const normalized = this.normalizeFormula(formula);
        
        if (normalized.includes('MEDIA(') && normalized.includes(':')) {
            if (question.table_data && question.table_data.rows) {
                const numericValues = question.table_data.rows
                    .flat()
                    .map(v => parseFloat(v.replace(/[^\d.-]/g, '')))
                    .filter(v => !isNaN(v));
                return numericValues.length > 0 ? 
                       numericValues.reduce((sum, val) => sum + val, 0) / numericValues.length : 0;
            }
            return question.expected_result;
        }
        
        return -999999; // Estrutura incorreta
    }

    simulateProcv(formula, question) {
        // Simular PROCV apenas se a estrutura da fórmula estiver correta
        const normalized = this.normalizeFormula(formula);
        
        // Verificar estrutura básica: PROCV("valor";tabela;coluna;FALSO)
        if (normalized.includes('PROCV(') && 
            normalized.includes('"') && 
            normalized.includes(';') && 
            normalized.includes('FALSO')) {
            
            // Se a estrutura estiver correta, retornar resultado esperado
            return question.expected_result;
        }
        
        return -999999; // Estrutura incorreta
    }

    simulateSe(formula, question) {
        // Simular SE apenas se a estrutura da fórmula estiver correta
        const normalized = this.normalizeFormula(formula);
        
        // Verificar estrutura básica: SE(condição;"valor1";"valor2")
        if (normalized.includes('SE(') && 
            normalized.includes(';') && 
            (normalized.includes('"') || normalized.includes('>'))) {
            
            // Para SE, validar principalmente pela estrutura
            // Retornar resultado esperado se estrutura básica estiver correta
            return question.expected_result;
        }
        
        return -999999; // Estrutura incorreta
    }

    trackFormulaUsage(formulaType, isCorrect) {
        if (this.formulasUsed[formulaType]) {
            this.formulasUsed[formulaType].count++;
            if (isCorrect) {
                this.formulasUsed[formulaType].correct++;
            }
        }
    }

    showHint() {
        const question = this.questions[this.currentQuestionIndex];
        if (question.hints && question.hints.length > 0) {
            const randomHint = question.hints[Math.floor(Math.random() * question.hints.length)];
            this.showWarning(randomHint);
        } else {
            this.showWarning('Nenhuma dica disponível para esta questão.');
        }
    }

    nextQuestion() {
        this.currentQuestionIndex++;
        
        if (this.currentQuestionIndex < this.questions.length) {
            setTimeout(() => {
                this.displayQuestion();
            }, 1000);
        } else {
            this.finishLab();
        }
    }

    updateProgressBar() {
        const progress = ((this.currentQuestionIndex + 1) / this.questions.length) * 100;
        document.getElementById('progressFill').style.width = `${progress}%`;
    }

    async finishLab() {
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
            scenario_id: 'techcorp_2024',
            startTime: this.startTime.toISOString(),
            endTime: this.endTime.toISOString(),
            totalQuestions: this.questions.length,
            correctAnswers: this.userAnswers.filter(a => a.isCorrect).length,
            answers: this.userAnswers,
            formulasUsed: this.formulasUsed
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
        const finalScore = this.calculateFinalScore();
        
        // Atualizar elementos da tela de resultados
        document.getElementById('finalScore').textContent = finalScore;
        document.getElementById('correctAnswers').textContent = 
            `${correctCount}/${this.questions.length}`;
        document.getElementById('totalTime').textContent = 
            this.formatTime(totalTime);
        
        // Mostrar performance por fórmula
        this.updateFormulaScores();
        
        // Mostrar análise de performance
        this.showPerformanceAnalysis(finalScore, correctCount);
    }

    calculateFinalScore() {
        const totalPoints = this.userAnswers.reduce((sum, answer) => sum + answer.pointsEarned, 0);
        const maxPoints = this.questions.reduce((sum, question) => sum + question.points, 0);
        return Math.round((totalPoints / maxPoints) * 100);
    }

    updateFormulaScores() {
        Object.keys(this.formulasUsed).forEach(formula => {
            const data = this.formulasUsed[formula];
            const percentage = data.count > 0 ? Math.round((data.correct / data.count) * 100) : 0;
            const elementId = formula.toLowerCase() + 'Score';
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = `${percentage}%`;
            }
        });
    }

    showPerformanceAnalysis(score, correct) {
        const analysisContainer = document.getElementById('performanceAnalysis');
        let analysis = '';
        
        if (score >= 85) {
            analysis = '<h3>Excelente Desempenho!</h3><p>Você demonstrou domínio excepcional das fórmulas Excel aplicadas ao RH. Suas habilidades estão prontas para o mercado de trabalho!</p>';
        } else if (score >= 70) {
            analysis = '<h3>Bom Desempenho!</h3><p>Você tem uma boa base nas fórmulas Excel para RH. Continue praticando as fórmulas que tiveram menor aproveitamento.</p>';
        } else if (score >= 50) {
            analysis = '<h3>Desempenho Regular</h3><p>Você está no caminho certo, mas é importante revisar os conceitos e praticar mais as fórmulas Excel.</p>';
        } else {
            analysis = '<h3>Precisa de Mais Estudo</h3><p>Recomendo revisar os conceitos básicos de Excel e praticar mais antes de tentar novamente.</p>';
        }
        
        // Adicionar recomendações específicas por fórmula
        const weakFormulas = Object.keys(this.formulasUsed).filter(formula => {
            const data = this.formulasUsed[formula];
            return data.count > 0 && (data.correct / data.count) < 0.7;
        });
        
        if (weakFormulas.length > 0) {
            analysis += `<p><strong>Foque nestas fórmulas:</strong> ${weakFormulas.join(', ')}</p>`;
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

    resetAnswerInterface() {
        document.getElementById('userAnswer').value = '';
        document.getElementById('formulaInput').value = '';
        document.getElementById('submitAnswer').disabled = true;
        document.getElementById('actionHint').textContent = 'Digite uma fórmula válida';
        
        // Remover seleção de células
        if (this.selectedCell) {
            this.selectedCell.classList.remove('selected');
            this.selectedCell = null;
        }
    }

    resetLab() {
        this.currentQuestionIndex = 0;
        this.userAnswers = [];
        this.formulasUsed = {
            'SOMA': { count: 0, correct: 0 },
            'SE': { count: 0, correct: 0 },
            'PROCV': { count: 0, correct: 0 },
            'MEDIA': { count: 0, correct: 0 }
        };
        
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }
        
        this.showScreen('welcomeScreen');
    }

    showDetailedResults() {
        // Implementar modal ou página com resultados detalhados
        alert('Funcionalidade em desenvolvimento: Resultados detalhados por questão');
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

    getTypeLabel(type) {
        const labels = {
            'calculation': 'Cálculo',
            'formula': 'Fórmula',
            'lookup': 'Busca',
            'analysis': 'Análise',
            'complex': 'Complexo'
        };
        return labels[type] || 'Questão';
    }

    getDifficultyLabel(difficulty) {
        const labels = {
            'easy': 'Fácil',
            'medium': 'Médio', 
            'hard': 'Difícil'
        };
        return labels[difficulty] || 'Médio';
    }

    handleKeyboardEvent(e) {
        // Bloquear algumas combinações durante o laboratório
        if (e.ctrlKey && (e.key === 'c' || e.key === 'v')) {
            // Permitir copiar/colar no campo de resposta
            const activeElement = document.activeElement;
            if (activeElement.id !== 'userAnswer') {
                e.preventDefault();
                this.logSuspiciousActivity('Copy/paste attempt outside answer field');
            }
        }
        
        if (e.key === 'F12') {
            e.preventDefault();
            this.logSuspiciousActivity('F12 blocked');
        }
    }

    logSuspiciousActivity(activity) {
        console.log(`Suspicious activity: ${activity}`);
        // Log para análise posterior
    }

    isLabActive() {
        return document.getElementById('labScreen').classList.contains('active');
    }
}

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    new RSLab();
});

// Prevenir fechamento acidental durante o laboratório
window.addEventListener('beforeunload', (e) => {
    if (document.getElementById('labScreen').classList.contains('active')) {
        e.preventDefault();
        e.returnValue = 'Tem certeza que deseja sair? Seu progresso será perdido.';
        return e.returnValue;
    }
});