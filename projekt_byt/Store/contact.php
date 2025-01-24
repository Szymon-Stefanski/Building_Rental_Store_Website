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
$categories = $stmt->fetchAll();

$groupedCategories = [];
foreach ($categories as $row) {
    $groupedCategories[$row['nazwa_kategorii']][] = $row;
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

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    $logged_in = false;
    $user_id = null;
} else {
    $logged_in = true;
    $user_id = $_SESSION['user_id'];
}

// Pobranie roli użytkownika z bazy danych
$stmt = getDbConnection()->prepare("SELECT rola FROM Uzytkownicy WHERE uzytkownik_id = ?");
$stmt->execute([$user_id]);
$userRole = $stmt->fetchColumn();

$query = "
    SELECT p.produkt_id, p.nazwa_produktu, p.cena, c.nazwa_kategorii AS category_name
    FROM Produkty p
    JOIN Kategorie c ON p.kategoria_id = c.id
";

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
    <link rel="stylesheet" href="../Style/style_contact.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../Image/Icon/budex.png">
</head>
<body>
<!-- Główny kontener strony -->
<div class="container">

    <!-- Header Section -->
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
                    <a href="favorites.php">
                        <div class="header-favorites-icon">
                            <img src="../Image/Icon/favourite.png" alt="Ulubione">
                            <span class="header-favorite-count">0</span>
                        </div>
                    </a>
                </div>
            <div class="cart-info">
                    <span style="font-weight: bold; text-align: center; margin-left: 15px;">Koszyk:<span id="total-price" style="margin-right: 10px; padding-left: 5px;"><?= number_format($totalPrice, 2) ?> zł</span><!--</p>-->
                    <?php
                    $current_url = urlencode("http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                    ?>
                    <a href="../Store/cart.php?source=<?php echo $current_url; ?>"> <!-- Link do strony koszyka -->
                        <div class="cart-icon">
                            <img src="../Image/Icon/pngegg.png" alt="Koszyk">
                            <span id="cart-count"><?= $itemCount ?></span> <!-- Liczba produktów w koszyku -->
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
                        <?php if (!empty($groupedCategories)): ?>
                            <?php foreach ($groupedCategories as $category => $categories): ?>
                                <a href="../index.php#<?= ($category) ?>" class="category-link"><?= htmlspecialchars($category) ?></a>
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
            <div class="advertisement-slider">
                <img src="../Image/Advert/reklamaswieta.png" alt="Reklama 1" class="ad-image">
                <img src="../Image/Advert/budex.png" alt="Reklama 2" class="ad-image">
                <img src="../Image/Advert/baner-uslugi.png" alt="Reklama 3" class="ad-image">
                <img src="../Image/Advert/szlifierkenmachendruten.jpg" alt="Reklama 4" class="ad-image">
            </div>
            <div class="advertisement-dots">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
        <section class="contact">
            <h2>Kontakt z nami</h2>
            <div class="contact-policy">
                <p><strong>Adres:</strong> Gdańsk, ul. Budowlana 4</p>
                <p><strong>Telefon:</strong> +48 555 348 591</p>
                <p><strong>Email:</strong> budexgdansk@gmail.com</p>

                <h3>Godziny dostępności:</h3>
                <ul>
                    <li><strong>Poniedziałek:</strong> 8:00 - 18:00</li>
                    <li><strong>Wtorek:</strong> 8:00 - 17:00</li>
                    <li><strong>Środa:</strong> 8:00 - 18:00</li>
                    <li><strong>Czwartek:</strong> 8:00 - 17:00</li>
                    <li><strong>Piątek:</strong> 8:00 - 18:00</li>
                    <li><strong>Sobota:</strong> 9:00 - 14:00</li>
                    <li><strong>Niedziela i święta:</strong> Nieczynne</li>
                </ul>

                <p><strong>Gwarancja jakości:</strong> Zapewniamy najwyższy standard świadczonych usług.</p>
            </div>
        </section>



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
                            <li><a href="deliveryCost.php">Koszty dostawy</a></li>
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
    </main>
</div>
<script>

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
</script>
</body>
</html>


