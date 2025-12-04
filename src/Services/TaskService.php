<?php
namespace App\Services;

use App\Client\AmoCrmV4Client;

/**
 * Сервис для работы с задачами
 */
class TaskService
{
    private AmoCrmV4Client $amoClient;

    public function __construct(AmoCrmV4Client $amoClient)
    {
        $this->amoClient = $amoClient;
    }

    public function getLeadTasks(int $leadId): array
    {
        return $this->amoClient->getAll('tasks', [
            "filter[entity_id]" => $leadId,
            "filter[entity_type]" => "leads"
        ]);
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

        $this->amoClient->post('tasks', $preparedTasks);
        return count($preparedTasks);
    }

    private function prepareTaskForCopy(array $task, int $targetLeadId): ?array
    {
        // Обработка текста задачи - обязательное поле
        if (empty($task['text']) || trim($task['text']) === '') {
            $taskText = 'Задача из сделки-донора';
        } else {
            $taskText = $task['text'];
        }

        // Тип задачи по умолчанию
        $taskTypeId = $task['task_type_id'] ?? 1;

        // Обработка даты выполнения
        if (empty($task['complete_till'])) {
            $completeTill = time() + 86400;
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