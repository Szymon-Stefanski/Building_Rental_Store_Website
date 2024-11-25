<?php
session_start();
require '../database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['username'];
    $password = $_POST['password'];

    $stmt = getDbConnection()->prepare("SELECT uzytkownik_id, login, haslo FROM uzytkownicy WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['haslo'])) {
        $_SESSION['user_id'] = $user['uzytkownik_id'];
        $_SESSION['username'] = $user['login'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Nieprawidłowa nazwa użytkownika lub hasło.";
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logowanie</title>
    <link rel="stylesheet" href="style_login.css">
</head>
<body>
<?php if (isset($error)): ?>
    <p><?php echo $error; ?></p>
<?php endif; ?>
<div class="login-form">
    <h1>Logowanie</h1>
    <form action="login.php" method="POST">
        <label for="username">Nazwa użytkownika:</label><br>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Hasło:</label><br>
        <input type="password" id="password" name="password" required><br><br>
        <button type="submit">Zaloguj</button>
    </form>
</div>
<div class="register-link">
    <p>Nie masz jeszcze konta? <a href="register.php">Zarejestruj się</a></p>
</div>
</body>
</html>
