<?php
session_start();
require '../database_connection.php';

try {
    // Zmiana zapytania SQL na tabelę Wynajmy
    $stmt = getDbConnection()->prepare("SELECT wynajem_id, uzytkownik_id, data_wynajmu, data_zwrotu, status FROM Wynajmy");
    $stmt->execute();
    $wynajmy = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Błąd połączenia z bazą danych: " . $e->getMessage();
    exit;
}

// Obsługa usunięcia wypożyczenia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $deleteId = intval($_POST['delete_id']);
        $deleteStmt = getDbConnection()->prepare("DELETE FROM Wynajmy WHERE wynajem_id = ?");
        $deleteStmt->execute([$deleteId]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        echo "Błąd usuwania wypożyczenia: " . $e->getMessage();
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Lista Wypożyczeń</title>
        <link rel="stylesheet" href="../Style/style_deliveryManagement.css">
    </head>
    <body>
        <header class="header">
            <h1>Lista Wypożyczeń</h1>
            <a href="../index.php" class="back-button">
                <img src="../Image/Icon/back.png" alt="Powrót" class="button-icon"> Powrót
            </a>
        </header>
        
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Użytkownik</th>
                <th>Data Wypożyczenia</th>
                <th>Data Zwrotu</th>
                <th>Status</th>
                <th>Akcje</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($wynajmy) > 0): ?>
                <?php foreach ($wynajmy as $wynajem): ?>
                    <tr>
                        <td><?= htmlspecialchars($wynajem['wynajem_id']) ?></td>
                        <?php
                        // Pobieramy dane użytkownika na podstawie jego ID
                        $userStmt = getDbConnection()->prepare("SELECT imie, nazwisko FROM Uzytkownicy WHERE uzytkownik_id = ?");
                        $userStmt->execute([$wynajem['uzytkownik_id']]);
                        $user = $userStmt->fetch();
                        ?>
                        <td><?= htmlspecialchars($user['imie'] . ' ' . $user['nazwisko']) ?></td>
                        <td><?= htmlspecialchars($wynajem['data_wynajmu']) ?></td>
                        <td><?= htmlspecialchars($wynajem['data_zwrotu']) ?></td>
                        <td><?= htmlspecialchars($wynajem['status']) ?></td>
                        <td>
                            <a href="../Store/rentalStatus.php?id=<?= $wynajem['wynajem_id'] ?>">Szczegóły</a> |
                            <a href="editRental.php?id=<?= $wynajem['wynajem_id'] ?>">Edytuj</a> |
                            <form action="" method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $wynajem['wynajem_id'] ?>">
                                <button type="submit" onclick="return confirm('Czy na pewno chcesz usunąć to wypożyczenie?')">Usuń</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Brak wypożyczeń do wyświetlenia.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </body>
</html>
