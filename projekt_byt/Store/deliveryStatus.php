<?php
session_start();
require '../database_connection.php';

if (!isset($_GET['id'])) {
    echo "Brak ID zamówienia w URL.";
    exit;
}

$zamowienie_id = $_GET['id'];

$db = getDbConnection();

// Pobieranie szczegółów zamówienia z loginem użytkownika (jeśli istnieje)
$zamowienie_stmt = $db->prepare("
    SELECT 
        z.uzytkownik_id,
        u.login AS uzytkownik_login,
        z.odbiorca_imie,
        z.odbiorca_nazwisko,
        z.odbiorca_email,
        z.adres,
        z.data_zamowienia,
        z.status
    FROM Zamowienia z
    LEFT JOIN Uzytkownicy u ON z.uzytkownik_id = u.uzytkownik_id
    WHERE z.zamowienie_id = ?
");
$zamowienie_stmt->execute([$zamowienie_id]);
$zamowienie = $zamowienie_stmt->fetch(PDO::FETCH_ASSOC);

if (!$zamowienie) {
    echo "Nie znaleziono zamówienia o podanym ID.";
    exit;
}

// Pobieranie pozycji zamówienia
$pozycje_stmt = $db->prepare("
    SELECT 
        pz.produkt_id,
        pz.ilosc,
        pz.cena_za_sztuke,
        (pz.ilosc * pz.cena_za_sztuke) AS wartosc_pozycji
    FROM Pozycje_Zamowien pz
    WHERE pz.zamowienie_id = ?
");
$pozycje_stmt->execute([$zamowienie_id]);
$pozycje = $pozycje_stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobieranie roli użytkownika
$stmt = getDbConnection()->prepare("SELECT rola FROM Uzytkownicy WHERE uzytkownik_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userRole = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły zamówienia</title>
    <link rel="stylesheet" href="../Style/style_deliveryStatus.css">
</head>
<body>
<h1>Szczegóły zamówienia</h1>
<h2>Dane zamówienia</h2>
<?php if (!is_null($zamowienie['uzytkownik_id'])): ?>
    <p><strong>Login użytkownika:</strong> <?= $zamowienie['uzytkownik_login'] ?></p>
<?php else: ?>
    <p><strong>Login użytkownika:</strong> Zakup jako gość</p>
<?php endif; ?>
<p><strong>Odbiorca:</strong> <?= $zamowienie['odbiorca_imie'] . ' ' . $zamowienie['odbiorca_nazwisko'] ?></p>
<p><strong>Email:</strong> <?= $zamowienie['odbiorca_email'] ?></p>
<p><strong>Adres:</strong> <?= $zamowienie['adres'] ?></p>
<p><strong>Data zamówienia:</strong> <?= $zamowienie['data_zamowienia'] ?></p>
<p><strong>Status:</strong> <?= $zamowienie['status'] ?></p>

<h2>Pozycje zamówienia</h2>
<?php if (count($pozycje) > 0): ?>
    <table>
        <thead>
        <tr>
            <th>ID Produktu</th>
            <th>Ilość</th>
            <th>Cena za sztukę</th>
            <th>Wartość pozycji</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($pozycje as $pozycja): ?>
            <tr>
                <td><?= $pozycja['produkt_id'] ?></td>
                <td><?= $pozycja['ilosc'] ?></td>
                <td><?= number_format($pozycja['cena_za_sztuke'], 2) ?> zł</td>
                <td><?= number_format($pozycja['wartosc_pozycji'], 2) ?> zł</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Brak pozycji w tym zamówieniu.</p>
<?php endif; ?>

<?php if (isset($_SESSION['user_id']) AND ($userRole === 'admin' || $userRole === 'mod')):?>
    <a href="../Warehouse/deliveryManagement.php">Zarządzanie zamówieniami</a>
<?php elseif(isset($_SESSION['user_id'])): ?>
<a href="userDeliverys.php">Moje zamówienia</a>
<?php endif; ?>
<a href="../index.php">Strona główna</a>
</body>
</html>
