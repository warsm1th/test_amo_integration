<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\TaskRepository;

class TaskService
{
    public function __construct(
        private readonly TaskRepository $taskRepository
    ) {}
    
    public function getLeadTasks(int $leadId): array
    {
        try {
            return $this->taskRepository->findByEntityId($leadId, 'leads');
        } catch (\Exception $e) {
            error_log("Ошибка получения задач для сделки {$leadId}: " . $e->getMessage());
            return [];
        }
    }
    
    public function copyTasks(int $sourceLeadId, int $targetLeadId): int
    {
        $tasks = $this->getLeadTasks($sourceLeadId);
        
        if (empty($tasks)) {
            return 0;
        }

        $preparedTasks = [];
        foreach ($tasks as $task) {
            $preparedTask = $this->prepareTaskForCopy($task, $targetLeadId);
            if ($preparedTask !== null) {
                $preparedTasks[] = $preparedTask;
            }
        }

        if (empty($preparedTasks)) {
            return 0;
        }

        return $this->taskRepository->batchCreate($preparedTasks);
    }
    
    private function prepareTaskForCopy(array $task, int $targetLeadId): ?array
    {
        // Обязательные поля
        if (empty($task['text'] ?? '')) {
            $taskText = 'Задача из сделки-донора (ID: ' . $targetLeadId . ')';
        } else {
            $taskText = $task['text'];
        }

        $taskTypeId = $task['task_type_id'] ?? 1;
        
        // Обработка даты выполнения
        if (empty($task['complete_till'])) {
            $completeTill = time() + 86400; // Завтра
        } else {
            $completeTill = $task['complete_till'];
            
            // Если дата в прошлом, ставим на завтра
            if ($completeTill < time()) {
                $completeTill = time() + 86400;
            }
        }

        // Подготавливаем данные задачи
        $preparedTask = [
            "task_type_id" => $taskTypeId,
            "text" => $taskText,
            "complete_till" => $completeTill,
            "entity_id" => $targetLeadId,
            "entity_type" => "leads",
            "responsible_user_id" => $task['responsible_user_id'] ?? 0,
            "created_by" => $task['created_by'] ?? 0,
            "is_completed" => $task['is_completed'] ?? false
        ];

        return $preparedTask;
    }
}