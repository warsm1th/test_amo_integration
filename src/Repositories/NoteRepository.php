<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Clients\AmoCrmV4Client;

class NoteRepository
{
    public function __construct(
        private readonly AmoCrmV4Client $client
    ) {}
    
    public function findByEntityId(int $entityId, string $entityType = 'leads'): array
    {
        return $this->client->getAll("{$entityType}/{$entityId}/notes");
    }
    
    public function createForEntity(int $entityId, string $entityType, array $noteData): bool
    {
        // Правильный эндпоинт для создания примечаний
        $response = $this->client->post("{$entityType}/notes", [$noteData]);
        return isset($response['_embedded']['notes'][0]['id']);
    }
    
    public function batchCreateForEntity(int $entityId, string $entityType, array $notesData): int
    {
        if (empty($notesData)) {
            return 0;
        }
        
        $response = $this->client->post("{$entityType}/notes", $notesData);
        return count($response['_embedded']['notes'] ?? []);
    }
}