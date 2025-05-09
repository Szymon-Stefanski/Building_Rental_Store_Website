<?php
session_start();

// zapisywanie przekierowania do zmiennej by zapobiec pętli przy odświeżaniu
$source = isset($_GET['source']) ? $_GET['source'] : '../index.php';

function cleanCart() {
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
}

// Sprawdzamy, czy użytkownik nacisnął "Powrót"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanCart') {
    cleanCart(); // Wyczyść koszyk
    header('Location: ../index.php'); // Przekierowanie na poprzednią stronę
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $logged_in = false;
    $user_id = null;
} else {
    $logged_in = true;
    $user_id = $_SESSION['user_id'];
}

// Czyszczenie koszyka
if (isset($_POST['action']) && $_POST['action'] === 'clear_cart') {
    unset($_SESSION['cart']);
    header("Refresh:0");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Obsługa dodawania i aktualizowania produktów w koszyku
    if (isset($_POST['action'])) {

        // Dodawanie nowego produktu lub aktualizacja
        if ($_POST['action'] === 'add') {
            $productId = $_POST['product_id'];
            $productName = $_POST['product_name'];
            $productPrice = $_POST['product_price'];
            $rentalDays = $_POST['rental_days'];

            // Jeżeli produkt już istnieje w sesji, to aktualizujemy dni wynajmu
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId]['quantity'] += 1; // Zwiększamy ilość
                $_SESSION['cart'][$productId]['rental_days'] = $rentalDays; // Aktualizujemy dni wynajmu
            } else {
                // Dodajemy nowy produkt do sesji
                $_SESSION['cart'][$productId] = [
                    'name' => $productName,
                    'price' => $productPrice,
                    'quantity' => 1,
                    'rental_days' => $rentalDays
                ];
            }
        }

        // Aktualizacja ilości produktów
        if ($_POST['action'] === 'update_quantity' && isset($_POST['item_index'], $_POST['change'])) {
            $index = intval($_POST['item_index']);
            $change = intval($_POST['change']);
            if (isset($_SESSION['cart'][$index])) {
                $_SESSION['cart'][$index]['quantity'] += $change;
                if ($_SESSION['cart'][$index]['quantity'] < 1) {
                    $_SESSION['cart'][$index]['quantity'] = 1;
                }
            }
        }

        // Usuwanie przedmiotu z koszyka
        if ($_POST['action'] === 'remove_item' && isset($_POST['item_index'])) {
            $index = intval($_POST['item_index']);
            if (isset($_SESSION['cart'][$index])) {
                unset($_SESSION['cart'][$index]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
            }
        }
    }

    header("Refresh:0");
    exit();
}

$Total = 0;
$Vat = 0.08;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $Total += $itemTotal;
    }
}
$Brutto = $Total + $Total * $Vat;

// Losowe wyświetlanie produktów z bazy
require '../database_connection.php';

$stmt = getDbConnection()->prepare("
    SELECT k.nazwa_kategorii, p.produkt_id, p.nazwa_produktu, p.cena
    FROM Produkty p
    LEFT JOIN Kategorie k ON p.kategoria_id = k.kategoria_id
    WHERE p.wynajem = 'TAK'
    ORDER BY RAND() LIMIT 1
");

try {
    $pdo = new PDO('mysql:host=localhost;dbname=build_store', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Błąd połączenia: " . $e->getMessage();
}

$stmt->execute();

$product = $stmt->fetch();

if ($product) {
    $productId = $product['produkt_id'];
    $productName = $product['nazwa_produktu'];
    $productPrice = $product['cena'];
    $categoryName = $product['nazwa_kategorii'];

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
    
    $productImage = findProductImage($productId, $categoryName, $productName);
} else {
    echo "Brak produktów w bazie.";
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wypożyczalnia sprzętu</title>
    <link rel="stylesheet" href="../Style/style_rental.css">
</head>
<body>
    <div class="cart-container">
        <div class="main-cart">
            <div class="breadcrumbs">
                <form method="post" action="">
                    <input type="hidden" name="action" value="cleanCart">
                    <button type="submit" class="back-button">
                        <img src="../Image/Icon/back.png" alt="Ikona Powrotu" class="icon"> Powrót
                    </button>
                </form>
            </div>

            <div class="cart-header">
                <h2>Wypożyczalnia sprzętu</h2>

            </div>
            <div class="cart-table-container">
                <table class="cart-table">
                <thead>
                    <tr>
                        <th>Produkt</th>
                        <th>Dostępność</th>
                        <th>Cena za dzień (brutto)</th>
                        <th>Liczba dni wynajmu</th>
                        <th>Razem (brutto)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    try {
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        // Pobieranie produktów, gdzie wynajem = 'TAK'
                        $query = "SELECT * FROM Produkty WHERE wynajem = 'TAK' OR wynajem = 'NIE'";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute();

                        $wynajemProdukty = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($wynajemProdukty) && (!isset($_SESSION['rental']) || empty($_SESSION['rental']))) {
                            echo '<tr><td colspan="6">Brak sprzętu do wypożyczenia.</td></tr>';
                        } else {
                            // Wyświetlanie produktów z bazy danych
                            foreach ($wynajemProdukty as $produkt) {
                                $imagePath = findProductImage($produkt['produkt_id'], 'all', $produkt['nazwa_produktu']);
                            
                                // Ustalamy status dostępności na podstawie wartości wynajem
                                $availabilityText = $produkt['wynajem'] === 'TAK' ? 'Dostępny' : 'Niedostępny';
                                $availabilityClass = $produkt['wynajem'] === 'TAK' ? 'available' : 'unavailable';
                            
                                echo '<tr>';
                                echo '<tr id="row-' . $produkt['produkt_id'] . '" class="product-row">'; // Dodajemy klasę dla wiersza
                                echo '<td class="product-info">
                                        <div class="product-name">
                                            <p>' . htmlspecialchars($produkt['nazwa_produktu']) . '</p>
                                        </div>
                                        <div class="product-image">
                                        <img src="' . $imagePath . '" alt="' . htmlspecialchars($produkt['nazwa_produktu']) . '" width="50" height="50">
                                        </div>
                                    </td>';
                                echo '<td class="availability">
                                    <span class="status ' . $availabilityClass . '">' . $availabilityText . '</span>
                                </td>';
                                echo '<td id="unit-price-' . $produkt['produkt_id'] . '" class="unit-price">' . number_format($produkt['cena'] / 20, 2) . ' zł</td>';
                                echo '<td>
                                        <div class="quantity-control rent-days" id="rent-days-' . $produkt['produkt_id'] . '">
                                            <button type="button" class="minus-button" onclick="updateRentDays(' . $produkt['produkt_id'] . ', -1)">-</button>
                                            <span id="rent-days-value-' . $produkt['produkt_id'] . '">1</span>
                                            <button type="button" class="plus-button" onclick="updateRentDays(' . $produkt['produkt_id'] . ', 1)">+</button>
                                        </div>
                                        <form method="post" action="rentalForm.php" class="add-to-cart-form" id="form-' . $produkt['produkt_id'] . '">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="' . $produkt['produkt_id'] . '">
                                            <input type="hidden" name="product_name" value="' . htmlspecialchars($produkt['nazwa_produktu']) . '">
                                            <input type="hidden" name="product_price" value="' . $produkt['cena'] . '">
                                            <input type="hidden" id="days-hidden-' . $produkt['produkt_id'] . '" name="rental_days" value="1"> <!-- Domyślnie 1 -->
                                        </form>
                                    </td>';
                                echo '<td class="total-price" id="total-price-' . $produkt['produkt_id'] . '">0,00 zł</td>';
                                if ($produkt['wynajem'] === 'TAK') {
                                    echo '<td id="td-checkbox-' . $produkt['produkt_id'] . '" class="highlight-checkbox-td">
                                        <input type="checkbox" id="checkbox-' . $produkt['produkt_id'] . '" name="product_select[]" value="' . $produkt['produkt_id'] . '" class="highlight-checkbox" onclick="toggleRentControls(' . $produkt['produkt_id'] . ')">
                                    </td>';
                                } else {
                                    echo '<td id="td-checkbox-' . $produkt['produkt_id'] . '" class="highlight-checkbox-td"></td>';
                                }
                                echo '</tr>';
                            }
                            

                            // Wyświetlanie sprzętu z sesji
                            if (isset($_SESSION['rental']) && !empty($_SESSION['rental'])) {
                                foreach ($_SESSION['rental'] as $index => $item) {
                                    $itemTotal = $item['price'] * $item['quantity'];
                                    $imagePath = findProductImage($item['id'], 'all', $item['name']);
                                    echo '<tr>';
                                    echo '<td class="product-info">
                                            <img src="' . $imagePath . '" alt="' . htmlspecialchars($item['name'] ?? 'Produkt bez nazwy') . '" width="50" height="50">
                                            <div>
                                                <p>' . htmlspecialchars($item['name'] ?? 'Produkt bez nazwy') . '</p>
                                            </div>
                                        </td>';
                                    echo '<td class="availability"><span class="status available">Dostępny</span></td>';
                                    echo '<td id="unit-price-' . $produkt['id'] . '" class="unit-price">' . number_format($produkt['cena'] / 20, 2) . ' zł</td>';
                                    echo '<td>
                                            <div class="quantity-control">
                                                <form method="post" class="quantity-form">
                                                    <input type="hidden" name="action" value="update_quantity">
                                                    <input type="hidden" name="item_index" value="' . $index . '">
                                                    <button type="submit" name="change" value="-1">-</button>
                                                    <input type="text" name="quantity" value="' . $item['quantity'] . '" min="1">
                                                    <input type="hidden" id="days-hidden-1" name="rental_days" value="1">
                                                    <button type="submit" name="change" value="1">+</button>
                                                </form>
                                            </div>
                                        </td>';
                                    echo '<td class="total-price">' . number_format($itemTotal, 2) . ' zł</td>';
                                    echo '<td>
                                            <form method="post">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="item_index" value="' . $index . '">
                                                <button type="submit" class="remove-button">Usuń</button>
                                            </form>
                                        </td>';
                                    echo '</tr>';
                                }
                            }
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='6'>Błąd połączenia z bazą danych: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                    }
                ?>

                </tbody>
                </table>
            </div>
            <!-- Przycisk na dole -->
            <div class="bottom-buttons">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="clear-cart">
                        <img src="../Image/Icon/cancel.png" alt="Ikona kosza"> WYCZYŚĆ KOSZYK
                    </button>
                </form>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $username = ($_SESSION['username']);
                    ?>

                    <form method="post" action="rentalForm.php" id="rentForm">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $produkt['produkt_id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($produkt['nazwa_produktu']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo $produkt['cena']; ?>">
                        <input type="hidden" id="days-hidden-<?php echo $produkt['produkt_id']; ?>" name="rental_days" value="1">
                        
                        <button id="guestButton" class="guest-button">
                        <a href="rentalForm.php">
                            <img src="../Image/Icon/user.png" alt="Ikona gościa"> Przejdź do wynajmu
                        </a>
                    </button>
                    </form>


                <?php else: ?>
                    <button id="loginButton" class="login-button">
                        <a href="../Login/login.php">
                            <img src="../Image/Icon/log-in.png" alt="Ikona logowania"> Zaloguj się, aby wypożyczyć
                        </a>
                    </button>

                <?php endif; ?>

                <script>
                        document.getElementById('guestButton').addEventListener('click', function(event) {
                            event.preventDefault();  // Zapobiega przeładowaniu strony

                            var selectedProducts = [];
                            var rentalDays = [];
                            var productPrices = [];

                            // Zbieramy zaznaczone produkty, ich dni wynajmu i ceny
                            document.querySelectorAll('.highlight-checkbox:checked').forEach(function(checkbox) {
                                var productId = checkbox.value;
                                selectedProducts.push(productId);

                                // Pobieramy liczbę dni wynajmu dla produktu
                                var daysInput = document.getElementById('rent-days-value-' + productId);
                                rentalDays.push({
                                    productId: productId,
                                    days: daysInput ? daysInput.textContent : 1 // Pobieramy wartość dni (zaktualizowana na stronie)
                                });

                                // Pobieramy cenę produktu
                                var priceElement = document.getElementById('total-price-' + productId);
                                var productPrice = priceElement ? priceElement.textContent.replace(' zł', '').replace(',', '.') : '0.00';
                                productPrices.push({
                                    productId: productId,
                                    price: productPrice
                                });
                            });

                            if (selectedProducts.length > 0) {
                                // Dodajemy zaznaczone produkty do formularza jako ukryte pola
                                selectedProducts.forEach(function(productId) {
                                    var hiddenInput = document.createElement('input');
                                    hiddenInput.type = 'hidden';
                                    hiddenInput.name = 'product_select[]';
                                    hiddenInput.value = productId;
                                    document.getElementById('rentForm').appendChild(hiddenInput);
                                });

                                // Dodajemy liczbę dni wynajmu dla każdego produktu
                                rentalDays.forEach(function(product) {
                                    var hiddenDaysInput = document.createElement('input');
                                    hiddenDaysInput.type = 'hidden';
                                    hiddenDaysInput.name = 'rental_days[' + product.productId + ']';
                                    hiddenDaysInput.value = product.days;
                                    document.getElementById('rentForm').appendChild(hiddenDaysInput);
                                });

                                // Dodajemy ceny produktów
                                productPrices.forEach(function(product) {
                                    var hiddenPriceInput = document.createElement('input');
                                    hiddenPriceInput.type = 'hidden';
                                    hiddenPriceInput.name = 'product_prices[' + product.productId + ']';
                                    hiddenPriceInput.value = product.price;
                                    document.getElementById('rentForm').appendChild(hiddenPriceInput);
                                });

                                // Po dodaniu wszystkich danych, wysyłamy formularz
                                document.getElementById('rentForm').submit();
                            } else {
                                alert("Proszę zaznaczyć co najmniej jeden produkt.");
                            }
                        });
                    </script>
            </div>

        </div>
        <aside class="recommended-product">
        <h3 style="color: red; text-align: center;">Warunki wynajmu sprzętu:</h3>
        <div class="terms-container">
            <ol style="text-align: justify; margin: 20px; font-size: 16px;">
                <li><strong>Dni wynajmu liczą się od dnia zgłoszenia wynajmu:</strong> <br> Wynajem rozpoczyna się w momencie dokonania opłaty i zarejestrowania zgłoszenia wynajmu, niezależnie od godziny odbioru sprzętu.</li>
                <br>
                <li><strong>Płatność dokonywana z góry:</strong> <br> Wszystkie opłaty za wynajem należy uiścić przed odbiorem sprzętu - w przeciwnym wypadku zgłoszenie nie zostanie zarejestrowane.</li>
                <br>
                <li><strong>Odpowiedzialność za uszkodzenia:</strong> <br> W przypadku uszkodzenia wypożyczonego sprzętu, klient zobowiązany jest do pokrycia pełnej wartości sprzętu zgodnie z jego ceną rynkową.</li>
                <br>
                <li><strong>Kara za zwłokę:</strong> <br> W przypadku opóźnienia w zwrocie sprzętu, za każdy dzień zwłoki naliczana jest kara w wysokości <strong>100% dziennej stawki wynajmu</strong>.</li>
                <br>
                <li><strong>Odwołanie wynajmu:</strong> <br> W przypadku odwołania chęci wynajmu, za każdy dzień który upłynął od zgłoszenia naliczana jest <strong>stawka dzienna wynajmu</strong>.</li>
                <br>
                <li><strong>Niedostępność produktu:</strong> <br> W przypadku niedostępności produktu prosimy o <strong>kontakt telefoniczny bądź mailowy podany w sekcji kontakt na stronie głównej</strong>.</li>
                <br>
                <li><strong>Akceptacja warunków:</strong> <br> zgłoszenie chęć wynajęcia sprzętu dokonane przez klient rozumiane jest jako <strong>akceptacja powyższych warunków</strong>.</li>
                <br>
            </ol>
        </div>
        </aside>
    </div>
    </div>

    <style>
        .highlight-checkbox:checked{
            background-color: rgba(255, 165, 0, 0.7);
            border-color: rgba(255, 165, 0, 1);
        }

        /* Stylowanie samego checkboxa */
        .highlight-checkbox {
            width: 20px;
            height: 20px;
            appearance: none;
            border: 2px solid #ccc;
            border-radius: 4px;
            background-color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .highlight-checkbox-td {
            transition: background-color 0.3s ease;
            background-color: transparent; /* Domyślnie przezroczysty */
        }

        /* Zmieniony wygląd, gdy checkbox jest zaznaczony */
        .highlight-checkbox-td.highlighted {
            background-color: rgba(255, 165, 0, 0.5); /* Transparentny pomarańczowy */
        }

        .rent-days {
            display: none;
        }

        .highlight-checkbox:checked + .rent-days {
            display: flex;
            align-items: center;
        }

        .minus-button,
        .plus-button {
            padding: 5px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f5f5f5;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .minus-button:hover,
        .plus-button:hover {
            background-color: #ddd;
        }
    </style>

    <script>
        function updateDays(action, productId, price) {
            const daysInput = document.getElementById(`days-${productId}`);
            const hiddenDaysInput = document.getElementById(`days-hidden-${productId}`);
            const totalPriceField = document.getElementById(`total-price-${productId}`);
            let days = parseInt(daysInput.value, 10);

            if (action === 'increase') {
                days += 1;
            } else if (action === 'decrease' && days > 1) { // Minimalna wartość dni to 1
                days -= 1;
            }

            // Aktualizacja widocznego i ukrytego pola
            daysInput.value = days;
            hiddenDaysInput.value = days;

            // Obliczanie całkowitej ceny brutto
            const totalPrice = days * price;
            totalPriceField.textContent = totalPrice.toFixed(2) + ' zł';
        }

        function updateRentDays(productId, change) {
            const rentDaysValue = document.getElementById(`rent-days-value-${productId}`);
            const totalPrice = document.getElementById(`total-price-${productId}`);
            const unitPrice = parseFloat(document.querySelector(`#row-${productId} .unit-price`).textContent.replace(' zł', ''));

            let currentDays = parseInt(rentDaysValue.innerText, 10) || 0;
            currentDays += change;

            if (currentDays < 1) {
                currentDays = 1;
            }

            rentDaysValue.innerText = currentDays;

            // Przeliczanie całkowitej ceny
            const total = currentDays * unitPrice;
            totalPrice.textContent = total.toFixed(2) + ' zł';

            const hiddenInput = document.getElementById(`days-hidden-${productId}`);
            if (hiddenInput) {
                hiddenInput.value = currentDays;
            }
        }



        function toggleRowHighlight(productId) {
            const row = document.getElementById(`row-${productId}`);
            const checkbox = document.getElementById(`checkbox-${productId}`);
            
            if (checkbox.checked) {
                row.classList.add('highlighted-row');
            } else {
                row.classList.remove('highlighted-row');
            }
        }

        function toggleRentControls(productId) {
            const checkbox = document.getElementById(`checkbox-${productId}`);
            const rentDays = document.getElementById(`rent-days-${productId}`);
            const totalPrice = document.getElementById(`total-price-${productId}`);
            const rentDaysValue = document.getElementById(`rent-days-value-${productId}`);
            const tdElement = document.getElementById(`td-checkbox-${productId}`);
            const priceElement = document.getElementById(`unit-price-${productId}`);
            const pricePerDay = parseFloat(priceElement.textContent.trim()); 

            const form = document.getElementById(`form-${productId}`); // Pobierz formularz dla produktu
            const days = parseInt(rentDaysValue.textContent || 1, 10);  // Pobierz dni wynajmu


            if (checkbox.checked) {
                rentDays.style.display = 'flex';
                tdElement.classList.add('highlighted');

                // Upewnij się, że rentDaysValue ma prawidłową wartość
                const days = parseInt(rentDaysValue.innerText || 1, 10);
                const total = pricePerDay * days;

                // Wyświetl obliczoną wartość
                totalPrice.textContent = (pricePerDay * days).toFixed(2) + ' zł';

                form.querySelector('input[name="rental_days"]').value = days;

            } else {
                tdElement.classList.remove('highlighted');
                rentDays.style.display = 'none';
                rentDaysValue.innerText = '1';  // Poprawka, zmieniać `innerText`, nie `textContent`
                totalPrice.textContent = '0.00 zł';
            }
        }

        window.onload = function() {
            // Pobranie danych koszyka z localStorage
            const cartProducts = JSON.parse(localStorage.getItem('cartItems')) || [];
            const cartProductsContainer = document.getElementById('cart-products');
            const cartTableBody = document.querySelector('.cart-table tbody');

            // Sprawdzanie, czy koszyk jest pusty
            if (cartProducts.length === 0) {
                cartProductsContainer.innerHTML = '<p>Twój koszyk jest pusty.</p>';
            } else {
                // Dynamiczne dodawanie produktów do tabeli
                cartProducts.forEach(product => {
                    const productRow = document.createElement('tr');

                    // Sprawdzenie dostępności na podstawie pola `rental`
                    const availabilityText = product.rental === 'TAK' ? 'Dostępny' : 'Niedostępny';
                    const availabilityClass = product.rental === 'TAK' ? 'available' : 'unavailable';

                    // Tworzymy wiersz z produktem
                    productRow.innerHTML = `
                        <td class="product-info">
                            <img src="${product.image || '../Image/Product/budowlanka/1.1.png'}" alt="${product.name}">
                            <div>
                                <p>${product.name}</p>
                            </div>
                        </td>
                        <td class="availability">
                            <span class="status ${availabilityClass}">${availabilityText}</span>
                        </td>
                        <td class="unit-price">${product.price.toFixed(2)} zł</td>
                        <td>
                            <div class="quantity-control">
                                <button class="decrease-quantity" onclick="changeQuantity(this, -1)">-</button>
                                <input type="text" value="${product.quantity}" min="1" class="quantity" onchange="updateCart()">
                                <button class="increase-quantity" onclick="changeQuantity(this, 1)">+</button>
                            </div>
                        </td>
                        <td class="total-price">${(product.price * product.quantity).toFixed(2)} zł</td>
                    `;

                    // Dodajemy nowy wiersz do tabeli
                    cartTableBody.appendChild(productRow);
                });
            }

            // Zliczenie całkowitej ceny koszyka
            const cartTotal = cartProducts.reduce((total, product) => total + product.price * product.quantity, 0);

            // Aktualizacja całkowitej ceny w koszyku
            document.getElementById('cart-total').textContent = `${cartTotal.toFixed(2)} zł`;
        };



        // Logika koszyka
        const freeShippingThreshold = 200; // Próg darmowej dostawy w zł
        const shippingCost = 13.99; // Koszt wysyłki
        const vatRate = 0.23; // Stawka VAT


        // Funkcja do aktualizowania cen w koszyku
        function updateCart() {
            // Znajdź wszystkie wiersze w tabeli
            const rows = document.querySelectorAll('.cart-table tbody tr');
            let totalProductPrice = 0;

            rows.forEach(row => {
                const quantityElement = row.querySelector('.quantity');
                const unitPriceElement = row.querySelector('.unit-price');
                const totalPriceElement = row.querySelector('.total-price');

                // Jeśli brakuje któregokolwiek z tych elementów, logujemy błąd
                if (!quantityElement || !unitPriceElement || !totalPriceElement) {
                    console.error("Brak wymaganych elementów w wierszu:", row);
                    return;
                }

                // Pobieramy dane
                const unitPrice = parseFloat(unitPriceElement.textContent);
                const quantity = parseInt(quantityElement.value);

                // Jeśli którykolwiek z danych jest niepoprawny, przerywamy
                if (isNaN(unitPrice) || isNaN(quantity)) {
                    console.error("Niepoprawne dane w wierszu:", row);
                    return;
                }

                // Obliczamy cenę całkowitą dla tego produktu
                const productTotalPrice = (unitPrice) * quantity;

                // Aktualizujemy cenę całkowitą dla produktu
                totalPriceElement.textContent = `${productTotalPrice.toFixed(2)} zł`;

                // Dodajemy cenę tego produktu do całkowitej ceny koszyka
                totalProductPrice += productTotalPrice;
                updateProgress();
            });
        }


        function clearCart() {

            // Zresetowanie produktów w koszyku
            const cartTable = document.querySelector('#cart-table tbody'); //Tutaj dodaj php który będzie wrzucał produkty w tbody, to zadziała dopiero wtedy kiedy php będzie je ładował.
                                                                           //narazie to jest tylko na sztywno js.

            if (!cartTable) {
                console.error('Tabela koszyka nie została znaleziona!');
                return;
            }
            cartTable.innerHTML = '';

            // Resetowanie wszystkich wartości na stronie
            document.getElementById('products-total').textContent = '0.00 zł'; // Produkty
            document.getElementById('cart-total').textContent = '0.00 zł'; // Całkowita cena
            document.getElementById('vat-amount').textContent = '0.00 zł'; // VAT
            document.getElementById('remaining-amount').textContent = `${freeShippingThreshold.toFixed(2)} zł`; // Pozostała kwota do darmowej dostawy

            // Resetowanie paska postępu
            const progressBarElement = document.getElementById('progress-bar');
            progressBarElement.style.width = '0%';
            progressBarElement.style.backgroundColor = '#8B0000'; // Kolor początkowy


            const quantityElements = document.querySelectorAll('.quantity');
            quantityElements.forEach(input => {
                input.value = 1;
        });



            alert('Koszyk został opróżniony.');
        }


    // Funkcja do zmiany ilości produktów za pomocą przycisków
        function changeQuantity(button, change) {
            const row = button.closest('tr');
            const quantityInput = row.querySelector('.quantity');
            const productName = row.querySelector('.product-info p').innerText;

            let quantity = parseInt(quantityInput.value) + change;
            if (quantity < 1) quantity = 1; // Ilość nie może być mniejsza niż 1
            quantityInput.value = quantity;

            // Zaktualizowanie danych w koszyku w localStorage
            const cartProducts = JSON.parse(localStorage.getItem('cartItems')) || [];
            const product = cartProducts.find(p => p.name === productName);
            if (product) {
                product.quantity = quantity;
            }

            localStorage.setItem('cartItems', JSON.stringify(cartProducts));
            updateCart();
        }



        window.onload = function() {
            updateCart();
        }
    </script>
</body>
</html>
