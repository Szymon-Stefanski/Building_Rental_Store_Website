<?php
session_start();
require_once 'Login/vendor/autoload.php';

// Ładowanie zmiennych z pliku .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Ustawienia klienta Google
$client = new Google\Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope(Google\Service\Oauth2::USERINFO_PROFILE);
$client->addScope(Google\Service\Oauth2::USERINFO_EMAIL);

// Jeśli nie ma kodu autoryzacji w URL
if (isset($_GET['code'])) {
    $accessToken = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $_SESSION['access_token'] = $accessToken;

    if (isset($accessToken['access_token'])) {
        $client->setAccessToken($accessToken['access_token']);
        
        $oauthService = new Google\Service\Oauth2($client);
        $userInfo = $oauthService->userinfo->get();
        
        $_SESSION['user_id'] = $userInfo->id;
        $_SESSION['username'] = $userInfo->name;
        $_SESSION['email'] = $userInfo->email;
        
        header('Location: index.php');
        exit;
    } else {
        echo "Błąd autoryzacji. Spróbuj ponownie.";
        exit;
    }
} else {
    echo "Błąd: Brak kodu autoryzacji.";
    exit;
}
?>
