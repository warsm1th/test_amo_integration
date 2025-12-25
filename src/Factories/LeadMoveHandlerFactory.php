<?php
declare(strict_types=1);

namespace App\Factories;

use App\Clients\AmoCrmV4Client;
use App\Repositories\LeadRepository;
use App\Services\LeadService;
use App\Handlers\LeadMoveHandler;

class LeadMoveHandlerFactory
{
    public static function create(array $config): LeadMoveHandler
    {
        $client = ClientFactory::createClient($config);
        $leadRepository = new LeadRepository($client);
        $leadService = new LeadService($leadRepository, $config);
        
        return new LeadMoveHandler($leadService);
    }
}