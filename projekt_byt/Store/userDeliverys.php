<?php
session_start();
require '../database_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userID = $_SESSION['user_id'];

// Pobranie zamówień dla zalogowanego użytkownika
$query = getDbConnection()->prepare("
    SELECT zamowienie_id, adres, data_zamowienia, status 
    FROM Zamowienia 
    WHERE uzytkownik_id = ?
");

$query->execute([$userID]);
$results = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje Zamówienia</title>
    <link rel="stylesheet" href="../Style/style_userDeliverys.css">
</head>
<body>
<h1>Moje Zamówienia</h1>
<?php if (count($results) > 0): ?>
    <table>
        <thead>
        <tr>
            <th>Adres</th>
            <th>Data zamówienia</th>
            <th>Status</th>
            <th>Szczegóły</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?php echo $row['adres']; ?></td>
                <td><?php echo $row['data_zamowienia']; ?></td>
                <td><?php echo $row['status']; ?></td>
                <td>
                    <a href="deliveryStatus.php?id=<?php echo $row['zamowienie_id']; ?>">Szczegóły zamówienia</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Nie masz jeszcze żadnych zamówień.</p>
<?php endif; ?>
<a href="../index.php">Strona główna</a>
<a href="../Login/profile.php?=<?= $userID ?>">Mój profil</a>
</body>
</html>
