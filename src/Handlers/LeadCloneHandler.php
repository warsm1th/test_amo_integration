<?php
declare(strict_types=1);

namespace App\Handlers;

use App\Services\LeadService;
use App\Services\NoteService;
use App\Services\TaskService;

class LeadCloneHandler
{
    public function __construct(
        private readonly LeadService $leadService,
        private readonly NoteService $noteService,
        private readonly TaskService $taskService
    ) {}
    
    public function handle(): array
    {
        $leads = $this->leadService->findLeadsForCloning();
        $results = [];
        
        foreach ($leads as $lead) {
            $newLeadId = $this->leadService->cloneLead($lead);
            
            if ($newLeadId !== null) {
                $notesCopied = $this->noteService->copyNotes($lead['id'], $newLeadId);
                $tasksCopied = $this->taskService->copyTasks($lead['id'], $newLeadId);
                
                $results[] = [
                    'original_id' => $lead['id'],
                    'new_id' => $newLeadId,
                    'notes_copied' => $notesCopied,
                    'tasks_copied' => $tasksCopied,
                    'original_name' => $lead['name'],
                    'budget' => $lead['price']
                ];
            } else {
                error_log("Ошибка копирования сделки {$lead['id']}");
            }
        }
        
        $summary = [
            'found' => count($leads),
            'cloned' => count($results),
            'details' => $results,
            'message' => 'Сделки с бюджетом = 4999 клонированы на этап "Ожидание клиента"'
        ];
        
        return $summary;
    }
}