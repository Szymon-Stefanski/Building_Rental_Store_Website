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

function displayCart() {
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        echo "<form action='update_cart.php' method='post'>
                <table class='cart-table'>
                    <thead>
                        <tr>
                            <th>Produkt</th>
                            <th>Cena</th>
                            <th>Ilość</th>
                            <th>Łączna cena</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($_SESSION['cart'] as $key => $item) {
            $totalPrice = $item['price'] * $item['quantity'];
            echo "<tr>
                    <td><img src='{$item['image']}' alt='{$item['name']}' style='width: 50px;'> {$item['name']}</td>
                    <td>{$item['price']} zł</td>
                    <td>
                        <input type='number' name='quantity[{$key}]' value='{$item['quantity']}' min='1' />
                    </td>
                    <td>{$totalPrice} zł</td>
                  </tr>";
        }

        echo "</tbody></table>
              <button type='submit'>Zaktualizuj koszyk</button>
              </form>";
    } else {
        echo "<p>Twój koszyk jest pusty.</p>";
    }
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
    <link rel="stylesheet" href="../Style/style_cart.css">
</head>
<body>
    <div class="cart-container">
        <!-- Główna sekcja koszyka -->
        <div class="main-cart">
            <div class="breadcrumbs">
                <?php
                echo '<a href="' . $source . '" class="back-button">◄ Powrót</a>';
                ?>
            </div>

            <div class="cart-header">
                <h2>Wypożyczalnia sprzętu</h2>

            </div>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Produkt</th>
                        <th>Dostępność</th>
                        <th>Cena za dzień (brutto)</th>
                        <th>Liczba dni</th>
                        <th>Razem (brutto)</th>
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
                                        <img src="' . $imagePath . '" alt="' . htmlspecialchars($item['name'] ?? 'Produkt bez nazwy') . '" width="50" height="50">
                                        <div>
                                            <p>' . htmlspecialchars($item['name'] ?? 'Produkt bez nazwy') . '</p>
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
                                            <button type="submit" class="remove-button">Usuń</button>
                                        </form>
                                    </td>';
                                echo '</tr>';
                            }
                        }
                    ?>
                </tbody>
            </table>
            <!-- Przycisk na dole -->
            <div class="bottom-buttons">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="clear-cart">
                        <img src="../Image/Icon/cancel.png" alt="Ikona kosza"> ODZNACZ ZAZNACZONE
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
                            <img src="../Image/Icon/log-in.png" alt="Ikona logowania"> Zaloguj się
                        </a>
                    </button>

                    <button id="guestButton" class="guest-button">
                        <a href="delivery.php">
                            <img src="../Image/Icon/user.png" alt="Ikona gościa"> WYPOŻYCZ JAKO GOŚĆ
                        </a>
                    </button>
                <?php endif; ?>


            </div>
        </div>
            <!-- Podsumowanie koszyka -->
            <div class="summary">
                <p>Produkty: <span id="products-total"><?php echo $Total;?> zł</span></p>
                <p>RAZEM (BRUTTO): <strong id="cart-total"></strong> <?php echo $Brutto;?> zł</p>
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
            const cartTotal = cartProducts.reduce((total, product) => total + product.price * product.quantity, 0);

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
    </script>
</body>
</html>
