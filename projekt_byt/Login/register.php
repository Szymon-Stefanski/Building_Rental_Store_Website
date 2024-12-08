<?php
session_start();
require '../database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);

    if (!$email) {
        echo "Nieprawidłowy email!";
        exit;
    }

    $stmt = getDbConnection()->prepare("INSERT INTO uzytkownicy 
        (login, email, haslo, imie, nazwisko, numer_telefonu, adres, rola)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password, $first_name, $last_name, $phone_number, $address, 'user']);

    $_SESSION['user_id'] = getDbConnection()->lastInsertId();
    $_SESSION['username'] = $username;
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
    <link rel="stylesheet" href="style_register.css">
</head>
<body>
<form action="register.php" method="POST">
    <h1>Rejestracja</h1>

    <label for="username">Nazwa użytkownika:</label>
    <input type="text" id="username" name="username" required><br>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required><br>

    <label for="password">Hasło:</label>
    <input type="password" id="password" name="password" required><br>

    <label for="first_name">Imię:</label>
    <input type="text" id="first_name" name="first_name" required><br>

    <label for="last_name">Nazwisko:</label>
    <input type="text" id="last_name" name="last_name" required><br>

    <label for="phone_number">Numer telefonu:</label>
    <input type="text" id="phone_number" name="phone_number" required><br>

    <label for="address">Adres:</label>
    <textarea id="address" name="address" required></textarea><br>

    <button type="submit">Zarejestruj</button>
</form>

<p>Masz już konto? <a href="login.php">Zaloguj się</a></p>
</body>
</html>
