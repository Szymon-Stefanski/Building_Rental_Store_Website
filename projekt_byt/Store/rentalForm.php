<?php
session_start();
require '../database_connection.php';
include '../email_sender.php';

// Funkcja czyszcząca koszyk z produktów bez dni wynajmu
function cleanCart() {
    if (isset($_SESSION['cart'])) {
        // Tworzymy nową tablicę, która będzie zawierać tylko produkty z dniami wynajmu
        $cleanedCart = [];

        // Iterujemy po każdym przedmiocie w koszyku
        foreach ($_SESSION['cart'] as $item) {
            if (isset($item['rental_days']) && $item['rental_days'] > 0) {
                // Jeśli produkt ma przypisane dni wynajmu, zachowujemy go
                $cleanedCart[] = $item;
            }
        }

        // Nadpisujemy koszyk wyczyszczoną wersją
        $_SESSION['cart'] = $cleanedCart;
    }
}

// Jeżeli użytkownik jest gościem, czyli przekierowanie z "?guest=true"
if (isset($_GET['guest']) && $_GET['guest'] == 'true') {
    cleanCart();
    // Ustawienie sesji dla gościa, np. brak ID użytkownika
    $_SESSION['user_id'] = null;  // Usuń ID użytkownika, by traktować go jako gościa
}

// Autouzupełnianie danych użytkownika jeżeli jest zalogowany
if (isset($_SESSION['user_id'])) {
    $stmt = getDbConnection()->prepare(
        "SELECT adres, imie, nazwisko, email FROM Uzytkownicy WHERE uzytkownik_id = ?"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        // Rozdziel adres na części
        $addressParts = explode(', ', $userData['adres'] ?? '');
        $_SESSION['user'] = [
            'first_name' => $userData['imie'] ?? '',
            'last_name' => $userData['nazwisko'] ?? '',
            'email' => $userData['email'] ?? '',
            'postal_code' => $addressParts[0] ?? '',
            'city' => $addressParts[1] ?? '',
            'street' => $addressParts[2] ?? '',
        ];

        // Rozdziel trzeci element na numer domu i numer mieszkania, jeśli istnieje
        if (strpos($addressParts[3] ?? '', '/') !== false) {
            list($houseNumber, $apartmentNumber) = explode('/', $addressParts[3]);
            $_SESSION['user']['house_number'] = $houseNumber;
            $_SESSION['user']['apartment_number'] = $apartmentNumber;
        } else {
            $_SESSION['user']['house_number'] = $addressParts[3] ?? '';
            $_SESSION['user']['apartment_number'] = ''; // Brak numeru mieszkania
        }
    }
}

// Wywołanie funkcji czyszczenia koszyka
cleanCart();

// Funkcja do znajdowania ścieżki do obrazu produktu
function findProductImage($productId, $categoryName, $productName) {
    global $pdo;

    if (empty($categoryName)) {
        $categoryName = 'elektryka';
    }

    $categoryName = strtolower($categoryName);
    $imageDir = "../Image/Product/";
    $extensions = ['png', 'jpg', 'gif'];

    if ($categoryName === 'all') {
        $query = "SELECT nazwa_kategorii FROM Kategorie";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($categories as $category) {
            $categoryName = strtolower($category['nazwa_kategorii']);
            foreach ($extensions as $extension) {
                $filePath = $imageDir . $categoryName . "/" . $productId . ".1." . $extension;

                if (file_exists($filePath)) {
                    return $filePath;
                }
            }
        }
    } else {
        foreach ($extensions as $extension) {
            $filePath = $imageDir . $categoryName . "/" . $productId . ".1." . $extension;

            if (file_exists($filePath)) {
                return $filePath;
            }
        }
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['product_select'])) {
        $selectedProducts = $_POST['product_select'];
        $rentalDays = isset($_POST['rental_days']) ? $_POST['rental_days'] : [];
        $productPrices = isset($_POST['product_prices']) ? $_POST['product_prices'] : [];

        // Sprawdzenie, czy koszyk istnieje, jeśli nie - inicjalizacja
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Funkcja do sprawdzenia, czy produkt już znajduje się w koszyku
        function isProductInCart($cart, $productId) {
            foreach ($cart as $item) {
                if ($item['id'] == $productId) {
                    return true;
                }
            }
            return false;
        }

        // Dodawanie produktów do koszyka
        foreach ($selectedProducts as $productId) {
            $stmt = $pdo->prepare("SELECT * FROM produkty WHERE produkt_id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if ($product) {
                $productName = $product['nazwa_produktu'];
                $basePrice = $product['cena']; // Cena z bazy
                $rentalDaysForProduct = isset($rentalDays[$productId]) ? $rentalDays[$productId] : 1;

                // Obliczanie ceny wynajmu
                $rentalPrice = $basePrice * 0.05 * $rentalDaysForProduct; // 5% ceny * liczba dni wynajmu

                // Sprawdzamy, czy produkt już jest w koszyku
                if (!isProductInCart($_SESSION['cart'], $productId)) {
                    // Jeśli produktu nie ma w koszyku, dodajemy go
                    $_SESSION['cart'][] = [
                        'id' => $productId,
                        'name' => $productName,
                        'price' => $rentalPrice, // Zapisujemy obliczoną cenę wynajmu
                        'rental_days' => $rentalDaysForProduct, // Zapisujemy liczbę dni wynajmu
                    ];
                } else {
                    // Jeśli produkt już jest w koszyku, aktualizujemy liczbę dni wynajmu oraz cenę
                    foreach ($_SESSION['cart'] as &$item) {
                        if ($item['id'] == $productId) {
                            // Zamiast dodawania, po prostu aktualizujemy dni wynajmu i cenę
                            $item['rental_days'] = $rentalDaysForProduct;
                            $item['price'] = $basePrice * 0.05 * $rentalDaysForProduct; // Ponownie obliczamy cenę
                        }
                    }
                }
            } else {
                echo "Produkt o ID $productId nie został znaleziony w bazie danych.";
            }
        }

        // Po zakończeniu dodawania produktów, przekierowanie do rentalForm.php
        header('Location: rentalForm.php');
        exit;
    }
}

// Przygotowanie danych koszyka
$cartData = [];
$Vat = 0.08;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $itemTotal = $item['price'] * $item['rental_days']; // Cena * liczba dni wynajmu
        $itemTotal = $itemTotal + $itemTotal * $Vat; // Dodanie VAT
        $imagePath = findProductImage($item['id'], 'all', $item['name']);

        // Dodanie danych do koszyka
        $cartData[] = [
            'name' => htmlspecialchars($item['name'] ?? 'Produkt bez nazwy'),
            'price' => number_format($item['price'], 2, '.', ''),
            'rentalDays' => $item['rental_days'],  // Liczba dni wynajmu
            'total' => number_format($itemTotal, 2, '.', ''),
            'img' => $imagePath
        ];
    }
    
}

// Dodanie wynajmu i pozycji wynajmu do bazy danych
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cart = $_SESSION['cart'] ?? [];

    if (empty($cart)) {
        echo "Koszyk jest pusty!";
        exit;
    }

    if (empty($firstName) || empty($lastName) || empty($email)) {
        echo "Proszę uzupełnić wszystkie wymagane pola.";
        exit;
    }

    // Walidacja e-maila
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Proszę podać poprawny adres e-mail.";
        exit;
    }

    try {
        $pdo = getDbConnection();

        // Dodanie wynajmu do bazy danych
        $stmt = $pdo->prepare("
            INSERT INTO Wynajmy (uzytkownik_id, data_wynajmu, data_zwrotu, status) 
            VALUES (:userId, :rentalOut, :rentalIn, :status)
        ");
        $stmt->execute([
            ':userId' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,  // Przekazywanie ID użytkownika lub NULL
            ':rentalOut' => date('Y-m-d', strtotime('2025-01-10')),
            ':rentalIn' => date('Y-m-d', strtotime('2025-10-10')),
            ':status' => 'Nieopłacony'
        ]);

        $wynajemId = $pdo->lastInsertId();

        // Dodanie pozycji wynajmu
        $stmt = $pdo->prepare("
            INSERT INTO Pozycje_Wynajmu (wynajem_id, produkt_id, ilosc, stawka_dzienna, koszt_calkowity, uzytkownik_id) 
            VALUES (:wynajemId, :productId, :quantity, :dailyRate, :totalCost, :userId)
        ");
        foreach ($cart as $item) {
            // Walidacja danych w koszyku
            if (empty($item['id']) || empty($item['price']) || empty($item['rental_days'])) {
                echo "Nieprawidłowe dane w koszyku!";
                exit;
            }

            $dailyRate = $item['price'];
            $totalCost = $dailyRate * $item['rental_days'];

            $stmt->execute([
                ':wynajemId' => $wynajemId,
                ':productId' => $item['id'],
                ':quantity' => 1,
                ':dailyRate' => $dailyRate,
                ':totalCost' => $totalCost,
                ':userId' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,  // Przekazywanie ID użytkownika lub NULL
            ]);
        }

        // Wysyłanie e-maila
        $subject = "Potwierdzenie wynajmu";
        $message = "Dziękujemy za wynajem!";

        sendEmail($email, $subject, $message);
        echo "Wynajem został pomyślnie dodany!";
    } catch (PDOException $e) {
        echo "Wystąpił błąd przy dodawaniu wynajmu: " . $e->getMessage();
    }
}

$rentalDate = date('Y-m-d');
$rentalDays = isset($_POST['rental_days']) ? $_POST['rental_days'] : $item['rental_days'];
$returnDate = date('Y-m-d', strtotime("+$rentalDays day", strtotime($rentalDate)));
?>

<script>
    const cartItems = <?php echo json_encode($cartData, JSON_UNESCAPED_UNICODE); ?>;
    const userData = <?php echo json_encode($_SESSION['user'] ?? null, JSON_UNESCAPED_UNICODE); ?>;
</script>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formularz Wypożyczenia Sprzętu i Podsumowanie</title>

    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="../Style/style_delivery.css">
</head>

<body>
    <div class="main-container">

        <!-- Kontener formularza i podsumowania -->
        <div class="container" id="formSummaryContainer">
            <!-- Kontener formularza -->
            <div class="form-container" id="formContainer">
                <h2>Formularz Wypożyczenia Sprzętu</h2>
                <form method="POST" action="paymentRent.php" enctype="multipart/form-data" id="deliveryForm">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <input type="hidden" name="userId" value="<?= htmlspecialchars($_SESSION['user_id']) ?>">
                <?php endif; ?>
                    <div class="form-group">
                        <label for="firstName">Imię*</label>
                        <input type="text" id="firstName" name="firstName" required>
                    </div>

                    <div class="form-group">
                        <label for="lastName">Nazwisko*</label>
                        <input type="text" id="lastName" name="lastName" required>
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail*</label>
                        <input type="text" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Nr telefonu*</label>
                        <input type="text" id="phone" name="phone" required>
                    </div>

                    <!-- Data wynajmu - pobierana z systemu -->
                    <label for="rentalDate">Data wynajmu:</label>
                    <input type="date" name="rentalDate" id="rentalDate" value="<?php echo $rentalDate; ?>" readonly><br>
                    
                    <!-- Data zwrotu - obliczana na podstawie rentalDate i rentalDays -->
                    <label for="returnDate">Data zwrotu:</label>
                    <input type="date" name="returnDate" id="returnDate" value="<?php echo $returnDate; ?>" readonly><br>

                    <!-- Zgody marketingowe -->
                    <div class="form-group">
                        <div class="marketing">
                            <label for="marketingConsent" class="consent">Wyrażam zgodę na otrzymywanie informacji marketingowych email</label>
                            <input type="checkbox" id="marketingConsent" name="marketingConsent">
                        </div>
                    </div>

                    <!-- Kontener płatności -->
                    <div class="payment-methods">
                        <h3>Wybierz metodę płatności:</h3>
                        <div class="payment-method">
                            <label for="blik">
                                <input type="radio" id="blik" name="paymentMethod" value="blik">
                                BLIK
                                <img src="../Image/Icon/blik.png" alt="BLIK" class="payment-icon">
                            </label>
                        </div>
                        <div class="payment-method">
                            <label for="paypal">
                                <input type="radio" id="paypal" name="paymentMethod" value="paypal">
                                PayPal
                                <img src="../Image/Icon/paypal.png" alt="PayPal" class="payment-icon">
                            </label>
                        </div>
                    </div>

                    <div class="return-button">
                        <button type="button" onclick="window.history.back()">Powrót</button>
                    </div>
                    
                    <div class="submit-button">
                        <button type="submit">Zgłoś wynajem</button>
                    </div>
                </form>


                    <div class="form-group hidden">
                        <input type="checkbox" id="pickupOption" name="pickupOption">
                        <label for="pickupOption">Odbiór osobisty w sklepie w następnym dniu roboczym</label>
                    </div>

                    <div class="form-group hidden">
                        <label for="street"></label>
                        <input type="text" id="street" name="street">
                    </div>

                    <div class="form-group hidden">
                        <label for="">Nr domu*</label>
                        <input type="text" id="" name="">
                    </div>

                    <div class="form-group hidden">
                        <label for="apartmentNumber">Nr mieszkania</label>
                        <input type="text" id="apartmentNumber" name="apartmentNumber">
                    </div>

                    <div class="form-group hidden">
                        <label for="city">Miasto*</label>
                        <input type="text" id="city" name="city">
                    </div>

                    <div class="form-group hidden">
                        <label for="postalCode">Kod pocztowy*</label>
                        <input type="text" placeholder="XX-XXX">
                    </div>

                    <div class="form-group hidden">
                        <label for="deliveryDate">Wybierz datę dostawy*</label>
                        <input type="date" id="deliveryDate" name="deliveryDate">
                    </div>

            </div>

            <!-- Kontener podsumowania -->
            <div class="summary-container">
                <h3>Podsumowanie Zakupów</h3>
                <div id="summaryList">
                    <!-- Elementy podsumowania będą dodawane dynamicznie -->
                </div>
                <div class="summary-total" id="summaryTotal">Razem (brutto): 0 zł</div>
            </div>
        </div>
    </div>

    <script>
        
        document.addEventListener("DOMContentLoaded", () => {
            const deliveryForm = document.getElementById("deliveryForm");
            const summaryList = document.getElementById("summaryList");
            const summaryTotal = document.getElementById("summaryTotal");
            const optionsContainer = document.getElementById("optionsContainer");
            const formContainer = document.getElementById("formContainer");
            const formSummaryContainer = document.getElementById("formSummaryContainer");
            const backButton = document.getElementById("backButton");

            const loginButton = document.getElementById("loginButton");
            const guestButton = document.getElementById("guestButton");

            if (userData) {
                document.getElementById("firstName").value = userData.first_name || "";
                document.getElementById("lastName").value = userData.last_name || "";
                document.getElementById("email").value = userData.email || "";
            }


            // Cena dostawy (przykład: różne ceny na weekend)
            const deliveryCosts = {
                weekday: 0,
                weekend: 10,
            };

            const deliveryCities = {
                Gdańsk: 14,
                Gdynia: 16,
                Sopot: 15,
                Inne: 40
            };

            // Funkcja powrotu do strony głównej
            function goToIndex() {
                window.location.href = "index.php";
            }

            // Funkcja do obsługi przycisku "Powrót do strony głównej"
            function handleBackButton() {
                if (formContainer.style.display === "block") {
                    if (
                        confirm(
                            "Jeżeli teraz powrócisz do strony głównej, twoja transakcja oraz wybrane produkty w koszyku zostaną wyzerowane."
                        )
                    ) {
                        goToIndex();
                    }
                } else {
                    goToIndex();
                }
            }

            // Funkcja do walidacji formularza
            function validateForm() {
                const firstName = document.getElementById("firstName");
                const lastName = document.getElementById("lastName");
                const pickupOption = document.getElementById("pickupOption");

                const nameRegex = /^[a-zA-ZżźćńółęąśŻŹĆĄŚĘŁÓŃ]+$/;
                const postalCodeRegex = /^\d{2}-\d{3}$/;

                if(!pickupOption.checked) {
                    if(
                        !nameRegex.test(firstName.value) ||
                        !nameRegex.test(lastName.value) ||
                        nameRegex.test(city.value)
                    ) {
                        alert("Pola Imię, Nazwisko i Miasto mogą zawierać tylko litery.");
                        return false;
                    }
                    if (postalCodeRegex.test(postalCode.value)) {
                        alert("Kod pocztowy musi być w formacie XX-XXX.");
                        return false;
                    }
                } else {
                    if(
                        !nameRegex.test(firstName.value) ||
                        !nameRegex.test(lastName.value)
                    ) {
                        alert("Pola Imię i Nazwisko mogą zawierać tylko litery.");
                        return false;
                    }
                }

                return true;
            }

            // funkcja ukrywania pól adresu po zaznaczeniu checkboxa
            const pickupOption = document.getElementById("pickupOption");
            const deliveryCostField = document.getElementById("deliveryCost");

            pickupOption.addEventListener("change", () => {
                if (pickupOption.checked) {
                    addressFields.forEach(field => {
                        field.value = "";
                        field.required = false;
                        field.closest(".form-group").classList.add("hidden");
                    });
                    deliveryCostField.value = "0";
                    updateSummary();
                } else {
                    addressFields.forEach(field => {
                        field.closest(".form-group").classList.remove("hidden");
                        field.required = true;
                    });
                    updateSummary();
                }
            });

            // Aktualizacja podsumowania zakupów
            function updateSummary() {
                summaryList.innerHTML = "";
                let total = 0;

                cartItems.forEach((item) => {
                    const itemElement = document.createElement("div");
                    itemElement.classList.add("summary-item");
                    itemElement.innerHTML = `
                        <img src="${item.img}" alt="${item.name}" width="50" height="50">
                        <div class="details">
                            <strong>${item.name}</strong>
                            <p>Cena za dzień: ${item.price} zł</p>
                            <p>Liczba dni wynajmu: ${item.rentalDays}</p> <!-- Wyświetlamy liczbę dni wynajmu -->
                            <p>VAT: 8%</p> <!-- Prawidłowa łączna cena -->
                        </div>
                    `;
                    summaryList.appendChild(itemElement);

                    // Dodajemy do całkowitej kwoty
                    total += item.price * 1.08;
                });

                // Wyświetl całkowitą kwotę
                summaryTotal.textContent = `Razem (brutto): ${total.toFixed(1)} zł`;
            }




            // Funkcja do obliczania kosztu dostawy
            function calculateDeliveryCost(date,city) {
                const deliveryCityCost = deliveryCities[city] || deliveryCities["Inne"];
                if (!date || !city) return 0; // Jeśli brak daty lub miasta - brak kosztu

                const selectedDate = new Date(date);
                const dayOfWeek = selectedDate.getDay(); // 0 = Niedziela, 6 = Sobota

                const weekendCost = deliveryCosts.weekend + deliveryCityCost; // Koszt dostawy w weekendy
                const weekdayCost = deliveryCosts.weekday + deliveryCityCost; // Koszt dostawy w dni robocze

                return dayOfWeek === 0 || dayOfWeek === 6 ? weekendCost : weekdayCost;
            }

            // Walidacja formularza
            deliveryForm.addEventListener("submit", (event) => {
                if (!validateForm()) {
                    event.preventDefault();
                } else {
                    updateSummary();
                }
            });


            // Obsługa zmiany daty dostawy
            document
                .getElementById("deliveryDate")
                .addEventListener("change", updateSummary);
            document
                .getElementById("city")
                .addEventListener("change", updateSummary);

            // Inicjalizacja podsumowania przy starcie
            updateSummary();

            formContainer.style.display = "block";
            formSummaryContainer.style.display = "flex";
            renderCart();
        });

    </script>
</body>

</html>
