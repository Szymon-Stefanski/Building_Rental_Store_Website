<?php
session_start();
require '../database_connection.php';
include '../email_sender.php';
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


    // Dodanie zamówiena do bazy danych i wysłanie maila do odbiorcy
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $street = trim($_POST['street'] ?? '');
        $houseNumber = trim($_POST['houseNumber'] ?? '');
        $apartmentNumber = trim($_POST['apartmentNumber'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postalCode = trim($_POST['postalCode'] ?? '');
        $deliveryDate = $_POST['deliveryDate'] ?? '';
        $cart = $_SESSION['cart'] ?? [];

        if (empty($cart)) {
            echo "Koszyk jest pusty!";
            exit;
        }

        if (!$apartmentNumber) {
            $address = $postalCode . ', ' . $city . ', ' . $street . ', ' . $houseNumber;
        } else{
            $address = $postalCode . ', ' . $city . ', ' . $street . ', ' . $houseNumber . '/' . $apartmentNumber ;
        }

    try {

        $brutto = 0;
        foreach ($cart as $item) {
            $brutto += $item['price'] * $item['quantity'];
        }
        $brutto += $brutto * $Vat;

        $deliveryCost = floatval($_POST['deliveryCost'] ?? 0);

        $total = $brutto + $deliveryCost;

        $pickupOption = isset($_POST['pickupOption']);
        if ($pickupOption) {
            $address = "Odbiór osobisty w sklepie";
            $deliveryCost = 0;
        }

        // Dodanie zamówienia
        $stmt = getDbConnection()->prepare("
            INSERT INTO Zamowienia (uzytkownik_id, odbiorca_imie, odbiorca_nazwisko, odbiorca_email, adres, data_zamowienia, status) 
            VALUES (:userId,:firstName, :lastName, :email, :address, :orderDate, :status)
        ");

        if (isset($_SESSION['user_id'])) {
            $stmt->execute([
                ':userId' => $_SESSION['user_id'],
                ':firstName' => $firstName,
                ':lastName' => $lastName,
                ':email' => $email,
                ':address' => $address,
                ':orderDate' => date('Y-m-d'),
                ':status' => 'Nieopłacone'
            ]);
        } else {
            $stmt->execute([
                ':userId' => null,
                ':firstName' => $firstName,
                ':lastName' => $lastName,
                ':email' => $email,
                ':address' => $address,
                ':orderDate' => date('Y-m-d'),
                ':status' => 'Nieopłacone'
            ]);
        }

        $orderId = getDbConnection()->lastInsertId();

        // Dodanie pozycji zamówienia
        $stmt = getDbConnection()->prepare("
            INSERT INTO Pozycje_Zamowien (zamowienie_id, produkt_id, ilosc, cena_za_sztuke) 
            VALUES (:orderId, :productId, :quantity, :price)
        ");
        foreach ($cart as $item) {
            $stmt->execute([
                ':orderId' => $orderId,
                ':productId' => $item['id'],
                ':quantity' => $item['quantity'],
                ':price' => $item['price']
            ]);
        }

        $subject = "Potwierdzenie zamówienia #$orderId";
        $message = "Szanowny/a $firstName $lastName,

        Dziękujemy za złożenie zamówienia w naszym sklepie budowlanym. Poniżej znajdują się szczegóły Twojego zamówienia:

        Adres dostawy: $address
        
        Zamówione produkty:
        ";

                foreach ($cart as $item) {
                    $message .= "- " . $item['name'] . " (Ilość: " . $item['quantity'] . ", Cena: " . $item['price'] . " zł)\n";
                }

                $total = $brutto + $deliveryCost;

                $message .= "
        Łączna wartość zamówienia (brutto): " . number_format($brutto, 2, '.', '') . " zł
        Koszt dostawy: " . number_format($deliveryCost, 2, '.', '') . " zł
        Razem do zapłaty: " . number_format($total, 2, '.', '') . " zł
        
        Jeśli zamówienie nie zostało jeszcze opłacone, możesz to zrobić, korzystając z poniższego linku:
        http://localhost/projekt_byt/Store/payment.php?id=$orderId
        
        Twoje zamówienie zostanie dostarczone w dniu: $deliveryDate
        
        Jeśli masz jakiekolwiek pytania lub uwagi, prosimy o kontakt z naszym działem obsługi klienta pod adresem: budexgdansk@gmail.com
        
        Dziękujemy za zakupy i zapraszamy ponownie!
        Budex sp z o.o.";

        sendEmail($email, $subject, $message);

        header("Location: payment.php?id=$orderId");
        unset($_SESSION['cart']); // Wyczyszczenie koszyka po złożeniu zamówienia
        exit;
    } catch (Exception $e) {
        echo "Wystąpił błąd podczas składania zamówienia: " . $e->getMessage();
    }


}

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
                <form method="POST" enctype="multipart/form-data" id="deliveryForm">
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
                        <input type="checkbox" id="pickupOption" name="pickupOption">
                        <label for="pickupOption">Odbiór osobisty w sklepie</label>
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

                    <div class="return-button">
                    <button onclick="window.history.back()">Powrót</button>
                    </div>

                    <div class="submit-button">
                        <button type="submit">Złóż zamówienie</button>
                    </div>

                    <!-- Przekazanie danych obliczanych w js do php -->
                    <input type="hidden" id="deliveryCost" name="deliveryCost" value="0">
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

            // Wycztywanie danych użytkownika, jeśli jest zalogowany
            if (userData) {
                document.getElementById("firstName").value = userData.first_name || "";
                document.getElementById("lastName").value = userData.last_name || "";
                document.getElementById("email").value = userData.email || "";
                document.getElementById("postalCode").value = userData.postal_code || "";
                document.getElementById("city").value = userData.city || "";
                document.getElementById("street").value = userData.street || "";
                document.getElementById("houseNumber").value = userData.house_number || "";
                document.getElementById("apartmentNumber").value = userData.apartment_number || "";
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
                const street = document.getElementById("street");
                const houseNumber = document.getElementById("houseNumber");
                const city = document.getElementById("city");
                const postalCode = document.getElementById("postalCode");
                const pickupOption = document.getElementById("pickupOption");

                const nameRegex = /^[a-zA-ZżźćńółęąśŻŹĆĄŚĘŁÓŃ]+$/;
                const postalCodeRegex = /^\d{2}-\d{3}$/;

                if(!pickupOption.checked) {
                    if(
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
            const addressFields = document.querySelectorAll("#street, #houseNumber, #apartmentNumber, #city, #postalCode");
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
                        <p>Cena: ${item.price} zł</p>
                        <p>Ilość: ${item.quantity}</p>
                    </div>
                `;
                    summaryList.appendChild(itemElement);
                    total += parseFloat(item.total);
                });

                const pickupOptionChecked = document.getElementById("pickupOption").checked;
                let deliveryCost = pickupOptionChecked ? 0 : calculateDeliveryCost(
                    document.getElementById("deliveryDate").value,
                    document.getElementById("city").value.trim()
                );

                // Zaktualizuj ukryte pole i podsumowanie
                document.getElementById("deliveryCost").value = deliveryCost.toFixed(2);
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
