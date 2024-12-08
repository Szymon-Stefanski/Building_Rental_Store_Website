<?php
session_start();
require '../database_connection.php';

if (!isset($_GET['id'])) {
    echo "Brak ID dostawcy w URL.";
    exit;
}

$supplier_id = $_GET['id'];

try {
    $stmt = getDbConnection()->prepare("SELECT * FROM Dostawcy WHERE dostawca_id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$supplier) {
        echo "Dostawca o podanym ID nie istnieje.";
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $supplier_name = isset($_POST['supplier_name']) ? $_POST['supplier_name'] : null;
        $contact_person = isset($_POST['contact_person']) ? $_POST['contact_person'] : null;
        $phone_number = isset($_POST['phone_number']) ? $_POST['phone_number'] : null;
        $email = isset($_POST['email']) ? $_POST['email'] : null;
        $address = isset($_POST['address']) ? $_POST['address'] : null;

        if (!$supplier_name || !$phone_number || !$email) {
            echo "Pola Nazwa dostawcy, Numer telefonu i Email są wymagane!";
            exit;
        }

        $update_stmt = getDbConnection()->prepare("
            UPDATE Dostawcy 
            SET nazwa_dostawcy = ?, osoba_kontaktowa = ?, numer_telefonu = ?, email = ?, adres = ?
            WHERE dostawca_id = ?
        ");
        $update_stmt->execute([$supplier_name, $contact_person, $phone_number, $email, $address, $supplier_id]);

        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Dane dostawcy zostały pomyślnie zaktualizowane.'
        ];
        header("Location: suppliersManagement.php");
        exit;
    }
} catch (PDOException $e) {
    echo "Wystąpił błąd: " . $e->getMessage();
    exit;
}
?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edytuj Dostawcę</title>
    <link rel="stylesheet" href="../Style/style_editSupplier.css">
</head>
<body>
<header class="header">
    <h1>Edytuj Dane Dostawcy</h1>
</header>

<main class="main-content">
    <form method="POST" class="edit-supplier-form">
        <label for="supplier_name">Nazwa Dostawcy:</label>
        <input type="text" id="supplier_name" name="supplier_name" value="<?php echo ($supplier['nazwa_dostawcy']); ?>" required>

        <label for="contact_person">Osoba Kontaktowa:</label>
        <input type="text" id="contact_person" name="contact_person" value="<?php echo ($supplier['osoba_kontaktowa']); ?>">

        <label for="phone_number">Numer Telefonu:</label>
        <input type="text" id="phone_number" name="phone_number" value="<?php echo ($supplier['numer_telefonu']); ?>" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo ($supplier['email']); ?>" required>

        <label for="address">Adres:</label>
        <textarea id="address" name="address"><?php echo ($supplier['adres']); ?></textarea>

        <button type="submit">Zapisz Zmiany</button>
    </form>
</main>

<footer>
    <p>&copy; 2024 Budex Sp z.o.o. Wszelkie prawa zastrzeżone.</p>
</footer>
    <script>
document.getElementById('phone_number').addEventListener('input', function (e) {
    let value = e.target.value.replace(/\D/g, ''); // Usuń wszystkie znaki niebędące cyframi
    if (value.length > 9) value = value.slice(0, 9); // Maksymalnie 9 cyfr
    let formatted = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1-$2-$3');
    e.target.value = formatted;
});
</script>

</body>
</html>
