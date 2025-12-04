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
            $preparedTasks[] = $this->prepareTaskForCopy($task, $targetLeadId);
        }

        $this->amoClient->post('tasks', $preparedTasks);
        return count($preparedTasks);
    }

    private function prepareTaskForCopy(array $task, int $targetLeadId): array
    {
        return [
            "task_type_id" => $task['task_type_id'],
            "text" => $task['text'],
            "complete_till" => $task['complete_till'],
            "entity_id" => $targetLeadId,
            "entity_type" => "leads",
            "responsible_user_id" => $task['responsible_user_id'] ?? 0,
            "created_by" => $task['created_by'] ?? 0,
            "is_completed" => $task['is_completed']
        ];
    }
}