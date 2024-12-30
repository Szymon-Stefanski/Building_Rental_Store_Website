<?php
session_start();
require 'database_connection.php';
require_once 'Login/vendor/autoload.php';

// Ładowanie zmiennych z pliku .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Ustawienia Google API Client
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope(Google_Service_Oauth2::USERINFO_EMAIL);
$client->addScope(Google_Service_Oauth2::USERINFO_PROFILE);

// Obsługa autoryzacji Google
if (isset($_GET['code'])) {
    try {
        // Pobierz token autoryzacyjny
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        $client->setAccessToken($token['access_token']);

        // Pobierz dane użytkownika z Google
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        $userinfo = [
            'email' => $google_account_info->email,
            'first_name' => $google_account_info->givenName,
            'last_name' => $google_account_info->familyName,
        ];

        // Połączenie z bazą danych
        $db = getDbConnection();

        // Sprawdź, czy użytkownik już istnieje
        $stmt = $db->prepare("SELECT uzytkownik_id FROM uzytkownicy WHERE email = ?");
        $stmt->execute([$userinfo['email']]);
        $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_user) {
            // Użytkownik istnieje
            $user_id = $existing_user['uzytkownik_id'];
        } else {
            // Dodaj nowego użytkownika do bazy
            $stmt = $db->prepare(
                "INSERT INTO uzytkownicy (imie, nazwisko, email, login, haslo, numer_telefonu, adres, rola) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $default_login = strtolower($userinfo['first_name'] . '.' . $userinfo['last_name']);
            $default_password = password_hash('google_login', PASSWORD_DEFAULT);

            $stmt->execute([
                $userinfo['first_name'],
                $userinfo['last_name'],
                $userinfo['email'],
                $default_login,
                $default_password,
                null, // numer_telefonu
                null, // adres
                'user' // rola
            ]);

            $user_id = $db->lastInsertId();
        }

        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $userinfo['email'];

        $source = isset($_GET['source']) ? $_GET['source'] : 'index.php';
        header('Location: ' . filter_var($source, FILTER_SANITIZE_URL));
        exit;
    } catch (Exception $e) {
        error_log("Błąd autoryzacji Google: " . $e->getMessage());
        die("Wystąpił błąd podczas autoryzacji przez Google.");
    }
} else {
    if (!isset($_SESSION['user_id'])) {
        // Brak sesji - przekieruj do logowania
        header("Location: ../index.php");
        exit;
    }
}
?>



