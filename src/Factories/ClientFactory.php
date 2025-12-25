<?php
declare(strict_types=1);

namespace App\Factories;

use App\Clients\AmoCrmV4Client;

class ClientFactory
{
    private static ?AmoCrmV4Client $client = null;

    private function __construct()
    {
        
    }
    
    public static function createClient(array $config): AmoCrmV4Client
    {
        if (self::$client === null) {
            self::$client = new AmoCrmV4Client($config['amo']);
        }
        return self::$client;
    }

    private function __clone()
    {

    }
}