<?php
session_start();
require '../database_connection.php';
include '../email_sender.php';

if (!isset($_GET['id'])) {
    echo "Brak ID kategorii w URL.";
    exit;
}

$id = $_GET['id'];

// Pobranie statusu zamówienia
try {
    $stmt = getDbConnection()->prepare(
        "SELECT status, odbiorca_email FROM Zamowienia WHERE zamowienie_id = ?"
    );
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if (!$result) {
        echo "Nie znaleziono zamówienia o podanym ID.";
        exit;
    }

    $status = $result['status'];
    $email = $result['odbiorca_email'];
} catch (Exception $e) {
    echo "Wystąpił błąd podczas pobierania danych: " . $e->getMessage();
    exit;
}

$subject = "Potwierdzenie opłaty zamówienia #$id";
$message = "Dziękujemy za opłacenie zamówienia w naszym sklepie budowlanym. Za pomocą poniższego linku można sprawdzić status Twojego zamówienia:
http://localhost/projekt_byt/Store/deliveryStatus.php?id=$id";

// Zmiana statusu na opłacone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    try {
        $updateStmt = getDbConnection()->prepare(
            "UPDATE Zamowienia SET status = 'Opłacone' WHERE zamowienie_id = ?"
        );
        $updateStmt->execute([$_GET['id']]);
        $status = 'Opłacone';
        sendEmail($email, $subject, $message);
        header("Refresh:0");
        exit;
    } catch (Exception $e) {
        echo "Wystąpił błąd podczas zmiany statusu: " . $e->getMessage();
        exit;
    }
}

?>
<!-- Strona akutalnie jest tylko symulacyjna więc posiada tylko i wyłącznie przycisk zmieniający status w bazie i wysyłający krótki email potwierdzenia zapłaty-->
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status zamówienia</title>
</head>
<body>
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
</body>
</html>
