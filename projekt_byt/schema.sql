
USE Build_Store;


CREATE TABLE Kategorie (
    kategoria_id INT AUTO_INCREMENT PRIMARY KEY,
    nazwa_kategorii VARCHAR(100) NOT NULL,
    opis TEXT
);


CREATE TABLE Dostawcy (
    dostawca_id INT AUTO_INCREMENT PRIMARY KEY,
    nazwa_dostawcy VARCHAR(100) NOT NULL,
    osoba_kontaktowa VARCHAR(50),
    numer_telefonu VARCHAR(15),
    email VARCHAR(100),
    adres TEXT
);


CREATE TABLE Produkty (
    produkt_id INT AUTO_INCREMENT PRIMARY KEY,
    nazwa_produktu VARCHAR(100) NOT NULL,
    kategoria_id INT,
    dostawca_id INT,
    cena DECIMAL(10, 2) NOT NULL,
    ilosc_w_magazynie INT,
    opis TEXT,
    FOREIGN KEY (kategoria_id) REFERENCES Kategorie(kategoria_id) ON DELETE SET NULL,
    FOREIGN KEY (dostawca_id) REFERENCES Dostawcy(dostawca_id) ON DELETE SET NULL
);


CREATE TABLE Uzytkownicy (
    uzytkownik_id INT AUTO_INCREMENT PRIMARY KEY,
    imie VARCHAR(50) NOT NULL,
    nazwisko VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    login VARCHAR(50) UNIQUE NOT NULL,
    haslo VARCHAR(255) NOT NULL,
    numer_telefonu VARCHAR(15),
    adres TEXT,
    rola VARCHAR(50) NOT NULL DEFAULT 'klient'
);


CREATE TABLE Opinie_Produktow (
    opinia_id INT AUTO_INCREMENT PRIMARY KEY,
    produkt_id INT NOT NULL,
    uzytkownik_id INT NOT NULL,
    ocena INT CHECK (ocena BETWEEN 1 AND 5),
    tresc_opinii TEXT,
    data_opinii DATE NOT NULL,
    FOREIGN KEY (produkt_id) REFERENCES Produkty(produkt_id) ON DELETE CASCADE,
    FOREIGN KEY (uzytkownik_id) REFERENCES Uzytkownicy(uzytkownik_id) ON DELETE CASCADE
);


CREATE TABLE Zamowienia (
    zamowienie_id INT AUTO_INCREMENT PRIMARY KEY,
    uzytkownik_id INT NOT NULL,
    data_zamowienia DATE NOT NULL,
    status VARCHAR(50) NOT NULL,
    FOREIGN KEY (uzytkownik_id) REFERENCES Uzytkownicy(uzytkownik_id) ON DELETE CASCADE
);


CREATE TABLE Pozycje_Zamowien (
    pozycja_id INT AUTO_INCREMENT PRIMARY KEY,
    zamowienie_id INT NOT NULL,
    produkt_id INT NOT NULL,
    ilosc INT NOT NULL,
    cena_za_sztuke DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (zamowienie_id) REFERENCES Zamowienia(zamowienie_id) ON DELETE CASCADE,
    FOREIGN KEY (produkt_id) REFERENCES Produkty(produkt_id) ON DELETE CASCADE
);


CREATE TABLE Wynajmy (
    wynajem_id INT AUTO_INCREMENT PRIMARY KEY,
    uzytkownik_id INT NOT NULL,
    data_wynajmu DATE NOT NULL,
    data_zwrotu DATE,
    status VARCHAR(50) NOT NULL,
    FOREIGN KEY (uzytkownik_id) REFERENCES Uzytkownicy(uzytkownik_id) ON DELETE CASCADE
);


CREATE TABLE Pozycje_Wynajmu (
    pozycja_wynajmu_id INT AUTO_INCREMENT PRIMARY KEY,
    wynajem_id INT NOT NULL,
    produkt_id INT NOT NULL,
    ilosc INT NOT NULL,
    stawka_dzienna DECIMAL(10, 2) NOT NULL,
    koszt_calkowity DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (wynajem_id) REFERENCES Wynajmy(wynajem_id) ON DELETE CASCADE,
    FOREIGN KEY (produkt_id) REFERENCES Produkty(produkt_id) ON DELETE CASCADE
);


CREATE TABLE Transakcje (
    transakcja_id INT AUTO_INCREMENT PRIMARY KEY,
    zamowienie_id INT,
    data_platnosci DATE NOT NULL,
    kwota DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (zamowienie_id) REFERENCES Zamowienia(zamowienie_id) ON DELETE CASCADE
);



INSERT INTO Kategorie (nazwa_kategorii, opis) VALUES 
('Budowlanka', 'Materiały i narzędzia budowlane do prac konstrukcyjnych i remontowych.'),
('Elektryka', 'Produkty związane z instalacjami elektrycznymi i oświetleniem.'),
('Narzędzia', 'Narzędzia ręczne i elektronarzędzia do prac warsztatowych i budowlanych.'),
('Sanitarka', 'Wyposażenie sanitarne oraz elementy instalacji wodno-kanalizacyjnych.');


INSERT INTO Dostawcy (nazwa_dostawcy, osoba_kontaktowa, numer_telefonu, email, adres) VALUES
('Budowlanix', 'Jan Kowalski', '123456789', 's22043@pjwstk.edu.pl', 'ul. Budowlana 1, 00-001 Warszawa'),
('Elektrix', 'Anna Nowak', '987654321', 's22043@pjwstk.edu.pl', 'ul. Elektryczna 2, 00-002 Warszawa'),
('Toolix', 'Piotr Wiśniewski', '555666777', 's22043@pjwstk.edu.pl', 'ul. Narzędziowa 3, 00-003 Warszawa'),
('Sanitarix', 'Katarzyna Zielińska', '444333222', 's22043@pjwstk.edu.pl', 'ul. Sanitarna 4, 00-004 Warszawa');
