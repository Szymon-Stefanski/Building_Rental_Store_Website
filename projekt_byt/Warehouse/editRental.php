<?php
session_start();
require '../database_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$wynajem_id = $_GET['id'];
$conn = getDbConnection();

// Pobierz dane wynajmu
$stmt = $conn->prepare("SELECT * FROM Wynajmy WHERE wynajem_id = ?");
$stmt->execute([$wynajem_id]);
$wynajem = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$wynajem) {
    die('Wynajem nie istnieje.');
}

// Pobierz pozycje wynajmu
$stmt = $conn->prepare("
    SELECT pw.*, p.nazwa_produktu AS produkt_nazwa, k.nazwa_kategorii 
    FROM Pozycje_Wynajmu pw
    JOIN Produkty p ON pw.produkt_id = p.produkt_id
    LEFT JOIN Kategorie k ON p.kategoria_id = k.kategoria_id
    WHERE pw.wynajem_id = ?
");
$stmt->execute([$wynajem_id]);
$pozycje = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obsługa formularza aktualizacji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        // Aktualizuj dane wynajmu
        $stmt = $conn->prepare("UPDATE Wynajmy SET uzytkownik_id = ?, data_wynajmu = ?, data_zwrotu = ?, status = ? WHERE wynajem_id = ?");
        $stmt->execute([
            $_POST['uzytkownik_id'],
            $_POST['data_wynajmu'],
            $_POST['data_zwrotu'],
            $_POST['status'],
            $wynajem_id
        ]);

        // Aktualizuj pozycje wynajmu
        foreach ($_POST['pozycje'] as $pozycja_id => $dane) {
            $stmt = $conn->prepare("UPDATE Pozycje_Wynajmu SET ilosc = ?, stawka_dzienna = ?, koszt_calkowity = ? WHERE pozycja_wynajmu_id = ?");
            $stmt->execute([
                $dane['ilosc'],
                $dane['stawka_dzienna'],
                $dane['koszt_calkowity'],
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
    <title>Edytuj Wynajem</title>
    <link rel="stylesheet" href="../Style/style_edit_order.css">
</head>
<body>
    <header class="header">
        <h1>Edytuj Wynajem #<?= htmlspecialchars($wynajem_id) ?></h1>
        <a href="deliveryManagement.php" class="back-button">
            <img src="../Image/Icon/log-in.png" alt="Powrót" class="button-icon"> Powrót
        </a>
    </header>

    <form method="post" class="order-form">
        <section class="customer-data">
            <h2 class="odbiorca">Dane wynajmującego</h2>
            <label>
                ID Użytkownika: <input type="number" name="uzytkownik_id" value="<?= htmlspecialchars($wynajem['uzytkownik_id']) ?>">
            </label><br>
            <label>
                Data Wynajmu: <input type="date" name="data_wynajmu" value="<?= htmlspecialchars($wynajem['data_wynajmu']) ?>">
            </label><br>
            <label>
                Data Zwrotu: <input type="date" name="data_zwrotu" value="<?= htmlspecialchars($wynajem['data_zwrotu']) ?>">
            </label><br>
            <label>
                Status: <input type="text" name="status" value="<?= htmlspecialchars($wynajem['status']) ?>">
            </label><br>
        </section>

        <section class="order-items">
            <h2 class="pozycje">Pozycje Wynajmu</h2>
            <?php foreach ($pozycje as $pozycja): ?>
                <div class="order-item">
                    <strong><?= htmlspecialchars($pozycja['produkt_nazwa']) ?></strong><br>
                    <img src="<?= findProductImage($pozycja['produkt_id'], htmlspecialchars($pozycja['nazwa_kategorii'] ?? 'unknown'), $pozycja['produkt_nazwa']) ?>" alt="Produkt">
                    <label>
                        Ilość: <input type="number" name="pozycje[<?= $pozycja['pozycja_wynajmu_id'] ?>][ilosc]" value="<?= htmlspecialchars($pozycja['ilosc']) ?>">
                    </label><br>
                    <label>
                        Stawka dzienna: <input type="number" step="0.01" name="pozycje[<?= $pozycja['pozycja_wynajmu_id'] ?>][stawka_dzienna]" value="<?= htmlspecialchars($pozycja['stawka_dzienna']) ?>">
                    </label><br>
                    <label>
                        Koszt całkowity: <input type="number" step="0.01" name="pozycje[<?= $pozycja['pozycja_wynajmu_id'] ?>][koszt_calkowity]" value="<?= htmlspecialchars($pozycja['koszt_calkowity']) ?>">
                    </label><br>
                </div>
            <?php endforeach; ?>
        </section>

        <button type="submit" class="submit-button">Zapisz zmiany</button>
    </form>
</body>
</html>
