<!DOCTYPE html>
<html lang="pl">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Newsletter</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-image: url('Image/Icon/brickwallpaper.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .container {
            display: flex;
            gap: 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        .box {
            background-color: rgba(255, 102, 0, 0.9);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 400px;
        }

        .box h1, .box h3 {
            color:rgb(254, 255, 254);
        }

        .box p {
            font-size: 1em;
            margin: 10px 0;
        }

        .form-container {
            text-align: center;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-size: 1.1em;
            color:rgb(254, 255, 254);
        }

        input[type="email"] {
            width: 100%;
            padding: 5px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }

        button {
            padding: 10px 20px;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }

        .submit-button {
            background-color: #28a745;
            color: #fff;
        }

        .submit-button:hover {
            background-color: #218838;
        }

        .back-button {
            background-color: #dc3545;
            color: #fff;
        }

        .back-button:hover {
            background-color: #c82333;
        }

        .benefits {
            text-align: left;
        }

        .benefits ul {
            list-style: none;
            padding: 0;
            color:rgb(254, 255, 254);
        }

        .benefits ul li {
            margin: 10px 0;
            display: flex;
            align-items: center;
        }

        .benefits ul li i {
            color: #28a745;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box form-container">
            <h1>Zapisz się do naszego newslettera!</h1>
            <form method="POST">
                <label for="email">Twój e-mail:</label>
                <input type="email" id="email" name="email" required>
                <div class="buttons">
                    <button type="submit" class="submit-button">ZAPISZ SIĘ</button>
                    <button type="button" class="back-button" onclick="history.back()">WRÓĆ</button>
                    <?php
                        require 'email_sender.php'; 

                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                            $file = 'emails.txt';

                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                echo '<div style="margin-top: 20px; padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 8px; text-align: center;">Nieprawidłowy adres e-mail.</div>';
                            } else {
                                if (!file_exists($file)) {
                                    file_put_contents($file, "");
                                }

                                $emails = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                                if (in_array($email, $emails)) {
                                    echo '<div style="margin-top: 20px; padding: 10px; background-color: #f8d7da; color: #721c24; border-radius: 8px; text-align: center;">Już jesteś zapisany!</div>';;
                                } else {

                                    file_put_contents($file, $email . PHP_EOL, FILE_APPEND);
                                    echo '<div style="margin-top: 20px; padding: 10px; background-color: #d4edda; color: #155724; border-radius: 8px; text-align: center;">Dziękujemy za zapisanie się!</div>';

                                    $subject = 'Dziękujemy za zapisanie się do newslettera Budex!';
                                    $body = <<<EOD
                                            Witamy w naszym newsletterze!

                                            Dziękujemy, że dołączyłeś/aś do newslettera hurtowni Budex. 
                                            Od teraz będziesz na bieżąco z:
                                            - Najnowszymi promocjami na nasze produkty.
                                            - Ekskluzywnymi ofertami tylko dla subskrybentów.
                                            - Poradami dotyczącymi budownictwa, instalacji i narzędzi.
                                            - Nowinkami technologicznymi i innowacjami w branży.

                                            Jeśli masz jakiekolwiek pytania lub potrzebujesz pomocy, skontaktuj się z nami: 
                                            - E-mail:  budexgdansk@gmail.com 
                                            - Telefon: +48 555 348 591

                                            Zespół Budex życzy Ci miłego dnia i udanych zakupów!

                                            Pozdrawiamy,
                                            Hurtownia Budex
                                            EOD;

                                    sendEmail($email, $subject, $body);
                                }
                            }
                        }
                    ?>
                </div>
            </form>
        </div>

        <div class="box benefits">
            <h3>Dlaczego warto zapisać się na nasz newsletter?</h3>
            <ul>
                <li><i class="fas fa-tags"></i>Najnowsze informacje o promocjach na produkty</li>
                <li><i class="fas fa-gift"></i>Ekskluzywne oferty i rabaty tylko dla subskrybentów</li>
                <li><i class="fas fa-tools"></i>Informacje o nowych narzędziach i technologiach</li>
                <li><i class="fas fa-calendar-alt"></i>Powiadomienia o nadchodzących wydarzeniach</li>
                <li><i class="fas fa-thumbs-up"></i>Porady dotyczące spraw budowlano-instalacyjnych</li>
                <li><i class="fas fa-envelope"></i>Podsumowanie najważniejszych informacji</li>
            </ul>
        </div>
    </div>
</body>
</html>
