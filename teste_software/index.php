<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Software - Aula e Atividade Interativa</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status"></div>
            <h4 class="mt-3">Carregando Teste de Software...</h4>
            <p>Preparando conteúdo educativo e atividade prática</p>
        </div>
    </div>

    <!-- Tela de Boas-vindas e Conteúdo Educativo -->
    <div id="welcomeScreen" class="screen active">
        <div class="container-fluid">
            <!-- Header -->
            <div class="header-section">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-4 fw-bold text-white">
                                <i class="fas fa-bug me-3"></i>
                                Teste de Software
                            </h1>
                            <p class="lead text-white-50 mb-0">
                                Aprenda os fundamentos e pratique casos reais de teste
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="../index.html" class="btn btn-outline-light">
                                <i class="fas fa-home me-2"></i>Voltar ao Hub
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conteúdo Educativo -->
            <div class="container my-5">
                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <i class="fas fa-info-circle fa-2x me-3"></i>
                            <div>
                                <h5 class="alert-heading mb-1">Antes de começar a prática...</h5>
                                <p class="mb-0">Estude o conteúdo abaixo e depois clique em "Iniciar Atividade" para aplicar o conhecimento.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cards de Conteúdo Educativo -->
                <div class="row g-4 mb-5">
                    <!-- Card 1: O que é Teste de Software -->
                    <div class="col-lg-6">
                        <div class="education-card h-100">
                            <div class="card-header">
                                <h4><i class="fas fa-question-circle text-primary me-2"></i>O que é Teste de Software?</h4>
                            </div>
                            <div class="card-body">
                                <p>O teste de software é um processo sistemático de avaliação e verificação que tem como objetivo:</p>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">🎯 <strong>Detectar defeitos</strong> antes do usuário final</li>
                                    <li class="list-group-item">✅ <strong>Verificar</strong> se o software atende aos requisitos</li>
                                    <li class="list-group-item">🛡️ <strong>Garantir qualidade</strong> e confiabilidade</li>
                                    <li class="list-group-item">📊 <strong>Validar</strong> o comportamento esperado</li>
                                </ul>
                                <div class="alert alert-light mt-3">
                                    <strong>Importante:</strong> Testar não é apenas "ver se funciona" - é um processo planejado e estruturado!
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Erro, Defeito e Falha -->
                    <div class="col-lg-6">
                        <div class="education-card h-100">
                            <div class="card-header">
                                <h4><i class="fas fa-exclamation-triangle text-warning me-2"></i>Erro, Defeito e Falha</h4>
                            </div>
                            <div class="card-body">
                                <p>É fundamental distinguir estes três conceitos:</p>
                                <div class="concept-item mb-3">
                                    <h6 class="text-danger"><i class="fas fa-user me-2"></i>ERRO (Error)</h6>
                                    <p class="ms-4">Ação humana incorreta durante desenvolvimento</p>
                                </div>
                                <div class="concept-item mb-3">
                                    <h6 class="text-warning"><i class="fas fa-code me-2"></i>DEFEITO (Bug/Defect)</h6>
                                    <p class="ms-4">Imperfeição no código que pode causar falha</p>
                                </div>
                                <div class="concept-item mb-3">
                                    <h6 class="text-info"><i class="fas fa-times-circle me-2"></i>FALHA (Failure)</h6>
                                    <p class="ms-4">Desvio do comportamento esperado do software</p>
                                </div>
                                <div class="alert alert-primary mt-3">
                                    <strong>Fluxo:</strong> Erro → gera → Defeito → pode causar → Falha
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Tipos Básicos de Teste -->
                    <div class="col-lg-6">
                        <div class="education-card h-100">
                            <div class="card-header">
                                <h4><i class="fas fa-layer-group text-success me-2"></i>Tipos Básicos de Teste</h4>
                            </div>
                            <div class="card-body">
                                <div class="test-type mb-3">
                                    <h6 class="text-success"><i class="fas fa-eye me-2"></i>Teste Funcional</h6>
                                    <p class="ms-4 mb-2">Verifica se o software faz <strong>o que deveria fazer</strong></p>
                                    <small class="text-muted ms-4">Ex: Login, cadastro, cálculos</small>
                                </div>
                                <div class="test-type mb-3">
                                    <h6 class="text-info"><i class="fas fa-tachometer-alt me-2"></i>Teste Não-Funcional</h6>
                                    <p class="ms-4 mb-2">Verifica <strong>como</strong> o software faz</p>
                                    <small class="text-muted ms-4">Ex: Performance, segurança, usabilidade</small>
                                </div>
                                <div class="test-type mb-3">
                                    <h6 class="text-primary"><i class="fas fa-hand-pointer me-2"></i>Teste Manual</h6>
                                    <p class="ms-4 mb-2">Executado por <strong>pessoas</strong></p>
                                </div>
                                <div class="test-type">
                                    <h6 class="text-secondary"><i class="fas fa-robot me-2"></i>Teste Automatizado</h6>
                                    <p class="ms-4 mb-2">Executado por <strong>ferramentas</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Por que testar? -->
                    <div class="col-lg-6">
                        <div class="education-card h-100">
                            <div class="card-header">
                                <h4><i class="fas fa-shield-alt text-primary me-2"></i>Por que Testar?</h4>
                            </div>
                            <div class="card-body">
                                <p>Os testes são essenciais porque:</p>
                                <div class="benefit-item mb-2">
                                    <span class="badge bg-danger me-2">💰</span>
                                    <strong>Reduzem custos:</strong> Encontrar defeitos cedo é mais barato
                                </div>
                                <div class="benefit-item mb-2">
                                    <span class="badge bg-success me-2">😊</span>
                                    <strong>Melhoram UX:</strong> Software mais confiável
                                </div>
                                <div class="benefit-item mb-2">
                                    <span class="badge bg-warning me-2">⚡</span>
                                    <strong>Aumentam produtividade:</strong> Menos retrabalho
                                </div>
                                <div class="benefit-item mb-2">
                                    <span class="badge bg-info me-2">🛡️</span>
                                    <strong>Garantem segurança:</strong> Evitam vulnerabilidades
                                </div>
                                <div class="alert alert-success mt-3">
                                    <strong>Lembre-se:</strong> Testar é investimento, não gasto!
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botão para iniciar atividade -->
                <div class="row">
                    <div class="col-12 text-center">
                        <div class="start-activity-section p-5 rounded">
                            <h3 class="mb-3">
                                <i class="fas fa-play-circle text-primary me-2"></i>
                                Pronto para Praticar?
                            </h3>
                            <p class="lead mb-4">
                                Agora que você aprendeu os conceitos básicos, vamos aplicar o conhecimento em situações reais!
                            </p>
                            <button id="startActivityBtn" class="btn btn-primary btn-lg px-5 py-3">
                                <i class="fas fa-rocket me-2"></i>
                                Iniciar Atividade Prática
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tela de Identificação -->
    <div id="identificationScreen" class="screen">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="identification-card">
                        <div class="card-header text-center">
                            <h3><i class="fas fa-user-check text-primary me-2"></i>Identificação</h3>
                            <p class="text-muted mb-0">Preencha seus dados para iniciar a atividade</p>
                        </div>
                        <div class="card-body">
                            <form id="identificationForm">
                                <div class="mb-4">
                                    <label for="studentName" class="form-label">
                                        <i class="fas fa-user me-2"></i>Nome Completo *
                                    </label>
                                    <input type="text" class="form-control form-control-lg" 
                                           id="studentName" required
                                           placeholder="Digite seu nome completo">
                                    <div class="invalid-feedback">
                                        Por favor, informe seu nome completo.
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="studentRegistration" class="form-label">
                                        <i class="fas fa-id-card me-2"></i>Matrícula *
                                    </label>
                                    <input type="text" class="form-control form-control-lg" 
                                           id="studentRegistration" required
                                           placeholder="Digite sua matrícula">
                                    <div class="invalid-feedback">
                                        Por favor, informe sua matrícula.
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="studentEmail" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>E-mail
                                    </label>
                                    <input type="email" class="form-control form-control-lg" 
                                           id="studentEmail"
                                           placeholder="seu.email@exemplo.com (opcional)">
                                </div>

                                <div class="mb-4">
                                    <label for="studentCourse" class="form-label">
                                        <i class="fas fa-graduation-cap me-2"></i>Curso
                                    </label>
                                    <select class="form-select form-select-lg" id="studentCourse">
                                        <option value="">Selecione seu curso</option>
                                        <option value="informatica">Técnico em Informática</option>
                                        <option value="recursos_humanos">Técnico em Recursos Humanos</option>
                                        <option value="outro">Outro</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-arrow-right me-2"></i>
                                        Continuar para Atividade
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" 
                                            onclick="showWelcomeScreen()">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Voltar ao Conteúdo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tela da Atividade (Cenários) -->
    <div id="activityScreen" class="screen">
        <div class="container-fluid">
            <!-- Progress Bar -->
            <div class="progress-section mb-4">
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Progresso da Atividade</span>
                                <span id="progressText" class="text-muted">1 de 4</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div id="progressBar" class="progress-bar bg-primary" 
                                     role="progressbar" style="width: 25%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Container dos Cenários -->
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <!-- Cenário 1: Login -->
                        <div id="scenario1" class="scenario-card active">
                            <div class="scenario-header">
                                <div class="scenario-number">1</div>
                                <div class="scenario-title">
                                    <h4><i class="fas fa-sign-in-alt text-primary me-2"></i>Formulário de Login</h4>
                                    <p class="text-muted mb-0">Sistema de autenticação com usuário e senha</p>
                                </div>
                            </div>
                            <div class="scenario-content">
                                <div class="context-box">
                                    <h6><i class="fas fa-info-circle me-2"></i>Contexto:</h6>
                                    <p>Você está testando um formulário de login que possui dois campos: "Usuário" e "Senha", além de um botão "Entrar". O sistema deve autenticar usuários válidos e bloquear acessos inválidos.</p>
                                </div>
                                <div class="question-box">
                                    <h6><i class="fas fa-question-circle me-2"></i>Sua Tarefa:</h6>
                                    <p class="fw-bold">Quais testes você faria neste formulário de login? Liste pelo menos 3 casos de teste diferentes:</p>
                                    <textarea id="answer1" class="form-control" rows="6" 
                                              placeholder="Digite aqui seus casos de teste para o formulário de login...&#10;&#10;Exemplo:&#10;1. Testar login com usuário e senha válidos&#10;2. Testar login com senha incorreta&#10;3. [continue listando seus casos de teste]"></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Pense em: credenciais válidas, inválidas, campos vazios, segurança, etc.
                                    </div>
                                </div>
                                <div class="scenario-actions">
                                    <button class="btn btn-primary" onclick="nextScenario(2)">
                                        <i class="fas fa-arrow-right me-2"></i>Próximo Cenário
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Cenário 2: Carrinho -->
                        <div id="scenario2" class="scenario-card">
                            <div class="scenario-header">
                                <div class="scenario-number">2</div>
                                <div class="scenario-title">
                                    <h4><i class="fas fa-shopping-cart text-success me-2"></i>Carrinho de Compras</h4>
                                    <p class="text-muted mb-0">Funcionalidade de adicionar, remover e finalizar compras</p>
                                </div>
                            </div>
                            <div class="scenario-content">
                                <div class="context-box">
                                    <h6><i class="fas fa-info-circle me-2"></i>Contexto:</h6>
                                    <p>Você está testando um carrinho de compras de e-commerce. Os usuários podem adicionar produtos, alterar quantidades, remover itens e finalizar a compra. O sistema calcula totais e verifica estoque.</p>
                                </div>
                                <div class="question-box">
                                    <h6><i class="fas fa-question-circle me-2"></i>Sua Tarefa:</h6>
                                    <p class="fw-bold">Quais situações você testaria no carrinho de compras? Liste pelo menos 3 casos de teste:</p>
                                    <textarea id="answer2" class="form-control" rows="6" 
                                              placeholder="Digite aqui seus casos de teste para o carrinho de compras...&#10;&#10;Pense em diferentes situações que podem acontecer ao usar um carrinho de e-commerce."></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Considere: adicionar/remover produtos, quantidades, estoque, preços, etc.
                                    </div>
                                </div>
                                <div class="scenario-actions">
                                    <button class="btn btn-outline-secondary me-2" onclick="previousScenario(1)">
                                        <i class="fas fa-arrow-left me-2"></i>Anterior
                                    </button>
                                    <button class="btn btn-primary" onclick="nextScenario(3)">
                                        <i class="fas fa-arrow-right me-2"></i>Próximo Cenário
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Cenário 3: Cadastro -->
                        <div id="scenario3" class="scenario-card">
                            <div class="scenario-header">
                                <div class="scenario-number">3</div>
                                <div class="scenario-title">
                                    <h4><i class="fas fa-user-plus text-warning me-2"></i>Cadastro de Cliente</h4>
                                    <p class="text-muted mb-0">Formulário de registro com dados pessoais e validações</p>
                                </div>
                            </div>
                            <div class="scenario-content">
                                <div class="context-box">
                                    <h6><i class="fas fa-info-circle me-2"></i>Contexto:</h6>
                                    <p>Você está testando um formulário de cadastro de cliente com campos como: nome, CPF, e-mail, telefone, endereço, senha e confirmação de senha. O sistema valida formatos e não permite dados duplicados.</p>
                                </div>
                                <div class="question-box">
                                    <h6><i class="fas fa-question-circle me-2"></i>Sua Tarefa:</h6>
                                    <p class="fw-bold">Quais entradas você testaria no cadastro de cliente? Liste pelo menos 3 casos de teste:</p>
                                    <textarea id="answer3" class="form-control" rows="6" 
                                              placeholder="Digite aqui seus casos de teste para o cadastro de cliente...&#10;&#10;Considere diferentes tipos de dados que podem ser inseridos nos campos."></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Pense em: dados válidos, inválidos, campos obrigatórios, formatos, duplicatas, etc.
                                    </div>
                                </div>
                                <div class="scenario-actions">
                                    <button class="btn btn-outline-secondary me-2" onclick="previousScenario(2)">
                                        <i class="fas fa-arrow-left me-2"></i>Anterior
                                    </button>
                                    <button class="btn btn-primary" onclick="nextScenario(4)">
                                        <i class="fas fa-arrow-right me-2"></i>Reflexão Final
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Reflexão Final -->
                        <div id="scenario4" class="scenario-card reflection">
                            <div class="scenario-header">
                                <div class="scenario-number"><i class="fas fa-lightbulb"></i></div>
                                <div class="scenario-title">
                                    <h4><i class="fas fa-brain text-info me-2"></i>Reflexão Final</h4>
                                    <p class="text-muted mb-0">Sua compreensão sobre a importância dos testes</p>
                                </div>
                            </div>
                            <div class="scenario-content">
                                <div class="question-box">
                                    <h6><i class="fas fa-question-circle me-2"></i>Pergunta de Reflexão:</h6>
                                    <p class="fw-bold">Qual a importância dos testes de software para garantir a qualidade de um sistema?</p>
                                    <textarea id="answer4" class="form-control" rows="6" 
                                              placeholder="Reflita sobre o que você aprendeu e compartilhe sua visão sobre a importância dos testes de software...&#10;&#10;Considere: impactos no usuário, custos, qualidade, confiabilidade, etc."></textarea>
                                    <div class="form-text">
                                        <i class="fas fa-lightbulb me-1"></i>
                                        Base-se no conteúdo estudado e na prática dos cenários anteriores.
                                    </div>
                                </div>
                                <div class="scenario-actions">
                                    <button class="btn btn-outline-secondary me-2" onclick="previousScenario(3)">
                                        <i class="fas fa-arrow-left me-2"></i>Anterior
                                    </button>
                                    <button id="submitBtn" class="btn btn-success btn-lg" onclick="submitActivity()">
                                        <i class="fas fa-paper-plane me-2"></i>Enviar Respostas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tela de Resultados/Feedback -->
    <div id="resultsScreen" class="screen">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="results-header text-center mb-5">
                        <div class="success-icon mb-3">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <h2 class="mb-3">Atividade Concluída!</h2>
                        <p class="lead text-muted">Suas respostas foram enviadas com sucesso. Confira o feedback abaixo:</p>
                    </div>

                    <!-- Feedback Cards -->
                    <div id="feedbackContent" class="feedback-section">
                        <!-- Conteúdo será preenchido dinamicamente -->
                    </div>

                    <!-- Ações Finais -->
                    <div class="final-actions text-center mt-5">
                        <button class="btn btn-primary me-3" onclick="location.reload()">
                            <i class="fas fa-redo me-2"></i>Fazer Novamente
                        </button>
                        <a href="../index.html" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Voltar ao Hub
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script principal -->
    <script src="js/script.js"></script>
</body>
</html>