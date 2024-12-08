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
        header('Location: ../index.php');
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
        <link rel="stylesheet" href="../Style/style_login.css">
    </head>
    <body>
        <?php if (isset($error)): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>
        <div class="login-wrapper">
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

            <div class="registration-benefits">
                <h2>Rejestracja</h2>
                <p>Co możesz zyskać rejestrując się na <strong>budex.pl?</strong></p>
                <ul>
                    <li><img src="../Image/Icon/discount.png" alt="Rabaty"> Rabaty dla stałych klientów</li>
                    <li><img src="../Image/Icon/delivery.png" alt="Szybka dostawa"> Szybsze składanie zamówień</li>
                    <li><img src="../Image/Icon/file.png" alt="Historia"> Dostęp do historii zamówień</li>
                </ul>
                <a href="register.php">
                    <button class="register-btn">Załóż konto</button>
                </a>
            </div>
        </div>


    </body>
</html>

