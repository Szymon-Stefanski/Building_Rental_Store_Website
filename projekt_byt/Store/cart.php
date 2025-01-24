<?php
session_start();
// zapisywanie przekierowania do zmiennej by zapobiec pętli przy odświeżaniu
$source = isset($_GET['source']) ? $_GET['source'] : '../index.php';

// funkca dodająca ilość produktów otrzymanych z różnych źródeł np. index.php, product.php
$product_id = isset($_POST['id']) ? $_POST['id'] : null;
$product_name = isset($_POST['name']) ? $_POST['name'] : null;
$product_price = isset($_POST['price']) ? $_POST['price'] : null;
$product_quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1;
if ($product_id) {
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $product_id) {
            $item['quantity'] += $product_quantity;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = [
            'id' => $product_id,
            'name' => $product_name,
            'price' => $product_price,
            'quantity' => $product_quantity
        ];
    }
}

if (!isset($_SESSION['user_id'])) {
    $logged_in = false;
    $user_id = null;
} else {
    $logged_in = true;
    $user_id = $_SESSION['user_id'];
}

if (isset($_POST['action']) && $_POST['action'] === 'clear_cart') {
    unset($_SESSION['cart']);
    header("Refresh:0");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_quantity':
                if (isset($_POST['item_index'], $_POST['change'])) {
                    $index = intval($_POST['item_index']);
                    $change = intval($_POST['change']);
                    if (isset($_SESSION['cart'][$index])) {
                        $_SESSION['cart'][$index]['quantity'] += $change;
                        if ($_SESSION['cart'][$index]['quantity'] < 1) {
                            $_SESSION['cart'][$index]['quantity'] = 1;
                        }
                    }
                }
                break;

            case 'remove_item':
                if (isset($_POST['item_index'])) {
                    $index = intval($_POST['item_index']);
                    if (isset($_SESSION['cart'][$index])) {
                        unset($_SESSION['cart'][$index]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']);
                    }
                }
                break;

            case 'fetch_promo_codes':
                header('Content-Type: application/json');
                try {
                    $pdo = new PDO('mysql:host=localhost;dbname=build_store', 'root', '');
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $stmt = $pdo->prepare("SELECT kod_id, nazwa_kodu, wartosc, data_waznosci FROM Kody_Rabatowe");
                    $stmt->execute();
                    $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode(['success' => true, 'codes' => $codes]);
                } catch (PDOException $e) {
                    echo json_encode(['success' => false, 'message' => 'Błąd połączenia: ' . $e->getMessage()]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Błąd: ' . $e->getMessage()]);
                }
                break;

            case 'apply_promo_code':
                if (isset($_POST['code_id'], $_POST['code_value'])) {
                    $codeId = intval($_POST['code_id']);
                    $codeValue = floatval($_POST['code_value']);

                    $_SESSION['promo_code'] = [
                        'id' => $codeId,
                        'value' => $codeValue
                    ];
                    // Oblicz brutto
                    $Total = 0;
                    $Vat = 0.08;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) {
                            $itemTotal = $item['price'] * $item['quantity'];
                            $Total += $itemTotal;
                        }
                    }
                    $discount = $Total * ($codeValue / 100);
                    $Brutto = $Total + $Total * $Vat - $discount;

                    // Wyślij nową wartość brutto do klienta
                    echo json_encode([
                        'success' => true,
                        'new_brutto' => $Brutto,
                        'message' => 'Kod rabatowy został zastosowany.'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Nieprawidłowe dane kodu rabatowego.'
                    ]);}
                break;

            default:
                echo "Nieznane działanie: " . htmlspecialchars($_POST['action']);
                break;
        }
    }
    header("Refresh:0");
    exit;
}


$Total = 0;
$Vat = 0.08;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) {
                            $itemTotal = $item['price'] * $item['quantity'];
                            $Total += $itemTotal;
                        }
                    }
$discount = 0;
if (isset($_SESSION['promo_code'])) {
    $discount = $Total * ($_SESSION['promo_code']['value'] / 100);
}
$Total = $Total - $discount;
$Brutto = $Total + $Total * $Vat;

// Losowe wyświetlanie produktów z bazy
require '../database_connection.php';

$stmt = getDbConnection()->prepare("
    SELECT k.nazwa_kategorii, p.produkt_id, p.nazwa_produktu, p.cena
    FROM Produkty p
    LEFT JOIN Kategorie k ON p.kategoria_id = k.kategoria_id
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
    <title>Koszyk</title>
    <link rel="stylesheet" href="../Style/style_cart.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

</head>
<body>
    <div class="cart-container">
        <!-- Główna sekcja koszyka -->
        <div class="main-cart">
            <div class="breadcrumbs">
                <?php
                echo '<a href="' . $source . '" class="back-button">
                        <img src="../Image/Icon/back.png" alt="Ikona Powrotu" class="icon"> Powrót
                     </a>';
                ?>
            </div>

            <div class="cart-header">
                <h2>KOSZYK</h2>

            </div>
            <div class="cart-table-container">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Produkt</th>
                            <th>Dostępność</th>
                            <th>Cena (brutto)</th>
                            <th>Ilość</th>
                            <th>Razem (brutto)</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
                                echo '<tr><td colspan="6">Koszyk jest pusty.</td></tr>';
                            } else {
                                foreach ($_SESSION['cart'] as $index => $item) {
                                    $itemTotal = $item['price'] * $item['quantity'];

                                    $imagePath = findProductImage($item['id'], 'all', $item['name']);

                                    echo '<tr>';
                                    echo '<td class="product-info">
                                            <div class="product-name">
                                                <p>' . htmlspecialchars($item['name'] ?? 'Produkt bez nazwy') . '</p>
                                            </div>
                                            <div class="product-image">
                                                <img src="' . $imagePath . '" alt="' . htmlspecialchars($item['name'] ?? 'Produkt bez nazwy') . '" width="50" height="50">
                                            </div>
                                        </td>';
                                    echo '<td class="availability"><span class="status available">Dostępny</span></td>';
                                    echo '<td class="unit-price">' . number_format($item['price'], 2) . ' zł</td>';
                                    echo '<td>
                                            <div class="quantity-control">
                                                <form method="post" class="quantity-form">
                                                    <input type="hidden" name="action" value="update_quantity">
                                                    <input type="hidden" name="item_index" value="' . $index . '">
                                                    <button type="submit" name="change" value="-1">-</button>
                                                    <input type="text" name="quantity" value="' . $item['quantity'] . '" min="1">
                                                    <button type="submit" name="change" value="1">+</button>
                                                </form>
                                            </div>
                                        </td>';
                                    echo '<td class="total-price">' . number_format($itemTotal, 2) . ' zł</td>';
                                    echo '<td>
                                            <form method="post">
                                                <input type="hidden" name="action" value="remove_item">
                                                <input type="hidden" name="item_index" value="' . $index . '">
                                                <button type="submit" class="remove-button">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </td>';
                                    echo '</tr>';
                                }
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <!-- Sekcja kodu rabatowego i darmowej dostawy -->
            <div class="cart-summary">
                <div class="promo-code">
                    <button id="toggle-code-btn">WPISZ KOD RABATOWY / BON PODARUNKOWY</button>
                    <div id="promo-code-input" style="display: none; margin-top: 10px;">
                        <input type="text" placeholder="Wpisz kod rabatowy" style="padding: 10px; width: 100%; box-sizing: border-box;">
                        <button type="submit" id="apply-code-btn" style="margin-top: 5px; padding: 10px; width: 100%;">Zastosuj kod</button>

                    </div>
                </div>
                <div class="free-shipping">
                    <?php
                    $Total = 0;
                    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
                        echo '<tr><td colspan="5">Koszyk jest pusty.</td></tr>';
                    } else {
                        foreach ($_SESSION['cart'] as $item) {
                            $itemTotal = $item['price'] * $item['quantity'];
                            $Total += $itemTotal;
                        }
                        echo '<tr><td colspan="5">Łączna wartość koszyka: ' . number_format($Total, 2, ',', '.') . ' PLN</td></tr>';
                    }
                    if ($Total >= 200) {
                        echo '<p>Gratulacje! Masz darmową dostawę!</p>';
                    } else {
                        echo '<p>DO DARMOWEJ DOSTAWY BRAKUJE CI: <strong><span id="remaining-amount">' . (200 - $Total) . ' zł</span></strong></p>';
                    }
                    ?>
                    <div class="progress-bar">
                        <div class="progress" id="progress-bar"></div>
                    </div>
                    <p class="note">Pospiesz się z zamówieniem, dodanie produktu do koszyka nie oznacza jego rezerwacji!</p>
                </div>
            </div>

            <!-- Przycisk na dole -->
            <div class="bottom-buttons">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="clear-cart">
                        <img src="../Image/Icon/cancel.png" alt="Ikona kosza"> OPRÓŻNIJ KOSZYK
                    </button>
                </form>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $username = ($_SESSION['username']);
                    ?>
                    <button id="guestButton" class="guest-button">
                        <a href="delivery.php">
                            <img src="../Image/Icon/user.png" alt="Ikona gościa"> Przejdź do dostawy
                        </a>
                    </button>
                <?php else: ?>
                    <button id="loginButton" class="login-button">
                        <a href="../Login/login.php">
                            <img src="../Image/Icon/log-in.png" alt="Ikona logowania">
                            <span>Zaloguj się</span>
                        </a>
                    </button>

                    <button id="guestButton" class="guest-button">
                        <a href="delivery.php">
                            <img src="../Image/Icon/user.png" alt="Ikona gościa"> KUPUJ JAKO GOŚĆ
                        </a>
                    </button>
                <?php endif; ?>


            </div>
        </div>

        <!-- Sekcja z polecanym produktem -->
        <aside class="recommended-product">
            <h3 style="color: red;">Polecany produkt</h3>
            <div class="product-card">
                <!-- Dynamiczne wstawienie zdjęcia i danych z bazy -->
                <img src="<?= $productImage ?>" alt="Obraz produktu">
                <p><?php echo $productName; ?></p>
                <p><strong><?php echo $productPrice; ?> zł</strong></p>
                <form method="POST" action="../Store/cart_actions.php" class="add-to-cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['produkt_id'] ?>">
                    <input type="hidden" name="product_name" value="<?= ($product['nazwa_produktu']) ?>">
                    <input type="hidden" name="product_price" value="<?= $product['cena'] ?>">
                    <input type="hidden" name="product_image" value="<?= $productImage?>">
                    <input type="hidden" class="form-quantity" name="quantity" value="1">
                    <button type="submit" class="add-to-cart" onclick="addToCart(this)">
                        DO KOSZYKA
                    </button>
                </form>
            </div>

            <!-- Podsumowanie koszyka -->
            <div class="summary">
                <p>Cena: <span id="products-total"><?php echo $Total;?> zł</span></p>
                <?php
                $discountValue = 0; // Domyślna wartość rabatu
                $textColor = "black"; // Domyślny kolor czcionki

                if (isset($_SESSION['promo_code'])) {
                    $discountValue = $_SESSION['promo_code']['value']; // Pobranie wartości rabatu z sesji
                    $textColor = "green"; // Zmiana koloru na zielony, gdy kod jest w sesji
                }
                ?>

                <p id="Rabat" style="color: <?php echo ($textColor); ?>;">
                    Rabat: <span><?php echo ($discountValue); ?>%</span>
                </p>
                <p>RAZEM (BRUTTO): <strong><span id="cart-total"> <?php echo $Brutto;?> zł</span></strong></p>
                <p>VAT (wliczony): <span id="vat-amount"><?php echo $Vat*100;?>%</span></p>
            </div>
        </aside>
    </div>

    <script>
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

                    // Tworzymy wiersz z produktem
                    productRow.innerHTML = `
                        <td class="product-info">
                            <img src="${product.image || '../Image/Product/budowlanka/1.1.png'}" alt="${product.name}">
                            <div>
                                <p>${product.name}</p>
                            </div>
                        </td>
                        <td class="availability">
                            <span class="status available">Dostępny</span>
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
            let cartTotal = cartProducts.reduce((total, product) => total + product.price * product.quantity, 0);

            // Aktualizacja całkowitej ceny w koszyku
            document.getElementById('cart-total').textContent = `${cartTotal.toFixed(2)} zł`;
        };


        // Logika koszyka
        const freeShippingThreshold = 200; // Próg darmowej dostawy w zł
        const shippingCost = 13.99; // Koszt wysyłki
        const vatRate = 0.23; // Stawka VAT

        // Funkcja do zmiany koloru paska postępu
        function updateProgressColor(progressElement, progress) {
            if (progress <= 20) {
                progressElement.style.backgroundColor = '#8B0000'; // Czerwony
            } else if (progress <= 40) {
                progressElement.style.backgroundColor = '#FF0000'; // Intensywny czerwony
            } else if (progress <= 60) {
                progressElement.style.backgroundColor = '#FFA500'; // Pomarańczowy
            } else if (progress <= 80) {
                progressElement.style.backgroundColor = '#FFFF00'; // Żółty
            } else {
                progressElement.style.backgroundColor = '#28a745'; // Zielony
            }
        }

        function updateProgressBar(total, threshold) {
            const progressBar = document.getElementById('progress-bar');
            const remainingAmount = document.getElementById('remaining-amount');

            const progress = Math.min((total / threshold) * 100, 100);
            progressBar.style.width = `${progress}%`;

            // Zmieniamy kolor w zależności od wartości
            if (progress <= 20) {
                progressBar.style.backgroundColor = '#8B0000'; // Czerwony
            } else if (progress <= 40) {
                progressBar.style.backgroundColor = '#FF4500'; // Pomarańczowy
            } else if (progress <= 60) {
                progressBar.style.backgroundColor = '#FFD700'; // Żółty
            } else if (progress <= 80) {
                progressBar.style.backgroundColor = '#32CD32'; // Zielony jasny
            } else {
                progressBar.style.backgroundColor = '#008000'; // Zielony ciemny
            }

            // Aktualizacja pozostałej kwoty do darmowej dostawy
            const remaining = threshold - total;
            remainingAmount.textContent = remaining > 0 ? `${remaining.toFixed(2)} zł` : '0.00 zł';
        }

        // Wywołaj funkcję na podstawie wartości PHP
        document.addEventListener('DOMContentLoaded', () => {
            const total = <?php echo $Total; ?>; // Łączna kwota koszyka
            const threshold = 200; // Próg darmowej dostawy
            updateProgressBar(total, threshold);
        });



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
                const productTotalPrice = unitPrice * quantity;

                // Aktualizujemy cenę całkowitą dla produktu
                totalPriceElement.textContent = `${productTotalPrice.toFixed(2)} zł`;

                // Dodajemy cenę tego produktu do całkowitej ceny koszyka
                totalProductPrice += productTotalPrice;

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


        function addRecommendedProduct() {
            // Pobierz element przycisku polecanego produktu
            const button = document.querySelector('.add-to-cart');

            // Dane polecanego produktu (pobrane dynamicznie z atrybutów przycisku)
            const recommendedProduct = {
                id: button.getAttribute('data-id'),
                name: button.getAttribute('data-name'),
                image: button.getAttribute('data-image'),
                price: parseFloat(button.getAttribute('data-price')),
                quantity: 1, // Domyślna ilość
                availability: "Dostępny" // Jeśli to statyczna wartość, można ją zmienić
            };

            // Znajdź tabelę koszyka
            const cartTable = document.querySelector('.cart-table tbody');

            // Sprawdź, czy produkt już istnieje w koszyku
            let existingProductRow = null;
            const rows = Array.from(cartTable.querySelectorAll('tr'));
            rows.forEach(row => {
                const productName = row.querySelector('.product-info p');
                if (productName && productName.textContent.trim() === recommendedProduct.name) {
                    existingProductRow = row;
                }
            });

            if (existingProductRow) {
                // Jeśli produkt już istnieje, zaktualizuj ilość
                const quantityInput = existingProductRow.querySelector('.quantity');
                quantityInput.value = parseInt(quantityInput.value) + 1;

                // Zaktualizuj cenę całkowitą
                const unitPrice = parseFloat(existingProductRow.querySelector('.unit-price').textContent);
                const newTotalPrice = unitPrice * parseInt(quantityInput.value);
                existingProductRow.querySelector('.total-price').textContent = `${newTotalPrice.toFixed(2)} zł`;
            } else {
                // Jeśli produkt nie istnieje, dodaj nowy wiersz do koszyka
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td class="product-info">
                        <img src="${recommendedProduct.image}" alt="${recommendedProduct.name}" style="width: 50px; height: auto;">
                        <div>
                            <p>${recommendedProduct.name}</p>
                        </div>
                    </td>
                    <td class="availability">
                        <span class="status available">${recommendedProduct.availability}</span>
                    </td>
                    <td class="unit-price">
                        ${recommendedProduct.price.toFixed(2)} zł
                    </td>
                    <td>
                        <div class="quantity-control">
                            <button class="decrease-quantity" onclick="changeQuantity(this, -1)">-</button>
                            <input type="text" value="${recommendedProduct.quantity}" min="1" class="quantity" onchange="updateCart()">
                            <button class="increase-quantity" onclick="changeQuantity(this, 1)">+</button>
                        </div>
                    </td>
                    <td class="total-price">${(recommendedProduct.price * recommendedProduct.quantity).toFixed(2)} zł</td>
                `;

                // Dodaj wiersz do tabeli koszyka
                cartTable.appendChild(newRow);
            }

            // Zaktualizuj koszyk (jeśli masz funkcję `updateCart`)
            updateCart();

            // Komunikat o sukcesie
            alert('Polecany produkt dodany do koszyka!');
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



            // Przełączanie widoczności pola
            document.getElementById("toggle-code-btn").addEventListener("click", () => {
                const promoCodeInput = document.getElementById("promo-code-input");

                if (promoCodeInput.style.display === "none" || promoCodeInput.style.display === "") {
                    promoCodeInput.style.display = "block";
                } else {
                    promoCodeInput.style.display = "none";
                }
            });

            // Pobieranie kodów rabatowych z backendu
            let promoCodes = [];

            console.log('Rozpoczęcie fetch...');
            fetch('cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'fetch_promo_codes' })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        promoCodes = data.codes; // Przypisujemy do globalnej zmiennej
                        console.log('Otrzymane kody rabatowe:', promoCodes);
                    } else {
                        console.error('Błąd podczas pobierania kodów:', data.message);
                    }
                })
                .catch(error => console.error('Błąd:', error));

            // Obsługa wpisanego kodu rabatowego
            document.getElementById("apply-code-btn").addEventListener("click", () => {
                let currentDiscount = 0;
                const codeInput = document.querySelector("#promo-code-input input").value;
                const rabatDiv = document.getElementById("Rabat");

                if (!codeInput) {
                    // Wyświetlenie powiadomienia o braku kodu
                    alert("Nie wprowadzono kodu rabatowego.");
                    return;
                }

                const normalizedInput = codeInput.trim().toUpperCase();
                const matchingCode = promoCodes.find(code => code.nazwa_kodu.toUpperCase() === normalizedInput);

                if (matchingCode) {
                    // Jeśli kod jest poprawny, wyświetlamy rabat w <div id="Rabat">
                    rabatDiv.innerHTML = `Rabat: <span>${matchingCode.wartosc}%</span>`;
                    rabatDiv.style.color = "green"; // Dodatkowe podkreślenie, że wszystko jest OK

                    // Wysłanie ID i wartości kodu do PHP
                    fetch('cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'apply_promo_code',
                            code_id: matchingCode.kod_id,
                            code_value: matchingCode.wartosc
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Aktualizuj wartość brutto w widoku
                                document.getElementById("cart-total").textContent = `${data.new_brutto.toFixed(2)} zł`;
                                alert(data.message);
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(error => console.error('Błąd podczas aktualizacji brutto:', error));
                } else {
                    alert("Nieprawidłowy kod rabatowy.");
                }
            });



    </script>
</body>
</html>
