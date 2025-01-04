<?php
session_start();
require '../database_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = getDbConnection()->prepare("SELECT * FROM uzytkownicy WHERE uzytkownik_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    echo "Użytkownik nie istnieje.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : null;
    $new_last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : null;
    $new_phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : null;
    $new_address = isset($_POST['address']) ? trim($_POST['address']) : null;

    if ($new_first_name && $new_last_name && $new_phone_number && $new_address) {
        $stmt = getDbConnection()->prepare("UPDATE uzytkownicy 
            SET imie = ?, nazwisko = ?, numer_telefonu = ?, adres = ? 
            WHERE uzytkownik_id = ?");
        $stmt->execute([$new_first_name, $new_last_name, $new_phone_number, $new_address, $user_id]);

        header("Location: profile.php");
        exit;
    } else {
        echo "Wszystkie pola są wymagane.";
    }
}
if (isset($_POST['delete_account'])) {
    $password = trim($_POST['password']);

    $stmt = getDbConnection()->prepare("SELECT haslo FROM uzytkownicy WHERE uzytkownik_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['haslo'])) {
        echo "Niepoprawne hasło.";
        exit;
    }

    $stmt = getDbConnection()->prepare("DELETE FROM uzytkownicy WHERE uzytkownik_id = ?");
    $stmt->execute([$user_id]);

    session_destroy();
    header("Location: ../index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sklep Budowlany Budex</title>
        <link rel="stylesheet" href="../Style/style_profile.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
        
        <link rel="icon" type="image/png" href="../Image/Icon/budex.png">
    </head>
    <body>
        <header class="header-container">
            <div class="logo">
                <a href="../index.php">
                    <img src="../Image/Icon/budex.png" alt="Logo sklepu" />
                </a>
            </div>
            <div class="napis">
                Moje Konto
            </div>
        </header>

        <div class="sidebar collapsed">
            <ul class="menu">
                <li class="menu-item">
                    <a href="profile.php?section=user-info">
                        <span class="icon"><img src="../Image/Icon/user.png" alt="User Icon"></span>
                        Moje Dane
                    </a>
                </li>
                <li class="menu-item">
                    <a href="profile.php?section=deliverys">
                        <span class="icon"><img src="../Image/Icon/pngegg.png" alt="Deliverys"></span>
                        Moje Zamówienia
                    </a>
                </li>
                <li class="menu-item">
                    <a href="#">
                        <span class="icon"><img src="../Image/Icon/favourite.png" alt="Favourite"></span>
                        Ulubione
                    </a>
                </li>
                <li class="menu-item logout">
                    <a href="logout.php">
                        <span class="icon"><img src="../Image/Icon/log-in.png" alt="Logout" class="mirror-icon"></span>
                        Wyloguj się
                    </a>
                </li>
            </ul>
        </div>
        <button class="sidebar-toggle" onclick="toggleSidebar()">☰</button>
        <main>
            <section id="user-deliverys" class="user-deliverys" >
                <?php
                    if (isset($_GET['section']) && $_GET['section'] == 'deliverys') {
                        // Wczytaj zawartość "Moje Zamówienia"
                        require '../Store/userDeliverys.php';
                    } else {
                        // Domyślnie wyświetl dane użytkownika
                    }
                ?>
            </section>
            
            <section class="user-info">
                <h2>Dane użytkownika</h2>
                <table>
                    <tr>
                        <th><br></th>
                        <th>Twoje Dane<br></th>
                    </tr>
                    <tr>
                        <td>Imię</td>
                        <td><?php echo htmlspecialchars($user['imie']); ?></td>
                    </tr>
                    <tr>
                        <td>Nazwisko</td>
                        <td><?php echo htmlspecialchars($user['nazwisko']); ?></td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                    </tr>
                    <tr>
                        <td>Numer telefonu</td>
                        <td><?php echo htmlspecialchars($user['numer_telefonu']); ?></td>
                    </tr>
                    <tr>
                        <td>Adres</td>
                        <td><?php echo htmlspecialchars($user['adres']); ?></td>
                    </tr>
                    <tr>
                        <th><br></th>
                        <th><br></th>
                    </tr>
                </table>
            </section>

            <section class="edit-form">
                <h2>Edytuj dane</h2>
                <form action="profile.php" method="POST">
                    <div class="form-group">
                        <label for="first_name">Imię:</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['imie']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Nazwisko:</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['nazwisko']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Numer telefonu:</label>
                        <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['numer_telefonu']); ?>" oninput="formatPhoneNumber(this)" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Adres:</label>
                        <textarea id="address" name="address" required><?php echo htmlspecialchars($user['adres']); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-save">Zapisz zmiany</button>
                    </div>
                </form>
            </section>


            <section class="delete-account">
                <h2>Usuń konto</h2>
                <form action="profile.php" method="POST">
                    <label for="password">Potwierdź hasłem:</label>
                    <input type="password" id="password" name="password" required>
                    <button type="submit" name="delete_account" onclick="return confirm('Czy na pewno chcesz usunąć konto?')">Usuń konto</button>
                </form>
            </section>

            <div class="actions">
                <a href="../index.php" class="btn btn-back">Powrót</a>
            </div>
        </main>
            <script>
                function formatPhoneNumber(input) {
                    let phone = input.value.replace(/\D/g, '');
                    phone = phone.substring(0, 9);

                    // Dodaj myślniki w odpowiednich miejscach
                    if (phone.length > 6) {
                        phone = phone.replace(/(\d{3})(\d{3})(\d+)/, '$1-$2-$3');
                    } else if (phone.length > 3) {
                        phone = phone.replace(/(\d{3})(\d+)/, '$1-$2');
                    }
                    input.value = phone;
                }
                
                function toggleSidebar() {
                    const sidebar = document.querySelector('.sidebar');
                    sidebar.classList.toggle('collapsed');
                    sidebar.classList.toggle('open');
                }
                
                
                document.addEventListener("DOMContentLoaded", function() {
                const deliverysLink = document.querySelector('a[href="profile.php?section=deliverys"]');
                const userInfoLink = document.querySelector('a[href="profile.php?section=user-info"]');
                //Tak samo dla sekcji favorite trzeba zrobić
                const userInfoSection = document.querySelector(".user-info");
                const editFormSection = document.querySelector(".edit-form");
                const deleteAccountSection = document.querySelector(".delete-account");
                const userDeliverysSection = document.getElementById("user-deliverys");
                const mainSection = document.querySelector("main");

                // Funkcja do ukrywania wszystkich sekcji oprócz zamówień
                function showDeliverys() {
                    if (userInfoSection) userInfoSection.style.display = "none";
                    if (editFormSection) editFormSection.style.display = "none";
                    if (deleteAccountSection) deleteAccountSection.style.display = "none";
                    if (userDeliverysSection) userDeliverysSection.style.display = "block";

                    if (mainSection) {
                        mainSection.classList.add("has-deliverys"); 
                    }
                    
                    if (deliverysLink) {
                        deliverysLink.classList.add("active");
                    }
                    if (userInfoLink) {
                        userInfoLink.classList.remove("active");
                    }
                }

                // Funkcja do wyświetlania danych użytkownika
                function showUserInfo() {
                    if (userInfoSection) userInfoSection.style.display = "block";
                    if (editFormSection) editFormSection.style.display = "block";
                    if (deleteAccountSection) deleteAccountSection.style.display = "block";
                    if (userDeliverysSection) userDeliverysSection.style.display = "none";

                    if (mainSection) {
                        mainSection.classList.remove("has-deliverys"); 
                    }
                    
                    if (userInfoLink) {
                        userInfoLink.classList.add("active");
                    }
                    if (deliverysLink) {
                        deliverysLink.classList.remove("active");
                    }
                }

                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('section') === 'deliverys') {
                    showDeliverys(); 
                } else {
                    showUserInfo(); 
                }

                if (deliverysLink) {
                    deliverysLink.addEventListener("click", function(e) {
                        e.preventDefault(); 
                        window.location.href = e.target.href; 
                    });
                } else {
                    console.error('Link "Moje Zamówienia" nie został znaleziony.');
                }
                    
                if (userInfoLink) {
                    userInfoLink.addEventListener("click", function(e) {
                        e.preventDefault(); 
                        window.location.href = e.target.href; 
                    });
                } else {
                    console.warn('Link "Moje Dane" nie został znaleziony.');
                }
            });
                

            </script>
        
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
                            <li><a href="#">Koszty dostawy</a></li>
                            <li><a href="#">Reklamacje</a></li>
                            <li><a href="#">Zwroty</a></li>
                        </ul>
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

    </body>
</html>
