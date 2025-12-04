<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Client\AmoCrmV4Client;
use App\Services\LeadService;
use App\Services\NoteService;
use App\Services\TaskService;

// Загрузка конфигурации
$config = require_once __DIR__ . '/config/amocrm_config.php';

echo "<pre>";

try {
    $amoClient = new AmoCrmV4Client($config);
    
    $action = $_GET['action'] ?? '';
    
    if ($action === 'move') {
        $leadService = new LeadService($amoClient, $config);
        
        $leads = $leadService->findLeadsWithBudgetGreaterThan(
            $config['pipeline_id'],
            $config['statuses']['application'],
            $config['budget_threshold']
        );
        
        echo "Найдено сделок > 5000: " . count($leads) . "\n";
        
        foreach ($leads as $lead) {
            $leadService->moveLeadToStatus($lead['id'], $config['statuses']['waiting']);
            echo "Перемещена сделка ID: {$lead['id']}\n";
        }
        
    } elseif ($action === 'copy') {
        $leadService = new LeadService($amoClient, $config);
        $noteService = new NoteService($amoClient);
        $taskService = new TaskService($amoClient);
        
        $leads = $leadService->findLeadsWithExactBudget(
            $config['pipeline_id'],
            $config['statuses']['confirmed'],
            $config['budget_specific']
        );
        
        echo "Найдено сделок = 4999: " . count($leads) . "\n";
        
        foreach ($leads as $lead) {
            $newLeadId = $leadService->copyLead($lead, $config['statuses']['waiting']);
            echo "Создана копия сделки ID: {$lead['id']} -> {$newLeadId}\n";
            
            $noteService->copyNotes($lead['id'], $newLeadId);
            $taskService->copyTasks($lead['id'], $newLeadId);
        }
        
    } else {
        // Главная страница
            echo "<h1>AmoCRM Integration API</h1>";
            echo "<p>Доступные эндпоинты:</p>";
            echo "<ul>";
            echo "<li><a href='?action=move'>/index.php?action=move</a> - Переместить сделки с бюджетом > 5000</li>";
            echo "<li><a href='?action=copy'>/index.php?action=copy</a> - Скопировать сделки с бюджетом = 4999</li>";
            echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    
    // Если проблема с авторизацией
    if (strpos($e->getMessage(), 'Authorization code has been revoked') !== false) {
        echo "\n⚠ Нужно обновить authorization code в config/amocrm_config.php\n";
    }
    
    // Записываем в лог
    file_put_contents($config['error_log'], date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n", FILE_APPEND);
}

echo "</pre>";