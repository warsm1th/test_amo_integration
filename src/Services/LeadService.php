<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\LeadRepository;

class LeadService
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly array $config
    ) {}
    
    public function moveHighBudgetLeads(): array
    {
        $leads = $this->leadRepository->findByBudgetGreaterThan(
            $this->config['pipelines']['default'],
            $this->config['statuses']['application'],
            $this->config['budget']['threshold']
        );
        
        $movedCount = 0;
        $movedIds = [];
        
        foreach ($leads as $lead) {
            if ($this->leadRepository->updateStatus(
                $lead['id'], 
                $this->config['statuses']['waiting_client']
            )) {
                $movedCount++;
                $movedIds[] = $lead['id'];
            }
        }
        
        return [
            'found' => count($leads),
            'moved' => $movedCount,
            'moved_ids' => $movedIds,
            'message' => 'Сделки с бюджетом > 5000 перемещены на этап "Ожидание клиента"'
        ];
    }
    
    public function findLeadsForCloning(): array
    {
        return $this->leadRepository->findByExactBudget(
            $this->config['pipelines']['default'],
            $this->config['statuses']['client_confirmed'],
            $this->config['budget']['specific']
        );
    }
    
    public function cloneLead(array $originalLead): ?int
    {
        $newLeadData = [
            "name" => "Копия: " . $originalLead['name'],
            "price" => $originalLead['price'],
            "pipeline_id" => $originalLead['pipeline_id'] ?? $this->config['pipelines']['default'],
            "status_id" => $this->config['statuses']['waiting_client'],
            "custom_fields_values" => $originalLead['custom_fields_values'] ?? [],
            "responsible_user_id" => $originalLead['responsible_user_id'] ?? null,
            "created_by" => $originalLead['created_by'] ?? 0
        ];
        
        // Удаляем null значения
        $newLeadData = array_filter($newLeadData, fn($value) => $value !== null);
        
        return $this->leadRepository->create($newLeadData);
    }
    
    public function getLeadById(int $leadId): ?array
    {
        return $this->leadRepository->findById($leadId);
    }
}