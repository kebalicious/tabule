<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['db_connected']) || $_SESSION['db_connected'] !== true) {
    header('Location: index.php');
    exit();
}

// Get database name from URL parameter
$dbName = $_GET['db'] ?? '';

if (empty($dbName)) {
    header('Location: dashboard.php');
    exit();
}

try {
    // Recreate PDO connection from session data
    $host = $_SESSION['db_host'];
    $port = $_SESSION['db_port'];
    $username = $_SESSION['db_username'];
    $password = $_SESSION['db_password'];
    
    // Build DSN (without database for now)
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    
    // Create PDO connection
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    // Test if we can connect to the selected database
    $pdo->exec("USE `$dbName`");
    
    // Update session with selected database
    $_SESSION['db_database'] = $dbName;
    
    // Redirect back to dashboard
    header('Location: dashboard.php');
    exit();
    
} catch (PDOException $e) {
    // If there's an error, redirect back to dashboard with error
    header('Location: dashboard.php?error=' . urlencode('Cannot access database: ' . $e->getMessage()));
    exit();
} catch (Exception $e) {
    // General error
    header('Location: dashboard.php?error=' . urlencode('Error: ' . $e->getMessage()));
    exit();
}
?>
