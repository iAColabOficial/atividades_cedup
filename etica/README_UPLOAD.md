# 🎯 LABORATÓRIO VIRTUAL DE DECISÕES ÉTICAS EM TI
## Professor Leandro Rodrigues

---

## 📂 **INSTRUÇÕES PARA UPLOAD NO HOSTINGER**

### **🎯 ESTRUTURA FINAL NO SERVIDOR**
```
public_html/
└── atividades/
    └── etica/
        ├── login.html              ✅ Sistema de login e cadastro
        ├── index.html              ✅ Laboratório principal  
        ├── style.css               ✅ Estilos CSS
        ├── script.js               ✅ JavaScript principal
        ├── auth.php                ✅ Sistema de autenticação
        ├── config.php              ✅ Configurações do banco
        ├── save_data.php           ✅ Salvar dados
        ├── admin.php               ✅ Painel administrativo
        ├── student_detail.php      ✅ Detalhes do estudante
        ├── export.php              ✅ Exportar CSV
        ├── setup.php               ✅ Instalador
        ├── .htaccess               ✅ Configurações de segurança
        ├── 404.html                ✅ Página de erro
        ├── logs/                   📁 Criar pasta para logs
        └── backups/                📁 Criar pasta para backups
```

---

## 🚀 **PASSO A PASSO PARA INSTALAÇÃO**

### **1️⃣ COPIAR OS ARQUIVOS**

**Para cada arquivo acima, faça:**

1. **Clique no bloco de código** correspondente nesta conversa
2. **Copie todo o conteúdo** (Ctrl+A, Ctrl+C)
3. **Crie um arquivo novo** no seu editor de texto
4. **Cole o conteúdo** (Ctrl+V)
5. **Salve com o nome exato** (ex: `login.html`, `auth.php`, etc.)

### **2️⃣ FAZER UPLOAD NO HOSTINGER**

1. **Acesse o painel do Hostinger**
2. **Vá em "Gerenciador de Arquivos"**
3. **Navegue até:** `public_html/atividades/`
4. **Crie a pasta:** `etica`
5. **Entre na pasta `etica`**
6. **Faça upload de todos os 13 arquivos**

### **3️⃣ CRIAR PASTAS NECESSÁRIAS**

No gerenciador de arquivos, dentro da pasta `etica`, crie:
- **📁 `logs`** - Para logs do sistema
- **📁 `backups`** - Para backups do banco

### **4️⃣ EXECUTAR O INSTALADOR**

1. **Acesse:** `https://proleandro.com.br/atividades/etica/setup.php`
2. **Siga os 4 passos** do instalador
3. **Teste a conexão** com o banco
4. **Crie as tabelas** automaticamente
5. **Delete o `setup.php`** por segurança

---

## 🔧 **CONFIGURAÇÕES DO BANCO DE DADOS**

### **✅ Dados já configurados:**
- **Host:** localhost
- **Banco:** u906658109_atividades
- **Usuário:** u906658109_atividades
- **Senha:** P@ncho2891

### **📊 Tabelas que serão criadas:**
1. **`ethics_lab_users`** - Usuários do sistema (login/cadastro)
2. **`ethics_lab_students`** - Resultados dos laboratórios
3. **`ethics_lab_choices`** - Escolhas individuais dos dilemas

---

## 🎯 **URLs DE ACESSO FINAL**

### **👨‍🎓 Para Estudantes:**
- **Login/Cadastro:** `https://proleandro.com.br/atividades/etica/login.html`
- **Laboratório:** `https://proleandro.com.br/atividades/etica/` *(redireciona automaticamente)*

### **👨‍🏫 Para Professor:**
- **Painel Admin:** `https://proleandro.com.br/atividades/etica/admin.php`

---

## 🆕 **SISTEMA DE CADASTRO DE ESTUDANTES**

### **🔐 Como os estudantes usarão:**

#### **1. Primeiro acesso (Cadastro):**
1. Acessa o link do laboratório
2. É redirecionado para tela de login
3. Clica em **"Cadastrar"**
4. Preenche dados: Nome, E-mail, Matrícula, Curso, Senha
5. Aceita termos de uso
6. Cria conta e faz login
7. Vai direto para o laboratório

#### **2. Próximos acessos (Login):**
1. Acessa o link do laboratório
2. Faz login com e-mail/matrícula + senha
3. Vai direto para o laboratório

#### **3. Modo Demonstração:**
1. Clica em **"Acessar Demo"** na tela de login
2. Usa sem cadastro *(dados não são salvos)*

---

## 📋 **LISTA COMPLETA DOS 14 ARQUIVOS**

| # | Arquivo | Descrição |
|---|---------|-----------|
| 1 | `login.html` | Sistema de login e cadastro |
| 2 | `auth.php` | Autenticação backend |
| 3 | `index.html` | Interface do laboratório |
| 4 | `style.css` | Estilos CSS |
| 5 | `script.js` | JavaScript principal |
| 6 | `config.php` | Configurações do banco |
| 7 | `save_data.php` | Salvar dados dos estudantes |
| 8 | `.htaccess` | Configurações de segurança |
| 9 | `admin.php` | Painel administrativo |
| 10 | `student_detail.php` | Relatório individual |
| 11 | `export.php` | Exportação CSV |
| 12 | `setup.php` | Instalador automático |
| 13 | `404.html` | Página de erro |
| 14 | `README_UPLOAD.md` | Este arquivo |

---

## ✅ **CHECKLIST DE VERIFICAÇÃO**

### **Antes do upload:**
- [ ] Copiei todos os 14 arquivos
- [ ] Verifiquei se os nomes estão corretos
- [ ] Testei se não há erros de sintaxe

### **Durante o upload:**
- [ ] Criei a pasta `atividades/etica/`
- [ ] Fiz upload de todos os arquivos
- [ ] Criei as pastas `logs/` e `backups/`
- [ ] Verifiquei permissões dos arquivos

### **Após o upload:**
- [ ] Executei o `setup.php`
- [ ] Testei a conexão com banco
- [ ] Criei as tabelas com sucesso
- [ ] Deletei o `setup.php`

### **Teste final:**
- [ ] Acessei a tela de login
- [ ] Criei uma conta de teste
- [ ] Fiz login com sucesso
- [ ] Completei o laboratório
- [ ] Visualizei o relatório final
- [ ] Acessei o painel admin
- [ ] Visualizei os dados salvos

---

## 🔒 **RECURSOS DE SEGURANÇA**

### **✅ Implementados:**
- Senhas criptografadas (password_hash)
- Proteção contra SQL injection
- Validação rigorosa de dados
- Headers de segurança
- Sessões com timeout
- Arquivos sensíveis protegidos
- Logs de auditoria

### **🔐 Validação de senha:**
- Mínimo 8 caracteres
- Pelo menos 1 maiúscula
- Pelo menos 1 minúscula
- Pelo menos 1 número
- Indicador visual de força

---

## 📊 **FUNCIONALIDADES COMPLETAS**

### **🎯 Para Estudantes:**
- Sistema de cadastro seguro
- Login com e-mail ou matrícula
- 10 dilemas éticos interativos
- Pontuação dinâmica (0-100)
- Relatório final detalhado
- Modo demonstração

### **👨‍🏫 Para Professor:**
- Painel administrativo completo
- Filtros avançados de busca
- Estatísticas em tempo real
- Relatórios individuais detalhados
- Exportação CSV
- Sistema de backup
- Logs de auditoria

---

## 🆘 **SOLUÇÃO DE PROBLEMAS**

### **❌ Erro de conexão com banco:**
- Verifique as credenciais em `config.php`
- Confirme se o banco existe no Hostinger
- Teste via phpMyAdmin

### **❌ Arquivos não encontrados:**
- Verifique se todos os arquivos foram uploadados
- Confirme os nomes dos arquivos
- Verifique as permissões

### **❌ Página em branco:**
- Ative logs de erro do PHP
- Verifique o arquivo `logs/activity.log`
- Teste individualmente cada arquivo

---

## 🎉 **SISTEMA COMPLETO E FUNCIONAL!**

### **🌟 Principais melhorias:**
- ✅ **Sistema de login/cadastro** completo
- ✅ **Validação de senha forte** 
- ✅ **Sessões seguras** com timeout
- ✅ **Modo demonstração** 
- ✅ **Interface moderna** e responsiva
- ✅ **Relatórios detalhados**
- ✅ **Painel administrativo** avançado
- ✅ **Sistema de backup** integrado
- ✅ **Segurança robusta**

### **📱 URLs Finais:**
- **Estudantes:** https://proleandro.com.br/atividades/etica/login.html
- **Professor:** https://proleandro.com.br/atividades/etica/admin.php

---

**🎓 Sistema 100% pronto para uso educacional!**
*Professor Leandro Rodrigues | 2025*