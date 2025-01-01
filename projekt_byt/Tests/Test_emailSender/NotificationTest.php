<?php

use PHPUnit\Framework\TestCase;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '../helpers.php';

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
        $mail = $this->createMock(PHPMailer::class);

        $mail->method('send')->willReturn(true);
        $this->assertTrue($mail->send());
    }

    public function testLoadNotificationLog()
    {

        file_put_contents($this->testFile, 'Test log content');


        $logContent = loadNotificationLog($this->testFile);
        $this->assertEquals('Test log content', $logContent);

        unlink($this->testFile);
    }

    public function testSaveNotificationLog()
    {

        saveNotificationLog('Test log content', $this->testFile);

        $this->assertFileExists($this->testFile);

        $this->assertEquals('Test log content', file_get_contents($this->testFile));
    }
}

