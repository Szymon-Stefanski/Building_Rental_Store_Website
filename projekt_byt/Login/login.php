<?php
    session_start();
    require 'vendor/autoload.php';
    require '../database_connection.php';
    $source = isset($_GET['source']) ? $_GET['source'] : '../index.php';

    // Załaduj zmienne środowiskowe z pliku .env
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();

    // Ustawienia Google API Client
    $client = new Google_Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
    $client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);

    // Sprawdź, czy użytkownik jest już zalogowany za pomocą Google
    if (isset($_GET['code'])) {
        try {
            // Autoryzacja użytkownika z kodem autoryzacyjnym
            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

            if (isset($token['error'])) {
                throw new Exception("Błąd autoryzacji: " . $token['error']);
            }

            $_SESSION['access_token'] = $token;
            header('Location: ' . filter_var($client->getRedirectUri(), FILTER_SANITIZE_URL));
            exit;
        } catch (Exception $e) {
            $error = "Błąd autoryzacji: " . $e->getMessage();
        }
    }

    // Jeśli istnieje token dostępu w sesji, ustaw go w kliencie Google
    if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
        $client->setAccessToken($_SESSION['access_token']);
    }


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $source = isset($_POST['source']) ? $_POST['source'] : '..\index.php';

        if (empty($login) || empty($password)) {
            $error = "Wszystkie pola są wymagane.";
        } else {
            try {
                $stmt = getDbConnection()->prepare("SELECT uzytkownik_id, login, haslo FROM uzytkownicy WHERE login = ?");
                $stmt->execute([$login]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['haslo'])) {
                    $_SESSION['user_id'] = $user['uzytkownik_id'];
                    $_SESSION['username'] = $user['login'];
                    header("Location: $source");
                    exit;
                } else {
                    $error = "Nieprawidłowa nazwa użytkownika lub hasło.";
                }
            } catch (PDOException $e) {
                $error = "Błąd bazy danych: " . $e->getMessage();
            }
        }
    }

    // Utwórz URL autoryzacji Google, jeśli nie ma tokena
    if (!isset($authUrl)) {
        $authUrl = $client->createAuthUrl();
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

