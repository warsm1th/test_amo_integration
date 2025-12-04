<?php
namespace App\Services;

use App\Client\AmoCrmV4Client;

/**
 * Сервис для работы со сделками (leads)
 */
class LeadService
{
    private AmoCrmV4Client $amoClient;
    private array $config;

    public function __construct(AmoCrmV4Client $amoClient, array $config)
    {
        $this->amoClient = $amoClient;
        $this->config = $config;
    }

    public function getLeadsByStatus(int $pipelineId, int $statusId, array $additionalFilters = []): array
    {
        $filters = array_merge([
            "filter[statuses][0][pipeline_id]" => $pipelineId,
            "filter[statuses][0][status_id]" => $statusId
        ], $additionalFilters);

        return $this->amoClient->getAll('leads', $filters);
    }

    public function moveLeadToStatus(int $leadId, int $statusId): bool
    {
        $data = [[
            "id" => $leadId,
            "status_id" => $statusId
        ]];

        $response = $this->amoClient->patch('leads', $data);
        
        return isset($response['_embedded']['leads'][0]['id']);
    }

    public function copyLead(array $sourceLead, int $newStatusId): ?int
    {
        $newLeadData = [
            "name" => $sourceLead['name'] . " (Копия)",
            "price" => $sourceLead['price'],
            "pipeline_id" => $sourceLead['pipeline_id'] ?? $this->config['pipeline_id'],
            "status_id" => $newStatusId,
            "custom_fields_values" => $sourceLead['custom_fields_values'] ?? [],
            "responsible_user_id" => $sourceLead['responsible_user_id'] ?? null,
            "created_by" => $sourceLead['created_by'] ?? 0
        ];

        $newLeadData = array_filter($newLeadData, function($value) {
            return $value !== null;
        });

        $response = $this->amoClient->post('leads', [$newLeadData]);

        if (isset($response['_embedded']['leads'][0]['id'])) {
            return $response['_embedded']['leads'][0]['id'];
        }

        return null;
    }

    public function findLeadsWithBudgetGreaterThan(
        int $pipelineId, 
        int $statusId, 
        int $budgetThreshold
    ): array {
        $leads = $this->getLeadsByStatus($pipelineId, $statusId);
        
        return array_filter($leads, function($lead) use ($budgetThreshold) {
            return (int)$lead['price'] > $budgetThreshold;
        });
    }

    public function findLeadsWithExactBudget(
        int $pipelineId, 
        int $statusId, 
        int $exactBudget
    ): array {
        return $this->getLeadsByStatus($pipelineId, $statusId, [
            "filter[price]" => $exactBudget
        ]);
    }
}