<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Data Extraction Debug</h2>";

if (isset($_SESSION['db_connected']) && $_SESSION['db_connected'] === true) {
    try {
        $host = $_SESSION['db_host'];
        $port = $_SESSION['db_port'];
        $username = $_SESSION['db_username'];
        $password = $_SESSION['db_password'];
        $database = $_SESSION['db_database'] ?? '';
        
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        if (!empty($database)) {
            $dsn .= ";dbname=$database";
        }
        
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        
        $pdo->exec("USE `$database`");
        
        echo "<h3>Connected to: $database</h3>";
        
        // Get tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Tables:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li><a href='?table=$table'>$table</a></li>";
        }
        echo "</ul>";
        
        if (isset($_GET['table'])) {
            $tableName = $_GET['table'];
            echo "<h3>Data from table: $tableName</h3>";
            
            // Get structure
            $stmt = $pdo->query("DESCRIBE `$tableName`");
            $structure = $stmt->fetchAll();
            
            echo "<h4>Structure:</h4>";
            echo "<table border='1'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($structure as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Get data
            $stmt = $pdo->query("SELECT * FROM `$tableName` LIMIT 5");
            $data = $stmt->fetchAll();
            
            echo "<h4>Data:</h4>";
            if (!empty($data)) {
                echo "<table border='1'>";
                // Headers
                echo "<tr>";
                foreach (array_keys($data[0]) as $header) {
                    echo "<th>$header</th>";
                }
                echo "</tr>";
                
                // Data rows
                foreach ($data as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        if ($value === null) {
                            echo "<td><em style='color: gray;'>NULL</em></td>";
                        } else {
                            echo "<td>" . htmlspecialchars($value) . "</td>";
                        }
                    }
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No data found</p>";
            }
            
            // Test edit simulation
            if (!empty($data)) {
                echo "<h4>Test Edit Simulation:</h4>";
                $firstRow = $data[0];
                $firstColumn = array_keys($firstRow)[0];
                $firstValue = $firstRow[$firstColumn];
                
                echo "<p>Simulating edit of first row:</p>";
                echo "<ul>";
                echo "<li>Table: $tableName</li>";
                echo "<li>Column: $firstColumn</li>";
                echo "<li>Current Value: " . ($firstValue === null ? 'NULL' : $firstValue) . "</li>";
                echo "<li>New Value: TEST_VALUE</li>";
                echo "</ul>";
                
                $testData = [
                    'table' => $tableName,
                    'data' => [$firstColumn => 'TEST_VALUE'],
                    'where' => $firstRow
                ];
                
                echo "<p>Test Data:</p>";
                echo "<pre>" . json_encode($testData, JSON_PRETTY_PRINT) . "</pre>";
                
                // Simulate API call
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-Type: application/json',
                        'content' => json_encode($testData)
                    ]
                ]);
                
                $result = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/edit_row.php', false, $context);
                
                echo "<p>API Response:</p>";
                echo "<pre>" . htmlspecialchars($result) . "</pre>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<h3>Database Error:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
    }
} else {
    echo "<h3>Not connected to database</h3>";
}
?>
