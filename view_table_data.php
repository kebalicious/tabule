<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['db_connected']) || $_SESSION['db_connected'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get current database and table
$current_database = $_SESSION['db_database'] ?? '';
$tableName = $_GET['table'] ?? '';

// Debug info
error_log("API Debug - Database: $current_database, Table: $tableName");

if (empty($current_database) || empty($tableName)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Database or table not specified',
        'debug' => [
            'database' => $current_database,
            'table' => $tableName,
            'session_keys' => array_keys($_SESSION)
        ]
    ]);
    exit();
}

// Recreate PDO connection from session data
try {
    $host = $_SESSION['db_host'];
    $port = $_SESSION['db_port'];
    $username = $_SESSION['db_username'];
    $password = $_SESSION['db_password'];
    $database = $_SESSION['db_database'] ?? '';
    
    // Build DSN
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    if (!empty($database)) {
        $dsn .= ";dbname=$database";
    }
    
    // Create PDO connection
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    // Switch to the selected database
    $pdo->exec("USE `$current_database`");
    
    // Get table structure
    $stmt = $pdo->query("DESCRIBE `$tableName`");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get table data (limit to 100 rows for performance)
    $stmt = $pdo->query("SELECT * FROM `$tableName` LIMIT 100");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get row count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$tableName`");
    $rowCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'structure' => $structure,
            'data' => $data,
            'rowCount' => $rowCount,
            'showing' => count($data)
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>
