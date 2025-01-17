<?php
session_start();
require '../database_connection.php';
include '../email_sender.php';

// Sprawdź, czy formularz został przesłany
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pobierz dane z formularza
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;  // Sprawdzamy ID użytkownika z sesji
    $rentalDate = $_POST['rentalDate'] ?? null;
    $returnDate = $_POST['returnDate'] ?? null;

    // Debugowanie, sprawdzenie, czy rentalDate jest ustawione
    echo 'rentalDate: ' . (isset($rentalDate) ? $rentalDate : 'brak') . '<br>';
    echo 'userId: ' . (isset($userId) ? $userId : 'brak') . '<br>';

    // Prosta walidacja
    if (empty($userId) || empty($rentalDate)) {
        echo "Wszystkie pola są wymagane!";
        exit;
    }

    // Dodaj wynajem do bazy danych
    try {
        $db = getDbConnection();

        // Tworzenie rekordu wynajmu
        $stmt = $db->prepare("
            INSERT INTO Wynajmy (uzytkownik_id, data_wynajmu, data_zwrotu, status)
            VALUES (?, ?, ?, 'Nieopłacone')
        ");
        $stmt->execute([$userId, $rentalDate, $returnDate]);

        // Pobierz ID nowo utworzonego wynajmu
        $rentalId = $db->lastInsertId();

        // Pobranie danych użytkownika
        $userStmt = $db->prepare("SELECT email, imie, nazwisko FROM Uzytkownicy WHERE uzytkownik_id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();

        if (!$user) {
            echo "Nie znaleziono użytkownika.";
            exit;
        }

        $email = $user['email'];
        $firstName = $user['imie'];
        $lastName = $user['nazwisko'];

        // Przygotowanie wiadomości e-mail
        $subject = "Potwierdzenie wynajmu #$rentalId";
        $message = "Dziękujemy, $firstName $lastName, za zgłoszenie wynajmu. Aby sfinalizować transakcję, opłać wynajem za pomocą poniższego linku:
http://localhost/projekt_byt/Rent/paymentRent.php?id=$rentalId";

        // Wyślij e-mail do klienta
        sendEmail($email, $subject, $message);

        // Przekierowanie na stronę z podsumowaniem
        header("Location: paymentRent.php?id=$rentalId");
        exit;
    } catch (Exception $e) {
        echo "Wystąpił błąd podczas przetwarzania danych: " . $e->getMessage();
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status wynajmu</title>
    <link rel="stylesheet" href="../Style/style_payment.css">
</head>
<body>
    <div id="loading">
    <div></div>
    <div></div>
    <div></div>
    </div>
<h1>Status zamówienia</h1>
<?php if ($status === 'Opłacone'): ?>
    <p>To zamówienie jest opłacone.</p>
    <a href="../index.php">Powrót do strony głównej</a>
    <a href="deliveryStatus.php?id=<?php echo $id;?>">Szczegóły zamówienia</a>
<?php else: ?>
    <p>Zamówienie nieopłacone.</p>
    <form method="POST">
        <button type="submit" name="mark_paid">Opłać</button>
    </form>
<?php endif; ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const loader = document.getElementById("loading");
            setTimeout(() => {
                loader.style.display = "none";
            }, 20000); 
        });
    </script>
</body>
</html>


