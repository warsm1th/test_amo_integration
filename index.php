<?php
// Включение отображения ошибок (только для разработки)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Загрузка автозагрузчика Composer
require_once __DIR__ . '/vendor/autoload.php';

use App\Client\AmoCrmV4Client;
use App\Services\LeadService;
use App\Services\NoteService;
use App\Services\TaskService;

// Загрузка конфигурации
$config = require_once __DIR__ . '/config/amocrm_config.php';

header('Content-Type: text/html; charset=utf-8');

try {
    // Инициализация клиента
    $amoClient = new AmoCrmV4Client($config);
    
    // Определяем запрашиваемый эндпоинт
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'move':
            require_once __DIR__ . '/endpoints/move_leads.php';
            break;
            
        case 'copy':
            require_once __DIR__ . '/endpoints/copy_leads.php';
            break;
            
        default:
            echo "<!DOCTYPE html>
            <html lang='ru'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>AmoCRM Integration API</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
                    h1 { color: #333; }
                    ul { list-style-type: none; padding: 0; }
                    li { margin: 10px 0; }
                    a { 
                        display: inline-block; 
                        padding: 10px 20px; 
                        background: #4CAF50; 
                        color: white; 
                        text-decoration: none; 
                        border-radius: 5px; 
                    }
                    a:hover { background: #45a049; }
                    .container { max-width: 800px; margin: 0 auto; }
                    .info { background: #f4f4f4; padding: 20px; border-radius: 5px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h1>🔄 AmoCRM Integration API</h1>
                    <p>Доступные эндпоинты:</p>
                    <ul>
                        <li><a href='?action=move'>/index.php?action=move</a> - Переместить сделки с бюджетом > 5000</li>
                        <li><a href='?action=copy'>/index.php?action=copy</a> - Скопировать сделки с бюджетом = 4999</li>
                    </ul>
                    
                    <div class='info'>
                        <h3>Информация:</h3>
                        <p><strong>Воронка ID:</strong> {$config['pipeline_id']}</p>
                        <p><strong>Этапы:</strong></p>
                        <ul>
                            <li>Заявка: {$config['statuses']['application']}</li>
                            <li>Ожидание клиента: {$config['statuses']['waiting']}</li>
                            <li>Клиент подтвердил: {$config['statuses']['confirmed']}</li>
                        </ul>
                    </div>
                </div>
            </body>
            </html>";
            break;
    }
    
} catch (Exception $e) {
    // Логирование ошибки
    $logMessage = '[' . date('Y-m-d H:i:s') . '] Error: ' . $e->getMessage() . 
                  ' File: ' . $e->getFile() . 
                  ' Line: ' . $e->getLine() . PHP_EOL;
    
    error_log($logMessage, 3, $config['error_log']);
    
    // Пользовательское сообщение
    http_response_code(500);
    echo "<h1>Ошибка 500</h1>";
    echo "<p>Внутренняя ошибка сервера. Подробности в логе.</p>";
    
    if (ini_get('display_errors')) {
        echo "<pre>Debug Info:\n";
        echo "Message: " . htmlspecialchars($e->getMessage()) . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "</pre>";
    }
}