<?php
session_start();
require '../database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];

    $stmt = getDbConnection()->prepare("INSERT INTO uzytkownicy (login, email, haslo, imie, nazwisko, numer_telefonu, adres, rola)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password, $first_name, $last_name, $phone_number, $address, 'user']);

    $_SESSION['user_id'] = getDbConnection()->lastInsertId();
    $_SESSION['username'] = $username;
    header('Location: index.php');
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

    <label for="username">Nazwa użytkownika:</label><br>
    <input type="text" id="username" name="username" required><br><br>

    <label for="email">Email:</label><br>
    <input type="email" id="email" name="email" required><br><br>

    <label for="password">Hasło:</label><br>
    <input type="password" id="password" name="password" required><br><br>

    <label for="first_name">Imię:</label><br>
    <input type="text" id="first_name" name="first_name" required><br><br>

    <label for="last_name">Nazwisko:</label><br>
    <input type="text" id="last_name" name="last_name" required><br><br>

    <label for="phone_number">Numer telefonu:</label><br>
    <input type="text" id="phone_number" name="phone_number" required><br><br>

    <label for="address">Adres:</label><br>
    <textarea id="address" name="address" required></textarea><br><br>

    <button type="submit">Zarejestruj</button>
</form>

<p>Masz już konto? <a href="login.php">Zaloguj się</a></p>
</body>
</html>
