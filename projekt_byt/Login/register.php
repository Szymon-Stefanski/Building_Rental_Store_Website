<?php
session_start();
require '../database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone_number = trim($_POST['phone_number']);
    $postal_code = trim($_POST['postal_code']);
    $city = trim($_POST['city']);
    $street = trim($_POST['street']);
    $house_number = trim($_POST['house_number']);
    $apartment_number = trim($_POST['apartment_number']);

    if (!$apartment_number) {
        $address = $postal_code . ', ' . $city . ', ' . $street . ', ' . $house_number;
    } else{
        $address = $postal_code . ', ' . $city . ', ' . $street . ', ' . $house_number . '/' . $apartment_number ;
    }

    if (!$email) {
        echo "Nieprawidłowy email!";
        exit;
    }

    $stmt = getDbConnection()->prepare("INSERT INTO uzytkownicy 
        (login, email, haslo, imie, nazwisko, numer_telefonu, adres, rola)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password, $first_name, $last_name, $phone_number, $address, 'user']);

    $_SESSION['user_id'] = getDbConnection()->lastInsertId();
    $_SESSION['username'] = $username;
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejestracja</title>
    <link rel="stylesheet" href="../Style/style_register.css">
</head>
<body>
    <div class="form-container">
        <form action="register.php" method="POST" id="registerForm">
            <h1>Rejestracja</h1>

            <label for="username">Nazwa użytkownika:</label>
            <input type="text" id="username" name="username" required minlength="3" maxlength="50"><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>

            <label for="password">Hasło:</label>
            <div class="password-container">
                <input type="password" id="password" name="password" required minlength="6">
                <div class="eye-icon-container">
                    <span id="togglePassword" class="eye-icon"></span>
                </div>
            </div>
            <div id="passwordStrengthContainer">
                <div id="passwordStrengthBar"></div>
            </div>
            <p id="passwordStrengthText" style="font-weight:bold;"></p>


            <label for="first_name">Imię:</label>
            <input type="text" id="first_name" name="first_name" required><br>

            <label for="last_name">Nazwisko:</label>
            <input type="text" id="last_name" name="last_name" required><br>

            <label for="phone_number">Numer telefonu:</label>
            <input type="text" id="phone_number" name="phone_number" required pattern="^\d{3}-\d{3}-\d{3}$" maxlength="12" oninput="formatPhoneNumber(this)"><br>

            <label for="postal_code">Adres:</label>
            <div class="address-container">
                <input type="text" id="postal_code" name="postal_code" required placeholder="XX-XXX" pattern="^\d{2}-\d{3}$" maxlength="6">
                <input type="text" id="city" name="city" required placeholder="Miasto"><br>

                <input type="text" id="street" name="street" required placeholder="Ulica">
                <input type="text" id="house_number" name="house_number" required placeholder="Numer domu">
                <input type="text" id="apartment_number" name="apartment_number" placeholder="Numer mieszkania">
            </div>

            <button type="submit">Z a r e j e s t r u j</button>
        </form>

        <p>Masz już konto? <a href="login.php">Zaloguj się</a></p>
    </div>
    
    <script>
        
      
        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, ''); 
            if (value.length <= 3) {
                input.value = value;
            } else if (value.length <= 6) {
                input.value = value.slice(0, 3) + '-' + value.slice(3);
            } else {
                input.value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 9);
            }
        }
        
        
        
        function validateForm(event) {
                
            let isValid = true;
            let errorMessages = [];
            
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const firstName = document.getElementById('first_name');
            const lastName = document.getElementById('last_name');
            const phoneNumber = document.getElementById('phone_number');
            const address = document.getElementById('address');

            
            if (username.value.length < 3) {
                isValid = false;
                errorMessages.push("Nazwa użytkownika musi mieć przynajmniej 3 znaki.");
            }

           
            const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
            if (!emailRegex.test(email.value)) {
                isValid = false;
                errorMessages.push("Proszę podać prawidłowy adres email.");
            }

           
            if (password.value.length < 6) {
                isValid = false;
                errorMessages.push("Hasło musi mieć przynajmniej 6 znaków.");
            }

            
            const nameRegex = /^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ]+$/;
            if (!nameRegex.test(firstName.value)) {
                isValid = false;
                errorMessages.push("Imię może zawierać tylko litery.");
            }
            if (!nameRegex.test(lastName.value)) {
                isValid = false;
                errorMessages.push("Nazwisko może zawierać tylko litery.");
            }

            
            const phoneRegex = /^\d{3}-\d{3}-\d{3}$/;
            if (!phoneRegex.test(phoneNumber.value)) {
                isValid = false;
                errorMessages.push("Numer telefonu musi być w formacie XXX-XXX-XXX.");
            }

            document.getElementById('postal_code').addEventListener('input', function (e) {
                const value = e.target.value;
                const formattedValue = value.replace(/[^\d]/g, '').slice(0, 5);

                if (formattedValue.length >= 3) {
                    e.target.value = `${formattedValue.slice(0, 2)}-${formattedValue.slice(2)}`;
                } else {
                    e.target.value = formattedValue;
                }
            });

            
            document.getElementById('city').addEventListener('input', function (e) {
                e.target.value = e.target.value.replace(/[^a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ\s]/g, '');
            });

            if (!isValid) {
                event.preventDefault(); 
                alert(errorMessages.join("\n")); 
            }
            
        }
        
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('password');
            const type = passwordField.type === 'password' ? 'text' : 'password';
            passwordField.type = type;
            this.textContent = type === 'password' ? '🙈' : '👁️' ;
        });

        // Ustaw ikonę początkową
        document.getElementById('togglePassword').innerHTML = '🙈';

        // Funkcja oceniająca siłę hasła
        document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthText = document.getElementById('passwordStrengthText');

        let strength = 0;
        let colorClass = '';

      
        if (password.length >= 8) {
            strength++;
        }
        if (/[A-Z]/.test(password)) {
            strength++;
        }
        if (/\d/.test(password)) {
            strength++;
        }
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            strength++;
        }
        if (password.length >= 12) {
            strength++;
        }

        switch (strength) {
            case 0:
                colorClass = 'strength-bad';
                strengthBar.style.width = '20%';
                strengthText.textContent = 'Bardzo słabe';
                break;
            case 1:
                colorClass = 'strength-weak';
                strengthBar.style.width = '40%';
                strengthText.textContent = 'Słabe';
                break;
            case 2:
                colorClass = 'strength-medium';
                strengthBar.style.width = '60%';
                strengthText.textContent = 'Średnie';
                break;
            case 3:
                colorClass = 'strength-good';
                strengthBar.style.width = '80%';
                strengthText.textContent = 'Dobre';
                break;
            case 4:
                colorClass = 'strength-strong';
                strengthBar.style.width = '90%';
                strengthText.textContent = 'Silne';
                break;
            case 5:
                colorClass = 'strength-vstrong';
                strengthBar.style.width = '100%';
                strengthText.textContent = 'Bardzo silne';
                break;
        }

        strengthBar.className = colorClass;
    });


    </script>
</body>
</html>
