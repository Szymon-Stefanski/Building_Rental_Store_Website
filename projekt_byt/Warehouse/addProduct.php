<?php
session_start();
require '../database_connection.php';

$stmt = getDbConnection()->prepare("SELECT rola FROM Uzytkownicy WHERE uzytkownik_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userRole = $stmt->fetchColumn();

if (!in_array($userRole, ['mod', 'admin'])) {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id'])) {
    echo "Brak ID kategorii w URL.";
    exit;
}

$category_id = $_GET['id'];

try {
    $stmt = getDbConnection()->prepare("SELECT nazwa_kategorii FROM Kategorie WHERE kategoria_id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        echo "Kategoria o podanym ID nie istnieje.";
        exit;
    }

    $category_name = $category['nazwa_kategorii'];

    $supplier_stmt = getDbConnection()->prepare("SELECT dostawca_id, nazwa_dostawcy FROM Dostawcy");
    $supplier_stmt->execute();
    $suppliers = $supplier_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Wystąpił błąd podczas pobierania danych: " . $e->getMessage();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = isset($_POST['product_name']) ? $_POST['product_name'] : null;
    $supplier_name = isset($_POST['supplier_name']) ? $_POST['supplier_name'] : null;
    $price = isset($_POST['price']) ? $_POST['price'] : null;
    $stock_quantity = isset($_POST['stock_quantity']) ? $_POST['stock_quantity'] : null;
    $description = isset($_POST['description']) ? $_POST['description'] : null;

    if (!$product_name || !$supplier_name || !$price || !$stock_quantity) {
        echo "Wszystkie pola muszą być wypełnione!";
        exit;
    }

    try {
        $supplier_id_stmt = getDbConnection()->prepare("SELECT dostawca_id FROM Dostawcy WHERE nazwa_dostawcy = ?");
        $supplier_id_stmt->execute([$supplier_name]);
        $supplier = $supplier_id_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$supplier) {
            echo "Nie znaleziono dostawcy o podanej nazwie.";
            exit;
        }

        $supplier_id = $supplier['dostawca_id'];
        $stmt = getDbConnection()->prepare("
        INSERT INTO Produkty 
        (kategoria_id, nazwa_produktu, dostawca_id, cena, ilosc_w_magazynie, opis) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");

        $stmt->execute([$category_id, $product_name, $supplier_id, $price, $stock_quantity, $description]);

        $product_id = getDbConnection()->lastInsertId();

        function uploadFiles($files, $uploadDir) {
            $uploadedFiles = [];
            foreach ($files['name'] as $index => $fileName) {
                $fileTmpName = $files['tmp_name'][$index];

                $destination = $uploadDir . $fileName;
                move_uploaded_file($fileTmpName, $destination);
                $uploadedFiles[] = $fileName;
            }
            return $uploadedFiles;
        }



        function renameFiles($uploadedFiles, $productId, $uploadDir) {
            foreach ($uploadedFiles as $index => $fileName) {
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newName = "{$productId}." . ($index + 1) . ".{$extension}";
                $oldPath = $uploadDir . $fileName;
                $newPath = $uploadDir . $newName;

                if (file_exists($oldPath)) {
                    rename($oldPath, $newPath);
                }
            }
        }


        if (isset($_FILES['product_images'])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $upload_dir = "../Image/Product/" . $category_name . "/";

            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    echo "Nie udało się utworzyć katalogu dla obrazów.";
                    exit;
                }
            }

            $uploadedFiles = uploadFiles($_FILES['product_images'], $upload_dir);
            renameFiles($uploadedFiles, $product_id, $upload_dir);
        }


        $_SESSION['success_message'] = "Produkt został pomyślnie dodany.";
        header("Location: stockManagement.php");
        exit;
    } catch (PDOException $e) {
        echo "Wystąpił błąd podczas dodawania produktu: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj Produkt</title>
    <link rel="stylesheet" href="../Style/style_add.editProduct.css">
</head>
<body>
<header class="header">
    <h1>Dodaj Nowy Produkt</h1>
</header>

<main class="main-content">
    <p>Kategoria: <strong><?php echo ($category_name); ?></strong></p>
    <form method="POST" enctype="multipart/form-data" class="add-product-form">
        <label for="product_name">Nazwa Produktu:</label>
        <input type="text" id="product_name" name="product_name" required>

        <label for="supplier_name">Dostawca:</label>
        <select id="supplier_name" name="supplier_name" required>
            <option value="">Wybierz dostawcę</option>
            <?php foreach ($suppliers as $supplier): ?>
                <option value="<?php echo ($supplier['nazwa_dostawcy']); ?>">
                    <?php echo ($supplier['nazwa_dostawcy']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="price">Cena:</label>
        <input type="number" step="0.01" id="price" name="price" required>

        <label for="stock_quantity">Ilość w Magazynie:</label>
        <input type="number" id="stock_quantity" name="stock_quantity" required>

        <label for="description">Opis:</label>
        <textarea id="description" name="description"></textarea>

        <label for="product_images">Obrazy Produktu:</label>
        <input type="file" id="product_images" name="product_images[]" multiple>
        
        

        <button type="submit">Dodaj Produkt</button>
    </form>
    
    
    
    <div class="back-button-container">
        <a href="stockManagement.php" class="back-button">Powrót</a>
    </div>

</main>

<footer>
    <p>&copy; 2024 Budex Sp z.o.o. Wszelkie prawa zastrzeżone.</p>
</footer>
</body>
</html>
