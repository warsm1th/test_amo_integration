<?php

use App\Services\LeadService;

$leadService = new LeadService($amoClient, $config);

$pipelineId = $config['pipeline_id'];
$applicationStatus = $config['statuses']['application'];
$waitingStatus = $config['statuses']['waiting'];
$budgetThreshold = $config['budget_threshold'];

echo "<div style='font-family: monospace; white-space: pre-wrap;'>";
echo "=== Начало обработки: Перемещение сделок с бюджетом > {$budgetThreshold} ===\n\n";

$leadsToMove = $leadService->findLeadsWithBudgetGreaterThan(
    $pipelineId,
    $applicationStatus,
    $budgetThreshold
);

echo "Найдено сделок для перемещения: " . count($leadsToMove) . "\n\n";

$movedCount = 0;
foreach ($leadsToMove as $lead) {
    $budget = (int)$lead['price'];
    echo "Обработка сделки ID: {$lead['id']}, Бюджет: {$budget}\n";
    
    if ($leadService->moveLeadToStatus($lead['id'], $waitingStatus)) {
        echo "✓ Сделка ID: {$lead['id']} перемещена на этап 'Ожидание клиента'\n\n";
        $movedCount++;
    } else {
        echo "✗ Ошибка при перемещении сделки ID: {$lead['id']}\n\n";
    }
}

echo "=== Обработка завершена. Перемещено сделок: {$movedCount} ===\n";
echo "</div>";