<?php
require 'vendor/autoload.php';

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

// Połączenie z bazą danych
$dsn = 'mysql:host=localhost;dbname=Build_Store;charset=utf8';
$username = 'root';
$password = '';

    try {
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Zapytanie SQL
        $query = "
            SELECT p.nazwa_produktu, p.ilosc_w_magazynie, d.email, d.nazwa_dostawcy 
            FROM Produkty p
            JOIN Dostawcy d ON p.dostawca_id = d.dostawca_id
            WHERE p.ilosc_w_magazynie < 50
        ";
        $stmt = $pdo->query($query);

        // Przetwarzanie wyników
        foreach ($stmt as $row) {
            $productName = $row['nazwa_produktu'];
            $quantity = $row['ilosc_w_magazynie'];
            $supplierEmail = $row['email'];
            $supplierName = $row['nazwa_dostawcy'];

            // Przygotowanie treści e-maila
            $subject = "BUDEX - niski stan magazynowy: $productName";
            $body = "Szanowni Panstwo,\n\nProdukt \"$productName\" ma tylko $quantity sztuk na stanie. Prosimy o uzupelnienie zapasow.\n\nPozdrawiam,\nBudex sp z o.o.";

            // Wysyłanie e-maila
            sendEmail($supplierEmail, $subject, $body);
        }
    } catch (PDOException $e) {
        echo "Błąd połączenia z bazą danych: " . $e->getMessage();
    }
?>
