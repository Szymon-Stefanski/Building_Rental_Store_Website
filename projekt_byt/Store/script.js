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

    // Symulowane dane produktów w koszyku
    const cartItems = [
        { name: "Produkt 1", price: 50, img: "https://via.placeholder.com/50" },
        { name: "Produkt 2", price: 30, img: "https://via.placeholder.com/50" }
    ];

    // Cena dostawy (przykład: różne ceny na weekend)
    const deliveryCosts = {
        weekday: 15,
        weekend: 25
    };

   
    // Funkcja powrotu do strony głównej
    function goToIndex() {
        window.location.href = "index.php";
    }

    // Funkcja do obsługi przycisku "Powrót do strony głównej"
    function handleBackButton() {
        if (formContainer.style.display === "block") {
            if (confirm("Jeżeli teraz powrócisz do strony głównej, twoja transakcja oraz wybrane produkty w koszyku zostaną wyzerowane.")) {
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

        if (!nameRegex.test(firstName.value) || !nameRegex.test(lastName.value) || !nameRegex.test(city.value)) {
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
                <img src="${item.img}" alt="${item.name}">
                <div class="details">
                    <strong>${item.name}</strong>
                    <p>Cena: ${item.price} zł</p>
                </div>
            `;
            summaryList.appendChild(itemElement);
            total += item.price;
        });

        // Uwzględnij koszt dostawy
        const deliveryDate = document.getElementById("deliveryDate").value;
        const deliveryCost = calculateDeliveryCost(deliveryDate);
        total += deliveryCost;

        summaryTotal.textContent = `Razem (brutto): ${total} zł`;
    }

    // Funkcja do obliczania kosztu dostawy
    function calculateDeliveryCost(date) {
        if (!date) return 0; // Brak daty - brak kosztu

        const selectedDate = new Date(date);
        const dayOfWeek = selectedDate.getDay(); // 0 = Niedziela, 6 = Sobota

        if (dayOfWeek === 0 || dayOfWeek === 6) {
            return deliveryCosts.weekend; // Weekendy
        } else {
            return deliveryCosts.weekday; // Dni robocze
        }
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
    document.getElementById("deliveryDate").addEventListener("change", updateSummary);

    // Inicjalizacja podsumowania przy starcie
    updateSummary();
    
    formContainer.style.display = "block";
    formSummaryContainer.style.display = "flex";
});
