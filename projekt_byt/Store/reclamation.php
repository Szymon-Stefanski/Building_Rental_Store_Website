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
    <title>Sklep Budowlany</title>
    <link rel="stylesheet" href="../Style/style_reclamation.css">

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
                    <span style="font-weight: bold;">Twój koszyk: <span id="total-price"><?= number_format($totalPrice, 2) ?> zł</span></p>
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
                <a href="#">
                    <img src="../Image/Icon/rent.png" class="category-icon"> WYPOŻYCZALNIA SPRZĘTU
                    <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                </a>

                <a href="#">
                    <img src="../Image/Icon/discount.png" class="category-icon"> PROMOCJE
                    <img src="../Image/Icon/down-arrow.png" alt="Strzałka w dół" class="arrow-icon">
                </a>
                <?php if ($userRole === 'administrator' || $userRole === 'moderator'): ?>
                    <a href="../Warehouse/stockManagement.php">
                        <img src="../Image/Icon/support.png" class="category-icon"> ZARZĄDZANIE STANEM MAGAZYNU
                    </a>
                <?php endif; ?>
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
                <img src="../Image/Advert/szlifierkenmachendruten.jpg" alt="Reklama 1" class="ad-image">
                <img src="../Image/Advert/budex.png" alt="Reklama 2" class="ad-image">
                <img src="../Image/Advert/reklama.png" alt="Reklama 3" class="ad-image">
            </div>
            <div class="advertisement-dots">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
        <section class="returns">
            <h2>Reklamacje i Zwroty</h2>
            <div class="returns-policy">
                <h3>Zwroty</h3>
                <p>
                    Masz prawo zwrócić zakupione produkty w ciągu 14 dni od daty ich otrzymania, bez podawania przyczyny.
                    Aby skorzystać z prawa zwrotu, upewnij się, że produkty nie były używane i są w oryginalnym opakowaniu.
                </p>
                <p>
                    Wypełnij formularz zwrotu dostępny na naszej stronie i wyślij produkty na adres:
                    <br><strong>Budex Sp. z o.o., ul. Budowlana 4, 80-270 Gdańsk</strong>
                </p>
            </div>
            <div class="complaints-policy">
                <h3>Reklamacje</h3>
                <p>
                    Jeśli otrzymany produkt jest uszkodzony lub niezgodny z zamówieniem, zgłoś reklamację w ciągu 7 dni od jego otrzymania.
                    Wypełnij formularz reklamacyjny dostępny na naszej stronie, dołącz zdjęcia produktu oraz opis problemu.
                </p>
                <p>
                    Reklamacje należy przesyłać na adres e-mail: <strong>budex@gmail.com</strong> lub kontaktować się telefonicznie pod numerem:
                    <strong>+48 555 348 591</strong>.
                </p>
            </div>
        </section>


        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> Budex Sp z.o.o . Wszelkie prawa zastrzeżone.</p>
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


