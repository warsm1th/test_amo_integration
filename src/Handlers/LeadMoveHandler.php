<?php
declare(strict_types=1);

namespace App\Handlers;

use App\Services\LeadService;

class LeadMoveHandler
{
    public function __construct(
        private readonly LeadService $leadService
    ) {}
    
    public function handle(): array
    {
        $result = $this->leadService->moveHighBudgetLeads();
        return $result;
    }
}