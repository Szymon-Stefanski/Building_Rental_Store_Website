<?php
session_start();
require '../database_connection.php';

if (!isset($_GET['id'])) {
    echo "Brak ID wynajmu w URL.";
    exit;
}

$wynajem_id = $_GET['id'];

$db = getDbConnection();

// Pobieranie szczegółów wynajmu z loginem użytkownika (jeśli istnieje)
$wynajem_stmt = $db->prepare("
    SELECT 
        w.uzytkownik_id,
        u.login AS uzytkownik_login,
        w.data_wynajmu,
        w.data_zwrotu,
        w.status
    FROM Wynajmy w
    LEFT JOIN Uzytkownicy u ON w.uzytkownik_id = u.uzytkownik_id
    WHERE w.wynajem_id = ?
");
$wynajem_stmt->execute([$wynajem_id]);
$wynajem = $wynajem_stmt->fetch(PDO::FETCH_ASSOC);

if (!$wynajem) {
    echo "Nie znaleziono wynajmu o podanym ID.";
    exit;
}

// Pobieranie pozycji wynajmu
$pozycje_stmt = $db->prepare("
    SELECT 
        pw.produkt_id,
        pw.ilosc,
        pw.stawka_dzienna,
        pw.koszt_calkowity
    FROM Pozycje_Wynajmu pw
    WHERE pw.wynajem_id = ?
");
$pozycje_stmt->execute([$wynajem_id]);
$pozycje = $pozycje_stmt->fetchAll(PDO::FETCH_ASSOC);

// Pobieranie roli użytkownika
$stmt = getDbConnection()->prepare("SELECT rola FROM Uzytkownicy WHERE uzytkownik_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userRole = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły wynajmu</title>
    <link rel="stylesheet" href="../Style/style_deliveryStatus.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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
            <nav>
                <ul>
                    <li><a href="../index.php">Strona główna</a></li>
                    <li><a href="userDeliverys.php">Moje wynajmy</a></li>
                    <?php if (isset($_SESSION['user_id']) && ($userRole === 'admin' || $userRole === 'mod')): ?>
                        <li><a href="../Warehouse/deliveryManagement.php">Zarządzanie wynajmami</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        
    </header>

    <main>
        <div class="container">
            <h1>Szczegóły wynajmu</h1>
            
            <section class="order-details">
                
                <h2>Dane wynajmu</h2>
                <?php if (!is_null($wynajem['uzytkownik_id'])): ?>
                    <p><strong>Login użytkownika:</strong> <?= $wynajem['uzytkownik_login'] ?></p>
                <?php else: ?>
                    <p><strong>Login użytkownika:</strong> Wynajem jako gość</p>
                <?php endif; ?>
                <p><strong>Data wynajmu:</strong> <?= $wynajem['data_wynajmu'] ?></p>
                <p><strong>Data zwrotu:</strong> <?= $wynajem['data_zwrotu'] ?></p>
                
                <!-- Dodanie klasy CSS do statusu w zależności od jego wartości -->
                <p class="status 
                    <?php 
                        if ($wynajem['status'] == 'Zakończony') {
                            echo 'zakończony'; 
                        } elseif ($wynajem['status'] == 'W trakcie') {
                            echo 'w-trakcie'; 
                        } else {
                            echo 'default'; 
                        }
                    ?>
                ">
                <strong>Status:</strong> <?= $wynajem['status'] ?>
                </p>
            </section>

            <section class="order-items">
                <h2>Pozycje wynajmu</h2>
                <?php if (count($pozycje) > 0): ?>
                    <table>
                        <thead>
                        <tr>
                            <th>ID Produktu</th>
                            <th>Ilość</th>
                            <th>Stawka dzienna</th>
                            <th>Koszt całkowity</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pozycje as $pozycja): ?>
                            <tr>
                                <td><?= $pozycja['produkt_id'] ?></td>
                                <td><?= $pozycja['ilosc'] ?></td>
                                <td><?= number_format($pozycja['stawka_dzienna'], 2) ?> zł</td>
                                <td><?= number_format($pozycja['koszt_calkowity'], 2) ?> zł</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Brak pozycji w tym wynajmie.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

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
