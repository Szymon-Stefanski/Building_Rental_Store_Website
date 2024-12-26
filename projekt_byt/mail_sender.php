<?php
//PROSZĘ NIE GRZEBAĆ - FUNKCJA DZIAŁA ALE TRZEBA TO JESZCZE Z BAZĄ DANYCH POŁĄCZYĆ

require 'vendor/autoload.php'; // Autoload PHPMailer (jeśli używasz Composer)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Konfiguracja serwera SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';            // Adres serwera SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'budexgdansk@gmail.com'; // Twój e-mail
        $mail->Password = 'thclcogenmuslkba'; // Hasło aplikacji (ważne: nie używaj zwykłego hasła!)
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
        echo "E-mail został wysłany do $to\n";
    } catch (Exception $e) {
        echo "E-mail nie został wysłany. Błąd: {$mail->ErrorInfo}\n";
    }
}

// Wywołanie funkcji
sendEmail(
    's22043@pjwstk.edu.pl', 
    'Testowy e-mail z PHPMailer', 
    'To jest treść testowego e-maila wysłanego za pomocą PHPMailer.'
);
?>
