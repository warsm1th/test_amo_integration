<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Clients\AmoCrmV4Client;

class LeadRepository
{
    public function __construct(
        private readonly AmoCrmV4Client $client
    ) {}
    
    public function findByStatus(int $pipelineId, int $statusId, array $filters = []): array
    {
        $params = array_merge([
            "filter[statuses][0][pipeline_id]" => $pipelineId,
            "filter[statuses][0][status_id]" => $statusId,
            "with" => "contacts"
        ], $filters);
        
        return $this->client->getAll('leads', $params);
    }
    
    public function updateStatus(int $leadId, int $statusId): bool
    {
        $response = $this->client->patch('leads', [[
            "id" => $leadId,
            "status_id" => $statusId
        ]]);
        
        return isset($response['_embedded']['leads'][0]['id']);
    }
    
    public function create(array $data): ?int
    {
        $response = $this->client->post('leads', [$data]);
        
        return $response['_embedded']['leads'][0]['id'] ?? null;
    }
    
    public function findByBudgetGreaterThan(
        int $pipelineId, 
        int $statusId, 
        int $budgetThreshold
    ): array {
        $leads = $this->findByStatus($pipelineId, $statusId);
        
        return array_filter($leads, 
            fn($lead) => (int)($lead['price'] ?? 0) > $budgetThreshold
        );
    }
    
    public function findByExactBudget(
        int $pipelineId, 
        int $statusId, 
        int $exactBudget
    ): array {
        return $this->findByStatus($pipelineId, $statusId, [
            "filter[price]" => $exactBudget
        ]);
    }
    
    public function findById(int $leadId): ?array
    {
        $response = $this->client->get("leads/{$leadId}");
        return $response ?? null;
    }
}