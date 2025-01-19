<?php
session_start();
require '../database_connection.php';

$stmt = getDbConnection()->prepare("
    SELECT k.nazwa_kategorii, p.produkt_id, p.nazwa_produktu, p.cena
    FROM Produkty p
    LEFT JOIN Kategorie k ON p.kategoria_id = k.kategoria_id
    ORDER BY k.nazwa_kategorii, p.nazwa_produktu
");

$stmt->execute();
$Products = $stmt->fetchAll();

// Konwersja danych na JSON do użytku w JavaScript
$productsJson = json_encode($Products);


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


// Odbieramy dane z POST
if (isset($_POST['favorites'])) {
    $favorites = json_decode($_POST['favorites']);

    // Zapytanie do bazy, aby pobrać szczegóły produktów
    $placeholders = implode(',', array_fill(0, count($favorites), '?'));
    $stmt = getDbConnection()->prepare("
        SELECT k.nazwa_kategorii, p.produkt_id, p.nazwa_produktu, p.cena
        FROM Produkty p
        LEFT JOIN Kategorie k ON p.kategoria_id = k.kategoria_id
        WHERE p.produkt_id IN ($placeholders)
        ORDER BY k.nazwa_kategorii, p.nazwa_produktu
    ");

    // Wykonanie zapytania z odpowiednimi produktami
    $stmt->execute($favorites);
    $products = $stmt->fetchAll();

    // Generowanie HTML na podstawie produktów
    $html = '';
    $groupedProducts = [];

    foreach ($products as $product) {
        $groupedProducts[$product['nazwa_kategorii']][] = $product;
    }

    foreach ($groupedProducts as $category => $productsInCategory) {
        $html .= "<section class='products'><div class='category-container' id='$category'><h2>$category</h2><div class='product-grid'>";

        foreach ($productsInCategory as $product) {
            $html .= "
        <div class='product-card'>
            <a href='Store/product.php?id={$product['produkt_id']}'>
                <img src='" . findProductImage($product['produkt_id'], $category, $product['nazwa_produktu']) . "' alt='Obraz produktu: {$product['nazwa_produktu']}' class='zdjecie'>
                <h3>{$product['nazwa_produktu']}</h3>
                <p class='product-price'>" . number_format($product['cena'], 2, ',', ' ') . " zł/szt.</p>
            </a>
            <div class='favorite-icon' data-product-id='{$product['produkt_id']}'>
            <img src='../Image/Icon/love-always-wins.png' alt='Ulubione'>
            </div>

             <div class='quantity-cart-container'>
                <div class='quantity-control'>
                    <button type='button' class='decrease-quantity' onclick='changeQuantity(this, -1)'>-</button>
                    <input type='number' value='1' min='1' class='quantity' name='quantity' onchange='updateQuantityDisplay(this)'>
                    <button type='button' class='increase-quantity' onclick='changeQuantity(this, 1)'>+</button>
                </div>
                
                <form method='POST' action='cart_actions.php' class='add-to-cart-form'>
                    <input type='hidden' name='action' value='add'>
                    <input type='hidden' name='product_id' value='{$product['produkt_id']}'>
                    <input type='hidden' name='product_name' value='" . htmlspecialchars($product['nazwa_produktu']) . "'>
                    <input type='hidden' name='product_price' value='{$product['cena']}'>
                    
                    <!-- Ta linia jest odpowiedzialna za dynamiczną ilość w formularzu -->
                    <input type='hidden' class='form-quantity' name='quantity' value='1'>
                    
                    <button type='submit' class='add-to-cart'>
                        <img src='../Image/Icon/pngegg.png' style='filter: invert(1) brightness(1000%);' alt='Dodaj do koszyka'> DO KOSZYKA
                    </button>
                </form>
            </div>
        </div>
    ";
        }


        $html .= "</div></div></section>";
    }

    // Zwracamy wygenerowany HTML
    echo $html;
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$itemCount = 0;
$totalPrice = 0.0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $itemCount += $item['quantity']; // Liczba przedmiotów
        $totalPrice += $item['price'] * $item['quantity']; // Łączna wartość
    }
}

// Pobranie wszystkich kategorii do nagłówka
$stmtCategories = getDbConnection()->prepare("
    SELECT DISTINCT k.nazwa_kategorii 
    FROM Kategorie k
    ORDER BY k.nazwa_kategorii
");

$stmtCategories->execute();
$categories = $stmtCategories->fetchAll(PDO::FETCH_COLUMN); // Pobranie samych nazw kategorii

$current_url = urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Style/style_favorites.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="Image/Icon/budex.png">
    <title>Ulubione produkty</title>
</head>
<body>
<main>
    <!-- Główny kontener strony -->
    <div class="container">

        <!-- Header Section -->
        <header class="header">
            <div class="top-bar">
                <div class="top-links">
                    <a href="../Store/deliveryCost.php">Koszty dostawy</a>
                    <a href="../Store/reclamation.php">Reklamacje i zwroty</a>
                    <a href="../Store/contact.php">Kontakt</a>
                </div>
                <div class="language-currency">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php
                        $username = ($_SESSION['username']);
                        ?>
                        <p><a href="../login/profile.php?id=<?php echo $_SESSION['user_id']; ?>">
                                Witaj <?php echo $username; ?> !
                            </a></p>
                    <?php else: ?>
                        <p>
                            <a href="../Login/login.php?source=<?php echo $current_url;?>">
                                <img src="../Image/Icon/user.png" alt="logowanie">
                                Logowanie
                            </a>
                        </p>

                    <?php endif; ?>
                </div>
            </div>

            <div class="header-container">
                <div class="logo">
                    <a href="../index.php">
                        <img src="../Image/Icon/budex.png" alt="Logo sklepu" />
                    </a>
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Wpisz nazwę lub kod produktu..." id="search-input" />
                    <button id="search-button">
                        <img src="../Image/Icon/magnifying-glass.png" alt="Szukaj" class="search-icon">
                        Szukaj
                    </button>
                </div>
                <div class="header-favorites">
                    <a href="../index.php">
                        <div class="header-favorites-icon">
                            <img src="../Image/Icon/favourite.png" alt="Ulubione">
                            <span class="header-favorite-count">0</span>
                        </div>
                    </a>
                </div>
                <div class="cart-info">
                    <span style="font-weight: bold; text-align: center; margin-left: 15px;">Koszyk:<span id="total-price" style="margin-right: 10px; padding-left: 5px;"><?= number_format($totalPrice, 2) ?> zł</span><!--</p> Tutaj ma ktoś nie zamknięte znaczniki, ale jak spróbujesz zamknąć dany znacznik to cały widok się psuje-->
                    <a href="../Store/cart.php?source=<?php echo $current_url; ?>">
                        <div class="cart-icon">
                            <img src="../Image/Icon/pngegg.png" alt="Koszyk">
                            <span id="cart-count"><?= $itemCount ?></span>
                        </div>
                    </a>
                </div>
            </div>

            <nav class="main-navigation">

                <div class="nav-links">
                    <div class="category-dropdown">
                        <img src="../Image/Icon/menu.png" alt="Kategoria" class="button-icon"> KATEGORIE
                        <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                        <div class="category-dropdown-menu">
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $category): ?>
                                    <a href="#<?= htmlspecialchars($category) ?>" class="category-link"><?= htmlspecialchars($category) ?></a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div id="no-results-message" class="no-results-message">
                                    Przepraszamy, nie znaleziono kategorii.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                        <a href="#">
                            <img src="../Image/Icon/brickwall.png" class="category-icon"> BUDOWA
                            <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                        </a>
                        <a href="#">
                            <img src="../Image/Icon/furnace.png" class="category-icon"> INSTALACJE
                            <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                        </a>
                        <a href="rental.php?source=<?php echo $current_url; ?>">
                            <img src="../Image/Icon/rent.png" class="category-icon"> WYPOŻYCZALNIA
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
            Przepraszamy, jeszcze nie mamy tego produktu.
        </div>


        <!-- Main Content -->
        <main class="main-content">

            <div class="advertisement-container">
                <h3 class="header-h3">Ulubione Produkty</h3>
                <p class="opis">Dodawaj produkty do ulubionych, aby mieć do nich szybki dostęp. Możesz je dodać do koszyka lub usunąć z listy w dowolnym momencie.</p>
               
            </div>

            <div id="favorites-container">
                <!-- Lista ulubionych generowana dynamiczne -->
            </div>

            <footer class="footer">
                <div class="footer-container">
                    <div class="footer-contact">
                        <h3>SKONTAKTUJ SIĘ Z NAMI</h3>
                        <ul>
                            <li><i class="fa fa-phone"></i> +48 555 348 591<br> Pn-Pt 8:00-18:00, Sb 9:00-14:00</li>
                            <li><i class="fa fa-envelope"></i> budexgdansk@gmail.com<br> Odpowiedź do 24H</li>
                            <li><i class="fa fa-map-marker"></i> ul. Budowlana 4, 80-253 Gdańsk</li>
                        </ul>
                    </div>
                    <div class="footer-links">
                        <h3>INFORMACJE</h3>
                        <ul>
                            <li><a href="#">O nas</a></li>
                            <li><a href="#">Rabaty</a></li>
                            <li><a href="#">Sprzedaż hurtowa</a></li>
                            <li><a href="#">Regulamin</a></li>
                        </ul>
                    </div>
                    <div class="footer-links">
                        <h3>OBSŁUGA KLIENTA</h3>
                        <ul>
                            <li><a href="#">Najczęściej zadawane pytania</a></li>
                            <li><a href="#">Koszty dostawy</a></li>
                            <li><a href="#">Reklamacje</a></li>
                            <li><a href="#">Zwroty</a></li>
                        </ul>
                    </div>
                    <div class="footer-links">
                        <h3>KONTO</h3>
                        <ul>
                            <li><a href="#">Dane osobowe</a></li>
                            <li><a href="#">Zamówienia</a></li>
                            <li><a href="#">Adresy</a></li>
                        </ul>
                    </div>
                    <div class="footer-newsletter">
                        <h3>NEWSLETTER</h3>
                        <p>Chcesz być na bieżąco z najlepszymi ofertami? Zapisz się do newslettera i nie przegap okazji!</p>
                        <form action="../newsletter.php" method="GET">
                            <button type="submit">
                                <i class="fa fa-arrow-right"></i> ZAPISZ SIĘ
                            </button>
                        </form>


                    </div>

                </div>
                <div class="footer-bottom">
                    <div class="social-media">
                        <h3>OBSERWUJ NAS</h3>
                        <a href="https://www.facebook.com/"><i class="fab fa-facebook"></i></a>
                        <a href="https://x.com/home"><i class="fab fa-x"></i></a>
                        <a href="https://www.instagram.com/"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.youtube.com/"><i class="fab fa-youtube"></i></a>
                    </div>
                    <div class="payment-methods">
                        <img src="../Image/Icon/symbols.png" alt="Visa" class="reverse-colors">
                        <img src="../Image/Icon/paypal.png" alt="PayPal" class="reverse-colors">
                        <img src="../Image/Icon/blik.png" alt="Blik">
                        <img src="../Image/Icon/apple-pay.png" alt="Apple Pay" class="reverse-colors">
                    </div>

                    <div class="footer-copyright">
                        <p>&copy; <?php echo date('Y'); ?> Budex Sp z.o.o. Wszelkie prawa zastrzeżone. All rights reserved. Realizacja: SEF </p>
                    </div>
                </div>
            </footer>

        </main>

        <button id="scrollToTop">
            <i class="fas fa-chevron-up"></i>
        </button>

    </div>
</main>
</body>
<script>
    // Funkcja do wyświetlenia ulubionych produktów
    function displayFavorites() {
        const favorites = JSON.parse(localStorage.getItem('favorites')) || [];
        const favoritesContainer = document.getElementById('favorites-container');
        const favoriteCountElement = document.querySelector('.header-favorite-count');
        favoriteCountElement.innerText = favorites.length;
        if (favorites.length === 0) {
            favoritesContainer.innerHTML = '<div class="elo">Twoja lista ulubionych produktów jest pusta. Przeglądaj naszą ofertę i dodawaj ulubione produkty, klikając ikonę serca!</div>';
            return;
        }

        // Wysyłanie listy ulubionych produktów do PHP
        fetch('favorites.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `favorites=${JSON.stringify(favorites)}`
        })
            .then(response => response.text())
            .then(data => {
                favoritesContainer.innerHTML = data;  // Wyświetlamy zwrócony HTML
                const favoriteIcons = document.querySelectorAll('.favorite-icon');
                console.log(favoriteIcons);  // Sprawdzamy, czy ikony są poprawnie znalezione

                favoriteIcons.forEach(icon => {
                    icon.addEventListener('click', function() {
                        const productId = this.dataset.productId;
                        console.log(`Kliknięto ikonę produktu: ${productId}`);
                        removeFavorite(productId, icon);  // Wywołanie funkcji usuwania z ulubionych
                    });
                });

                favoritesContainer.addEventListener('click', function(event) {
                    if (event.target.closest('.favorite-icon')) {
                        const icon = event.target.closest('.favorite-icon');
                        const productId = icon.dataset.productId;
                        console.log(`Kliknięto ikonę produktu: ${productId}`);
                        removeFavorite(productId, icon);
                    }
                });
            })
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log("Skrypt załadowany i strona gotowa.");
        displayFavorites();
    });

    // Funkcja usuwająca produkt z ulubionych
    function removeFavorite(productId, iconElement) {
        console.log(`Usuwam produkt z ulubionych: ${productId}`);
        let favorites = JSON.parse(localStorage.getItem('favorites')) || [];
        favorites = favorites.filter(id => id !== parseInt(productId));  // Usunięcie produktu
        localStorage.setItem('favorites', JSON.stringify(favorites));  // Zapisanie zaktualizowanej listy

        // Usuwamy produkt z widoku
        iconElement.closest('.product-card').remove();  // Usuwamy cały kartonik z produktem

        // Usuwany produkt z licznika
        const favoriteCountElement = document.querySelector('.header-favorite-count');
        favoriteCountElement.innerText = favorites.length;

        // Jeśli lista ulubionych jest pusta, pokaż wiadomość
        if (favorites.length === 0) {
            document.getElementById('favorites-container').innerHTML = '<div class="elo">Twoja lista ulubionych produktów jest pusta. Przeglądaj naszą ofertę i dodawaj ulubione produkty, klikając ikonę serca!</div>';
        }
    }


    //płynne przewijanie do kategorii
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    let cart = [];

    let currentIndex = 0;
    const images = document.querySelectorAll('.advertisement-slider .ad-image');
    const dots = document.querySelectorAll('.advertisement-dots .dot');


    // Funkcja zmieniająca aktywny obrazek
    function changeImage() {
        // Ukryj wszystkie obrazki
        images.forEach(image => image.style.display = 'none');
        // Ukryj wszystkie kółeczka
        dots.forEach(dot => dot.classList.remove('active'));

        // Pokaż obecny obrazek
        images[currentIndex].style.display = 'block';
        dots[currentIndex].classList.add('active');

        // Przejdź do następnego obrazka
        currentIndex = (currentIndex + 1) % images.length;
    }

    // Funkcja zmieniająca obrazek na kliknięcie kółeczka
    function goToImage(index) {
        currentIndex = index;
        changeImage();
    }

    // Dodajemy obsługę kliknięć na kółeczka
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => goToImage(index));
    });

    // Automatyczne przełączanie obrazków co 3 sekundy
    setInterval(changeImage, 6000);

    // Inicjalne ustawienie pierwszego obrazka
    changeImage();


    // Funkcja do animacji kółeczka z liczbą produktów
    function animateCartCount() {
        const cartCountElement = document.getElementById('cart-count');
        // Dodajemy klasę animującą
        cartCountElement.classList.add('animating');

        // Usuwamy klasę po zakończeniu animacji, by kółeczko wróciło do normalnych rozmiarów
        setTimeout(() => {
            cartCountElement.classList.remove('animating');
        }, 300); // Długość animacji (w tym przypadku 0.3 sekundy)
    }

    // Funkcja do aktualizacji zawartości koszyka
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

        // Animacja kółeczka
        animateCartCount();

        // Ukrywamy lub pokazujemy komunikat o braku produktów
        const noResultsMessage = document.getElementById('no-results-message');
        if (totalQuantity === 0) {
            noResultsMessage.style.display = 'block';
        } else {
            noResultsMessage.style.display = 'none';
        }
    }

    // Funkcja dodająca produkt do koszyka
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

        // Zaktualizowanie zawartości koszyka po dodaniu produktu
        updateCart();
    }


    //funkcje changeQuantity, updateQuantityDisplay, updateQuantityInForm w js odpowiadają za sychronizacę widocznych przycisków z forumlarzem koszyka w php
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




    // Wyświetlanie przycisku po przewinięciu strony
    const scrollToTopButton = document.getElementById('scrollToTop');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 200) {
            scrollToTopButton.style.display = 'flex';
        } else {
            scrollToTopButton.style.display = 'none';
        }
    });

    // Przewijanie do góry po kliknięciu przycisku
    scrollToTopButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });



    function toggleFavorite(productId) {
        let favorites = JSON.parse(localStorage.getItem('favorites')) || [];
        if (favorites.includes(productId)) {
            favorites = favorites.filter(id => id !== productId); // Usuń produkt z ulubionych
        } else {
            favorites.push(productId); // Dodaj produkt do ulubionych
        }
        localStorage.setItem('favorites', JSON.stringify(favorites));
        updateFavoriteCount();
    }

    function updateFavoriteCount() {
        const favorites = JSON.parse(localStorage.getItem('favorites')) || [];
        const favoriteCountElement = document.querySelector('.header-favorite-count');
        const favoriteIconElement = document.querySelector('.header-favorites-icon');

        // Aktualizacja licznika
        favoriteCountElement.innerText = favorites.length;


        if (favorites.length > 0) {
            favoriteIconElement.classList.add('active');
        } else {
            favoriteIconElement.classList.remove('active');
        }
    }
    
    

</script>
</html>
