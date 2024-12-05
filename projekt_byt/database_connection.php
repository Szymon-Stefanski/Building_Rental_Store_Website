<?php
$host = 'localhost';
$db = 'Build_Store';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

function initializeDatabase(PDO $pdo) {
    $dbName = 'Build_Store';

    // Sprawdzamy, czy baza danych istnieje
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    $existingDb = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingDb) {
        // Jeśli baza danych nie istnieje, tworzymy ją
        $pdo->exec("CREATE DATABASE $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    }

    // Używamy bazy danych
    $pdo->exec("USE $dbName");

    // Sprawdzamy, czy kluczowa tabela istnieje
    $stmt = $pdo->query("SHOW TABLES LIKE 'Kategorie'");
    $tableExists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tableExists) {
        // Jeśli tabela nie istnieje, uruchamiamy plik schema.sql
        $sqlFile = __DIR__ . '/schema.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $queries = explode(";", $sql);

            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    try {
                        $pdo->exec($query);
                    } catch (PDOException $e) {
                        echo "Błąd podczas wykonywania zapytania: " . $e->getMessage();
                    }
                }
            }
        } else {
            throw new Exception("Plik schema.sql nie został znaleziony!");
        }
    }
}

try {
    initializeDatabase($pdo);
} catch (Exception $e) {
    echo "Wystąpił błąd: " . $e->getMessage();
}

function getDbConnection() {
    global $pdo; // Zwracamy połączenie PDO
    return $pdo;
}
?>
