<?php

use App\Services\LeadService;
use App\Services\NoteService;
use App\Services\TaskService;

$leadService = new LeadService($amoClient, $config);
$noteService = new NoteService($amoClient);
$taskService = new TaskService($amoClient);

$pipelineId = $config['pipeline_id'];
$confirmedStatus = $config['statuses']['confirmed'];
$waitingStatus = $config['statuses']['waiting'];
$exactBudget = $config['budget_specific'];

echo "<div style='font-family: monospace; white-space: pre-wrap;'>";
echo "=== Начало обработки: Копирование сделок с бюджетом = {$exactBudget} ===\n\n";

$leadsToCopy = $leadService->findLeadsWithExactBudget(
    $pipelineId,
    $confirmedStatus,
    $exactBudget
);

echo "Найдено сделок для копирования: " . count($leadsToCopy) . "\n\n";

$copiedCount = 0;
foreach ($leadsToCopy as $lead) {
    echo "Обработка сделки-донора ID: {$lead['id']}\n";
    
    $newLeadId = $leadService->copyLead($lead, $waitingStatus);
    
    if (!$newLeadId) {
        echo "✗ Ошибка при создании копии сделки ID: {$lead['id']}\n\n";
        continue;
    }
    
    echo "✓ Создана новая сделка ID: {$newLeadId}\n";
    
    $notesCount = $noteService->copyNotes($lead['id'], $newLeadId);
    echo "Скопировано примечаний: {$notesCount}\n";
    
    $tasksCount = $taskService->copyTasks($lead['id'], $newLeadId);
    echo "Скопировано задач: {$tasksCount}\n\n";
    
    $copiedCount++;
}

echo "=== Обработка завершена. Скопировано сделок: {$copiedCount} ===\n";
echo "</div>";