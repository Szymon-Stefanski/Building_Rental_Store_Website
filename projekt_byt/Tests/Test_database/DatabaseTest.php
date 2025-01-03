<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../database_connection.php';

class DatabaseTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('mysql:host=localhost;dbname=Build_Store', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testDatabaseConnection(): void
    {
        $this->assertInstanceOf(PDO::class, $this->pdo, 'Połączenie z bazą danych nie zostało utworzone.');
    }

    public function testTableExists(): void
    {
        $tableName = 'Kategorie';
        $stmt = $this->pdo->query("SHOW TABLES LIKE '$tableName'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($result, "Tabela '$tableName' nie istnieje w bazie danych.");
    }

    public function testInsertAndFetchData(): void
    {
        $tableName = 'Kategorie';
    
        $this->pdo->exec("
            INSERT INTO $tableName (nazwa_kategorii, opis) 
            VALUES ('Testowa Kategoria', 'Opis testowej kategorii')
        ");
    
        $stmt = $this->pdo->query("
            SELECT kategoria_id, nazwa_kategorii, opis 
            FROM $tableName 
            WHERE nazwa_kategorii = 'Testowa Kategoria'
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        $this->assertNotEmpty($result, "Nie znaleziono danych w tabeli '$tableName'.");
        $this->assertEquals('Testowa Kategoria', $result['nazwa_kategorii'], "Nazwa kategorii nie jest zgodna.");
        $this->assertEquals('Opis testowej kategorii', $result['opis'], "Opis kategorii nie jest zgodny.");
    
        $this->pdo->exec("DELETE FROM $tableName WHERE nazwa_kategorii = 'Testowa Kategoria'");
    }
}
