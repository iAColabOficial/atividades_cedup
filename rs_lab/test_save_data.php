<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== TESTE DIRETO SAVE_DATA.PHP ===<br>";

// Teste 1: Verificar se auth.php existe e carrega
echo "1. Testando auth.php...<br>";
if (file_exists('../auth/auth.php')) {
    echo "✓ Auth existe<br>";
    try {
        require_once '../config/config.php';
        require_once '../auth/auth.php';
        echo "✓ Auth carregado<br>";
    } catch (Exception $e) {
        echo "✗ Erro no auth: " . $e->getMessage() . "<br>";
        die();
    }
} else {
    echo "✗ Auth não encontrado<br>";
    die();
}

// Teste 2: Verificar funções necessárias
echo "2. Testando funções...<br>";
$functions = ['jsonResponse', 'logActivity', 'sanitizeInput', 'requireAuth'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✓ $func existe<br>";
    } else {
        echo "✗ $func NÃO existe<br>";
    }
}

// Teste 3: Simular requisição GET para status
echo "3. Testando acesso direto ao save_data.php...<br>";
try {
    // Configurar variáveis para simular requisição GET
    $_GET['status'] = true;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    // Capturar saída
    ob_start();
    $error_before = error_get_last();
    
    // Tentar incluir save_data.php
    include 'save_data.php';
    
    $output = ob_get_clean();
    $error_after = error_get_last();
    
    echo "✓ Save_data.php incluído sem erro fatal<br>";
    echo "Output length: " . strlen($output) . " chars<br>";
    
    if ($output) {
        echo "Primeiros 500 chars da saída:<br>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
    }
    
    if ($error_after && $error_after != $error_before) {
        echo "Último erro PHP: " . $error_after['message'] . " em " . $error_after['file'] . ":" . $error_after['line'] . "<br>";
    }
    
} catch (ParseError $e) {
    echo "✗ Erro de sintaxe no save_data.php: " . $e->getMessage() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
} catch (Error $e) {
    echo "✗ Erro fatal no save_data.php: " . $e->getMessage() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
} catch (Exception $e) {
    echo "✗ Exceção no save_data.php: " . $e->getMessage() . "<br>";
}

echo "=== TESTE CONCLUÍDO ===<br>";
?>