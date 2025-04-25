<?php
    $connStr = getenv('MYSQLCONNSTR_AZURE_MYSQL_CONNECTIONSTRING');

    if (!$connStr) {
        die("Environment variable AZURE_MYSQL_CONNECTIONSTRING not set.");
    }

    // Verbindungsdaten extrahieren
    preg_match("/Database=(.+?);/", $connStr, $db);
    preg_match("/Server=(.+?);/", $connStr, $host);
    preg_match("/User Id=(.+?);/", $connStr, $user);
    preg_match("/Password=(.+)/", $connStr, $pass);

    if (!$db || !$host || !$user || !$pass) {
        die("Fehler beim Parsen der Verbindungszeichenfolge.");
    }

    // DSN für PDO
    $dsn = "mysql:host={$host[1]};dbname={$db[1]};charset=utf8mb4;sslmode=require";

    try {
        $pdo = new PDO($dsn, $user[1], $pass[1]);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Falls Formular abgeschickt wurde
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['level'])) {
            $newLevel = trim($_POST['level']);

            if (is_numeric($newLevel)) {
                $stmt = $pdo->prepare("INSERT INTO oillevels (level, timestamp) VALUES (?, NOW())");
                $stmt->execute([$newLevel]);
                header("Location: " . $_SERVER['PHP_SELF']); // Seite neu laden
                exit;
            } else {
                $error = "Bitte eine gültige Zahl eingeben.";
            }
        }

        // Daten abrufen
        $stmt = $pdo->query("SELECT * FROM oillevels ORDER BY timestamp DESC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Datenbankfehler: " . $e->getMessage());
    }
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ölstand Übersicht</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        form {
            max-width: 500px;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
        }

        input[type="text"] {
            padding: 10px;
            flex: 1;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        input[type="submit"] {
            padding: 10px 20px;
            background-color: #0078D7;
            border: none;
            color: white;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #005fa3;
        }

        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
        }

        table {
            margin: 20px auto;
            border-collapse: collapse;
            width: 90%;
            max-width: 800px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #0078D7;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            color: #777;
        }
    </style>
</head>
<body>

    <h1>Ölstand Übersicht</h1>

    <form method="POST">
        <input type="text" name="level" placeholder="Aktuellen Ölstand eingeben" required>
        <input type="submit" value="Eintragen">
    </form>

    <?php if (!empty($error)) : ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <canvas id="oilChart" style="max-width: 800px; margin: 0 auto 40px; display: block;"></canvas>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ölstand</th>
                <th>Zeitstempel</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data)) : ?>
                <?php foreach ($data as $row) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['level']) ?></td>
                        <td><?= htmlspecialchars($row['timestamp']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="3">Keine Daten vorhanden.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <footer>
        &copy; <?= date("Y") ?> Ölstand-Tracker
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const oilData = <?php echo json_encode($data); ?>;
        const labels = oilData.map(row => row.timestamp);
        const levels = oilData.map(row => parseFloat(row.level));

        const ctx = document.getElementById('oilChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.reverse(),
                datasets: [{
                    label: 'Ölstand',
                    data: levels.reverse(),
                    fill: true,
                    borderColor: 'rgba(0, 120, 215, 1)',
                    backgroundColor: 'rgba(0, 120, 215, 0.2)',
                    tension: 0.3,
                    pointRadius: 2,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Ölstand über Zeit',
                        font: {
                            size: 18
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Zeitstempel'
                        },
                        ticks: {
                            maxTicksLimit: 10,
                            callback: val => labels[val]?.slice(0, 16) || ''
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Ölstand'
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>
