<?php
/**
 * Эндпоинт для копирования сделок с бюджетом = 4999
 */

use App\Services\LeadService;
use App\Services\NoteService;
use App\Services\TaskService;

// Инициализация сервисов
$leadService = new LeadService($amoClient, $config);
$noteService = new NoteService($amoClient);
$taskService = new TaskService($amoClient);

// Получаем параметры из конфига
$pipelineId = $config['pipeline_id'];
$confirmedStatus = $config['statuses']['confirmed'];
$waitingStatus = $config['statuses']['waiting'];
$exactBudget = $config['budget_specific'];

echo "<div style='font-family: monospace; white-space: pre-wrap;'>";
echo "=== Начало обработки: Копирование сделок с бюджетом = {$exactBudget} ===\n\n";

// Находим сделки для копирования
$leadsToCopy = $leadService->findLeadsWithExactBudget(
    $pipelineId,
    $confirmedStatus,
    $exactBudget
);

echo "Найдено сделок для копирования: " . count($leadsToCopy) . "\n\n";

// Копируем сделки
$copiedCount = 0;
foreach ($leadsToCopy as $lead) {
    echo "Обработка сделки-донора ID: {$lead['id']}\n";
    
    // a) Создаем копию сделки
    $newLeadId = $leadService->copyLead($lead, $waitingStatus);
    
    if (!$newLeadId) {
        echo "✗ Ошибка при создании копии сделки ID: {$lead['id']}\n\n";
        continue;
    }
    
    echo "✓ Создана новая сделка ID: {$newLeadId}\n";
    
    // b) Копируем примечания
    $notesCount = $noteService->copyNotes($lead['id'], $newLeadId);
    echo "Скопировано примечаний: {$notesCount}\n";
    
    // c) Копируем задачи
    $tasksCount = $taskService->copyTasks($lead['id'], $newLeadId);
    echo "Скопировано задач: {$tasksCount}\n\n";
    
    $copiedCount++;
}

echo "=== Обработка завершена. Скопировано сделок: {$copiedCount} ===\n";
echo "</div>";