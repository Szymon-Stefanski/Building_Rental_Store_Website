<?php
session_start();
require '../database_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = getDbConnection()->prepare("SELECT * FROM uzytkownicy WHERE uzytkownik_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    echo "Użytkownik nie istnieje.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : null;
    $new_last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : null;
    $new_phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
    $new_address = isset($_POST['address']) ? trim($_POST['address']) : null;

    if ($new_first_name && $new_last_name && $new_phone_number && $new_address) {
        $stmt = getDbConnection()->prepare("UPDATE uzytkownicy 
            SET imie = ?, nazwisko = ?, numer_telefonu = ?, adres = ? 
            WHERE uzytkownik_id = ?");
        $stmt->execute([$new_first_name, $new_last_name, $new_phone_number, $new_address, $user_id]);

        header("Location: profile.php");
        exit;
    } else {
        echo "Wszystkie pola są wymagane.";
    }
}
if (isset($_POST['delete_account'])) {
    $password = trim($_POST['password']);

    $stmt = getDbConnection()->prepare("SELECT haslo FROM uzytkownicy WHERE uzytkownik_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['haslo'])) {
        echo "Niepoprawne hasło.";
        exit;
    }

    $stmt = getDbConnection()->prepare("DELETE FROM uzytkownicy WHERE uzytkownik_id = ?");
    $stmt->execute([$user_id]);

    session_destroy();
    header("Location: ../index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twój profil</title>
    <link rel="stylesheet" href="style_profile.css">
</head>
<body>
<h1>Twój profil</h1>
<p>Imię: <?php echo htmlspecialchars($user['imie']); ?></p>
<p>Nazwisko: <?php echo htmlspecialchars($user['nazwisko']); ?></p>
<p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
<p>Numer telefonu: <?php echo htmlspecialchars($user['numer_telefonu']); ?></p>
<p>Adres: <?php echo htmlspecialchars($user['adres']); ?></p>

<h2>Edytuj dane</h2>
<form action="profile.php" method="POST">
    <label for="first_name">Imię:</label>
    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['imie']); ?>" required><br>

    <label for="last_name">Nazwisko:</label>
    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['nazwisko']); ?>" required><br>

    <label for="phone_number">Numer telefonu:</label>
    <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['numer_telefonu']); ?>" required><br>

    <label for="address">Adres:</label>
    <textarea id="address" name="address" required><?php echo htmlspecialchars($user['adres']); ?></textarea><br>

    <button type="submit">Zapisz zmiany</button>
</form>

<h2>Usuń konto</h2>
<form action="profile.php" method="POST">
    <label for="password">Potwierdź hasłem:</label>
    <input type="password" id="password" name="password" required><br>
    <button type="submit" name="delete_account" onclick="return confirm('Czy na pewno chcesz usunąć konto?')">Usuń konto</button>
</form>

<a href="logout.php">Wyloguj się</a><br>
<a href="../index.php">Powrót</a>
</body>
</html>
