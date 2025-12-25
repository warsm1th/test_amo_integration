<?php
declare(strict_types=1);

namespace App\Factories;

use App\Repositories\{LeadRepository, NoteRepository, TaskRepository};
use App\Services\{LeadService, NoteService, TaskService};
use App\Handlers\LeadCloneHandler;

class LeadCloneHandlerFactory
{
    public static function create(array $config): LeadCloneHandler
    {
        $client = ClientFactory::createClient($config);
        
        $leadRepository = new LeadRepository($client);
        $noteRepository = new NoteRepository($client);
        $taskRepository = new TaskRepository($client);
        
        $leadService = new LeadService($leadRepository, $config);
        $noteService = new NoteService($noteRepository);
        $taskService = new TaskService($taskRepository);
        
        return new LeadCloneHandler($leadService, $noteService, $taskService);
    }
}