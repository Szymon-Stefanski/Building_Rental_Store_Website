<?php
session_start();
require '../database_connection.php';

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

    try {
        $stmt = getDbConnection()->prepare("
            INSERT INTO Dostawcy (nazwa_dostawcy, osoba_kontaktowa, numer_telefonu, email, adres) 
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([$supplier_name, $contact_person, $phone_number, $email, $address]);

        $_SESSION['message'] = [
            'type' => 'success',
            'text' => 'Dostawca został pomyślnie dodany.'
        ];
        header("Location: suppliersManagement.php");
        exit;
    } catch (PDOException $e) {
        echo "Wystąpił błąd podczas dodawania dostawcy: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj Dostawcę</title>
    <link rel="stylesheet" href="../Style/style_add.editSupplier.css">
</head>
<body>
<header class="header">
    <h1>Dodaj Nowego Dostawcę</h1>
</header>

<main class="main-content">
    <form method="POST" class="add-supplier-form">
        <label for="supplier_name">Nazwa Dostawcy:</label>
        <input type="text" id="supplier_name" name="supplier_name" required>

        <label for="contact_person">Osoba Kontaktowa:</label>
        <input type="text" id="contact_person" name="contact_person">

        <label for="phone_number">Numer Telefonu:</label>
        <input type="text" id="phone_number" name="phone_number" required>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="address">Adres:</label>
        <textarea id="address" name="address"></textarea>

        <button type="submit">Dodaj Dostawcę</button>
    </form>
</main>

<footer>
    <p>&copy; 2024 Budex Sp z.o.o. Wszelkie prawa zastrzeżone.</p>
</footer>
</body>
</html>
