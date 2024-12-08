
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
('Sanitarka', 'Wyposażenie sanitarne oraz elementy instalacji wodno-kanalizacyjnych.'),
('Narzędzia', 'Narzędzia ręczne i elektronarzędzia do prac warsztatowych i budowlanych.');


INSERT INTO Dostawcy (nazwa_dostawcy, osoba_kontaktowa, numer_telefonu, email, adres) VALUES
('Budowlanix', 'Jan Kowalski', '123456789', 's22043@pjwstk.edu.pl', 'ul. Budowlana 1, 00-001 Warszawa'),
('Elektrix', 'Anna Nowak', '987654321', 's22043@pjwstk.edu.pl', 'ul. Elektryczna 2, 00-002 Warszawa'),
('Toolix', 'Piotr Wiśniewski', '555666777', 's22043@pjwstk.edu.pl', 'ul. Narzędziowa 3, 00-003 Warszawa'),
('Sanitarix', 'Katarzyna Zielińska', '444333222', 's22043@pjwstk.edu.pl', 'ul. Sanitarna 4, 00-004 Warszawa');


INSERT INTO Produkty (nazwa_produktu, kategoria_id, dostawca_id, cena, ilosc_w_magazynie, opis) VALUES
('Beton (25 kg) B20', 1, 1, 21.00, 100, 'Workowany beton klasy B20 o wysokiej wytrzymałości, idealny do wylewek i konstrukcji fundamentowych.'),
('Piasek workowany (25 kg)', 1, 1, 25.00, 100, 'Czysty, suchy piasek budowlany, przeznaczony do murowania, tynkowania i betonu.'),
('Gładź szpachlowa', 1, 1, 40.00, 100, 'Gotowa do użycia gładź szpachlowa, zapewniająca idealnie gładkie powierzchnie ścian i sufitów.'),
('Płyta gipsowo-kartonowa', 1, 1, 60.00, 100, 'Standardowa płyta G-K o wymiarach 2,5m x 1,2m, przeznaczona do suchej zabudowy wnętrz.'),
('Cegła ceramiczna', 1, 1, 2.00, 100, 'Cegła ceramiczna pełna, doskonała do budowy ścian nośnych i działowych.'),
('Bloczek betonowy', 1, 1, 5.00, 100, 'Bloczek fundamentowy o wymiarach 12x25x38 cm, odporny na mróz i wilgoć.'),
('Zaprawa murarska', 1, 1, 13.00, 100, 'Sucha mieszanka do murowania cegieł i bloczków, łatwa w przygotowaniu.'),
('Siatka zbrojeniowa', 1, 1, 75.00, 100, 'Siatka zbrojeniowa o wymiarach 2m x 1m, zapewniająca wzmocnienie konstrukcji betonowych.'),
('Profil aluminiowy do regipsów', 1, 1, 84.00, 100, 'Profil aluminiowy U o długości 3m, przeznaczony do montażu ścian i sufitów z płyt G-K.'),
('Kołki rozporowe (10 szt.)', 1, 1, 10.00, 100, 'Wytrzymałe kołki rozporowe do montażu w ścianach murowanych i betonowych.'),
('Pianka montażowa', 1, 1, 30.00, 100, 'Jednoskładnikowa pianka poliuretanowa, doskonała do montażu i izolacji.'),
('Folia budowlana', 1, 1, 35.00, 100, 'Folia o grubości 0,2 mm, stosowana jako izolacja przeciwwilgociowa.'),
('Folia malarska', 1, 1, 12.00, 100, 'Lekka folia ochronna o wymiarach 4x5 m, idealna do zabezpieczenia powierzchni podczas malowania.'),
('Zszywacz budowlany', 1, 1, 60.00, 100, 'Profesjonalny zszywacz budowlany do mocowania folii i materiałów izolacyjnych.'),
('Paca zębata', 1, 1, 40.00, 100, 'Paca zębata 10x10 cm, idealna do nakładania kleju pod płytki.'),
('Szpachla', 1, 1, 20.00, 100, 'Wytrzymała szpachla stalowa o szerokości 5 cm, przeznaczona do prac wykończeniowych.'),
('Grunt', 1, 1, 60.00, 100, 'Preparat gruntujący poprawiający przyczepność farb i zapraw.'),
('Młotek murarski', 1, 1, 25.00, 100, 'Młotek murarski z hartowaną głowicą, idealny do prac konstrukcyjnych.'),
('Kątownik budowlany', 1, 1, 3.00, 100, 'Metalowy kątownik o długości 30 cm, pomocny przy precyzyjnym cięciu i montażu.'),
('Wkręty (10 szt.)', 1, 1, 10.00, 100, 'Ocynkowane wkręty do drewna i metalu, odporne na korozję.');


INSERT INTO Produkty (nazwa_produktu, kategoria_id, dostawca_id, cena, ilosc_w_magazynie, opis) VALUES
('Antena', 2, 2, 148.00, 100, 'Antena telewizyjna do odbioru sygnału DVB-T/T2 o wysokiej wydajności.'),
('Gniazdko elektryczne x1', 2, 2, 5.00, 100, 'Pojedyncze gniazdko elektryczne 230V z uziemieniem.'),
('Gniazdko elektryczne x2', 2, 2, 5.00, 100, 'Podwójne gniazdko elektryczne 230V z uziemieniem.'),
('Włącznik x1', 2, 2, 10.00, 100, 'Jednostykowy włącznik światła z białym wykończeniem.'),
('Włącznik x2', 2, 2, 10.00, 100, 'Dwustykowy włącznik światła, wygodny do podwójnych obwodów.'),
('Żarówka e27', 2, 2, 8.00, 100, 'Klasyczna żarówka LED o gwincie E27, moc 10W.'),
('Żarówka e14', 2, 2, 5.00, 100, 'Żarówka LED z gwintem E14, idealna do lamp i żyrandoli.'),
('Rozdzielnia elektryczna', 2, 2, 65.00, 100, 'Rozdzielnia modułowa na 12 zabezpieczeń, montowana natynkowo.'),
('Bezpiecznik s1', 2, 2, 20.00, 100, 'Automatyczny bezpiecznik nadmiarowo-prądowy typu S1, 16A.'),
('Czujnik ruchu', 2, 2, 30.00, 100, 'Czujnik ruchu PIR do automatycznego sterowania oświetleniem.'),
('Latarka LED', 2, 2, 130.00, 100, 'Latarka akumulatorowa LED o dużej jasności i wytrzymałej obudowie.'),
('Multimetr cyfrowy', 2, 2, 20.00, 100, 'Uniwersalny multimetr cyfrowy do pomiarów elektrycznych.'),
('Przedłużacz bębnowy', 2, 2, 120.00, 100, 'Przedłużacz bębnowy 25m, z 4 gniazdami i zabezpieczeniem termicznym.'),
('Oprawa oświetleniowa', 2, 2, 45.00, 100, 'Oprawa LED zewnętrzna IP65, idealna do oświetlenia elewacji i ogrodów.'),
('Lampa robocza LED', 2, 2, 300.00, 100, 'Mocna lampa robocza LED, 5000 lumenów, na statywie.'),
('Taśma izolacyjna', 2, 2, 5.00, 100, 'Elastyczna taśma izolacyjna do zabezpieczania przewodów.'),
('Tester napięcia', 2, 2, 30.00, 100, 'Precyzyjny tester napięcia, obsługujący zakres od 12V do 250V.'),
('Termostat', 2, 2, 120.00, 100, 'Cyfrowy termostat do sterowania ogrzewaniem podłogowym.'),
('Złączki kablowe', 2, 2, 10.00, 100, 'Zestaw 10 szt. złączek kablowych do bezpiecznego łączenia przewodów.'),
('Śrubokręt izolowany', 2, 2, 30.00, 100, 'Izolowany śrubokręt płaski, wytrzymujący napięcie do 1000V.');


INSERT INTO Produkty (nazwa_produktu, kategoria_id, dostawca_id, cena, ilosc_w_magazynie, opis) VALUES
('Rura PVC', 3, 4, 24.00, 100, 'Rura PVC o średnicy 50 mm, odporna na działanie chemikaliów i temperatury.'),
('Kolanko PVC', 3, 4, 5.00, 100, 'Kolanko PVC 90°, idealne do zmian kierunku w instalacjach wodnych.'),
('Złączka PVC', 3, 4, 3.00, 100, 'Złączka do rur PVC, zapewniająca trwałe i szczelne połączenie.'),
('Wężyk do wody', 3, 4, 7.00, 100, 'Elastyczny wężyk przyłączeniowy do wody, długość 50 cm.'),
('Syfon do umywalki', 3, 4, 60.00, 100, 'Syfon butelkowy z możliwością regulacji wysokości.'),
('Odpływ liniowy', 3, 4, 270.00, 100, 'Nowoczesny odpływ liniowy o długości 80 cm, wykonany ze stali nierdzewnej.'),
('Zestaw prysznicowy', 3, 4, 130.00, 100, 'Zestaw prysznicowy z deszczownicą i słuchawką natryskową.'),
('Sedes kompaktowy', 3, 4, 800.00, 100, 'Nowoczesny sedes kompaktowy z cichym spłukiwaniem.'),
('Pisuar', 3, 4, 300.00, 100, 'Ceramiczny pisuar z zaworem spłukującym.'),
('Umywalka', 3, 4, 270.00, 100, 'Ceramiczna umywalka nablatowa o nowoczesnym designie.'),
('Płyn udrażniający', 3, 4, 30.00, 100, 'Silny płyn do udrażniania rur kanalizacyjnych.'),
('Grzałka do wody', 3, 4, 100.00, 100, 'Elektryczna grzałka do bojlerów, moc 1500W.'),
('Uszczelki do rur (10 szt.)', 3, 4, 5.00, 100, 'Zestaw 10 uszczelek gumowych do rur wodnych.'),
('Taśma teflonowa', 3, 4, 3.00, 100, 'Wysokiej jakości taśma teflonowa do uszczelniania połączeń gwintowych.'),
('Klucz do rur', 3, 4, 30.00, 100, 'Solidny klucz do rur, długość 25 cm, idealny do instalacji sanitarnych.'),
('Termometr rurowy', 3, 4, 40.00, 100, 'Termometr do pomiaru temperatury w rurach ciepłowniczych.'),
('Pompa do szamba', 3, 4, 300.00, 100, 'Mocna pompa do szamba z funkcją rozdrabniania.'),
('Zawór kulowy', 3, 4, 20.00, 100, 'Zawór kulowy 1/2", wykonany z mosiądzu, przeznaczony do instalacji wodnych.'),
('Filtr wodny', 3, 4, 80.00, 100, 'Filtr wodny do oczyszczania wody użytkowej z osadów.'),
('Deska sedesowa', 3, 4, 170.00, 100, 'Deska sedesowa wolnoopadająca, wykonana z wytrzymałego materiału.');


INSERT INTO Produkty (nazwa_produktu, kategoria_id, dostawca_id, cena, ilosc_w_magazynie, opis) VALUES
('Śrubokręty zestaw', 4, 3, 30.00, 100, 'Zestaw 6 śrubokrętów z magnetycznymi końcówkami.'),
('Klucze nasadowe komplet', 4, 3, 500.00, 100, 'Profesjonalny zestaw kluczy nasadowych 1/4" i 1/2" z grzechotkami.'),
('Zestaw nasadek do wkrętarki', 4, 3, 60.00, 100, 'Zestaw nasadek magnetycznych kompatybilnych z wkrętarkami.'),
('Młotek stolarski', 4, 3, 50.00, 100, 'Młotek stolarski z drewnianą rękojeścią, waga 500g.'),
('Piła ręczna', 4, 3, 40.00, 100, 'Piła ręczna do drewna z hartowanymi zębami.'),
('Nożyk introligatorski', 4, 3, 10.00, 100, 'Ostry nożyk introligatorski z wymiennymi ostrzami.'),
('Liniał sztywny', 4, 3, 20.00, 100, 'Metalowy liniał o długości 50 cm, z milimetrową podziałką.'),
('Poziomica', 4, 3, 60.00, 100, 'Precyzyjna poziomica 60 cm z trzema libellami.'),
('Miarka zwijana', 4, 3, 30.00, 100, 'Taśma miernicza 5m z blokadą zwijania.'),
('Imadło stołowe', 4, 3, 120.00, 100, 'Solidne imadło stołowe, rozstaw szczęk 100 mm.'),
('Wiertarka udarowa', 4, 3, 400.00, 100, 'Wiertarka udarowa o mocy 750W, przeznaczona do prac domowych.'),
('Młot wyburzeniowy', 4, 3, 1000.00, 100, 'Profesjonalny młot wyburzeniowy 1500W, idealny do ciężkich prac budowlanych.'),
('Szlifierka kątowa', 4, 3, 500.00, 100, 'Szlifierka kątowa 125mm, moc 1200W, z funkcją miękkiego startu.'),
('Pilarka tarczowa', 4, 3, 600.00, 100, 'Pilarka tarczowa 1800W, idealna do cięcia drewna i płyt.'),
('Agregat prądotwórczy', 4, 3, 2000.00, 100, 'Agregat prądotwórczy 3kW, z automatycznym regulatorem napięcia.'),
('Myjka ciśnieniowa', 4, 3, 700.00, 100, 'Myjka ciśnieniowa 140 bar, wyposażona w dodatkowe akcesoria.'),
('Przecinarka do płytek', 4, 3, 400.00, 100, 'Przecinarka do płytek ceramicznych, długość cięcia do 600 mm.'),
('Odkurzacz przemysłowy', 4, 3, 1500.00, 100, 'Mocny odkurzacz przemysłowy 1600W, do pracy na sucho i mokro.'),
('Wiertnica do betonu', 4, 3, 2500.00, 100, 'Wiertnica do betonu 2000W, średnica wiercenia do 200 mm.'),
('Drabina teleskopowa', 4, 3, 1000.00, 100, 'Aluminiowa drabina teleskopowa o maksymalnej wysokości 5m.');
