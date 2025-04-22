<?php
    $connStr = getenv('MYSQLCONNSTR_AZURE_MYSQL_CONNECTIONSTRING');

    if (!$connStr) {
        die("Environment variable AZURE_MYSQL_CONNECTIONSTRING not set.");
    }

    // Die Zeichenkette parsen
    preg_match("/Database=(.+?);/", $connStr, $db);
    preg_match("/Data Source=(.+?);/", $connStr, $host);
    preg_match("/User Id=(.+?);/", $connStr, $user);
    preg_match("/Password=(.+)/", $connStr, $pass);

    if (!$db || !$host || !$user || !$pass) {
        die("Fehler beim Parsen der Verbindungszeichenfolge.");
    }

    // DSN korrekt aufbauen
    $dsn = "mysql:host={$host[1]};dbname={$db[1]};charset=utf8";

    // Verbindung aufbauen
    try {
        $pdo = new PDO($dsn, $user[1], $pass[1]);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM oillevels ORDER BY timestamp DESC";
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
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

</body>
</html>