<?php
session_start();
require '../database_connection.php';
if (!isset($_GET['id'])) {
    echo "Brak podanego ID produktu.";
    exit;
}

$product_id = $_GET['id'];

$stmt = getDbConnection()->prepare("
    SELECT 
        p.produkt_id, 
        p.nazwa_produktu, 
        p.dostawca_id, 
        p.cena, 
        p.ilosc_w_magazynie, 
        p.opis, 
        d.nazwa_dostawcy, 
        k.nazwa_kategorii
    FROM 
        Produkty p
    LEFT JOIN 
        Dostawcy d 
    ON 
        p.dostawca_id = d.dostawca_id
    LEFT JOIN 
        Kategorie k 
    ON 
        p.kategoria_id = k.kategoria_id
    WHERE 
        p.produkt_id = ?
");

$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) {
    echo "Produkt nie istnieje.";
    exit;
}
$stmt = getDbConnection()->prepare("
    SELECT o.opinia_id, o.tresc_opinii, o.data_opinii, o.ocena, u.login
    FROM Opinie_Produktow o
    LEFT JOIN Uzytkownicy u ON o.uzytkownik_id = u.uzytkownik_id
    WHERE o.produkt_id = ?
    ORDER BY o.data_opinii ASC
");

$stmt->execute([$product_id]);
$opinions = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['opinion'])) {
    $tresc_opinii = $_POST['opinion'];
    $ocena = isset($_POST['rating']) ? intval($_POST['rating']) : null;
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $stmt = getDbConnection()->prepare("
    INSERT INTO Opinie_Produktow (uzytkownik_id, produkt_id, ocena, tresc_opinii, data_opinii) 
    VALUES (?, ?, ?, ?, NOW())
");
    $stmt->execute([$user_id, $product_id, $ocena, $tresc_opinii]);
    header("Location: product.php?id=$product_id");
    exit;
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
    <title>Szczegóły Produktu</title>
    <link rel="stylesheet" href="../Style/style_product.css">
</head>
<body>
<!-- Nagłówek strony -->
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
            <img src="../Image/Icon/budex.png" alt="Logo sklepu" />
        </div>
        <div class="search-bar">
            <input type="text" placeholder="Wpisz nazwę lub kod produktu..." id="search-input" />
            <button id="search-button">
                <img src="../Image/Icon/magnifying-glass.png" alt="Szukaj" class="search-icon">
                Szukaj
            </button>
        </div>
        <div class="cart-info">
            <span style="font-weight: bold;">Twój koszyk: <span id="cart-total">0 zł</span></span>
            <?php
            $current_url = urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            ?>
            <a href="cart.php?source=<?php echo $current_url; ?>"> <!-- Link do strony koszyka -->
                <div class="cart-icon">
                    <img src="../Image/Icon/pngegg.png" alt="Koszyk">
                    <span id="cart-count">0</span>
                </div>
            </a>
        </div>
    </div>



    <nav class="main-navigation">
        <button class="category-dropdown">
            <img src="../Image/Icon/menu.png" alt="Kategoria" class="button-icon"> KATEGORIE
            <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
        </button>
        <div class="nav-links">
            <a href="#">
                <img src="../Image/Icon/brickwall.png" class="category-icon"> BUDOWA
                <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
            </a>
            <a href="#">
                <img src="../Image/Icon/furnace.png" class="category-icon"> INSTALACJE
                <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
            </a>
            <a href="#">
                <img src="../Image/Icon/rent.png" class="category-icon"> WYPOŻYCZALNIA SPRZĘTU
                <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
            </a>
            <a href="#">
                <img src="../Image/Icon/discount.png" class="category-icon"> PROMOCJE
                <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
            </a>
        </div>
    </nav>
</header>

<div id="no-results-message" class="no-results-message" style="display: none;">
    Nie ma takiego produktu.
</div>

<!-- Główna sekcja produktu -->
<main class="main-content">
    <div class="container">

        <div class="back-button-container">
            <a href="../index.php" class="back-button">◄  Powrót do strony głównej</a>
        </div>

        <div class="product-container">
            <!-- Sekcja zdjęć produktu -->
            <div class="product-images">
                <div class="thumbnail-images">
                    <?php
                    $images_dir = "../Image/Product/" . $product['nazwa_kategorii'] . "/";
                    $images = glob($images_dir . $product_id . '.*.*');

                    foreach ($images as $image) {
                        echo "<img src='{$image}' alt='Zdjęcie produktu {$product_id}' class='thumbnail'><br>";}

                    ?>
                </div>
            </div>

            <div id="imageModal" class="modal">
                <span class="close">&times;</span>
                <img class="modal-content" id="enlargedImage">
                <div class="navigation">
                    <span class="prev">&lt;</span>
                    <span class="next">&gt;</span>
                </div>
            </div>

            <!-- Szczegóły produktu -->
            <div class="product-details">
                <h2> <?php echo ($product['nazwa_produktu']); ?> </h2>
                <p>Nr produktu: <?php echo ($product['produkt_id']); ?></p>
                <div class="product-price">
                    <span class="current-price"> <?php echo ($product['cena']); ?> </span>
                </div>
                <div class="product-availability">
                    <span>Dostępność: <strong> <?php echo ($product['ilosc_w_magazynie']); ?></strong></span>
                    <span>Dostawca: <strong> <?php echo ($product['nazwa_dostawcy']); ?> </strong></span>
                </div>
                <div class="product-actions">
                    <div class="quantity-cart-container">
                        <div class="quantity-control">
                            <button type="button" class="decrease-quantity" onclick="changeQuantity(this, -1)">-</button>
                            <input type="number" value="1" min="1" class="quantity" name="quantity" onchange="updateQuantityDisplay(this)">
                            <button type="button" class="increase-quantity" onclick="changeQuantity(this, 1)">+</button>
                        </div>
                        <form method="POST" action="../Store/cart_actions.php" class="add-to-cart-form">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $product['produkt_id'] ?>">
                            <input type="hidden" name="product_name" value="<?= ($product['nazwa_produktu']) ?>">
                            <input type="hidden" name="product_price" value="<?= $product['cena'] ?>">
                            <input type="hidden" class="form-quantity" name="quantity" value="1">
                            <button type="submit" class="add-to-cart">
                                <img src="../Image/Icon/pngegg.png" style="filter: invert(1) brightness(1000%);" alt="Dodaj do koszyka"> DO KOSZYKA
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opis produktu poniżej -->
        <div class="product-description">
            <h3>Opis produktu</h3>
            <p><?php echo $product['opis']; ?></p>
        </div>
        <div class="product-opinions">
            <h3>Opinie produktu</h3>
            <?php if (empty($opinions)): ?>
                <p>Brak komentarzy.</p>
            <?php else: ?>
                <?php foreach ($opinions as $opinion): ?>
                    <div class="comment">
                        <p><?php echo nl2br(($opinion['tresc_opinii'])); ?></p>
                        <p>Autor:
                            <?php
                            if ($opinion['login']) {
                                echo ($opinion['login']);
                            } else {
                                echo "Gość";
                            }
                            ?>
                        </p>
                        <p>Data: <?php echo $opinion['data_opinii']; ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <h2>Dodaj Opinię</h2>
            <form method="POST">
                <textarea name="opinion" required></textarea>
                <select name="rating" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
                <button type="submit">Dodaj Opinię</button>
            </form>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2024 Budex Sp z.o.o . Wszelkie prawa zastrzeżone.</p>
</footer>
<script>
    let cart = [];

    // Funkcja dodająca produkt do koszyka
    function addToCart(event) {
        const quantityInput = event.target.closest('.product-actions').querySelector('.quantity');
        const productName = event.target.closest('.product-details').querySelector('h2').innerText;
        const productPriceText = event.target.closest('.product-details').querySelector('.current-price').innerText;

        const productPrice = parseFloat(productPriceText.replace(' zł', '').replace(',', '.'));
        const quantity = parseInt(quantityInput.value);


        const existingProduct = cart.find(item => item.name === productName);

        if (existingProduct) {
            existingProduct.quantity += quantity;
        } else {
            cart.push({ name: productName, price: productPrice, quantity });
        }

        localStorage.setItem('cartItems', JSON.stringify(cart));

        updateCart();
    }

    // Funkcja aktualizująca widok koszyka
    function updateCart() {
        const cartCountElement = document.getElementById('cart-count');
        const cartInfoElement = document.querySelector('.cart-info span');
        let totalPrice = 0;
        let totalQuantity = 0;

        cart = JSON.parse(localStorage.getItem('cartItems')) || [];

        cart.forEach(item => {
            totalQuantity += item.quantity;
            totalPrice += item.price * item.quantity;
        });

        cartCountElement.textContent = totalQuantity;


        cartInfoElement.textContent = `Twój koszyk: ${totalPrice.toFixed(2)} zł`;


        const noResultsMessage = document.getElementById('no-results-message');
        if (totalQuantity === 0) {
            noResultsMessage.style.display = 'block';
        } else {
            noResultsMessage.style.display = 'none';
        }
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


    // Funkcja do filtrowania produktów
    function filterProducts() {
        const query = document.getElementById('search-input').value.toLowerCase();
        const products = document.querySelectorAll('.product-container');
        let found = false;

        if (query.trim() === '') {

            products.forEach(product => {
                product.style.display = '';
            });
            document.getElementById('no-results-message').style.display = 'none';
            return;
        }

        // Iteracja po wszystkich produktach
        products.forEach(product => {
            const productName = product.querySelector('h2').textContent.toLowerCase();


            if (productName.includes(query)) {
                product.style.display = '';
                found = true;
            } else {
                product.style.display = 'none';
            }
        });


        const noResultsMessage = document.getElementById('no-results-message');
        if (!found && query.trim() !== '') {
            noResultsMessage.style.display = 'block';
        } else {
            noResultsMessage.style.display = 'none';
        }
    }




    document.getElementById('search-button').addEventListener('click', filterProducts);
    document.getElementById('search-input').addEventListener('input', filterProducts);

    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('.decrease-quantity').forEach(button => {
            button.addEventListener('click', function() {
                changeQuantity(this, -1);
            });
        });

        document.querySelectorAll('.increase-quantity').forEach(button => {
            button.addEventListener('click', function() {
                changeQuantity(this, 1);
            });
        });


        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', addToCart);
        });
    });



    document.addEventListener('DOMContentLoaded', function() {
        const thumbnails = document.querySelectorAll('.thumbnail');
        const mainImage = document.getElementById('mainImage');
        const modal = document.getElementById('imageModal');
        const enlargedImage = document.getElementById('enlargedImage');
        const closeBtn = document.querySelector('.close');
        const prevBtn = document.querySelector('.prev');
        const nextBtn = document.querySelector('.next');

        // Tablica wszystkich zdjęć do nawigacji
        const images = Array.from(thumbnails).map(thumbnail => thumbnail.dataset.large);
        let currentIndex = 0;

        // Kliknięcie miniaturki - zmiana głównego zdjęcia
        thumbnails.forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', () => {
                mainImage.src = thumbnail.dataset.large;
                currentIndex = index;
            });
        });

        // Kliknięcie głównego zdjęcia - otwarcie modala
        mainImage.addEventListener('click', () => {
            openModal(currentIndex);
        });

        // Funkcja otwierająca modal
        function openModal(index) {
            enlargedImage.src = images[index];
            modal.style.display = 'block';
            currentIndex = index;
        }

        // Funkcja zamykająca modal
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });


        prevBtn.addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                openModal(currentIndex);
            }
        });


        nextBtn.addEventListener('click', () => {
            if (currentIndex < images.length - 1) {
                currentIndex++;
                openModal(currentIndex);
            }
        });

        // Zamknięcie modala po kliknięciu poza zdjęcie
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Obsługa klawiszy strzałek
        window.addEventListener('keydown', (event) => {
            if (modal.style.display === 'block') {
                if (event.key === 'ArrowLeft') {
                    prevBtn.click();
                } else if (event.key === 'ArrowRight') {
                    nextBtn.click();
                } else if (event.key === 'Escape') {
                    modal.style.display = 'none';
                }
            }
        });
    });



</script>
</body>
</html>

