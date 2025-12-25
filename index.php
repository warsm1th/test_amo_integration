<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Factories\LeadMoveHandlerFactory;
use App\Factories\LeadCloneHandlerFactory;

// Загрузка конфигурации
$config = require __DIR__ . '/config/amocrm_config.php';

echo "<pre>";

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'move':
            echo "=== Перемещение сделок с бюджетом > 5000 ===\n";
            $handler = LeadMoveHandlerFactory::create($config);
            $result = $handler->handle();
            print_r($result);
            break;
            
        case 'copy':
            echo "=== Клонирование сделок с бюджетом = 4999 ===\n";
            $handler = LeadCloneHandlerFactory::create($config);
            $result = $handler->handle();
            print_r($result);
            break;
            
        default:
            echo "<h1>AmoCRM Integration API</h1>";
            echo "<p>Тестовое задание: интеграция с amoCRM</p>";
            echo "<p>Доступные эндпоинты:</p>";
            echo "<ul>";
            echo "<li><a href='?action=move'>/index.php?action=move</a> - Переместить сделки с бюджетом > 5000</li>";
            echo "<li><a href='?action=copy'>/index.php?action=copy</a> - Скопировать сделки с бюджетом = 4999</li>";
            echo "</ul>";
    }
    
} catch (Throwable $e) {
    $errorMessage = '[' . date('Y-m-d H:i:s') . '] Ошибка: ' . $e->getMessage() . 
                    ' в файле ' . $e->getFile() . ':' . $e->getLine() . "\n";
    
    echo "❌ Критическая ошибка: " . $e->getMessage() . "\n";
    echo "📄 Подробности в лог-файле: " . $config['error_log'] . "\n";
    
    if (strpos($e->getMessage(), 'Authorization code has been revoked') !== false) {
        echo "\n⚠️ ВНИМАНИЕ: Нужно обновить authorization code в config/amocrm_config.php\n";
        echo "   Получите новый код авторизации в amoCRM\n";
    }
    
    // Записываем в лог с трассировкой
    file_put_contents(
        $config['error_log'], 
        $errorMessage . "Трассировка:\n" . $e->getTraceAsString() . "\n\n", 
        FILE_APPEND
    );
}

echo "</pre>";