<?php
session_start();
require 'vendor/autoload.php';
require '../database_connection.php';

$source = isset($_GET['source']) ? $_GET['source'] : '../index.php';

// Ładowanie zmiennych środowiskowych
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Inicjalizacja klienta Google
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
$client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);


// Obsługa już zalogowanego użytkownika
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
}

// Generowanie URL do autoryzacji, jeśli użytkownik nie jest zalogowany
if (!isset($authUrl)) {
    $authUrl = $client->createAuthUrl();
    error_log("Generowanie URL do autoryzacji Google: " . $authUrl);
}
?>


<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logowanie</title>
        <link rel="stylesheet" href="../Style/style_login.css">
    </head>
    <body>
        
        <div class="login-wrapper">
            <div class="login-form">
                <h1>Logowanie</h1>
                <form action="login.php" method="POST">
                    <input type="hidden" name="source" value="<?php echo htmlspecialchars($source, ENT_QUOTES, 'UTF-8'); ?>">
                    <label for="username">Nazwa użytkownika:</label><br>
                    <input type="text" id="username" name="username" required><br><br>
                    <label for="password">Hasło:</label><br>
                    <input type="password" id="password" name="password" required><br><br>
                    <button type="submit">Zaloguj</button>

                    <a href="index.php">
                        <button class="back-btn" onclick="window.history.back()">Powrót</button>
                    </a>
                </form>
                
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Przycisk logowania przez Google -->
                <div class="google-login-container">
                    <a href="<?php echo $authUrl; ?>" class="google-login-btn">
                        <img src="../Image/Icon/search.png" class="google-logo">
                        Zaloguj się przez Google
                    </a>
                </div>
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

