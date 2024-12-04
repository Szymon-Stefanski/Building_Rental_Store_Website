<?php
session_start();
require '../database_connection.php';

if (!isset($_GET['id'])) {
    echo "Brak podanego ID produktu.";
    exit;
}

$product_id = $_GET['id'];

try {
    $stmt = getDbConnection()->prepare("
        SELECT 
            p.produkt_id, 
            p.nazwa_produktu, 
            p.dostawca_id, 
            p.cena, 
            p.ilosc_w_magazynie, 
            p.opis, 
            d.nazwa_dostawcy
        FROM 
            Produkty p
        JOIN 
            Dostawcy d 
        ON 
            p.dostawca_id = d.dostawca_id
        WHERE 
            p.produkt_id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo "Produkt nie istnieje.";
        exit;
    }

    $supplier_stmt = getDbConnection()->prepare("SELECT dostawca_id, nazwa_dostawcy FROM Dostawcy");
    $supplier_stmt->execute();
    $suppliers = $supplier_stmt->fetchAll(PDO::FETCH_ASSOC);

    $images_dir = '../Image/Product/';
    $product_images = glob($images_dir . $product_id . '.*');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['product_name'])) {
            $product_name = $_POST['product_name'];
            $supplier_id = $_POST['supplier_id'];
            $price = $_POST['price'];
            $stock_quantity = $_POST['stock_quantity'];
            $description = $_POST['description'];

            $update_stmt = getDbConnection()->prepare("
                UPDATE Produkty 
                SET 
                    nazwa_produktu = ?, 
                    dostawca_id = ?, 
                    cena = ?, 
                    ilosc_w_magazynie = ?, 
                    opis = ?
                WHERE 
                    produkt_id = ?
            ");
            $update_stmt->execute([$product_name, $supplier_id, $price, $stock_quantity, $description, $product_id]);

            $_SESSION['success_message'] = "Produkt został pomyślnie zaktualizowany.";
            header("Location: stockManagement.php");
            exit;
        }

        if (isset($_FILES['new_images'])) {
            $existingFiles = glob($images_dir . "{$product_id}.*.*");
            $maxIndex = 0;

            foreach ($existingFiles as $file) {
                $fileNameParts = explode('.', basename($file));
                if (count($fileNameParts) >= 3 && is_numeric($fileNameParts[1])) {
                    $maxIndex = max($maxIndex, intval($fileNameParts[1]));
                }
            }

            foreach ($_FILES['new_images']['name'] as $index => $fileName) {
                $fileTmpName = $_FILES['new_images']['tmp_name'][$index];
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newFileIndex = $maxIndex + $index + 1;
                $newFileName = "{$product_id}.{$newFileIndex}.{$fileExtension}";
                $destination = $images_dir . $newFileName;
                move_uploaded_file($fileTmpName, $destination);
            }
            header("Location: editProduct.php?id=$product_id");
            exit;
        }

        if (isset($_POST['delete_image'])) {
            $image_path = $_POST['delete_image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }

            session_destroy();
            header("Location: editProduct.php?id=$product_id");
            exit;
        }
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
    <title>Edytuj Produkt</title>
    <link rel="stylesheet" href="../Style/style_add.editProduct.css">
</head>
<body>
<header class="header">
    <h1>Edytuj Produkt</h1>
</header>

<main class="main-content">
    <form method="POST" class="edit-product-form">
        <label for="product_name">Nazwa Produktu:</label>
        <input type="text" id="product_name" name="product_name" value="<?php echo ($product['nazwa_produktu']); ?>" required>

        <label for="supplier_id">Dostawca:</label>
        <select id="supplier_id" name="supplier_id" required>
            <?php foreach ($suppliers as $supplier): ?>
                <option value="<?php echo $supplier['dostawca_id']; ?>" <?php echo ($product['dostawca_id'] == $supplier['dostawca_id']) ? 'selected' : ''; ?>>
                    <?php echo ($supplier['nazwa_dostawcy']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="price">Cena:</label>
        <input type="number" step="0.01" id="price" name="price" value="<?php echo ($product['cena']); ?>" required>

        <label for="stock_quantity">Ilość w Magazynie:</label>
        <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo ($product['ilosc_w_magazynie']); ?>" required>

        <label for="description">Opis:</label>
        <textarea id="description" name="description"><?php echo ($product['opis']); ?></textarea>

        <button type="submit">Zapisz zmiany</button>
    </form>

    <h2>Zarządzanie obrazkami produktu</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="new_images">Dodaj nowe obrazy:</label>
        <input type="file" id="new_images" name="new_images[]" multiple>
        <button type="submit">Prześlij</button>
    </form>

    <h3>Obecne obrazy:</h3>
    <div class="image-gallery">
        <?php foreach ($product_images as $image): ?>
            <div class="image-item">
                <img src="<?php echo ($image); ?>" alt="Obraz produktu" class="product-image">
                <form method="POST" class="delete-form">
                    <input type="hidden" name="delete_image" value="<?php echo ($image); ?>">
                    <button type="submit">Usuń</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="back-button-container">
        <a href="stockManagement.php" class="back-button">◄ Powrót do stanu magazynu</a>
    </div>
</main>


<footer>
    <p>&copy; 2024 Budex Sp z.o.o. Wszelkie prawa zastrzeżone.</p>
</footer>
</body>
</html>
