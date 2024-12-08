<?php
session_start();
require '../database_connection.php';

try {
    // Pobieranie dostawców
    $stmt = getDbConnection()->prepare("SELECT * FROM Dostawcy");
    $stmt->execute();
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Usuwanie dostawcy
    if (isset($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];

        try {
            $delete_stmt = getDbConnection()->prepare("DELETE FROM Dostawcy WHERE dostawca_id = ?");
            $delete_stmt->execute([$delete_id]);

            $_SESSION['message'] = [
                'type' => 'delete',
                'text' => 'Dostawca został usunięty.'
            ];
        } catch (PDOException $e) {
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Wystąpił błąd podczas usuwania dostawcy.'
            ];
        }

        header("Location: suppliersManagement.php");
        exit;
    }
} catch (PDOException $e) {
    echo "Wystąpił błąd podczas pobierania danych dostawców: " . $e->getMessage();
}
?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zarządzanie Dostawcami</title>
    <link rel="stylesheet" href="../Style/style_suppliersManagement.css">
</head>
<body>

<header class="header">
    <h1>Zarządzanie Dostawcami</h1>
</header>

<main class="main-content">

    <?php if (isset($_SESSION['message'])): ?>
    <p class="message <?php echo $_SESSION['message']['type']; ?>">
        <?php echo $_SESSION['message']['text']; ?>
    </p>
    <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="table-container">
        <table class="suppliers-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nazwa Dostawcy</th>
                <th>Osoba Kontaktowa</th>
                <th>Numer Telefonu</th>
                <th>Email</th>
                <th>Adres</th>
                <th>Akcje</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($suppliers): ?>
                <?php foreach ($suppliers as $supplier): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($supplier['dostawca_id']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['nazwa_dostawcy']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['osoba_kontaktowa']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['numer_telefonu']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                        <td><?php echo htmlspecialchars($supplier['adres']); ?></td>
                        <td>
                            <a href="editSupplier.php?id=<?php echo $supplier['dostawca_id']; ?>">Edytuj</a> |
                            <a href="?delete_id=<?php echo $supplier['dostawca_id']; ?>" onclick="return confirm('Czy na pewno chcesz usunąć tego dostawcę?');">Usuń</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">Brak dostawców w bazie danych.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <a href="addSupplier.php" class="add-supplier-btn">Dodaj Nowego Dostawcę</a>
    <a href="stockManagement.php" class="index-button green-button">Do zarządzania magazynem</a>
    
</main>

<footer>
    <p>&copy; 2024 Budex Sp z.o.o. Wszelkie prawa zastrzeżone.</p>
</footer>

</body>
</html>

