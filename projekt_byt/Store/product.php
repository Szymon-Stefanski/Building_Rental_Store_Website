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

$itemCount = 0;
$totalPrice = 0.0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $itemCount += $item['quantity']; // Liczba przedmiotów
        $totalPrice += $item['price'] * $item['quantity']; // Łączna wartość
    }
}

$current_url = urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sklep Budowlany Budex</title>
    <link rel="stylesheet" href="../Style/style_product.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../Image/Icon/budex.png">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="top-bar">
                <div class="top-links">
                    <a href="deliveryCost.php">Koszty dostawy</a>
                    <a href="reclamation.php">Reklamacje i zwroty</a>
                    <a href="contact.php">Kontakt</a>
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
                        <a href="../Login/login.php?source=<?php echo $current_url;?>">
                            <img src="../Image/Icon/user.png" alt="logowanie">
                            Logowanie
                        </a>
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
                    <a href="Store/favorites.php">
                        <div class="header-favorites-icon">
                            <img src="../Image/Icon/favourite.png" alt="Ulubione">
                            <span class="header-favorite-count">0</span>
                        </div>
                    </a>
                </div>
                <div class="cart-info">
                    <span style="font-weight: bold; text-align: center; margin-left: 15px;">Twój koszyk: <span id="total-price" style="margin-right: 10px; padding-left: 5px;"><?= number_format($totalPrice, 2) ?> zł</span></p>
                    <?php
                    $current_url = urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                    ?>
                    <a href="cart.php?source=<?php echo $current_url; ?>"> <!-- Link do strony koszyka -->
                        <div class="cart-icon">
                            <img src="../Image/Icon/pngegg.png" alt="Koszyk">
                            <span id="cart-count"><?= $itemCount ?></span> <!-- Liczba produktów w koszyku -->
                        </div>
                    </a>
                </div>
            </div>



            <nav class="main-navigation">
                
                <div class="nav-links">
                    <a href="../index.php" class="back">
                        <img src="../Image/Icon/log-in.png" class="category-icon"> POWRÓT
                    </a>
                    <button class="category-dropdown" style="color:white;">
                        <img src="../Image/Icon/menu.png" alt="Kategoria" class="button-icon"> KATEGORIE
                        <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                    </button>
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
            <h3 id="toggle-opinions">
                Opinie produktu (<span id="opinion-count"><?php echo count($opinions); ?></span>)
                <img class="arrow" src="../Image/Icon/arrow2.png" alt="Strzałka w dół">
            </h3>

            <div id="opinions-container" class="hidden">
                <?php if (empty($opinions)): ?>
                    <p>Brak komentarzy.</p>
                <?php else: ?>
                    <div class="comments-container">
                        <?php foreach ($opinions as $opinion): ?>
                            <div class="comment">
                                <div class="stars">
                                    <?php for ($i = 0; $i < $opinion['ocena']; $i++): ?>
                                        <span class="star">&#9733;</span>
                                    <?php endfor; ?>
                                </div>

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
                    </div>
                <?php endif; ?>
            </div>

            <h2>Dodaj Opinię</h2>
            <form method="POST">
                <textarea name="opinion" required placeholder="Napisz swoją opinię..."></textarea>

                <div class="rating">
                    <label>
                        <input type="radio" name="rating" value="1" required>
                        <span class="star">&#9733;</span>
                    </label>
                    <label>
                        <input type="radio" name="rating" value="2">
                        <span class="star">&#9733;</span>
                    </label>
                    <label>
                        <input type="radio" name="rating" value="3">
                        <span class="star">&#9733;</span>
                    </label>
                    <label>
                        <input type="radio" name="rating" value="4">
                        <span class="star">&#9733;</span>
                    </label>
                    <label>
                        <input type="radio" name="rating" value="5">
                        <span class="star">&#9733;</span>
                    </label>
                </div>

                <button type="submit">
                    <img src="../Image/Icon/plus.png" alt="Ikona opinii">
                    Dodaj Opinię
                </button>
            </form>
        </div>

    </div>
</main>

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
                        <button type="submit"><i class="fa fa-arrow-right"></i> ZAPISZ SIĘ</button>
                        
                        
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

    </div>
        


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

    
            document.addEventListener('DOMContentLoaded', () => {
            const stars = document.querySelectorAll('.rating .star');
            const inputs = document.querySelectorAll('.rating input[type="radio"]');

            inputs.forEach((input, index) => {
                input.addEventListener('change', () => {
                    highlightStars(index + 1);
                });
            });

            stars.forEach((star, index) => {
                star.addEventListener('mouseover', () => {
                    highlightStars(index + 1);
                });

                star.addEventListener('mouseout', () => {
                    const checkedInput = document.querySelector('.rating input[type="radio"]:checked');
                    if (checkedInput) {
                        highlightStars(parseInt(checkedInput.value));
                    } else {
                        resetStars();
                    }
                });
            });

            function highlightStars(count) {
                stars.forEach((star, index) => {
                    star.style.color = index < count ? '#ffd700' : '#ccc';
                });
            }

            function resetStars() {
                stars.forEach((star) => {
                    star.style.color = '#ccc';
                });
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
        const toggleOpinions = document.getElementById('toggle-opinions');
        const opinionsContainer = document.getElementById('opinions-container');
        const arrow = toggleOpinions.querySelector('.arrow');

        toggleOpinions.addEventListener('click', () => {
            opinionsContainer.classList.toggle('hidden');
            arrow.classList.toggle('up');
        });
    });

    
    
    

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

        document.addEventListener('DOMContentLoaded', updateFavoriteCount);


</script>
</body>
</html>

