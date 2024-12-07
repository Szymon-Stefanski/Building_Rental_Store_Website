<?php
session_start();
require '../database_connection.php';

$stmt = getDbConnection()->prepare("
    SELECT k.nazwa_kategorii, p.produkt_id, p.nazwa_produktu, p.cena, p.ilosc_w_magazynie
    FROM Kategorie k
    LEFT JOIN Produkty p ON p.kategoria_id = k.kategoria_id
    ORDER BY k.nazwa_kategorii, p.nazwa_produktu
");

$stmt->execute();
$Products = $stmt->fetchAll();

$groupedProducts = [];
foreach ($Products as $row) {
    $categoryName = isset($row['nazwa_kategorii']) ? $row['nazwa_kategorii'] : 'Nieznana kategoria';
    $groupedProducts[$categoryName][] = $row;
}

$allCategoriesStmt = getDbConnection()->prepare("
    SELECT kategoria_id, nazwa_kategorii 
    FROM Kategorie
");
$allCategoriesStmt->execute();
$allCategories = $allCategoriesStmt->fetchAll(PDO::FETCH_ASSOC);

$groupedProducts = [];
foreach ($Products as $row) {
    $categoryName = isset($row['nazwa_kategorii']) ? $row['nazwa_kategorii'] : 'Nieznana kategoria';
    $groupedProducts[$categoryName][] = $row;
}

foreach ($allCategories as $category) {
    $categoryName = $category['nazwa_kategorii'];
    $categoryId = $category['kategoria_id'];
    if (!isset($groupedProducts[$categoryName])) {
        $groupedProducts[$categoryName] = ['id' => $categoryId, 'products' => []];
    } else {
        $groupedProducts[$categoryName]['id'] = $categoryId;
    }
}
function findProductImage($productId, $categoryName, $productName) {
    $imageDir = "../Image/Product/$categoryName/";
    $extensions = ['png', 'jpg', 'gif'];

    foreach ($extensions as $extension) {
        $filePath = $imageDir . $productId . ".1." . $extension;
        if (file_exists($filePath)) {
            return $filePath;
        }
    }

    return "Brak obrazu dla produktu: " . ($productName);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldCategoryName = isset($_POST['old_category_name']) ? $_POST['old_category_name'] : null;
    $newCategoryName = isset($_POST['new_category_name']) ? $_POST['new_category_name'] : null;

    if ($oldCategoryName && $newCategoryName) {
        $stmt = getDbConnection()->prepare("
            UPDATE Kategorie
            SET nazwa_kategorii = :newCategoryName
            WHERE nazwa_kategorii = :oldCategoryName
        ");
        $stmt->execute([
            ':newCategoryName' => $newCategoryName,
            ':oldCategoryName' => $oldCategoryName,
        ]);

        $_SESSION['success_message'] = 'Nazwa kategorii została zmieniona.';
    } else {
        $_SESSION['error_message'] = 'Brak wymaganych danych.';
    }

    $action = isset($_POST['action']) ? $_POST['action'] : null;

    if ($action === 'add_category') {
        $newCategoryName = isset($_POST['new_category_name']) ? trim($_POST['new_category_name']) : null;

        if ($newCategoryName) {
            $addCategoryStmt = getDbConnection()->prepare("
            INSERT INTO Kategorie (nazwa_kategorii) VALUES (:newCategoryName)
        ");
            $addCategoryStmt->execute([':newCategoryName' => $newCategoryName]);

            $_SESSION['success_message'] = 'Nowa kategoria została dodana.';
        } else {
            $_SESSION['error_message'] = 'Nie udało się dodać kategorii.';
        }
    }

    if ($action === 'delete_category') {
        $categoryId = isset($_POST['category_id']) ? $_POST['category_id'] : null;

        if ($categoryId) {
            $deleteProductsStmt = getDbConnection()->prepare("
            DELETE FROM Produkty WHERE kategoria_id = :categoryId
        ");
            $deleteProductsStmt->execute([':categoryId' => $categoryId]);

            $deleteCategoryStmt = getDbConnection()->prepare("
            DELETE FROM Kategorie WHERE kategoria_id = :categoryId
        ");
            $deleteCategoryStmt->execute([':categoryId' => $categoryId]);

            $_SESSION['success_message'] = 'Kategoria została usunięta.';
        } else {
            $_SESSION['error_message'] = 'Nie udało się usunąć kategorii.';
        }
    }

    if ($action === 'update_quantity') {
        $productId = isset($_POST['product_id']) ? $_POST['product_id'] : null;
        $newQuantity = isset($_POST['new_quantity']) ? $_POST['new_quantity'] : null;

        if ($productId && is_numeric($newQuantity)) {
            $stmt = getDbConnection()->prepare("
                UPDATE Produkty
                SET ilosc_w_magazynie = :newQuantity
                WHERE produkt_id = :productId
            ");
            $stmt->execute([
                ':newQuantity' => $newQuantity,
                ':productId' => $productId,
            ]);

            $_SESSION['success_message'] = 'Ilość produktu została zaktualizowana.';
        } else {
            $_SESSION['error_message'] = 'Brak wymaganych danych lub nieprawidłowa ilość.';
        }
    }

    if ($action === 'update_price') {
        $productId = isset($_POST['product_id']) ? $_POST['product_id'] : null;
        $newPrice = isset($_POST['new_price']) ? $_POST['new_price'] : null;

        if ($productId && is_numeric($newPrice)) {
            $stmt = getDbConnection()->prepare("
                UPDATE Produkty
                SET cena = :newPrice
                WHERE produkt_id = :productId
            ");
            $stmt->execute([
                ':newPrice' => $newPrice,
                ':productId' => $productId,
            ]);

            $_SESSION['success_message'] = 'Cena produktu została zaktualizowana.';
        } else {
            $_SESSION['error_message'] = 'Brak wymaganych danych lub nieprawidłowa cena.';
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sklep Budowlany</title>
    <link rel="stylesheet" href="../Style/style_stockManagement.css">

</head>
<body>
<div class="container">

    <header>
        <nav class="main-navigation">
                <div class="index-button-container">
                    <a href="../index.php" class="index-button">◄  Do strony głównej</a>
                </div>
                <div class="index-button-container">
                   <a href="suppliersManagement.php" class="index-button">◄  Dostawcy</a>
                </div>
            <div class="nav-links">
                <a href="#">
                    <img src="../Image/Icon/discount.png" class="category-icon"> PROMOCJE
                    <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                </a>
            </div>
        </nav>
    </header>

    <main class="main-content">

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php
                echo ($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($groupedProducts)): ?>
            <?php foreach ($groupedProducts as $category => $products): ?>
                <section class="products">
                    <div class="category-container">
                        <h2>
                            <span class="category-name"><?= ($category) ?></span>
                            <button class="edit-category-button" onclick="showEditCategoryForm(this)">Zmień nazwę</button>
                            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="category_id" value="<?= $groupedProducts[$category]['id'] ?>">
                                <button type="submit" onclick="return confirm('Czy na pewno chcesz usunąć tę kategorię?')">Usuń</button>
                            </form>
                        </h2>
                        <?php if (!empty($products[0]['produkt_id'])): ?>
                            <div class="product-grid">
                                <?php foreach ($products as $product): ?>
                                    <?php if (!empty($product['produkt_id'])): ?>
                                        <div class="product-card">
                                            <a href="editProduct.php?id=<?=$product['produkt_id']?>">
                                                <div class="favorite-icon">
                                                    <img src="../Image/Icon/love-always-wins.png" alt="Ulubione">
                                                </div>
                                                <img src="<?= findProductImage($product['produkt_id'], $category, $product['nazwa_produktu']) ?>"
                                                     alt="Obraz produktu: <?= ($product['nazwa_produktu']) ?>">

                                                <h3><?= ($product['nazwa_produktu']) ?></h3>
                                                <p class="product-price"><?= number_format($product['ilosc_w_magazynie']) ?> sztuk</p>
                                                <p class="product-price"><?= number_format($product['cena'], 2, ',', ' ') ?> zł/szt.</p>
                                            </a>
                                            <div class="quantity-cart-container">
                                                <!-- Formularz zmiany ilości -->
                                                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" class="edit-form">
                                                    <input type="hidden" name="action" value="update_quantity">
                                                    <input type="hidden" name="product_id" value="<?= ($product['produkt_id']) ?>">
                                                    <label for="new_quantity">Nowa ilość:</label>
                                                    <input type="number" name="new_quantity" min="0" value="<?= ($product['ilosc_w_magazynie']) ?>" required>
                                                    <button type="submit">Zmień ilość</button>
                                                </form>

                                                <!-- Formularz zmiany ceny -->
                                                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST" class="edit-form">
                                                    <input type="hidden" name="action" value="update_price">
                                                    <input type="hidden" name="product_id" value="<?= ($product['produkt_id']) ?>">
                                                    <label for="new_price">Nowa cena:</label>
                                                    <input type="text" name="new_price" value="<?= (number_format($product['cena'], 2, '.', '')) ?>" required>
                                                    <button type="submit">Zmień cenę</button>
                                                </form>

                                                <button class="add-to-cart" ="addToCart(this)">
                                                <a href="editProduct.php?id=<?=$product['produkt_id']?>"> Edytuj Szczegóły produktu </a>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p>Brak produktów w tej kategorii.</p>
                        <?php endif; ?>
                        <button class="add-to-cart">
                            <a href="addProduct.php?id=<?= $groupedProducts[$category]['id'] ?>">Dodaj produkt do tej kategorii</a>
                        </button>
                    </div>
                </section>
            <?php endforeach; ?>

        <?php else: ?>
            <div id="no-results-message" class="no-results-message">
                Brak produktów i kategorii.
            </div>
        <?php endif; ?>
        <div class="add-category-container">
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
                <input type="hidden" name="action" value="add_category">
                <label for="new_category_name">Nowa kategoria:</label>
                <input type="text" name="new_category_name" id="new_category_name" placeholder="Nazwa kategorii" required>
                <button type="submit">Dodaj kategorię</button>
            </form>
        </div>
    </main>
</div>
<script>
    function showEditCategoryForm(button) {
        const form = button.closest('.category-container').querySelector('.edit-category-form');
        form.style.display = 'block';
        button.style.display = 'none';
    }

    function hideEditCategoryForm(button) {
        const form = button.closest('.edit-category-form');
        const editButton = form.closest('.category-container').querySelector('.edit-category-button');
        form.style.display = 'none';
        editButton.style.display = 'inline-block';
    }

    function editCategory(event, form) {
        event.preventDefault(); // Zapobiega tradycyjnemu przesyłaniu formularza

        const formData = new FormData(form);

        fetch('edit_category.php', {
            method: 'POST',
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const categoryContainer = form.closest('.category-container');
                    const categoryNameSpan = categoryContainer.querySelector('.category-name');
                    categoryNameSpan.textContent = formData.get('new_category_name');
                    hideEditCategoryForm(form.querySelector('button[type="button"]')); // Ukryj formularz
                    alert('Nazwa kategorii została zmieniona.');
                } else {
                    alert('Wystąpił błąd: ' + data.message);
                }
            })
            .catch(error => {
                alert('Wystąpił błąd: ' + error.message);
            });
    }
</script>
</body>
</html>