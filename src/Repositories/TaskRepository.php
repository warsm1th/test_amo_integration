<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Clients\AmoCrmV4Client;

class TaskRepository
{
    public function __construct(
        private readonly AmoCrmV4Client $client
    ) {}
    
    public function findByEntityId(int $entityId, string $entityType = 'leads'): array
    {
        return $this->client->getAll('tasks', [
            "filter[entity_id]" => $entityId,
            "filter[entity_type]" => $entityType
        ]);
    }
    
    public function create(array $taskData): bool
    {
        $response = $this->client->post('tasks', [$taskData]);
        return isset($response['_embedded']['tasks'][0]['id']);
    }
    
    public function batchCreate(array $tasksData): int
    {
        if (empty($tasksData)) {
            return 0;
        }
        
        $response = $this->client->post('tasks', $tasksData);
        return count($response['_embedded']['tasks'] ?? []);
    }
}