<?php

use PHPUnit\Framework\TestCase;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../email_sender.php';

class NotificationTest extends TestCase
{
    private $testFile = 'test_log.json';

    protected function setUp(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    public function testSendEmail()
    {
        $this->expectNotToPerformAssertions();
        sendEmail('test@example.com', 'Test Subject', 'Test Body');
    }

    public function testLoadNotificationLog()
    {
        file_put_contents($this->testFile, json_encode(['test_key' => 'test_value']));

        $logContent = loadNotificationLog($this->testFile);

        $this->assertIsArray($logContent);
        $this->assertArrayHasKey('test_key', $logContent);
        $this->assertEquals('test_value', $logContent['test_key']);
    }

    public function testSaveNotificationLog()
    {
        $data = ['test_key' => 'test_value'];

        saveNotificationLog($this->testFile, $data);
        
        $this->assertFileExists($this->testFile);

        $savedData = json_decode(file_get_contents($this->testFile), true);
        $this->assertIsArray($savedData);
        $this->assertArrayHasKey('test_key', $savedData);
        $this->assertEquals('test_value', $savedData['test_key']);
    }
}

