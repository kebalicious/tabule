<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Selection Debug</h1>";

// Check if user is logged in
if (!isset($_SESSION['db_connected']) || $_SESSION['db_connected'] !== true) {
    echo "<p style='color: red;'>❌ Not logged in</p>";
    echo "<p><a href='index.php'>Go to Login</a></p>";
    exit();
}

echo "<p style='color: green;'>✅ User is logged in</p>";

// Get database name from URL parameter
$dbName = $_GET['db'] ?? '';

echo "<p><strong>Database Name:</strong> " . (empty($dbName) ? 'Empty' : $dbName) . "</p>";

if (empty($dbName)) {
    echo "<p style='color: red;'>❌ No database name provided</p>";
    echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
    exit();
}

echo "<h3>Session Data:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

try {
    // Recreate PDO connection from session data
    $host = $_SESSION['db_host'];
    $port = $_SESSION['db_port'];
    $username = $_SESSION['db_username'];
    $password = $_SESSION['db_password'];
    
    echo "<p><strong>Host:</strong> $host</p>";
    echo "<p><strong>Port:</strong> $port</p>";
    echo "<p><strong>Username:</strong> $username</p>";
    
    // Build DSN (without database for now)
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    
    echo "<p><strong>DSN:</strong> $dsn</p>";
    
    // Create PDO connection
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "<p style='color: green;'>✅ PDO Connection created successfully</p>";
    
    // Test if we can connect to the selected database
    echo "<p><strong>Testing database access:</strong> USE `$dbName`</p>";
    $pdo->exec("USE `$dbName`");
    
    echo "<p style='color: green;'>✅ Database access successful</p>";
    
    // Update session with selected database
    $_SESSION['db_database'] = $dbName;
    
    echo "<p style='color: green;'>✅ Session updated with database: $dbName</p>";
    
    echo "<h3>Updated Session Data:</h3>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    echo "<h3>Redirecting to Dashboard...</h3>";
    echo "<p><a href='dashboard.php'>Click here if not redirected automatically</a></p>";
    
    // Redirect back to dashboard
    header('Location: dashboard.php');
    exit();
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ PDO Error: " . $e->getMessage() . "</p>";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    echo "<p><a href='dashboard.php?error=" . urlencode('Cannot access database: ' . $e->getMessage()) . "'>Go to Dashboard with Error</a></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ General Error: " . $e->getMessage() . "</p>";
    echo "<p><a href='dashboard.php?error=" . urlencode('Error: ' . $e->getMessage()) . "'>Go to Dashboard with Error</a></p>";
}

echo "<hr>";
echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='dashboard.php'>Go to Dashboard</a></li>";
echo "<li><a href='index.php'>Go to Login</a></li>";
echo "<li><a href='debug.php'>Run Debug Test</a></li>";
echo "</ul>";
?>
