<?php
require 'vendor/autoload.php'; // Autoload PHPMailer (jeśli używasz Composer)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Funkcja wysyłająca email
function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Konfiguracja serwera SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'budexgdansk@gmail.com'; 
        $mail->Password = 'thclcogenmuslkba';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->CharSet = 'UTF-8'; // Kodowanie UTF-8
        $mail->Encoding = 'base64'; // Kodowanie treści

        // Nadawca
        $mail->setFrom('budexgdansk@gmail.com', 'Budex sp z o.o.');

        // Odbiorca
        $mail->addAddress($to);

        // Treść e-maila
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Wyślij e-mail
        $mail->send();
    } catch (PDOException $e) {
        echo "Błąd połączenia z bazą danych: " . $e->getMessage();
    }
}

function loadNotificationLog($filePath) {
    if (!file_exists($filePath)) {
        file_put_contents($filePath, json_encode([]));
    }
    return json_decode(file_get_contents($filePath), true);
}

function saveNotificationLog($filePath, $data) {
    file_put_contents($filePath, json_encode($data));
}

$logFilePath = 'notification_log.json';
$notificationLog = loadNotificationLog($logFilePath);

// Połączenie z bazą danych
$dsn = 'mysql:host=localhost;dbname=Build_Store;charset=utf8';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = "
        SELECT p.produkt_id, p.nazwa_produktu, p.ilosc_w_magazynie, d.email, d.nazwa_dostawcy 
        FROM Produkty p
        JOIN Dostawcy d ON p.dostawca_id = d.dostawca_id
        WHERE p.ilosc_w_magazynie < 50
    ";
    $stmt = $pdo->query($query);

    // Przetwarzanie wyników
    foreach ($stmt as $row) {
        $productId = $row['produkt_id'];
        $productName = $row['nazwa_produktu'];
        $quantity = $row['ilosc_w_magazynie'];
        $supplierEmail = $row['email'];
        $supplierName = $row['nazwa_dostawcy'];

        $currentDate = date('Y-m-d');
        $lastNotificationDate = $notificationLog[$productId] ?? null;
        $supplyNumber = 100 - $quantity;

        if ($lastNotificationDate === null || strtotime($lastNotificationDate) < strtotime("-7 days")) {
            $subject = "BUDEX DOSTAWA - niski stan magazynowy: $productName";
            $body = "Szanowni Państwo, \n\nmamy tylko $quantity sztuk na stanie produktu $productName. Prosimy o uzupełnienie zapasów przy następnej dostawie o liczbę: $supplyNumber sztuk.\n\nPozdrawiamy,\nZespół Budex sp z o.o.";

            sendEmail($supplierEmail, $subject, $body);

            $notificationLog[$productId] = $currentDate;
        }
    }

    saveNotificationLog($logFilePath, $notificationLog);

} catch (PDOException $e) {
    echo "Błąd połączenia z bazą danych: " . $e->getMessage();
}

?>
