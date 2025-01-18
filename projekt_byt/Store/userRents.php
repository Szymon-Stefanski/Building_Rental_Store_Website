<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userID = $_SESSION['user_id'];

// Pobranie zamówień dla zalogowanego użytkownika
$query = getDbConnection()->prepare("
    SELECT wynajem_id, data_wynajmu, data_zwrotu, status 
    FROM Wynajmy 
    WHERE uzytkownik_id = ?
");

$query->execute([$userID]);
$results = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moje Wypożyczenia</title>
    <link rel="stylesheet" href="path/to/your/style.css"> <!-- Jeśli chcesz dodać zewnętrzny plik CSS -->
    <style>
        /* Style dla tabeli */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-family: Arial, sans-serif;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
            font-size: 16px;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }

        .no-orders {
            text-align: center;
            font-size: 18px;
            color: #888;
        }

        /* Styl dla linków */
        a {
            color:rgb(56, 129, 60); /* Zielony kolor linku */
            text-decoration: none; /* Brak podkreślenia */
            font-weight: bold;
        }

        a:hover {
            color:rgb(56, 129, 60); /* Ciemniejszy zielony kolor przy najechaniu */
        }

        /* Responsywność */
        @media (max-width: 600px) {
            table, th, td {
                font-size: 14px;
            }
            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Moje Wypożyczenia</h1>

        <?php if (count($results) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Nr wynajmu</th>
                        <th>Data wynajmu</th>
                        <th>Data zwrotu</th>
                        <th>Status</th>
                        <th>Pozycje wynajmu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['wynajem_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['data_wynajmu']); ?></td>
                            <td><?php echo htmlspecialchars($row['data_zwrotu']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td>
                                <a href="../Store/rentalStatus.php?id=<?php echo $row['wynajem_id']; ?>">Szczegóły wynajmu</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-orders">Nie masz jeszcze żadnych wypożyczeń.</p>
        <?php endif; ?>
    </div>
</body>
</html>

