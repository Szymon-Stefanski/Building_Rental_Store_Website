<?php

use PHPUnit\Framework\TestCase;
use Google\Client as Google_Client;

require_once 'GoogleAuthHandler.php';

class RedirectTest extends TestCase
{
    public function testHandle(): void
    {
        // Mock PDO
        $dbMock = $this->createMock(PDO::class);

        // Mock Google_Client
        $clientMock = $this->createMock(Google_Client::class);

        // Tworzenie instancji klasy GoogleAuthHandler
        $handler = new GoogleAuthHandler($dbMock, $clientMock);

        // Wywołanie metody handle
        $handler->handle('test_auth_code');

        // Weryfikacja danych w sesji
        $this->assertArrayHasKey('user', $_SESSION);
        $this->assertEquals('Test User', $_SESSION['user']['name']);
        $this->assertEquals('test@example.com', $_SESSION['user']['email']);
    }
}



