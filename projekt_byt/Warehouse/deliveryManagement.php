<?php
session_start();
require '../database_connection.php';

try {
    $stmt = getDbConnection()->prepare("SELECT zamowienie_id, odbiorca_imie, odbiorca_nazwisko, data_zamowienia, status FROM Zamowienia");
    $stmt->execute();
    $zamowienia = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Błąd połączenia z bazą danych: " . $e->getMessage();
    exit;
}

// Obsługa usunięcia zamówienia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $deleteId = intval($_POST['delete_id']);
        $deleteStmt = getDbConnection()->prepare("DELETE FROM Zamowienia WHERE zamowienie_id = ?");
        $deleteStmt->execute([$deleteId]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        echo "Błąd usuwania zamówienia: " . $e->getMessage();
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Lista Zamówień</title>
        <link rel="stylesheet" href="../Style/style_deliveryManagement.css">
    </head>
    <body>
        <header class="header">
            <h1>Lista Zamówień</h1>
            <a href="../index.php" class="back-button">
                <img src="../Image/Icon/log-in.png" alt="Powrót" class="button-icon"> Powrót
            </a>
        </header>
        
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Odbiorca</th>
                <th>Data Zamówienia</th>
                <th>Status</th>
                <th>Akcje</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($zamowienia) > 0): ?>
                <?php foreach ($zamowienia as $zamowienie): ?>
                    <tr>
                        <td><?= htmlspecialchars($zamowienie['zamowienie_id']) ?></td>
                        <td><?= htmlspecialchars($zamowienie['odbiorca_imie'] . ' ' . $zamowienie['odbiorca_nazwisko']) ?></td>
                        <td><?= htmlspecialchars($zamowienie['data_zamowienia']) ?></td>
                        <td><?= htmlspecialchars($zamowienie['status']) ?></td>
                        <td>
                            <a href="../Store/deliveryStatus.php?id=<?= $zamowienie['zamowienie_id'] ?>">Szczegóły</a> |
                            <a href="editDelivery.php?id=<?= $zamowienie['zamowienie_id'] ?>">Edytuj</a> |
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $zamowienie['zamowienie_id'] ?>">
                                <button type="submit" onclick="return confirm('Czy na pewno chcesz usunąć to zamówienie?')">Usuń</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">Brak zamówień do wyświetlenia.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </body>
</html>
