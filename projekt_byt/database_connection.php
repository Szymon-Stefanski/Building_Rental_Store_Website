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

    // Sprawdzamy, czy baza danych istnieje, jeśli tak, używamy jej
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    $existingDb = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingDb) {
        // Baza danych istnieje, przechodzimy do jej używania
        $pdo->exec("USE $dbName");
    } else {
        // Jeśli baza danych nie istnieje, zgłaszamy błąd
        throw new Exception("Baza danych '$dbName' nie istnieje.");
    }

    // Sprawdzanie i uruchamianie pliku schema.sql
    $sqlFile = __DIR__ . '/schema.sql';
    if (file_exists($sqlFile)) {
        // Wczytanie zawartości pliku SQL
        $sql = file_get_contents($sqlFile);

        // Podzielenie zapytań na pojedyncze komendy
        $queries = explode(";", $sql);

        // Uruchamianie każdej komendy
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    $pdo->exec($query); // Wykonanie zapytania
                } catch (PDOException $e) {
                    echo "Błąd podczas wykonywania zapytania: " . $e->getMessage();
                }
            }
        }

        echo "Tabele zostały utworzone (lub baza danych była już gotowa).";
    } else {
        throw new Exception("Plik schema.sql nie został znaleziony!");
    }
}

try {
    initializeDatabase($pdo);
} catch (Exception $e) {
    echo "Wystąpił błąd: " . $e->getMessage();
}
?>
