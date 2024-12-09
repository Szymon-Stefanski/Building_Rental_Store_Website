<?php
session_start();
// zapisywanie przekierowania do zmiennej by zapobiec pętli przy odświeżaniu
if (!isset($_SESSION['original_referer'])) {
    $_SESSION['original_referer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
}

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
    header("Location: ".$_SERVER['PHP_SELF']);
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
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

$Total = 0;
$Delivery = 13.99;
$Vat = 0.08;
                    if (isset($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) {
                            $itemTotal = $item['price'] * $item['quantity'];
                            $Total += $itemTotal;
                        }
                    }
$Brutto = $Total + $Delivery + $Total * $Vat;

// Losowe wyświetlanie produktów z bazy
require '../database_connection.php';

$stmt = getDbConnection()->prepare("
    SELECT k.nazwa_kategorii, p.produkt_id, p.nazwa_produktu, p.cena
    FROM Produkty p
    LEFT JOIN Kategorie k ON p.kategoria_id = k.kategoria_id
    ORDER BY RAND() LIMIT 1
");

$stmt->execute();

$product = $stmt->fetch();

if ($product) {
    $productId = $product['produkt_id'];
    $productName = $product['nazwa_produktu'];
    $productPrice = $product['cena'];
    $categoryName = $product['nazwa_kategorii'];

    function findProductImage($productId, $categoryName, $productName) {
        $categoryName = strtolower($categoryName);
        $imageDir = "../Image/Product/$categoryName/";
        $extensions = ['png', 'jpg', 'gif'];
    
        foreach ($extensions as $extension) {
            $filePath = $imageDir . $productId . ".1." . $extension;
    
    
            if (file_exists($filePath)) {
                return $filePath;
            }
        }
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
</head>
<body>
    <div class="cart-container">
        <!-- Główna sekcja koszyka -->
        <div class="main-cart">
            <div class="breadcrumbs">
                <?php
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $redirectUrl = $_SERVER['HTTP_REFERER'];
                } else {
                    $redirectUrl = '../index.php';
                }
                echo '<a href="' . $redirectUrl . '" class="back-button">◄  Powrót</a>';
                ?>
            </div>

            <div class="cart-header">
                <h2>KOSZYK</h2>

            </div>

            <table class="cart-table">
                <thead>
                <tr>
                    <th>Produkt</th>
                    <th>Dostępność</th>
                    <th>Cena (brutto)</th>
                    <th>Ilość</th>
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
                        echo '<tr>';
                        echo '<td class="product-info">' . ($item['name']) . '</td>';
                        echo '<td class="availability"><span class="status available">Dostępny</span></td>';
                        echo '<td class="unit-price">' . number_format($item['price'], 2) . ' zł</td>';
                        echo '<td>
                <div class="quantity-control">
                    <form method="post" class="quantity-form">
                        <input type="hidden" name="action" value="update_quantity">
                        <input type="hidden" name="item_index" value="' . $index . '">
                        <button type="submit" name="change" value="-1">-</button>
                        <input type="text" name="quantity" value="' . ($item['quantity']) . '" min="1">
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

            <!-- Sekcja kodu rabatowego i darmowej dostawy -->
            <div class="cart-summary">
                <div class="promo-code">
                    <button>WPISZ KOD RABATOWY / BON PODARUNKOWY</button>
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
                        <a href="delivery.html">
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
                        <a href="delivery.html">
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
                <button class="add-to-cart" onclick="addRecommendedProduct()">
                    <span class="icon-basket"></span>
                    DO KOSZYKA
                </button>
            </div>

            <!-- Podsumowanie koszyka -->
            <div class="summary">
                <p>Produkty: <span id="products-total"><?php echo $Total;?> zł</span></p>
                <p>Wysyłka: <span id="shipping-cost">13,99 zł</span></p>
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
                            <img src="${product.image || 'default-image.jpg'}" alt="${product.name}">
                            <div>
                                <p>${product.name}</p>
                                <span>Indeks: ${product.index || 'Brak'}</span>
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
                progressElement.style.backgroundColor = '#8B0000';
            } else if (progress <= 40) {
                progressElement.style.backgroundColor = '#FF0000';
            } else if (progress <= 60) {
                progressElement.style.backgroundColor = '#FFA500';
            } else if (progress <= 80) {
                progressElement.style.backgroundColor = '#FFFF00';
            } else {
                progressElement.style.backgroundColor = '#28a745';
            }
        }

        // Funkcja aktualizująca koszyk
        function updateCart() {
            // Znajdź wszystkie wiersze produktów w tabeli koszyka
            const rows = document.querySelectorAll('.cart-table tbody tr');
            const remainingAmountElement = document.getElementById('remaining-amount');
            const progressBarElement = document.getElementById('progress-bar');
            const productsTotalElement = document.getElementById('products-total');
            const cartTotalElement = document.getElementById('cart-total');
            const vatAmountElement = document.getElementById('vat-amount');

            let totalProductPrice = 0;

            // Przejdź przez wszystkie produkty w koszyku i oblicz całkowitą wartość
            rows.forEach(row => {
                const unitPrice = parseFloat(row.querySelector('.unit-price').textContent);
                const quantity = parseInt(row.querySelector('.quantity').value);
                const productTotalPrice = unitPrice * quantity;

                // Aktualizuj cenę całkowitą dla tego produktu
                row.querySelector('.total-price').textContent = `${productTotalPrice.toFixed(2)} zł`;

                // Dodaj cenę tego produktu do całkowitej ceny produktów
                totalProductPrice += productTotalPrice;
            });

            // Aktualizacja kosztu produktów i całkowitego koszyka
            productsTotalElement.textContent = `${totalProductPrice.toFixed(2)} zł`;
            const cartTotal = totalProductPrice >= freeShippingThreshold ? totalProductPrice : totalProductPrice + shippingCost;
            cartTotalElement.textContent = `${cartTotal.toFixed(2)} zł`;

            // Obliczanie VAT
            const vatAmount = totalProductPrice * vatRate;
            vatAmountElement.textContent = `${vatAmount.toFixed(2)} zł`;

            // Obliczanie brakującej kwoty do darmowej dostawy
            const remainingAmount = freeShippingThreshold - totalProductPrice;
            remainingAmountElement.textContent = remainingAmount > 0 ? `${remainingAmount.toFixed(2)}` : '0.00';

            // Aktualizacja paska postępu
            const progress = (totalProductPrice / freeShippingThreshold) * 100;
            progressBarElement.style.width = `${Math.min(progress, 100)}%`;

            // Zmiana koloru paska postępu
            updateProgressColor(progressBarElement, progress);
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
            // Znajdź tabelę koszyka
            const cartTable = document.querySelector('.cart-table tbody');

            // Informacje o polecanym produkcie
            const recommendedProduct = {
            name: "Profil główny do sufitów podwieszanych RIGIPS T24 QUICK-LOCK 3600 mm",
            image: "profilglowny.png",
            price: 20.00, // Cena polecanego produktu
            quantity: 1,  // Domyślna ilość
            availability: "Dostępny"
        };

            let existingProductRow = null;
            const rows = Array.from(cartTable.querySelectorAll('tr'));
            rows.forEach(row => {
            const productName = row.querySelector('.product-info p');
            if (productName && productName.textContent === recommendedProduct.name) {
                existingProductRow = row;
            }
        });

    if (existingProductRow) {
        const quantityInput = existingProductRow.querySelector('.quantity');
        quantityInput.value = parseInt(quantityInput.value) + 1;

        // Zaktualizuj cenę całkowitą dla tego produktu
        const unitPrice = parseFloat(existingProductRow.querySelector('.unit-price').textContent);
        const newTotalPrice = unitPrice * parseInt(quantityInput.value);
        existingProductRow.querySelector('.total-price').textContent = `${newTotalPrice.toFixed(2)} zł`;
    } else {
        // Utwórz nowy wiersz dla polecanego produktu
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td class="product-info">
                <img src="${recommendedProduct.image}" alt="${recommendedProduct.name}">
                <div>
                    <p>${recommendedProduct.name}</p>
                    <span>Indeks: REK-12345</span>
                </div>
            </td>
            <td class="availability">
                <span class="status available">${recommendedProduct.availability}</span>
            </td>
            <td class="unit-price">
                <span class="current-price">${recommendedProduct.price.toFixed(2)} zł</span>
                <!-- Jeśli chcesz pokazać poprzednią cenę, dodaj ją poniżej -->
                ${recommendedProduct.oldPrice ? `<span class="old-price">${recommendedProduct.oldPrice.toFixed(2)} zł</span>` : ''}
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


        cartTable.appendChild(newRow);
    }

    updateCart();


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
