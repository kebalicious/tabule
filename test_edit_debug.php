<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Edit Row Debug Test</h2>";

// Check session
echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test database connection
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
        
        echo "<h3>Database Connection: SUCCESS</h3>";
        echo "<p>Connected to: $database</p>";
        
        // Get tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<h3>Tables in database:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        
        // Test a sample table if exists
        if (!empty($tables)) {
            $sampleTable = $tables[0];
            echo "<h3>Sample data from table: $sampleTable</h3>";
            
            $stmt = $pdo->query("SELECT * FROM `$sampleTable` LIMIT 3");
            $data = $stmt->fetchAll();
            
            echo "<pre>";
            print_r($data);
            echo "</pre>";
            
            // Test structure
            $stmt = $pdo->query("DESCRIBE `$sampleTable`");
            $structure = $stmt->fetchAll();
            
            echo "<h3>Table structure:</h3>";
            echo "<pre>";
            print_r($structure);
            echo "</pre>";
        }
        
    } catch (PDOException $e) {
        echo "<h3>Database Connection Error:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
    }
} else {
    echo "<h3>Not connected to database</h3>";
}

echo "<h3>Test Edit Row API</h3>";
echo "<form method='post'>";
echo "<p>Table Name: <input type='text' name='table_name' value=''></p>";
echo "<p>Column Name: <input type='text' name='column_name' value=''></p>";
echo "<p>New Value: <input type='text' name='new_value' value=''></p>";
echo "<p>Where Column: <input type='text' name='where_column' value=''></p>";
echo "<p>Where Value: <input type='text' name='where_value' value=''></p>";
echo "<input type='submit' value='Test Edit'>";
echo "</form>";

if ($_POST) {
    echo "<h3>Test Results:</h3>";
    
    $testData = [
        'table' => $_POST['table_name'],
        'data' => [$_POST['column_name'] => $_POST['new_value']],
        'where' => [$_POST['where_column'] => $_POST['where_value']]
    ];
    
    echo "<p>Test Data:</p>";
    echo "<pre>";
    print_r($testData);
    echo "</pre>";
    
    // Simulate the API call
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($testData)
        ]
    ]);
    
    $result = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/edit_row.php', false, $context);
    
    echo "<p>API Response:</p>";
    echo "<pre>";
    print_r(json_decode($result, true));
    echo "</pre>";
}
?>
