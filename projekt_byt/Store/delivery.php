<?php
session_start();
require '../database_connection.php';
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


// Przygotowanie danych koszyka
$cartData = [];
$Vat = 0.08;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $itemTotal = $itemTotal + $itemTotal * $Vat;
        $imagePath = findProductImage($item['id'], 'all', $item['name']);
        $cartData[] = [
            'name' => htmlspecialchars($item['name'] ?? 'Produkt bez nazwy'),
            'price' => number_format($item['price'], 2, '.', ''),
            'quantity' => htmlspecialchars($item['quantity']),
            'total' => number_format($itemTotal, 2, '.', ''),
            'img' => $imagePath
        ];
    }
}
?>
<script>
    const cartItems = <?php echo json_encode($cartData, JSON_UNESCAPED_UNICODE); ?>;
</script>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formularz Dostawy i Podsumowanie Zakupów</title>
    <link rel="stylesheet" href="../Style/style_delivery.css">
    <link rel="manifest" href="manifest.json">
</head>

<body>
    <div class="main-container">

        <!-- Kontener formularza i podsumowania -->
        <div class="container" id="formSummaryContainer">
            <!-- Kontener formularza -->
            <div class="form-container" id="formContainer">
                <h2>Formularz Dostawy</h2>
                <form id="deliveryForm">
                    <div class="form-group">
                        <label for="firstName">Imię*</label>
                        <input type="text" id="firstName" name="firstName" required>
                    </div>

                    <div class="form-group">
                        <label for="lastName">Nazwisko*</label>
                        <input type="text" id="lastName" name="lastName" required>
                    </div>

                    <div class="form-group">
                        <label for="street">Ulica*</label>
                        <input type="text" id="street" name="street" required>
                    </div>

                    <div class="form-group">
                        <label for="houseNumber">Nr domu*</label>
                        <input type="text" id="houseNumber" name="houseNumber" required>
                    </div>

                    <div class="form-group">
                        <label for="apartmentNumber">Nr mieszkania</label>
                        <input type="text" id="apartmentNumber" name="apartmentNumber">
                    </div>

                    <div class="form-group">
                        <label for="city">Miasto*</label>
                        <input type="text" id="city" name="city" required>
                    </div>

                    <div class="form-group">
                        <label for="postalCode">Kod pocztowy*</label>
                        <input type="text" id="postalCode" name="postalCode" placeholder="XX-XXX" required>
                    </div>

                    <div class="calendar">
                        <label for="deliveryDate">Wybierz datę dostawy*</label>
                        <input type="date" id="deliveryDate" name="deliveryDate" required>
                    </div>

                    <div class="submit-button">
                        <button onclick="window.history.back()">Powrót</button>
                        <button type="submit">Złóż zamówienie</button>
                    </div>
                </form>
            </div>
            
            <!-- wstawić metody płatności w osobnym kontenerze, oraz informacje do sprzedającego -->

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


            // Cena dostawy (przykład: różne ceny na weekend)
            const deliveryCosts = {
                weekday: 15,
                weekend: 25,
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
                const street = document.getElementById("street");
                const houseNumber = document.getElementById("houseNumber");
                const city = document.getElementById("city");
                const postalCode = document.getElementById("postalCode");

                const nameRegex = /^[a-zA-ZżźćńółęąśŻŹĆĄŚĘŁÓŃ]+$/;
                const postalCodeRegex = /^\d{2}-\d{3}$/;

                if (
                    !nameRegex.test(firstName.value) ||
                    !nameRegex.test(lastName.value) ||
                    !nameRegex.test(city.value)
                ) {
                    alert("Pola Imię, Nazwisko i Miasto mogą zawierać tylko litery.");
                    return false;
                }

                if (!postalCodeRegex.test(postalCode.value)) {
                    alert("Kod pocztowy musi być w formacie XX-XXX.");
                    return false;
                }

                return true;
            }

            // Aktualizacja podsumowania zakupów
            function updateSummary() {
                summaryList.innerHTML = "";
                let total = 0;

                cartItems.forEach((item) => {
                    const itemElement = document.createElement("div");
                    itemElement.classList.add("summary-item");
                    itemElement.innerHTML = `
                <img src="${item.img}" alt="${item.name}"  width="50" height="50">
                <div class="details">
                    <strong>${item.name}</strong>
                    <p>Cena: ${item.price} zł</p>
                    <p>Ilość: ${item.quantity}</p>
                </div>
            `;
                    summaryList.appendChild(itemElement);
                    total += parseFloat(item.total);
                });

                // Uwzględnij koszt dostawy
                const deliveryDate = document.getElementById("deliveryDate").value;
                const deliveryCity = document.getElementById("city").value.trim();

                let deliveryCost = 0;
                if (total <= 200) {
                    deliveryCost = calculateDeliveryCost(deliveryDate, deliveryCity);
                }

                total += deliveryCost;
                summaryTotal.textContent = `Razem (brutto): ${total.toFixed(2)} zł`;
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
                event.preventDefault();
                if (validateForm()) {
                    alert("Zamówienie zostało złożone!");
                    deliveryForm.reset();
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
