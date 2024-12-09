<?php
session_start();
require 'database_connection.php';

$stmt = getDbConnection()->prepare("
    SELECT k.nazwa_kategorii, p.produkt_id, p.nazwa_produktu, p.cena
    FROM Produkty p
    LEFT JOIN Kategorie k ON p.kategoria_id = k.kategoria_id
    ORDER BY k.nazwa_kategorii, p.nazwa_produktu
");

$stmt->execute();
$Products = $stmt->fetchAll();

$groupedProducts = [];
foreach ($Products as $row) {
    $groupedProducts[$row['nazwa_kategorii']][] = $row;
}


function findProductImage($productId, $categoryName, $productName) {
    $imageDir = "Image/Product/$categoryName/";
    $extensions = ['png', 'jpg', 'gif'];

    foreach ($extensions as $extension) {
        $filePath = $imageDir . $productId . ".1." . $extension;
        if (file_exists($filePath)) {
            return $filePath;
        }
    }

    return "Brak obrazu dla produktu: " . ($productName);
}


if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!isset($_SESSION['user_id'])) {
    $logged_in = false;
    $user_id = null;
} else {
    $logged_in = true;
    $user_id = $_SESSION['user_id'];
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sklep Budowlany</title>
    <link rel="stylesheet" href="Style/style_index.css">
    
</head>
<body>
    <!-- Główny kontener strony -->
    <div class="container">

        <!-- Header Section -->
        <header class="header">
            <div class="top-bar">
                <div class="top-links">
                    <a href="#">Koszty dostawy</a>
                    <a href="#">Reklamacje i zwroty</a>
                    <a href="#">Kontakt</a>
                </div>
                <div class="language-currency">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                        $username = ($_SESSION['username']);
                        ?>
                        <p><a href="login/profile.php?id=<?php echo $_SESSION['user_id']; ?>">
                                Witaj <?php echo $username; ?> !
                            </a></p>
                    <?php else: ?>
                        <p><a href="Login/login.php">Witaj gość ! Zaloguj się !</a></p>

                    <?php endif; ?>
                </div>
            </div>
            
            <div class="header-container">
                <div class="logo">
                    <img src="Image/Icon/budex.png" alt="Logo sklepu" />
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Wpisz nazwę lub kod produktu..." id="search-input" />
                    <button id="search-button">
                        <img src="Image/Icon/magnifying-glass.png" alt="Szukaj" class="search-icon">
                        Szukaj
                    </button>
                </div>
                <div class="cart-info">
                    <span style="font-weight: bold;">Twój koszyk: <span id="cart-total">0 zł</span></span>
                    <?php
                    $current_url = urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                    ?>
                    <a href="Store/cart.php?source=<?php echo $current_url; ?>"> <!-- Link do strony koszyka -->
                        <div class="cart-icon">
                            <img src="Image/Icon/pngegg.png" alt="Koszyk">
                            <span id="cart-count">0</span> <!-- Liczba produktów w koszyku -->
                        </div>
                    </a>
                </div>
            </div>

            <nav class="main-navigation">
                <button class="category-dropdown">
                    <img src="Image/Icon/menu.png" alt="Kategoria" class="button-icon"> KATEGORIE
                    <img src="Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                </button>
                <div class="nav-links">
                    <a href="#">
                        <img src="Image/Icon/brickwall.png" class="category-icon"> BUDOWA
                        <img src="Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                    </a>
                    <a href="#">
                        <img src="Image/Icon/furnace.png" class="category-icon"> INSTALACJE
                        <img src="Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                    </a>
                    <a href="#">
                        <img src="Image/Icon/rent.png" class="category-icon"> WYPOŻYCZALNIA SPRZĘTU
                        <img src="Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                    </a>
                   
                    <a href="#">
                        <img src="Image/Icon/discount.png" class="category-icon"> PROMOCJE
                        <img src="Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                    </a>
                    <!-- Domyślinie będzie tylko z uprawnieniami -->
                    <a href="Warehouse/stockManagement.php">
                        <img src="Image/Icon/management.png" class="category-icon"> ZARZĄDZANIE STANEM MAGAZYNU
                    </a>
                </div>
            </nav>

        </header>
        <div id="no-results-message" class="no-results-message" style="display: none;">
                Przepraszamy, jeszcze nie mamy tego produktu.
            </div>


        <!-- Main Content -->
        <main class="main-content">

            <?php if (!empty($groupedProducts)): ?>
                <?php foreach ($groupedProducts as $category => $products): ?>
                    <section class="products">
                        <div class="category-container">
                            <h2><?= ($category) ?></h2>
                            <div class="product-grid">
                                <?php foreach ($products as $product): ?>
                                    <div class="product-card">
                                        <a href="Store/product.php?id=<?= $product['produkt_id'] ?>">
                                            <div class="favorite-icon">
                                                <img src="Image/Icon/love-always-wins.png" alt="Ulubione">
                                            </div>
                                            <img src="<?= findProductImage($product['produkt_id'], $category, $product['nazwa_produktu']) ?>"
                                                 alt="Obraz produktu: <?= ($product['nazwa_produktu']) ?>">

                                            <h3><?= ($product['nazwa_produktu']) ?></h3>
                                            <p class="product-price"><?= number_format($product['cena'], 2, ',', ' ') ?> zł/szt.</p>
                                        </a>
                                        <div class="quantity-cart-container">
                                            <div class="quantity-control">
                                                <button type="button" class="decrease-quantity" onclick="changeQuantity(this, -1)">-</button>
                                                <input type="number" value="1" min="1" class="quantity" name="quantity" onchange="updateQuantityDisplay(this)">
                                                <button type="button" class="increase-quantity" onclick="changeQuantity(this, 1)">+</button>
                                            </div>
                                            <form method="POST" action="Store/cart_actions.php" class="add-to-cart-form">
                                                <input type="hidden" name="action" value="add">
                                                <input type="hidden" name="product_id" value="<?= $product['produkt_id'] ?>">
                                                <input type="hidden" name="product_name" value="<?= ($product['nazwa_produktu']) ?>">
                                                <input type="hidden" name="product_price" value="<?= $product['cena'] ?>">
                                                <input type="hidden" class="form-quantity" name="quantity" value="1">
                                                <button type="submit" class="add-to-cart-button">
                                                    <img src="Image/Icon/pngegg.png" style="filter: invert(1) brightness(1000%);" alt="Dodaj do koszyka"> DO KOSZYKA
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php else: ?>
                <div id="no-results-message" class="no-results-message">
                    Przepraszamy, nie znaleziono produktów.
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
        
        let cart = [];

        function addToCart() {
            const quantityInput = event.target.closest('.product-card').querySelector('.quantity');
            const productName = event.target.closest('.product-card').querySelector('h3').innerText;
            const productPriceText = event.target.closest('.product-card').querySelector('.product-price').innerText;

            const productPrice = parseFloat(productPriceText.replace(' zł/szt.', '').replace(',', '.'));

            const quantity = parseInt(quantityInput.value);

            // Szukamy, czy produkt już istnieje w koszyku
            const existingProduct = cart.find(item => item.name === productName);

            if (existingProduct) {
                existingProduct.quantity += quantity; // Dodajemy ilość do istniejącego produktu
            } else {
                cart.push({ name: productName, price: productPrice, quantity }); // Dodajemy nowy produkt do koszyka
            }

            localStorage.setItem('cartItems', JSON.stringify(cart));

            updateCart();
        }

        <!-- funkcje changeQuantity, updateQuantityDisplay, updateQuantityInForm w js odpowiadają za sychronizacę widocznych przycisków z forumlarzem koszyka w php -->
        function changeQuantity(button, change) {
            const quantityInput = button.closest('.quantity-cart-container').querySelector('.quantity');
            let currentValue = parseInt(quantityInput.value) || 1; // Ustaw domyślnie na 1, jeśli pole jest puste
            currentValue += change;

            if (currentValue < 1) currentValue = 1; // Ilość nie może być mniejsza niż 1
            quantityInput.value = currentValue;

            updateQuantityInForm(quantityInput);
        }

        function updateQuantityDisplay(input) {
            let currentValue = parseInt(input.value) || 1; // Walidacja dla pustego pola
            if (currentValue < 1) currentValue = 1;
            input.value = currentValue;

            updateQuantityInForm(input);
        }

        function updateQuantityInForm(input) {
            const formQuantityInput = input.closest('.quantity-cart-container').querySelector('.form-quantity');
            formQuantityInput.value = input.value;
        }


        function updateCart() {
            const cartCountElement = document.getElementById('cart-count');
            const cartInfoElement = document.querySelector('.cart-info span');
            let totalPrice = 0;
            let totalQuantity = 0;

            // Zliczamy łączną ilość produktów i cenę
            cart.forEach(item => {
                totalQuantity += item.quantity;
                totalPrice += item.price * item.quantity;
            });

            // Aktualizujemy licznik produktów w koszyku
            cartCountElement.textContent = totalQuantity;

            // Aktualizujemy cenę w koszyku
            cartInfoElement.textContent = `Twój koszyk: ${totalPrice.toFixed(2)} zł`;

            // Zapisz zmodyfikowany koszyk w localStorage
            localStorage.setItem('cartItems', JSON.stringify(cart));

            // Ukrywamy lub pokazujemy komunikat o braku produktów
            const noResultsMessage = document.getElementById('no-results-message');
            if (totalQuantity === 0) {
                noResultsMessage.style.display = 'block';
            } else {
                noResultsMessage.style.display = 'none';
            }
        }

        
        
        
       // Funkcja do filtrowania produktów
        function filterProducts() {
            const query = document.getElementById('search-input').value.toLowerCase(); // Pobranie tekstu z inputa i zamiana na małe litery
            const products = document.querySelectorAll('.product-card'); // Pobranie wszystkich kart produktów
            let found = false; 

            // Iteracja po wszystkich produktach
            products.forEach(product => {
                const productName = product.querySelector('h3').textContent.toLowerCase(); 

                // Sprawdzanie, czy nazwa produktu zawiera wyszukiwaną frazę
                if (productName.includes(query)) {
                    product.style.display = ''; 
                    found = true; 
                } else {
                    product.style.display = 'none'; // Jeśli nie pasuje, ukrywamy produkt
                }
            });

            // Jeśli nie znaleziono żadnych pasujących produktów, pokazujemy komunikat
            const noResultsMessage = document.getElementById('no-results-message');
            if (!found && query.trim() !== '') {
                noResultsMessage.style.display = 'block'; 
            } else {
                noResultsMessage.style.display = 'none'; 
            }
        }

        // Dodanie nasłuchiwacza do przycisku "Szukaj"
        document.getElementById('search-button').addEventListener('click', filterProducts);

        // Dodanie nasłuchiwacza do inputa, aby reagować na zmiany tekstu
        document.getElementById('search-input').addEventListener('input', filterProducts);



    </script>
</body>
</html>
