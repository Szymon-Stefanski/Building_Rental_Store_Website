<?php
session_start();
require '../database_connection.php';
include '../email_sender.php';

function cleanCart() {
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
}

// Sprawdzamy, czy użytkownik nacisnął "Powrót"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanCart') {
    cleanCart(); // Wyczyść koszyk
    header('Location: ../index.php'); // Przekierowanie na poprzednią stronę
    exit;
}

// Obsługa wynajmu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['mark_paid'])) {
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; // ID użytkownika z sesji
    $rentalDate = $_POST['rentalDate'] ?? null;
    $returnDate = $_POST['returnDate'] ?? null;
    

    // Walidacja danych
    if (empty($userId) || empty($rentalDate)) {
        echo "Wszystkie pola są wymagane!";
        exit;
    }

    try {
        $db = getDbConnection();

        // Tworzenie rekordu wynajmu
        $stmt = $db->prepare("
            INSERT INTO Wynajmy (uzytkownik_id, data_wynajmu, data_zwrotu, status)
            VALUES (?, ?, ?, 'Nieopłacone')
        ");
        $stmt->execute([$userId, $rentalDate, $returnDate]);

        $rentalId = $db->lastInsertId();
        $status = 'Nieopłacone';
        $id = $rentalId;

        if (!empty($_SESSION['rental_items'])) {
            $positionStmt = $db->prepare("
                INSERT INTO Pozycje_Wynajmu (wynajem_id, produkt_id, ilosc, stawka_dzienna, koszt_calkowity)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($_SESSION['rental_items'] as $item) {
                $positionStmt->execute([
                    $rentalId,
                    $item['produkt_id'],
                    $item['ilosc'],
                    $item['stawka_dzienna'],
                    $item['koszt_calkowity']
                ]);
            }
        }

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
        $start = $rentalDate;
        $end = $returnDate;

        // Przygotowanie wiadomości e-mail
        $subject = "Potwierdzenie wynajmu #$rentalId";
        $message = "Dziękujemy za wypożyczenie sprzętu w naszym sklepie budowlanym. Za pomocą poniższego linku można sprawdzić status Twojego wypożyczenia:
http://localhost/projekt_byt/Rent/paymentRent.php?id=$rentalId";

        // Wyślij e-mail do klienta
        sendEmail($email, $subject, $message);

    } catch (Exception $e) {
        echo "Wystąpił błąd podczas przetwarzania danych: " . $e->getMessage();
        exit;
    }
}

// Obsługa płatności
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    // Pobierz ID wynajmu z formularza
    $rentalId = $_POST['rentalId'] ?? null;

    if (empty($rentalId)) {
        echo "Wszystkie pola są wymagane!";
        exit;
    }

    try {
        $db = getDbConnection();

        // Zaktualizuj status wynajmu na "Opłacone"
        $stmt = $db->prepare("UPDATE Wynajmy SET status = 'Opłacone' WHERE wynajem_id = ?");
        $stmt->execute([$rentalId]);

        // Sprawdź, czy aktualizacja się powiodła
        if ($stmt->rowCount() > 0) {
            $status = 'Opłacone';

            // Po dokonaniu płatności, wyczyść koszyk
            cleanCart();
        } else {
            echo "Nie udało się zaktualizować statusu wynajmu.";
            exit;
        }
    } catch (Exception $e) {
        echo "Wystąpił błąd: " . $e->getMessage();
        exit;
    }
}

// Przypisanie zmiennych do widoku
$status = $status ?? 'Nieopłacone';
$id = $id ?? 0;
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
    <h1>Status wynajmu</h1>
    <?php if ($status === 'Opłacone'): ?>
        <p>To zamówienie jest opłacone.</p>
        <a href="../index.php" name="action" value="cleanCart">Powrót na strone główną</a>
        <a href="rentalStatus.php?id=<?php echo $rentalId; ?>">Szczegóły wynajmu</a>
    <?php else: ?>
        <p>Zamówienie nieopłacone.</p>
        <form method="POST">
            <!-- Ukryte pole do przesyłania ID wynajmu -->
            <input type="hidden" name="rentalId" value="<?php echo $rentalId; ?>">
            <button type="submit" name="mark_paid">Opłać</button>
        </form>
    <?php endif; ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const loader = document.getElementById("loading");
            setTimeout(() => {
                loader.style.display = "none";
            }, 2000); 
        });
    </script>
</body>
</html>


