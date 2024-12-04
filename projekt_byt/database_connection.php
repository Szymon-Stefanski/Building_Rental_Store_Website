<?php
$host = 'localhost';
$db = 'Store';
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
    

    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE $dbName");


    $sqlFile = __DIR__ . '/schema.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
    } else {
        throw new Exception("Plik schema.sql nie został znaleziony!");
    }
}


try {
    initializeDatabase($pdo);
    echo "Baza danych została utworzona lub już istnieje.";
} catch (Exception $e) {
    echo "Wystąpił błąd podczas inicjalizacji bazy danych: " . $e->getMessage();
}
?>
