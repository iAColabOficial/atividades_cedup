<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== TESTE REQUISIÇÃO POST ===<br>";

// Dados de teste mínimos (similares ao que JS envia)
$test_data = [
    'user_id' => 'admin',
    'name' => 'teste admin',
    'email' => 'admin@teste.com',
    'registration' => '44322',
    'course' => 'recursos_humanos',
    'scenario_id' => 'techcorp_2024',
    'startTime' => '2024-01-01T10:00:00.000Z',
    'endTime' => '2024-01-01T10:30:00.000Z',
    'totalQuestions' => 10,
    'correctAnswers' => 7,
    'formulasUsed' => [
        'SOMA' => ['count' => 2, 'correct' => 2],
        'SE' => ['count' => 2, 'correct' => 1]
    ],
    'answers' => [
        [
            'questionId' => 'Q001',
            'questionTitle' => 'Teste',
            'formulaType' => 'SOMA',
            'userFormula' => '=SOMA(A1:A5)',
            'expectedFormula' => '=SOMA(A1:A5)',
            'isCorrect' => true,
            'timeSpent' => 120,
            'pointsEarned' => 10
        ]
    ]
];

// Simular requisição POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simular dados JSON
$GLOBALS['_TEST_INPUT'] = json_encode($test_data);

// Sobrescrever file_get_contents para teste
function test_file_get_contents($filename) {
    if ($filename === 'php://input') {
        return $GLOBALS['_TEST_INPUT'];
    }
    return file_get_contents($filename);
}

echo "Dados de teste preparados<br>";
echo "JSON size: " . strlen($GLOBALS['_TEST_INPUT']) . " chars<br>";

// Tentar incluir save_data.php
try {
    ob_start();
    
    // Redefinir file_get_contents temporariamente
    if (!function_exists('original_file_get_contents')) {
        function original_file_get_contents($filename) {
            return file_get_contents($filename);
        }
    }
    
    // Incluir com dados de teste
    include 'save_data.php';
    
    $output = ob_get_clean();
    
    echo "✓ POST executado sem erro fatal<br>";
    echo "Output length: " . strlen($output) . " chars<br>";
    
    if ($output) {
        echo "Resposta (200 chars):<br>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 200)) . "</pre>";
        
        // Tentar decodificar como JSON
        $json = json_decode($output, true);
        if ($json) {
            echo "JSON válido: " . ($json['success'] ? 'SUCCESS' : 'ERROR') . "<br>";
            if (!$json['success']) {
                echo "Erro: " . $json['message'] . "<br>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
} catch (Error $e) {
    echo "✗ ERRO FATAL: " . $e->getMessage() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
}

echo "=== FIM TESTE ===";
?>