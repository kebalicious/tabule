<?php
session_start();

echo "<h1>Session Debug</h1>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Current Database</h2>";
echo "db_database: " . ($_SESSION['db_database'] ?? 'NOT SET') . "<br>";

echo "<h2>Database Connection Info</h2>";
echo "db_connected: " . ($_SESSION['db_connected'] ?? 'NOT SET') . "<br>";
echo "db_host: " . ($_SESSION['db_host'] ?? 'NOT SET') . "<br>";
echo "db_port: " . ($_SESSION['db_port'] ?? 'NOT SET') . "<br>";
echo "db_username: " . ($_SESSION['db_username'] ?? 'NOT SET') . "<br>";

if (isset($_SESSION['db_connected']) && $_SESSION['db_connected'] === true) {
    try {
        $host = $_SESSION['db_host'];
        $port = $_SESSION['db_port'];
        $username = $_SESSION['db_username'];
        $password = $_SESSION['db_password'];
        
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password);
        
        echo "<h2>Available Databases</h2>";
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<ul>";
        foreach ($databases as $db) {
            $selected = ($db === ($_SESSION['db_database'] ?? '')) ? ' (SELECTED)' : '';
            echo "<li>$db$selected</li>";
        }
        echo "</ul>";
        
        if (!empty($_SESSION['db_database'])) {
            echo "<h2>Tables in " . $_SESSION['db_database'] . "</h2>";
            $pdo->exec("USE `" . $_SESSION['db_database'] . "`");
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tables)) {
                echo "<p>No tables found</p>";
            } else {
                echo "<ul>";
                foreach ($tables as $table) {
                    echo "<li>$table</li>";
                }
                echo "</ul>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
}
?>
