# Strona Hurtowni Budowlanej 🧱

Jest to aplikacja internetowa dla fikcyjnej hurtowni materiałów budowlanych.  
Strona umożliwia przeglądanie produktów, zapisanie się na newsletter oraz wypożyczenie sprzętu budowlanego.

## ✨ Główne funkcje

- 🛒 Przeglądanie produktów według kategorii
- 📰 Możliwość zapisania się na newsletter
- 🛠️ System wypożyczania sprzętu budowlanego
- 🔐 Logowanie za pomocą konta Google
- 👤 Obsługa ról użytkowników: Administrator i Moderator
- 📧 Wysyłka e-maili przez PHPMailer
- 🧪 Testowanie funkcjonalności przy pomocy PHPUnit

I Logowanie poprzez google

    Konta domyślne:
    administrator login: admin hasło: admin
    moderator login: mod hasło: mod

    Wymagania do logowania kontem google:
    Dodaj w projekt_byt/ - plik .env - z taką zawartością

    			GOOGLE_CLIENT_ID=51496553536-7ocdcs2n8a6rfej3eus4d1rdmg6i1i7g.apps.googleusercontent.com
    			GOOGLE_CLIENT_SECRET=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
    			GOOGLE_REDIRECT_URI=http://localhost/projekt_byt/redirect.php

    Ściągnij composer z poniżeszego linku:
    https://getcomposer.org/

    1. Open CMD

    2. cd C:\xampp\htdocs\projekt_byt\Login

    3. composer install

    	- tworzy folder projekt_byt/Login/vendor

    	4. then
    			composer require vlucas/phpdotenv

    	- dodaje dodatkowe biblioteki w projekt_byt/Login/vendor

II PHPMailer - automatyczne wysyłanie emaili:

    Instalacja poprzez composer:

        composer require phpmailer/phpmailer

III PHPUnit - framework do testowania funkcji:

    Instalacja poprzez composer:

        composer require --dev phpunit/phpunit

    Przykład wywołania testu:

        php vendor/bin/phpunit Tests/Test_database/DatabaseTest.php

    Wywołanie testu sprawdzającego autoryzację poprzez google wymaga instalacji api clienta:

        composer require google/apiclient
