<?php
session_start();
require '../database_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$zamowienie_id = $_GET['id'];
$conn = getDbConnection();

// Pobierz dane zamówienia
$stmt = $conn->prepare("SELECT * FROM Zamowienia WHERE zamowienie_id = ?");
$stmt->execute([$zamowienie_id]);
$zamowienie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$zamowienie) {
    die('Zamówienie nie istnieje.');
}

// Pobierz pozycje zamówienia
$stmt = $conn->prepare("
    SELECT pz.*, p.nazwa_produktu AS produkt_nazwa, k.nazwa_kategorii 
    FROM Pozycje_Zamowien pz
    JOIN Produkty p ON pz.produkt_id = p.produkt_id
    LEFT JOIN Kategorie k ON p.kategoria_id = k.kategoria_id
    WHERE pz.zamowienie_id = ?
");
$stmt->execute([$zamowienie_id]);
$pozycje = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obsługa formularza aktualizacji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Aktualizuj dane zamówienia
        $stmt = $conn->prepare("UPDATE Zamowienia SET odbiorca_imie = ?, odbiorca_nazwisko = ?, odbiorca_email = ?, adres = ?, status = ? WHERE zamowienie_id = ?");
        $stmt->execute([
            $_POST['odbiorca_imie'],
            $_POST['odbiorca_nazwisko'],
            $_POST['odbiorca_email'],
            $_POST['adres'],
            $_POST['status'],
            $zamowienie_id
        ]);

        // Aktualizuj pozycje zamówienia
        foreach ($_POST['pozycje'] as $pozycja_id => $dane) {
            $stmt = $conn->prepare("UPDATE Pozycje_Zamowien SET ilosc = ?, cena_za_sztuke = ? WHERE pozycja_id = ?");
            $stmt->execute([
                $dane['ilosc'],
                $dane['cena_za_sztuke'],
                $pozycja_id
            ]);
        }

        $conn->commit();
        header("Location: deliveryManagement.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        die("Błąd podczas aktualizacji: " . $e->getMessage());
    }
}

// Znajdz obraz produktu
function findProductImage($productId, $categoryName, $productName) {
    $imageDir = "../Image/Product/$categoryName/";
    $extensions = ['png', 'jpg', 'gif'];

    foreach ($extensions as $extension) {
        $filePath = $imageDir . $productId . ".1." . $extension;
        if (file_exists($filePath)) {
            return $filePath;
        }
    }

    return "Brak obrazu dla produktu: " . htmlspecialchars($productName);
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj Zamówienie</title>
</head>
<body>
<h1>Edytuj Zamówienie #<?= htmlspecialchars($zamowienie_id) ?></h1>

<form method="post">
    <h2>Dane odbiorcy</h2>
    <label>
        Imię: <input type="text" name="odbiorca_imie" value="<?= htmlspecialchars($zamowienie['odbiorca_imie']) ?>">
    </label><br>
    <label>
        Nazwisko: <input type="text" name="odbiorca_nazwisko" value="<?= htmlspecialchars($zamowienie['odbiorca_nazwisko']) ?>">
    </label><br>
    <label>
        Email: <input type="email" name="odbiorca_email" value="<?= htmlspecialchars($zamowienie['odbiorca_email']) ?>">
    </label><br>
    <label>
        Adres: <textarea name="adres"><?= htmlspecialchars($zamowienie['adres']) ?></textarea>
    </label><br>
    <label>
        Status: <input type="text" name="status" value="<?= htmlspecialchars($zamowienie['status']) ?>">
    </label><br>

    <h2>Pozycje Zamówienia</h2>
    <?php foreach ($pozycje as $pozycja): ?>
        <div>
            <strong><?= htmlspecialchars($pozycja['produkt_nazwa']) ?></strong><br>
            <img src="<?= findProductImage($pozycja['produkt_id'], htmlspecialchars($pozycja['nazwa_kategorii'] ?? 'unknown'), $pozycja['produkt_nazwa']) ?>"
            <label>
                Ilość: <input type="number" name="pozycje[<?= $pozycja['pozycja_id'] ?>][ilosc]" value="<?= htmlspecialchars($pozycja['ilosc']) ?>">
            </label><br>
            <label>
                Cena za sztukę: <input type="number" step="0.01" name="pozycje[<?= $pozycja['pozycja_id'] ?>][cena_za_sztuke]" value="<?= htmlspecialchars($pozycja['cena_za_sztuke']) ?>">
            </label><br>
        </div>
    <?php endforeach; ?>

    <button type="submit">Zapisz zmiany</button>
</form>

<a href="deliveryManagement.php">Powrót do listy zamówień</a>
</body>
</html>
