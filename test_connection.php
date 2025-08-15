<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Check if PDO MySQL extension is available
if (!extension_loaded('pdo_mysql')) {
    echo "<div style='color: red;'>❌ PDO MySQL extension is not loaded!</div>";
    echo "<p>Please enable the PDO MySQL extension in your php.ini file.</p>";
    exit();
} else {
    echo "<div style='color: green;'>✅ PDO MySQL extension is loaded</div>";
}

// Test connection parameters
$host = 'localhost';
$port = '3306';
$username = 'root';
$password = '';

echo "<h3>Testing Connection Parameters:</h3>";
echo "<ul>";
echo "<li><strong>Host:</strong> $host</li>";
echo "<li><strong>Port:</strong> $port</li>";
echo "<li><strong>Username:</strong> $username</li>";
echo "<li><strong>Password:</strong> " . (empty($password) ? 'Empty' : 'Set') . "</li>";
echo "</ul>";

try {
    // Build DSN
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    
    echo "<h3>Attempting Connection...</h3>";
    
    // Create PDO connection
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div style='color: green;'>✅ Connection successful!</div>";
    
    // Test query
    $stmt = $pdo->query('SELECT VERSION() as version');
    $result = $stmt->fetch();
    
    echo "<h3>Server Information:</h3>";
    echo "<ul>";
    echo "<li><strong>MySQL Version:</strong> " . $result['version'] . "</li>";
    
    // Get databases
    $stmt = $pdo->query('SHOW DATABASES');
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<li><strong>Available Databases:</strong> " . count($databases) . "</li>";
    echo "<li><strong>Database List:</strong> " . implode(', ', $databases) . "</li>";
    echo "</ul>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>✅ Connection Test Passed!</h4>";
    echo "<p>Your MySQL server is running and accessible. You can now use the Database Manager application.</p>";
    echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Database Manager</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Connection Failed!</h4>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    
    $error_code = $e->getCode();
    echo "<h4>Troubleshooting Tips:</h4>";
    echo "<ul>";
    
    switch ($error_code) {
        case 2002:
            echo "<li>MySQL server is not running</li>";
            echo "<li>Check if MySQL service is started</li>";
            echo "<li>For XAMPP: Start MySQL from XAMPP Control Panel</li>";
            echo "<li>For Laragon: Start MySQL from Laragon</li>";
            break;
        case 1045:
            echo "<li>Access denied - wrong username or password</li>";
            echo "<li>Try username: 'root' with empty password</li>";
            echo "<li>Check your MySQL user credentials</li>";
            break;
        case 2006:
            echo "<li>MySQL server has gone away</li>";
            echo "<li>Try restarting MySQL service</li>";
            break;
        default:
            echo "<li>Check if MySQL is installed and running</li>";
            echo "<li>Verify the host and port settings</li>";
            echo "<li>Check firewall settings</li>";
    }
    
    echo "</ul>";
    echo "</div>";
    
    echo "<h4>Common Solutions:</h4>";
    echo "<ol>";
    echo "<li><strong>For XAMPP:</strong> Open XAMPP Control Panel → Start MySQL</li>";
    echo "<li><strong>For Laragon:</strong> Open Laragon → Start MySQL</li>";
    echo "<li><strong>For WAMP:</strong> Start WAMP → MySQL should start automatically</li>";
    echo "<li><strong>For standalone MySQL:</strong> Check if MySQL service is running</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<h3>PHP Information:</h3>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>PDO Drivers:</strong> " . implode(', ', PDO::getAvailableDrivers()) . "</li>";
echo "<li><strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "</ul>";
?>
