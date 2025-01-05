<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Google\Client as Google_Client;
use Google\Service\Oauth2 as Google_Service_Oauth2;

class GoogleAuthHandler
{
    private $db;
    private $client;

    public function __construct($db, $client)
    {
        $this->db = $db;
        $this->client = $client;
    }

    public function handle($authCode)
    {
        // Symulacja działania bezpośrednio bez wywołań API
        $_SESSION['user'] = [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];
    }
}

