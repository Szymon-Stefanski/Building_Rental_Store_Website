<?php
require 'email_sender.php'; // Import funkcji sendEmail

// Plik przechowujący zapisane adresy e-mail
$file = 'emails.txt';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']); // Pobierz treść wiadomości

    if (empty($message)) {
        $status = '<p style="color: red;">Treść wiadomości nie może być pusta.</p>';
    } elseif (!file_exists($file)) {
        $status = '<p style="color: red;">Brak subskrybentów do wysłania wiadomości.</p>';
    } else {
        $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($emails)) {
            $status = '<p style="color: red;">Brak subskrybentów do wysłania wiadomości.</p>';
        } else {
            // Wysyłanie wiadomości do każdego subskrybenta
            foreach ($emails as $email) {
                $subject = 'Newsletter od Hurtowni Budex';
                sendEmail($email, $subject, $message);
            }
            $status = '<p style="color: green;">Wiadomość została pomyślnie wysłana do wszystkich subskrybentów!</p>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Newslettera</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
            background-color:rgb(255, 136, 0);
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        textarea {
            width: 97%;
            height: 200px;
            font-size: 16px;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .status {
            margin-top: 20px;
            text-align: center;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Wyślij wiadomość do subskrybentów</h1>
        <form method="POST">
            <textarea name="message" placeholder="Wpisz tutaj treść wiadomości..."></textarea>
            <button type="submit">WYŚLIJ</button>
        </form>
    </div>
</body>
</html>
